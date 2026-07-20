<?php

namespace App\Reports;

use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Every report the system offers.
 *
 * One list drives the menu, the screen and the export, so a report cannot exist
 * in the dropdown without being downloadable, or the other way round.
 */
class ReportRegistry
{
    /**
     * @var array<int, class-string<Report>>
     */
    private const REPORTS = [
        StudentAttendanceReport::class,
        CheckInsByPeriodReport::class,
        AttendanceRateReport::class,
        AbsencesReport::class,
        ClassOccupancyReport::class,
        PopularTimesReport::class,
        TeacherActivityReport::class,
        LowFrequencyReport::class,
        PendingPaymentsReport::class,
        ReceivedPaymentsReport::class,
        PlanUsageReport::class,
        GoalProgressReport::class,
    ];

    public function __construct(private Container $container) {}

    /**
     * Get every report, resolved from the container.
     *
     * @return array<int, Report>
     */
    public function all(): array
    {
        return array_map(
            fn (string $class): Report => $this->container->make($class),
            self::REPORTS,
        );
    }

    /**
     * Find one report by its key.
     *
     * @throws NotFoundHttpException
     */
    public function find(string $key): Report
    {
        foreach ($this->all() as $report) {
            if ($report->key() === $key) {
                return $report;
            }
        }

        throw new NotFoundHttpException("Relatório [{$key}] não existe.");
    }

    /**
     * Get the first report, used when none was chosen.
     */
    public function first(): Report
    {
        return $this->all()[0];
    }
}
