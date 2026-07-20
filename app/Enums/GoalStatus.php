<?php

namespace App\Enums;

enum GoalStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    /**
     * Get the human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Em andamento',
            self::Completed => 'Concluída',
            self::Expired => 'Encerrada',
            self::Cancelled => 'Cancelada',
        };
    }
}
