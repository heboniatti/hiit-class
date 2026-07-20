<?php

namespace App\Actions\CheckIns;

use App\Enums\CheckInStatus;
use App\Enums\PlanType;
use App\Exceptions\CheckInDeniedException;
use App\Actions\Plans\ResolveStudentPlan;
use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\User;
use App\Support\HiitSettings;
use Illuminate\Support\Facades\DB;

class PerformCheckIn
{
    public function __construct(
        private CalculateAvailableCheckIns $availableCheckIns,
        private HiitSettings $settings,
        private ResolveStudentPlan $resolveStudentPlan,
    ) {}

    /**
     * Reserve the student a seat in the class.
     *
     * @throws CheckInDeniedException
     */
    public function handle(User $student, ClassSession $classSession): CheckIn
    {
        return DB::transaction(function () use ($student, $classSession): CheckIn {
            $existing = $this->assertCanCheckIn($student, $classSession, persistTrial: true);

            return $this->reserveSeat($student, $classSession, $existing);
        });
    }

    /**
     * Explain why the student cannot check in, or null when they can.
     *
     * The screens call this to decide what the check-in button says. It runs
     * the very same guards as handle(), so the button and the refusal can never
     * disagree about the rules.
     */
    public function denialReason(User $student, ClassSession $classSession): ?string
    {
        try {
            $this->assertCanCheckIn($student, $classSession, persistTrial: false);
        } catch (CheckInDeniedException $exception) {
            return $exception->getMessage();
        }

        return $classSession->isFull()
            ? CheckInDeniedException::classFull()->getMessage()
            : null;
    }

    /**
     * Run every guard except capacity, which is enforced by the write itself.
     *
     * Returns the student's cancelled row for this class when there is one, so
     * the caller can reuse it instead of inserting a duplicate.
     *
     * @throws CheckInDeniedException
     */
    private function assertCanCheckIn(
        User $student,
        ClassSession $classSession,
        bool $persistTrial,
    ): ?CheckIn
    {
        $this->assertAccountIsActive($student);
        $this->assertClassAcceptsCheckIns($classSession);
        $this->assertCheckInWindowIsOpen($classSession);

        $studentPlan = $persistTrial
            ? $this->resolveStudentPlan->forCheckIn($student)
            : $this->resolveStudentPlan->forDisplay($student);

        if (! $studentPlan) {
            throw CheckInDeniedException::noActivePlan();
        }

        $this->assertPaymentIsUpToDate($student);

        $existing = CheckIn::query()
            ->where('class_session_id', $classSession->id)
            ->where('user_id', $student->id)
            ->first();

        if ($existing && $existing->status !== CheckInStatus::Cancelled) {
            throw CheckInDeniedException::alreadyCheckedIn();
        }

        $available = $this->availableCheckIns->forWeek($studentPlan, $classSession->date);

        if ($available !== null && $available < 1) {
            throw $studentPlan->plan->type === PlanType::Trial
                ? CheckInDeniedException::trialAlreadyUsed()
                : CheckInDeniedException::noCheckInsLeft();
        }

        return $existing;
    }

    /**
     * Take a seat, refusing to overbook the class.
     *
     * The capacity check lives inside the write statement rather than in PHP.
     * Reading the count and then inserting would race two students for the last
     * seat, and lockForUpdate() cannot save us here: SQLiteGrammar::compileLock()
     * returns an empty string, so the lock silently does nothing. The row is
     * still locked as well, because that is what serialises writers on MySQL and
     * Postgres, where the conditional statement alone can still race under READ
     * COMMITTED.
     *
     * @throws CheckInDeniedException
     */
    private function reserveSeat(User $student, ClassSession $classSession, ?CheckIn $existing): CheckIn
    {
        return DB::transaction(function () use ($student, $classSession, $existing): CheckIn {
            ClassSession::query()->whereKey($classSession->id)->lockForUpdate()->first();

            $affected = $existing
                ? $this->reclaimSeat($existing, $classSession)
                : $this->claimSeat($student, $classSession);

            if ($affected === 0) {
                throw CheckInDeniedException::classFull();
            }

            return CheckIn::query()
                ->where('class_session_id', $classSession->id)
                ->where('user_id', $student->id)
                ->firstOrFail();
        });
    }

    /**
     * Insert a new seat only while the class still has room.
     */
    private function claimSeat(User $student, ClassSession $classSession): int
    {
        $now = now();

        return DB::affectingStatement(
            <<<'SQL'
                insert into check_ins (class_session_id, user_id, status, checked_in_at, created_at, updated_at)
                select ?, ?, ?, ?, ?, ? from (select 1) as dummy
                where (
                    select count(*) from check_ins
                    where class_session_id = ? and status != ?
                ) < (select max_students from class_sessions where id = ?)
                SQL,
            [
                $classSession->id,
                $student->id,
                CheckInStatus::CheckedIn->value,
                $now,
                $now,
                $now,
                $classSession->id,
                CheckInStatus::Cancelled->value,
                $classSession->id,
            ],
        );
    }

    /**
     * Turn a cancelled seat back into an active one, if there is still room.
     *
     * A student who cancels and changes their mind reuses their row: the unique
     * index on (class_session_id, user_id) is what rejects duplicate check-ins,
     * and the trail of what happened lives in audit_logs.
     */
    private function reclaimSeat(CheckIn $existing, ClassSession $classSession): int
    {
        $now = now();

        return DB::affectingStatement(
            <<<'SQL'
                update check_ins
                set status = ?, checked_in_at = ?, cancelled_at = null, updated_at = ?
                where id = ?
                and (
                    select count(*) from check_ins as taken
                    where taken.class_session_id = ? and taken.status != ?
                ) < (select max_students from class_sessions where id = ?)
                SQL,
            [
                CheckInStatus::CheckedIn->value,
                $now,
                $now,
                $existing->id,
                $classSession->id,
                CheckInStatus::Cancelled->value,
                $classSession->id,
            ],
        );
    }

    /**
     * @throws CheckInDeniedException
     */
    private function assertAccountIsActive(User $student): void
    {
        if (! $student->isActive()) {
            throw CheckInDeniedException::inactiveAccount();
        }
    }

    /**
     * @throws CheckInDeniedException
     */
    private function assertClassAcceptsCheckIns(ClassSession $classSession): void
    {
        if (! $classSession->status->acceptsCheckIns()) {
            throw CheckInDeniedException::classCancelled();
        }
    }

    /**
     * @throws CheckInDeniedException
     */
    private function assertCheckInWindowIsOpen(ClassSession $classSession): void
    {
        $startsAt = $classSession->startsAt();
        $opensAt = $startsAt->copy()->subHours($this->settings->checkInOpensHoursBefore());
        $closesAt = $startsAt->copy()->subMinutes($this->settings->checkInClosesMinutesBefore());

        if (now()->lt($opensAt)) {
            throw CheckInDeniedException::windowNotOpenYet();
        }

        if (now()->gte($closesAt)) {
            throw CheckInDeniedException::classAlreadyStarted();
        }
    }

    /**
     * Blocking on an overdue fee never removes anything: existing check-ins and
     * the student's history stay exactly as they were.
     *
     * @throws CheckInDeniedException
     */
    private function assertPaymentIsUpToDate(User $student): void
    {
        $hasOverduePayment = $student->payments()->overdue()->exists();

        if ($hasOverduePayment) {
            throw CheckInDeniedException::paymentOverdue();
        }
    }
}
