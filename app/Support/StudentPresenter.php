<?php

namespace App\Support;

use App\Actions\CheckIns\CalculateAvailableCheckIns;
use App\Actions\CheckIns\PerformCheckIn;
use App\Actions\Plans\ResolveStudentPlan;
use App\Enums\CheckInStatus;
use App\Enums\PaymentStatus;
use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\StudentPlan;
use App\Models\User;
use Illuminate\Support\Facades\Date;

/**
 * Shapes the data the student's screens need.
 *
 * Everything here is scoped to the signed-in student. Nothing it returns ever
 * mentions another student, which is what keeps the experience free of any
 * comparison or ranking.
 */
class StudentPresenter
{
    public function __construct(
        private PerformCheckIn $performCheckIn,
        private CalculateAvailableCheckIns $availableCheckIns,
        private ResolveStudentPlan $resolveStudentPlan,
    ) {}

    /**
     * Describe a class from this student's point of view.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     date: string,
     *     weekday: string,
     *     starts_at: string,
     *     ends_at: string,
     *     teacher: string,
     *     max_students: int,
     *     available_spots: int,
     *     is_full: bool,
     *     status: string,
     *     is_checked_in: bool,
     *     can_check_in: bool,
     *     denial_reason: string|null,
     *     can_cancel: bool,
     *     notes: string|null,
     * }
     */
    public function classCard(User $student, ClassSession $classSession): array
    {
        $checkIn = $classSession->checkIns
            ->firstWhere('user_id', $student->id);

        $isCheckedIn = $checkIn !== null && $checkIn->status !== CheckInStatus::Cancelled;
        $denialReason = $isCheckedIn
            ? null
            : $this->performCheckIn->denialReason($student, $classSession);

        return [
            'id' => $classSession->id,
            'name' => $classSession->name,
            'date' => $classSession->date->format('d/m'),
            'weekday' => $this->weekday($classSession),
            'starts_at' => substr($classSession->start_time, 0, 5),
            'ends_at' => substr($classSession->end_time, 0, 5),
            'teacher' => $classSession->teacher->name,
            'max_students' => $classSession->max_students,
            'available_spots' => $classSession->availableSpots(),
            'is_full' => $classSession->isFull(),
            'status' => $classSession->status->label(),
            'is_checked_in' => $isCheckedIn,
            'can_check_in' => ! $isCheckedIn && $denialReason === null,
            'denial_reason' => $denialReason,
            'can_cancel' => $isCheckedIn && $this->canCancel($classSession),
            'notes' => $classSession->notes,
        ];
    }

    /**
     * Summarise the student's plan and what they owe.
     *
     * @return array{
     *     has_plan: bool,
     *     plan_name: string|null,
     *     allowance: string|null,
     *     started_at: string|null,
     *     available_check_ins: int|null,
     *     is_unlimited: bool,
     *     payment_status: string,
     *     payment_is_blocking: bool,
     *     next_due_date: string|null,
     *     amount: string|null,
     *     is_trial_offer: bool,
     * }
     */
    public function planSummary(User $student): array
    {
        $studentPlan = $this->resolveStudentPlan->forDisplay($student);

        if (! $studentPlan) {
            return [
                'has_plan' => false,
                'plan_name' => null,
                'allowance' => null,
                'started_at' => null,
                'available_check_ins' => 0,
                'is_unlimited' => false,
                'payment_status' => 'Sem plano',
                'payment_is_blocking' => false,
                'next_due_date' => null,
                'amount' => null,
                'is_trial_offer' => false,
            ];
        }

        $available = $this->availableCheckIns->forWeek($studentPlan);
        $isTrialOffer = ! $studentPlan->exists;
        $overdue = $student->payments()->overdue()->first();
        $upcoming = $student->payments()
            ->where('status', PaymentStatus::Pending)
            ->orderBy('due_date')
            ->first();

        $payment = $overdue ?? $upcoming;

        if ($isTrialOffer) {
            return [
                'has_plan' => true,
                'plan_name' => $studentPlan->plan->name,
                'allowance' => $studentPlan->plan->allowanceLabel(),
                'started_at' => null,
                'available_check_ins' => $available,
                'is_unlimited' => false,
                'payment_status' => $payment ? $payment->statusLabel() : 'Disponível',
                'payment_is_blocking' => $overdue !== null,
                'next_due_date' => $payment?->due_date->format('d/m/Y'),
                'amount' => $payment ? number_format((float) $payment->amount, 2, ',', '.') : null,
                'is_trial_offer' => true,
            ];
        }

        return [
            'has_plan' => true,
            'plan_name' => $studentPlan->plan->name,
            'allowance' => $studentPlan->plan->allowanceLabel(),
            'started_at' => $studentPlan->started_at->format('d/m/Y'),
            'available_check_ins' => $available,
            'is_unlimited' => $available === null,
            'payment_status' => $payment ? $payment->statusLabel() : 'Em dia',
            'payment_is_blocking' => $overdue !== null,
            'next_due_date' => $payment?->due_date->format('d/m/Y'),
            'amount' => $payment ? number_format((float) $payment->amount, 2, ',', '.') : null,
            'is_trial_offer' => false,
        ];
    }

    /**
     * Count how many classes the student attended in the last four weeks.
     *
     * @return array{attended: int, weeks: int}
     */
    public function recentAttendance(User $student): array
    {
        $since = Date::today()->subWeeks(4);

        $attended = $student->checkIns()
            ->where('status', CheckInStatus::Present)
            ->whereHas('classSession', fn ($query) => $query->whereDate('date', '>=', $since))
            ->count();

        return ['attended' => $attended, 'weeks' => 4];
    }

    /**
     * Describe one row of the student's history.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     date: string,
     *     starts_at: string,
     *     status: string,
     *     attended: bool,
     * }
     */
    public function historyRow(CheckIn $checkIn): array
    {
        return [
            'id' => $checkIn->id,
            'name' => $checkIn->classSession->name,
            'date' => $checkIn->classSession->date->format('d/m/Y'),
            'starts_at' => substr($checkIn->classSession->start_time, 0, 5),
            'status' => $checkIn->status->label(),
            'attended' => $checkIn->status->countsAsAttendance(),
        ];
    }

    /**
     * Determine whether the cancellation window is still open.
     */
    private function canCancel(ClassSession $classSession): bool
    {
        $settings = app(HiitSettings::class);

        return now()->lt(
            $classSession->startsAt()->subMinutes($settings->cancellationDeadlineMinutes()),
        );
    }

    /**
     * Name the weekday in Portuguese.
     */
    private function weekday(ClassSession $classSession): string
    {
        return match ($classSession->date->dayOfWeekIso) {
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            default => 'Domingo',
        };
    }
}
