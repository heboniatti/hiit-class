<?php

namespace App\Enums;

enum ClassSessionStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get the human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendada',
            self::InProgress => 'Em andamento',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    /**
     * Determine whether the class still accepts new check-ins.
     */
    public function acceptsCheckIns(): bool
    {
        return $this === self::Scheduled;
    }
}
