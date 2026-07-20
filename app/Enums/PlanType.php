<?php

namespace App\Enums;

enum PlanType: string
{
    case Weekly = 'weekly';
    case Trial = 'trial';

    /**
     * Get the human readable label for the plan type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Semanal',
            self::Trial => 'Experimental',
        };
    }

    /**
     * Determine whether the quota renews every week.
     *
     * Trial plans grant a single check-in for the lifetime of the enrollment.
     */
    public function renewsWeekly(): bool
    {
        return $this === self::Weekly;
    }
}
