<?php

namespace App\Reports;

/**
 * One report.
 *
 * Each implementation owns its own columns and rows, so the screen and the CSV
 * are fed by the very same method and can never disagree about what a report
 * says.
 */
interface Report
{
    /**
     * The key used in the URL and the filename.
     */
    public function key(): string;

    /**
     * The name shown to the administrator.
     */
    public function name(): string;

    /**
     * A one line explanation of what the report answers.
     */
    public function description(): string;

    /**
     * The column headings, in order.
     *
     * @return array<int, string>
     */
    public function headings(): array;

    /**
     * The rows, each one aligned to headings().
     *
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array;
}
