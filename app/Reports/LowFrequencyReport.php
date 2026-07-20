<?php

namespace App\Reports;

use App\Enums\CheckInStatus;
use App\Enums\UserStatus;
use App\Models\CheckIn;
use App\Models\User;
use App\Reports\Concerns\QueriesCheckIns;
use App\Support\HiitSettings;

class LowFrequencyReport implements Report
{
    use QueriesCheckIns;

    public function __construct(private HiitSettings $settings) {}

    public function key(): string
    {
        return 'low-frequency';
    }

    public function name(): string
    {
        return 'Alunos com baixa frequência';
    }

    public function description(): string
    {
        return 'Alunos ativos que treinaram pouco no período — para o professor dar um alô.';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Aluno', 'Telefone', 'Plano', 'Presenças no período', 'Último treino'];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array
    {
        $threshold = $this->settings->lowFrequencyThreshold();

        $byStudent = $this->checkInsQuery($filters)
            ->where('check_ins.status', CheckInStatus::Present)
            ->with('classSession')
            ->get()
            ->groupBy('user_id');

        return $this->studentsQuery($filters)
            ->where('status', UserStatus::Active)
            ->with('currentStudentPlan.plan')
            ->get()
            ->map(function (User $student) use ($byStudent): array {
                $checkIns = $byStudent->get($student->id) ?? collect();

                $last = $checkIns
                    ->sortByDesc(fn (CheckIn $c) => $c->classSession->date)
                    ->first();

                return [
                    'count' => $checkIns->count(),
                    'row' => [
                        $student->name,
                        $student->phone,
                        $student->currentStudentPlan?->plan->name ?? 'Sem plano',
                        $checkIns->count(),
                        $last?->classSession->date->format('d/m/Y') ?? 'Nunca treinou',
                    ],
                ];
            })
            // This is operational care, not a ranking: it is a worklist for the
            // teacher and never reaches a student's screen.
            ->filter(fn (array $entry): bool => $entry['count'] < $threshold)
            ->sortBy('count')
            ->pluck('row')
            ->values()
            ->all();
    }
}
