<?php

namespace App\Enums;

enum GoalType: string
{
    case ClassesInPeriod = 'classes_in_period';
    case ClassesPerWeek = 'classes_per_week';
    case WeeklyStreak = 'weekly_streak';
    case ReturnAfterAbsence = 'return_after_absence';
    case AttendanceRate = 'attendance_rate';

    /**
     * Get the human readable label for the goal type.
     */
    public function label(): string
    {
        return match ($this) {
            self::ClassesInPeriod => 'Aulas no período',
            self::ClassesPerWeek => 'Aulas por semana',
            self::WeeklyStreak => 'Sequência de semanas',
            self::ReturnAfterAbsence => 'Retorno aos treinos',
            self::AttendanceRate => 'Taxa de presença',
        };
    }

    /**
     * Get the unit used when describing the goal target to the student.
     */
    public function unit(): string
    {
        return match ($this) {
            self::ClassesInPeriod, self::ClassesPerWeek => 'aulas',
            self::WeeklyStreak => 'semanas',
            self::ReturnAfterAbsence => 'aula',
            self::AttendanceRate => '%',
        };
    }
}
