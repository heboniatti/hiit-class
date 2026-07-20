<?php

namespace App\Actions\Gamification;

use App\Enums\CheckInStatus;
use App\Models\CheckIn;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

class CalculateStreak
{
    /**
     * Count how many weeks in a row the student has trained.
     *
     * Computed, never stored: a streak is just a reading of the attendance
     * that already exists.
     *
     * The current week does not break the streak while it is still running —
     * it is Monday morning for everyone at some point, and telling someone
     * their four week streak died on Monday would be both wrong and unkind.
     * The count therefore starts at the current week if it already has a
     * session, otherwise at last week.
     */
    public function handle(User $student): int
    {
        $weeks = $this->weeksAttended($student);

        if ($weeks === []) {
            return 0;
        }

        $thisWeek = Date::today()->startOfWeek(CarbonInterface::MONDAY);
        $cursor = in_array($thisWeek->toDateString(), $weeks, true)
            ? $thisWeek
            : $thisWeek->subWeek();

        $streak = 0;

        while (in_array($cursor->toDateString(), $weeks, true)) {
            $streak++;
            $cursor = $cursor->subWeek();
        }

        return $streak;
    }

    /**
     * Get the Monday of every week the student attended a class.
     *
     * @return array<int, string>
     */
    private function weeksAttended(User $student): array
    {
        return CheckIn::query()
            ->where('user_id', $student->id)
            ->where('check_ins.status', CheckInStatus::Present)
            ->join('class_sessions', 'class_sessions.id', '=', 'check_ins.class_session_id')
            ->orderByDesc('class_sessions.date')
            ->pluck('class_sessions.date')
            ->map(fn (mixed $date): string => Date::parse((string) $date)
                ->startOfWeek(CarbonInterface::MONDAY)
                ->toDateString())
            ->unique()
            ->values()
            ->all();
    }
}
