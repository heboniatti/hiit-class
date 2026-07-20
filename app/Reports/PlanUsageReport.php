<?php

namespace App\Reports;

use App\Enums\CheckInStatus;
use App\Models\Plan;
use App\Reports\Concerns\QueriesCheckIns;
use Illuminate\Database\Eloquent\Builder;

class PlanUsageReport implements Report
{
    use QueriesCheckIns;

    public function key(): string
    {
        return 'plan-usage';
    }

    public function name(): string
    {
        return 'Utilização dos planos';
    }

    public function description(): string
    {
        return 'Quantos alunos cada plano tem e quanto da cota eles realmente usam.';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Plano', 'Tipo', 'Cota', 'Mensalidade', 'Alunos ativos', 'Presenças no período', 'Média por aluno'];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array
    {
        return Plan::query()
            ->withCount(['studentPlans as active_students_count' => fn ($query) => $query
                ->whereNull('ended_at'),
            ])
            ->when($filters->planId, fn (Builder $query, int $id) => $query->whereKey($id))
            ->orderBy('name')
            ->get()
            ->map(function (Plan $plan) use ($filters): array {
                $studentIds = $this->studentIdsOnPlan($plan->id);
                $active = (int) $plan->active_students_count;

                $present = $studentIds === [] ? 0 : $this->checkInsQuery($filters)
                    ->whereIn('check_ins.user_id', $studentIds)
                    ->where('check_ins.status', CheckInStatus::Present)
                    ->count();

                return [
                    $plan->name,
                    $plan->type->label(),
                    $plan->allowanceLabel(),
                    number_format((float) $plan->price, 2, ',', '.'),
                    $active,
                    $present,
                    $active === 0 ? 0 : round($present / $active, 1),
                ];
            })
            ->values()
            ->all();
    }
}
