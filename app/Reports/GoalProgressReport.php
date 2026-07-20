<?php

namespace App\Reports;

use App\Actions\Gamification\CalculateGoalProgress;
use App\Models\Goal;
use Illuminate\Database\Eloquent\Builder;

class GoalProgressReport implements Report
{
    public function __construct(private CalculateGoalProgress $progress) {}

    public function key(): string
    {
        return 'goal-progress';
    }

    public function name(): string
    {
        return 'Progresso das metas';
    }

    public function description(): string
    {
        return 'Metas individuais e do grupo, com o quanto já andou.';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Meta', 'Alcance', 'Aluno', 'Tipo', 'Progresso', 'Objetivo', 'Percentual', 'Prazo', 'Situação'];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array
    {
        return Goal::query()
            ->with('user')
            // A collective goal has no owner, so filtering by student must not
            // drop it: it belongs to that student as much as to anyone.
            ->when($filters->studentId, fn (Builder $query, int $id) => $query
                ->where(fn (Builder $inner) => $inner
                    ->where('user_id', $id)
                    ->orWhereNull('user_id'),
                ),
            )
            ->whereDate('ends_on', '>=', $filters->from)
            ->whereDate('starts_on', '<=', $filters->to)
            ->orderBy('ends_on')
            ->get()
            ->map(function (Goal $goal): array {
                $progress = $this->progress->handle($goal);

                return [
                    $goal->title,
                    $goal->isCollective() ? 'Grupo' : 'Individual',
                    $goal->isCollective() ? 'Todos os alunos' : $goal->user->name,
                    $goal->type->label(),
                    $progress['current'],
                    $progress['target'],
                    $progress['percent'].'%',
                    $goal->ends_on->format('d/m/Y'),
                    $goal->status->label(),
                ];
            })
            ->values()
            ->all();
    }
}
