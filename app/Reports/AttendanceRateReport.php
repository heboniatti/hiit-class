<?php

namespace App\Reports;

use App\Enums\CheckInStatus;
use App\Models\CheckIn;
use App\Reports\Concerns\QueriesCheckIns;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

class AttendanceRateReport implements Report
{
    use QueriesCheckIns;

    public function key(): string
    {
        return 'attendance-rate';
    }

    public function name(): string
    {
        return 'Taxa de presença';
    }

    public function description(): string
    {
        return 'Semana a semana, quantas reservas viraram presença de fato.';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Semana', 'Reservas', 'Presenças', 'Faltas', 'Taxa de presença'];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array
    {
        $byWeek = $this->checkInsQuery($filters)
            ->with('classSession')
            ->get()
            ->groupBy(fn (CheckIn $checkIn): string => $checkIn->classSession->date
                ->startOfWeek(CarbonInterface::MONDAY)
                ->toDateString());

        return $byWeek
            ->sortKeys()
            ->map(function ($checkIns, string $weekStart): array {
                $present = $checkIns
                    ->filter(fn (CheckIn $c): bool => $c->status === CheckInStatus::Present)
                    ->count();
                $absent = $checkIns
                    ->filter(fn (CheckIn $c): bool => $c->status === CheckInStatus::Absent)
                    ->count();

                $monday = Date::parse($weekStart);

                return [
                    $monday->format('d/m').' a '.$monday->addDays(6)->format('d/m/Y'),
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
