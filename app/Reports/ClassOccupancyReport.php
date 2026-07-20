<?php

namespace App\Reports;

use App\Enums\CheckInStatus;
use App\Enums\ClassSessionStatus;
use App\Models\ClassSession;
use App\Reports\Concerns\QueriesCheckIns;
use Illuminate\Database\Eloquent\Builder;

class ClassOccupancyReport implements Report
{
    use QueriesCheckIns;

    public function key(): string
    {
        return 'class-occupancy';
    }

    public function name(): string
    {
        return 'Ocupação média das aulas';
    }

    public function description(): string
    {
        return 'Quanto de cada aula foi preenchido, para calibrar as vagas.';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Data', 'Horário', 'Aula', 'Professor', 'Vagas', 'Ocupadas', 'Ocupação', 'Situação'];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array
    {
        return ClassSession::query()
            ->with('teacher')
            ->withCount(['checkIns as taken_count' => fn ($query) => $query
                ->where('status', '!=', CheckInStatus::Cancelled),
            ])
            ->whereDate('date', '>=', $filters->from)
            ->whereDate('date', '<=', $filters->to)
            ->when($filters->teacherId, fn (Builder $query, int $id) => $query->where('teacher_id', $id))
            ->when($filters->classScheduleId, fn (Builder $query, int $id) => $query->where('class_schedule_id', $id))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSession $class): array => [
                $class->date->format('d/m/Y'),
                substr($class->start_time, 0, 5),
                $class->name,
                $class->teacher->name,
                $class->max_students,
                (int) $class->taken_count,
                $this->percentage((int) $class->taken_count, $class->max_students).'%',
                $class->status === ClassSessionStatus::Cancelled ? 'Cancelada' : $class->status->label(),
            ])
            ->values()
            ->all();
    }
}
