<?php

namespace App\Reports;

use App\Enums\CheckInStatus;
use App\Models\CheckIn;
use App\Reports\Concerns\QueriesCheckIns;

class AbsencesReport implements Report
{
    use QueriesCheckIns;

    public function key(): string
    {
        return 'absences';
    }

    public function name(): string
    {
        return 'Faltas e cancelamentos';
    }

    public function description(): string
    {
        return 'Quem reservou vaga e não treinou, e quem desmarcou a tempo.';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Data', 'Aula', 'Aluno', 'Situação', 'Cancelado em'];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array
    {
        return $this->checkInsQuery($filters)
            ->whereIn('check_ins.status', [
                CheckInStatus::Absent,
                CheckInStatus::JustifiedAbsence,
                CheckInStatus::Cancelled,
            ])
            ->with(['user', 'classSession'])
            ->orderBy('class_sessions.date')
            ->get()
            ->map(fn (CheckIn $checkIn): array => [
                $checkIn->classSession->date->format('d/m/Y'),
                $checkIn->classSession->name,
                $checkIn->user->name,
                $checkIn->status->label(),
                $checkIn->cancelled_at?->format('d/m/Y H:i') ?? '—',
            ])
            ->values()
            ->all();
    }
}
