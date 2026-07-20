<?php

namespace App\Http\Controllers\Student;

use App\Actions\Gamification\CalculateGoalProgress;
use App\Actions\Gamification\CalculateStreak;
use App\Enums\GoalStatus;
use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Goal;
use App\Models\User;
use App\Models\UserAchievement;
use App\Support\MotivationalMessages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class GoalController extends Controller
{
    public function __construct(
        private CalculateGoalProgress $progress,
        private CalculateStreak $streak,
        private MotivationalMessages $messages,
    ) {}

    /**
     * Show the student their goals and achievements.
     *
     * Nothing on this screen mentions another student. Collective goals arrive
     * as a single aggregate number, so there is nothing to compare yourself
     * against and no way to come last.
     */
    public function __invoke(Request $request): Response
    {
        $student = $request->user();

        return Inertia::render('student/goals', [
            'streak' => $this->streak->handle($student),
            'message' => $this->messages->forToday($student),
            'personalGoals' => $this->personalGoals($student),
            'groupGoals' => $this->groupGoals(),
            'achievements' => $this->achievements($student),
        ]);
    }

    /**
     * Get the student's own goals with how far along they are.
     *
     * @return array<int, array<string, mixed>>
     */
    private function personalGoals(User $student): array
    {
        return Goal::query()
            ->where('user_id', $student->id)
            ->where('status', GoalStatus::Active)
            ->orderBy('ends_on')
            ->get()
            ->map(function (Goal $goal): array {
                $progress = $this->progress->handle($goal);

                return [
                    'id' => $goal->id,
                    'title' => $goal->title,
                    'description' => $goal->description,
                    'unit' => $goal->type->unit(),
                    'ends_on' => $goal->ends_on->format('d/m/Y'),
                    ...$progress,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Get the goals the whole group shares.
     *
     * @return array<int, array<string, mixed>>
     */
    private function groupGoals(): array
    {
        return Goal::query()
            ->collective()
            ->where('status', GoalStatus::Active)
            ->orderBy('ends_on')
            ->get()
            ->map(function (Goal $goal): array {
                $progress = $this->progress->handle($goal);

                return [
                    'id' => $goal->id,
                    'title' => $goal->title,
                    'description' => $goal->description,
                    'unit' => $goal->type->unit(),
                    'ends_on' => $goal->ends_on->format('d/m/Y'),
                    // Aggregate only: never who contributed what.
                    ...$progress,
                    'encouragement' => $this->encouragement($progress),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Cheer the group on without naming anybody.
     *
     * @param  array{current: int, target: int, percent: int, is_complete: bool}  $progress
     */
    private function encouragement(array $progress): string
    {
        if ($progress['is_complete']) {
            return 'Meta alcançada! Vocês conseguiram juntos.';
        }

        $missing = $progress['target'] - $progress['current'];

        return match (true) {
            $progress['percent'] >= 75 => "Vocês já completaram {$progress['current']} de {$progress['target']}. Falta pouco!",
            $progress['percent'] >= 40 => "Vocês já completaram {$progress['current']} de {$progress['target']}. Metade do caminho!",
            default => "Faltam {$missing} para o grupo chegar lá. Cada treino conta.",
        };
    }

    /**
     * Get the whole catalogue, marking what the student has unlocked.
     *
     * Locked achievements are shown too, so there is something to aim at — but
     * they are framed as "ainda não", never as a failure.
     *
     * @return array<int, array<string, mixed>>
     */
    private function achievements(User $student): array
    {
        $unlocked = UserAchievement::query()
            ->where('user_id', $student->id)
            ->pluck('unlocked_at', 'achievement_id');

        return Achievement::query()
            ->where('is_active', true)
            // Easiest first. Threshold-less achievements (comeback, goals) sort
            // last rather than first, which is where SQLite puts NULLs: nobody
            // should open this screen and see "Retorno aos treinos" above
            // "Primeira aula concluída".
            ->orderByRaw('threshold is null')
            ->orderBy('threshold')
            ->get()
            ->map(fn (Achievement $achievement): array => [
                'id' => $achievement->id,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'icon' => $achievement->icon,
                'is_unlocked' => $unlocked->has($achievement->id),
                'unlocked_at' => $unlocked->has($achievement->id)
                    ? Date::parse((string) $unlocked->get($achievement->id))->format('d/m/Y')
                    : null,
            ])
            ->values()
            ->all();
    }
}
