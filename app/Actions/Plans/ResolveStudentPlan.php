<?php

namespace App\Actions\Plans;

use App\Enums\PlanType;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use Illuminate\Support\Facades\Date;

class ResolveStudentPlan
{
    /**
     * Plans resolved for display during the current request.
     *
     * @var array<int, StudentPlan|null>
     */
    private array $displayPlans = [];

    /**
     * Resolve the current enrollment or an unsaved experimental offer.
     */
    public function forDisplay(User $student): ?StudentPlan
    {
        if (array_key_exists($student->id, $this->displayPlans)) {
            return $this->displayPlans[$student->id];
        }

        $current = $this->current($student);

        if ($current !== null || $this->hasUsedTrial($student)) {
            return $this->displayPlans[$student->id] = $current;
        }

        $trialPlan = $this->availableTrialPlan();

        if ($trialPlan === null) {
            return $this->displayPlans[$student->id] = null;
        }

        $offer = new StudentPlan([
            'user_id' => $student->id,
            'plan_id' => $trialPlan->id,
            'started_at' => Date::today(),
        ]);
        $offer->setRelation('plan', $trialPlan);

        return $this->displayPlans[$student->id] = $offer;
    }

    /**
     * Resolve the enrollment used by a check-in, persisting a trial if needed.
     *
     * The caller runs this inside the same transaction as the seat reservation.
     */
    public function forCheckIn(User $student): ?StudentPlan
    {
        $current = $this->current($student);

        if ($current !== null || $this->hasUsedTrial($student)) {
            return $current;
        }

        $trialPlans = Plan::query()
            ->where('type', PlanType::Trial)
            ->where('is_active', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        // Another simultaneous check-in may have created the enrollment while
        // this request waited for the trial plan lock.
        $current = $this->current($student);

        if ($current !== null) {
            return $current;
        }

        $trialPlan = $trialPlans->first(
            fn (Plan $plan): bool => ! $plan->isAtCapacity(),
        );

        if ($trialPlan === null) {
            return null;
        }

        $studentPlan = StudentPlan::create([
            'user_id' => $student->id,
            'plan_id' => $trialPlan->id,
            'started_at' => Date::today(),
        ]);
        $studentPlan->setRelation('plan', $trialPlan);

        return $studentPlan;
    }

    /**
     * Get the student's persisted current enrollment.
     */
    private function current(User $student): ?StudentPlan
    {
        return $student->currentStudentPlan()->with('plan')->first();
    }

    /**
     * A student receives an experimental enrollment only once.
     */
    private function hasUsedTrial(User $student): bool
    {
        return $student->studentPlans()
            ->whereHas('plan', fn ($query) => $query->where('type', PlanType::Trial))
            ->exists();
    }

    /**
     * Find the first active experimental plan with capacity.
     */
    private function availableTrialPlan(): ?Plan
    {
        return Plan::query()
            ->where('type', PlanType::Trial)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->first(fn (Plan $plan): bool => ! $plan->isAtCapacity());
    }
}
