<?php

namespace App\Actions\CheckIns;

use App\Enums\CheckInStatus;
use App\Exceptions\CheckInDeniedException;
use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\User;
use App\Support\HiitSettings;

class CancelCheckIn
{
    public function __construct(private HiitSettings $settings) {}

    /**
     * Give the student's seat back to the class.
     *
     * The check-in itself needs no refunding: the balance is computed from the
     * rows that still hold a seat, so cancelling one restores it by itself.
     *
     * @throws CheckInDeniedException
     */
    public function handle(User $student, ClassSession $classSession): CheckIn
    {
        $checkIn = CheckIn::query()
            ->where('class_session_id', $classSession->id)
            ->where('user_id', $student->id)
            ->first();

        if (! $checkIn || $checkIn->status === CheckInStatus::Cancelled) {
            throw CheckInDeniedException::checkInNotFound();
        }

        $deadline = $classSession->startsAt()
            ->subMinutes($this->settings->cancellationDeadlineMinutes());

        if (now()->gte($deadline)) {
            throw CheckInDeniedException::cancellationDeadlinePassed();
        }

        $checkIn->update([
            'status' => CheckInStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $checkIn;
    }
}
