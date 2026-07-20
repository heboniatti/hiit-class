<?php

namespace App\Reports;

use App\Models\CheckIn;
use App\Reports\Concerns\QueriesCheckIns;

class CheckInsByPeriodReport implements Report
{
    use QueriesCheckIns;

    public function key(): string
    {
        return 'check-ins';
    }

    public function name(): string
    {
        return 'Check-ins por período';
    }

    public function description(): string
    {
        return 'Cada check-in do período, com a aula e a situação final.';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Data', 'Horário', 'Aula', 'Professor', 'Aluno', 'Check-in em', 'Situação'];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array
    {
        return $this->checkInsQuery($filters)
            ->with(['user', 'classSession.teacher'])
            ->orderBy('class_sessions.date')
            ->orderBy('class_sessions.start_time')
            ->get()
            ->map(fn (CheckIn $checkIn): array => [
                $checkIn->classSession->date->format('d/m/Y'),
                substr($checkIn->classSession->start_time, 0, 5),
                $checkIn->classSession->name,
                $checkIn->classSession->teacher->name,
                $checkIn->user->name,
                $checkIn->checked_in_at->format('d/m/Y H:i'),
                $checkIn->status->label(),
            ])
            ->values()
            ->all();
    }
}
