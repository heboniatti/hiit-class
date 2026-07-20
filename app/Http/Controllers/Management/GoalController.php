<?php

namespace App\Http\Controllers\Management;

use App\Actions\Gamification\CalculateGoalProgress;
use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\GoalRequest;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GoalController extends Controller
{
    public function __construct(private CalculateGoalProgress $progress) {}

    /**
     * List the goals the gym is running.
     */
    public function index(): Response
    {
        $goals = Goal::query()
            ->with('user')
            ->orderByDesc('status')
            ->orderBy('ends_on')
            ->limit(100)
            ->get()
            ->map(function (Goal $goal): array {
                $progress = $this->progress->handle($goal);

                return [
                    'id' => $goal->id,
                    'title' => $goal->title,
                    'description' => $goal->description,
                    'type' => $goal->type->value,
                    'type_label' => $goal->type->label(),
                    'unit' => $goal->type->unit(),
                    'is_collective' => $goal->isCollective(),
                    'student' => $goal->user?->name,
                    'user_id' => $goal->user_id,
                    'target_value' => $goal->target_value,
                    'starts_on' => $goal->starts_on->toDateString(),
                    'ends_on' => $goal->ends_on->toDateString(),
                    'ends_on_label' => $goal->ends_on->format('d/m/Y'),
                    'status' => $goal->status->value,
                    'status_label' => $goal->status->label(),
                    ...$progress,
                ];
            })
            ->values();

        return Inertia::render('management/goals/index', [
            'goals' => $goals,
            'students' => $this->studentOptions(),
            'types' => collect(GoalType::cases())
                ->map(fn (GoalType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values(),
            'statuses' => collect(GoalStatus::cases())
                ->map(fn (GoalStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values(),
        ]);
    }

    /**
     * Create a goal, individual or shared by the group.
     */
    public function store(GoalRequest $request): RedirectResponse
    {
        Goal::create([
            ...$request->goalAttributes(),
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meta criada.')]);

        return back();
    }

    /**
     * Update a goal.
     */
    public function update(GoalRequest $request, Goal $goal): RedirectResponse
    {
        $goal->update($request->goalAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meta atualizada.')]);

        return back();
    }

    /**
     * Get the students a goal can be set for.
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function studentOptions(): array
    {
        return User::query()
            ->where('role', UserRole::Student)
            ->orderBy('name')
            ->get()
            ->map(fn (User $student): array => [
                'value' => $student->id,
                'label' => $student->name,
            ])
            ->values()
            ->all();
    }
}
