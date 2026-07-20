<?php

namespace App\Actions\Attendance;

use App\Actions\Gamification\EvaluateAchievements;
use App\Enums\CheckInStatus;
use App\Models\AuditLog;
use App\Models\CheckIn;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkAttendance
{
    public function __construct(private EvaluateAchievements $evaluateAchievements) {}

    /**
     * Record who was there, keeping a trail of every change.
     *
     * "Toda confirmação ou cancelamento de presença deve manter um histórico":
     * the check_ins row only ever holds the current state, so each transition
     * is written to audit_logs with the staff member who made it.
     *
     * Confirming attendance is also what unlocks achievements — the moment the
     * student's history actually changes.
     *
     * @param  array<int, string>  $statuses  check-in id => CheckInStatus value
     */
    public function handle(User $staff, array $statuses): int
    {
        /** @var array<int, int> $attended */
        $attended = [];

        $changed = DB::transaction(function () use ($staff, $statuses, &$attended): int {
            $changed = 0;

            foreach ($statuses as $checkInId => $status) {
                $checkIn = CheckIn::query()->find($checkInId);

                if (! $checkIn) {
                    continue;
                }

                $newStatus = CheckInStatus::from($status);

                if ($checkIn->status === $newStatus) {
                    continue;
                }

                $previous = $checkIn->status;

                $checkIn->update([
                    'status' => $newStatus,
                    'attendance_marked_by' => $staff->id,
                    'attendance_marked_at' => now(),
                    'cancelled_at' => $newStatus === CheckInStatus::Cancelled ? now() : null,
                ]);

                AuditLog::create([
                    'auditable_type' => $checkIn->getMorphClass(),
                    'auditable_id' => $checkIn->id,
                    'user_id' => $staff->id,
                    'action' => 'attendance.marked',
                    'old_values' => ['status' => $previous->value],
                    'new_values' => ['status' => $newStatus->value],
                ]);

                $attended[$checkIn->user_id] = $checkIn->user_id;
                $changed++;
            }

            return $changed;
        });

        // Achievements are worked out after the transaction commits: they only
        // read attendance, and an unlock must never be able to roll a teacher's
        // roll-call back.
        foreach ($attended as $studentId) {
            $student = User::query()->find($studentId);

            if ($student) {
                $this->evaluateAchievements->handle($student);
            }
        }

        return $changed;
    }
}
