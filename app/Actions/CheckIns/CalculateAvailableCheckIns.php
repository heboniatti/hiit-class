<?php

namespace App\Actions\CheckIns;

use App\Models\CheckIn;
use App\Models\StudentPlan;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * Work out how many check-ins a student has left.
 *
 * The balance is always computed, never stored. That is what makes the weekly
 * renewal happen on its own (the week window simply moves), and what makes a
 * cancellation refund the check-in with no refund code at all: a cancelled row
 * stops counting.
 *
 * Dates are typed as CarbonInterface throughout because the application runs
 * Date::use(CarbonImmutable::class), so every date cast hands back an immutable
 * instance rather than an Illuminate\Support\Carbon.
 */
class CalculateAvailableCheckIns
{
    /**
     * Count the check-ins available to the student in the given week.
     *
     * Returns null when the plan is unlimited.
     *
     * @param  CarbonInterface|null  $week  any day inside the week of interest
     */
    public function forWeek(StudentPlan $studentPlan, ?CarbonInterface $week = null): ?int
    {
        $plan = $studentPlan->plan;

        if ($plan->hasUnlimitedCheckIns()) {
            return null;
        }

        if (! $plan->type->renewsWeekly()) {
            return $this->trialBalance($studentPlan);
        }

        $quota = $plan->checkins_per_week ?? 0;
        $week ??= Date::today();

        $used = $this->usedInWeek($studentPlan, $week);
        $rollover = $plan->allows_rollover
            ? $this->rolloverFrom($studentPlan, $week, $quota)
            : 0;

        return max(0, $quota + $rollover - $used);
    }

    /**
     * Count the check-ins a trial enrollment has left.
     *
     * The single check-in is granted per enrollment rather than per week, so
     * once it is used the student cannot book again until a teacher moves them
     * onto another plan.
     */
    private function trialBalance(StudentPlan $studentPlan): int
    {
        $used = CheckIn::query()
            ->consumingQuota()
            ->where('user_id', $studentPlan->user_id)
            ->whereHas(
                'classSession',
                fn ($query) => $query->whereDate('date', '>=', $studentPlan->started_at),
            )
            ->count();

        return max(0, 1 - $used);
    }

    /**
     * Count the check-ins already spent on classes happening in the week.
     *
     * Counted by the class's date, never by when the student tapped the button:
     * booking on Sunday for next Tuesday belongs to next week's quota.
     */
    private function usedInWeek(StudentPlan $studentPlan, CarbonInterface $week): int
    {
        [$start, $end] = $this->weekBounds($week);

        return CheckIn::query()
            ->consumingQuota()
            ->where('user_id', $studentPlan->user_id)
            ->whereHas('classSession', fn ($query) => $query
                ->whereDate('date', '>=', $start)
                ->whereDate('date', '<=', $end),
            )
            ->count();
    }

    /**
     * Count the unused check-ins carried over from the previous week.
     *
     * Capped at a single week's quota on purpose: without a ceiling a student
     * could vanish for two months and come back with a balance big enough to
     * fill a whole class on their own.
     */
    private function rolloverFrom(StudentPlan $studentPlan, CarbonInterface $week, int $quota): int
    {
        [$start] = $this->weekBounds($week);

        $previousWeek = $start->subWeek();

        // Never carry anything in from before the student joined the plan.
        if ($previousWeek->lt($studentPlan->started_at->startOfWeek(CarbonInterface::MONDAY))) {
            return 0;
        }

        $usedLastWeek = $this->usedInWeek($studentPlan, $previousWeek);

        return max(0, min($quota, $quota - $usedLastWeek));
    }

    /**
     * Get the first and last day of the week the given date falls in.
     *
     * Monday is passed explicitly rather than relying on the locale default,
     * which shifts once APP_LOCALE becomes pt_BR.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function weekBounds(CarbonInterface $date): array
    {
        return [
            $date->copy()->startOfWeek(CarbonInterface::MONDAY),
            $date->copy()->endOfWeek(CarbonInterface::SUNDAY),
        ];
    }
}
