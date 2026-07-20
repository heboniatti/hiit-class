<?php

namespace App\Reports;

use App\Models\CheckIn;
use App\Reports\Concerns\QueriesCheckIns;

class PopularTimesReport implements Report
{
    use QueriesCheckIns;

    public function key(): string
    {
        return 'popular-times';
    }

    public function name(): string
    {
        return 'Horários com maior participação';
    }

    public function description(): string
    {
        return 'Em que dia e hora os alunos realmente aparecem.';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Dia da semana', 'Horário', 'Aulas', 'Reservas', 'Média por aula'];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array
    {
        $checkIns = $this->checkInsQuery($filters)->with('classSession')->get();

        $grouped = $checkIns->groupBy(fn (CheckIn $checkIn): string => implode('|', [
            $checkIn->classSession->date->dayOfWeekIso,
            substr($checkIn->classSession->start_time, 0, 5),
        ]));

        return $grouped
            ->map(function ($group, string $key): array {
                [$isoDay, $time] = explode('|', $key);

                // Distinct classes, so the average is per class, not per row.
                $classes = $group->pluck('class_session_id')->unique()->count();

                return [
                    'sort' => $group->count(),
                    'row' => [
                        $this->weekday((int) $isoDay),
                        $time,
                        $classes,
                        $group->count(),
                        $classes === 0 ? 0 : round($group->count() / $classes, 1),
                    ],
                ];
            })
            // Busiest first: this report exists to answer "where is the demand".
            ->sortByDesc('sort')
            ->pluck('row')
            ->values()
            ->all();
    }

    /**
     * Name the weekday in Portuguese.
     */
    private function weekday(int $isoDay): string
    {
        return match ($isoDay) {
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            default => 'Domingo',
        };
    }
}
