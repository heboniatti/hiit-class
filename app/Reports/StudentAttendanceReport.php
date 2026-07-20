<?php

namespace App\Reports;

use App\Enums\CheckInStatus;
use App\Models\CheckIn;
use App\Models\User;
use App\Reports\Concerns\QueriesCheckIns;

class StudentAttendanceReport implements Report
{
    use QueriesCheckIns;

    public function key(): string
    {
        return 'student-attendance';
    }

    public function name(): string
    {
        return 'Frequência por aluno';
    }

    public function description(): string
    {
        return 'Quantas aulas cada aluno reservou, frequentou e perdeu no período.';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Aluno', 'Telefone', 'Plano', 'Reservas', 'Presenças', 'Faltas', 'Taxa de presença'];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array
    {
        // Every check-in in the period, in one query and grouped in memory: a
        // query per student is what turns a 200 student gym into a timeout.
        $byStudent = $this->checkInsQuery($filters)
            ->get()
            ->groupBy('user_id');

        return $this->studentsQuery($filters)
            ->with('currentStudentPlan.plan')
            ->get()
            ->map(function (User $student) use ($byStudent): array {
                $checkIns = $byStudent->get($student->id) ?? collect();

                $present = $checkIns
                    ->filter(fn (CheckIn $c): bool => $c->status === CheckInStatus::Present)
                    ->count();
                $absent = $checkIns
                    ->filter(fn (CheckIn $c): bool => $c->status === CheckInStatus::Absent)
                    ->count();

                return [
                    $student->name,
                    $student->phone,
                    $student->currentStudentPlan?->plan->name ?? 'Sem plano',
                    $checkIns->count(),
                    $present,
                    $absent,
                    $this->percentage($present, $present + $absent).'%',
                ];
            })
            ->values()
            ->all();
    }
}
