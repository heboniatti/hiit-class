<?php

namespace App\Http\Controllers\Management;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Plan;
use App\Models\User;
use App\Reports\Report;
use App\Reports\ReportFilters;
use App\Reports\ReportRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * How many rows reach the screen before it stops being readable.
     *
     * The export has no such cap: a spreadsheet is exactly where the long tail
     * belongs.
     */
    private const PREVIEW_ROWS = 200;

    public function __construct(private ReportRegistry $registry) {}

    /**
     * Show a report on screen.
     */
    public function index(Request $request): Response
    {
        $report = $request->filled('report')
            ? $this->registry->find((string) $request->query('report'))
            : $this->registry->first();

        $filters = ReportFilters::fromArray($request->query());
        $rows = $report->rows($filters);

        return Inertia::render('management/reports/index', [
            'reports' => array_map(fn (Report $item): array => [
                'key' => $item->key(),
                'name' => $item->name(),
                'description' => $item->description(),
            ], $this->registry->all()),
            'current' => [
                'key' => $report->key(),
                'name' => $report->name(),
                'description' => $report->description(),
            ],
            'headings' => $report->headings(),
            'rows' => array_slice($rows, 0, self::PREVIEW_ROWS),
            'total' => count($rows),
            // Say so out loud rather than letting the screen imply it showed
            // everything.
            'truncated' => count($rows) > self::PREVIEW_ROWS,
            'previewLimit' => self::PREVIEW_ROWS,
            'filters' => $request->only(['from', 'to', 'student_id', 'teacher_id', 'class_schedule_id', 'plan_id'])
                + ['from' => $filters->from->toDateString(), 'to' => $filters->to->toDateString()],
            'period' => $filters->periodLabel(),
            'options' => $this->filterOptions(),
        ]);
    }

    /**
     * Download the report as a spreadsheet.
     *
     * Streamed and built with fputcsv: no dependency, and the memory stays flat
     * however many rows come back.
     */
    public function export(Request $request, string $report): StreamedResponse
    {
        $definition = $this->registry->find($report);
        $filters = ReportFilters::fromArray($request->query());

        $filename = $definition->key().'-'.$filters->from->format('Y-m-d')
            .'-a-'.$filters->to->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($definition, $filters): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            // Excel opens a CSV as the system's legacy encoding unless the file
            // announces UTF-8. Without this byte order mark, every "ç" and "ã"
            // in a Brazilian gym's report arrives mangled.
            fwrite($handle, "\xEF\xBB\xBF");

            // Semicolons, because that is what a pt-BR Excel expects as the
            // separator — a comma file lands entirely in column A.
            fputcsv($handle, $definition->headings(), ';');

            foreach ($definition->rows($filters) as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Get the things a report can be filtered by.
     *
     * @return array<string, array<int, array{value: int, label: string}>>
     */
    private function filterOptions(): array
    {
        return [
            'students' => User::query()
                ->where('role', UserRole::Student)
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): array => ['value' => $user->id, 'label' => $user->name])
                ->values()
                ->all(),
            'teachers' => User::query()
                ->whereIn('role', [UserRole::Teacher, UserRole::Admin])
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): array => ['value' => $user->id, 'label' => $user->name])
                ->values()
                ->all(),
            'classes' => ClassSchedule::query()
                ->orderBy('name')
                ->get()
                ->map(fn (ClassSchedule $schedule): array => [
                    'value' => $schedule->id,
                    'label' => $schedule->name,
                ])
                ->values()
                ->all(),
            'plans' => Plan::query()
                ->orderBy('name')
                ->get()
                ->map(fn (Plan $plan): array => ['value' => $plan->id, 'label' => $plan->name])
                ->values()
                ->all(),
        ];
    }
}
