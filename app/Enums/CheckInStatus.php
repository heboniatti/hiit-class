<?php

namespace App\Enums;

/**
 * The lifecycle of a student's seat in a class.
 *
 * The spec requires telling apart "check-in realizado", "presença confirmada",
 * "falta" and "cancelamento" — each is a case here.
 */
enum CheckInStatus: string
{
    case CheckedIn = 'checked_in';
    case Present = 'present';
    case Absent = 'absent';
    case JustifiedAbsence = 'justified_absence';
    case Cancelled = 'cancelled';

    /**
     * Get the human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::CheckedIn => 'Check-in realizado',
            self::Present => 'Presença confirmada',
            self::Absent => 'Falta',
            self::JustifiedAbsence => 'Ausência justificada',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Determine whether the status still holds a seat in the class.
     *
     * Only a cancellation frees the seat back to other students.
     */
    public function occupiesSpot(): bool
    {
        return $this !== self::Cancelled;
    }

    /**
     * Determine whether the status spends one of the student's weekly check-ins.
     *
     * A cancellation refunds the check-in, and a justified absence is a courtesy
     * granted by the teacher, so neither is charged. A plain absence is: the
     * student held a seat that nobody else could take.
     */
    public function consumesQuota(): bool
    {
        return ! in_array($this, [self::Cancelled, self::JustifiedAbsence], true);
    }

    /**
     * Determine whether the status counts as attendance for goals and streaks.
     */
    public function countsAsAttendance(): bool
    {
        return $this === self::Present;
    }
}
