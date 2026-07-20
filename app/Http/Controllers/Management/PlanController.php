<?php

namespace App\Http\Controllers\Management;

use App\Enums\PlanType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\PlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    /**
     * List the plans on offer.
     *
     * There is no delete: a plan is pointed at by every enrollment that ever
     * used it, so retiring one means switching it off, which keeps the history
     * and the reports intact.
     */
    public function index(): Response
    {
        $plans = Plan::query()
            ->withCount(['studentPlans as active_students_count' => fn ($query) => $query
                ->whereNull('ended_at'),
            ])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (Plan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'type' => $plan->type->value,
                'type_label' => $plan->type->label(),
                'checkins_per_week' => $plan->checkins_per_week,
                'max_students' => $plan->max_students,
                'allowance' => $plan->allowanceLabel(),
                'price' => number_format((float) $plan->price, 2, ',', '.'),
                'price_raw' => (float) $plan->price,
                'payment_due_day' => $plan->payment_due_day,
                'allows_rollover' => $plan->allows_rollover,
                'is_active' => $plan->is_active,
                'active_students' => (int) $plan->active_students_count,
                'is_full' => $plan->isAtCapacity((int) $plan->active_students_count),
            ])
            ->values();

        return Inertia::render('management/plans/index', [
            'plans' => $plans,
            'types' => collect(PlanType::cases())
                ->map(fn (PlanType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values(),
        ]);
    }

    /**
     * Create a plan.
     */
    public function store(PlanRequest $request): RedirectResponse
    {
        Plan::create($request->planAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Plano criado.')]);

        return back();
    }

    /**
     * Update a plan.
     */
    public function update(PlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->planAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Plano atualizado.')]);

        return back();
    }
}
