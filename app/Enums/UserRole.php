<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';

    /**
     * Get the human readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Teacher => 'Professor',
            self::Student => 'Aluno',
        };
    }

    /**
     * Determine whether the role has access to the management area.
     *
     * Administrators inherit every teacher capability.
     */
    public function managesStudents(): bool
    {
        return in_array($this, [self::Admin, self::Teacher], true);
    }
}
