<?php

namespace App\Actions\Gamification;

use App\Enums\CheckInStatus;
use App\Enums\GoalType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\CheckIn;
use App\Models\Goal;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

class CalculateGoalProgress
{
    public function __construct(private CalculateStreak $streak) {}

    /**
     * Measure how far along a goal is.
     *
     * Progress is derived from attendance rather than stored, so it can never
     * drift from the truth.
     *
     * A collective goal returns one aggregate number and nothing else — no
     * per-student breakdown ever leaves this method, which is what keeps the
     * group goals free of comparison.
     *
     * @return array{current: int, target: int, percent: int, is_complete: bool}
     */
    public function handle(Goal $goal): array
    {
        $current = $goal->isCollective()
            ? $this->collectiveProgress($goal)
            : $this->individualProgress($goal, $goal->user);

        $target = max(1, $goal->target_value);
        $percent = (int) min(100, round($current / $target * 100));

        return [
            'current' => $current,
            'target' => $goal->target_value,
            'percent' => $percent,
            'is_complete' => $current >= $goal->target_value,
        ];
    }

    /**
     * Measure one student against a goal.
     */
    private function individualProgress(Goal $goal, ?User $student): int
    {
        if (! $student) {
            return 0;
        }

        return match ($goal->type) {
            GoalType::ClassesInPeriod => $this->attendedBetween($student, $goal->starts_on, $goal->ends_on),
            GoalType::ClassesPerWeek => $this->attendedThisWeek($student),
            GoalType::WeeklyStreak => $this->streak->handle($student),
            GoalType::ReturnAfterAbsence => min(1, $this->attendedBetween($student, $goal->starts_on, $goal->ends_on)),
            GoalType::AttendanceRate => $this->attendanceRate($student, $goal->starts_on, $goal->ends_on),
        };
    }

    /**
     * Add up the whole group's progress toward a shared goal.
     */
    private function collectiveProgress(Goal $goal): int
    {
        $participants = $this->participants($goal);

        if ($participants === []) {
            return 0;
        }

        if ($goal->type === GoalType::AttendanceRate) {
            // An average, not a sum: rates do not add up.
            $rates = array_map(
                fn (User $student): int => $this->attendanceRate($student, $goal->starts_on, $goal->ends_on),
                $participants,
            );

            return (int) round(array_sum($rates) / count($rates));
        }

        return CheckIn::query()
            ->whereIn('user_id', array_map(fn (User $student): int => $student->id, $participants))
            ->where('check_ins.status', CheckInStatus::Present)
            ->whereHas('classSession', fn ($query) => $query
                ->whereDate('date', '>=', $goal->starts_on)
                ->whereDate('date', '<=', $goal->ends_on),
            )
            ->count();
    }

    /**
     * Get the students a collective goal covers.
     *
     * An empty participant list means every active student takes part.
     *
     * @return array<int, User>
     */
    private function participants(Goal $goal): array
    {
        $chosen = $goal->participants()->get();

        if ($chosen->isNotEmpty()) {
            return $chosen->all();
        }

        return User::query()
            ->where('role', UserRole::Student)
            ->where('status', UserStatus::Active)
            ->get()
            ->all();
    }

    /**
     * Count the classes a student attended inside a window.
     */
    private function attendedBetween(User $student, CarbonInterface $from, CarbonInterface $to): int
    {
        return CheckIn::query()
            ->where('user_id', $student->id)
            ->where('check_ins.status', CheckInStatus::Present)
            ->whereHas('classSession', fn ($query) => $query
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to),
            )
            ->count();
    }

    /**
     * Count the classes a student attended this week.
     */
    private function attendedThisWeek(User $student): int
    {
        $today = Date::today();

        return $this->attendedBetween(
            $student,
            $today->startOfWeek(CarbonInterface::MONDAY),
            $today->endOfWeek(CarbonInterface::SUNDAY),
        );
    }

    /**
     * Work out what share of a student's booked classes they showed up to.
     */
    private function attendanceRate(User $student, CarbonInterface $from, CarbonInterface $to): int
    {
        $booked = CheckIn::query()
            ->where('user_id', $student->id)
            ->whereIn('check_ins.status', [CheckInStatus::Present, CheckInStatus::Absent])
            ->whereHas('classSession', fn ($query) => $query
                ->whereDate('date', '>=', $from)
                ->whereDate('date', '<=', $to),
            )
            ->count();

        if ($booked === 0) {
            return 0;
        }

        return (int) round($this->attendedBetween($student, $from, $to) / $booked * 100);
    }
}
