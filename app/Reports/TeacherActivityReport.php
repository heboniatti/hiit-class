<?php

namespace App\Reports;

use App\Enums\CheckInStatus;
use App\Enums\ClassSessionStatus;
use App\Enums\UserRole;
use App\Models\ClassSession;
use App\Models\User;
use App\Reports\Concerns\QueriesCheckIns;
use Illuminate\Database\Eloquent\Builder;

class TeacherActivityReport implements Report
{
    use QueriesCheckIns;

    public function key(): string
    {
        return 'teacher-activity';
    }

    public function name(): string
    {
        return 'Aulas por professor';
    }

    public function description(): string
    {
        return 'Quantas aulas cada professor deu e quanto elas encheram.';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Professor', 'Aulas dadas', 'Canceladas', 'Reservas', 'Presenças', 'Ocupação média'];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array
    {
        return User::query()
            ->whereIn('role', [UserRole::Teacher, UserRole::Admin])
            ->when($filters->teacherId, fn (Builder $query, int $id) => $query->whereKey($id))
            ->orderBy('name')
            ->get()
            ->map(function (User $teacher) use ($filters): array {
                $classes = ClassSession::query()
                    ->where('teacher_id', $teacher->id)
                    ->whereDate('date', '>=', $filters->from)
                    ->whereDate('date', '<=', $filters->to)
                    ->withCount(['checkIns as taken_count' => fn ($query) => $query
                        ->where('status', '!=', CheckInStatus::Cancelled),
                    ])
                    ->withCount(['checkIns as present_count' => fn ($query) => $query
                        ->where('status', CheckInStatus::Present),
                    ])
                    ->get();

                $held = $classes->filter(
                    fn (ClassSession $c): bool => $c->status !== ClassSessionStatus::Cancelled,
                );

                $capacity = $held->sum('max_students');
                $taken = $held->sum(fn (ClassSession $c): int => (int) $c->taken_count);

                return [
                    $teacher->name,
                    $held->count(),
                    $classes->count() - $held->count(),
                    $taken,
                    $held->sum(fn (ClassSession $c): int => (int) $c->present_count),
                    $this->percentage($taken, (int) $capacity).'%',
                ];
            })
            // Teachers who did nothing in the window are noise, not information.
            ->filter(fn (array $row): bool => $row[1] > 0 || $row[2] > 0)
            ->values()
            ->all();
    }
}
