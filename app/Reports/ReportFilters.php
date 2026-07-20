<?php

namespace App\Reports;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * The filters every report understands.
 *
 * A value object rather than a bag of request keys, so a report reads
 * $filters->studentId instead of guessing at array shapes.
 */
class ReportFilters
{
    public function __construct(
        public readonly CarbonInterface $from,
        public readonly CarbonInterface $to,
        public readonly ?int $studentId = null,
        public readonly ?int $teacherId = null,
        public readonly ?int $classScheduleId = null,
        public readonly ?int $planId = null,
    ) {}

    /**
     * Build the filters from request input, defaulting to the last 30 days.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $from = isset($input['from']) && $input['from'] !== ''
            ? Date::parse((string) $input['from'])
            : Date::today()->subDays(30);

        $to = isset($input['to']) && $input['to'] !== ''
            ? Date::parse((string) $input['to'])
            : Date::today();

        return new self(
            from: $from->startOfDay(),
            to: $to->endOfDay(),
            studentId: self::intOrNull($input['student_id'] ?? null),
            teacherId: self::intOrNull($input['teacher_id'] ?? null),
            classScheduleId: self::intOrNull($input['class_schedule_id'] ?? null),
            planId: self::intOrNull($input['plan_id'] ?? null),
        );
    }

    /**
     * Describe the window in words, for the screen and the file.
     */
    public function periodLabel(): string
    {
        return $this->from->format('d/m/Y').' a '.$this->to->format('d/m/Y');
    }

    /**
     * Normalise a filter value that arrives as "", "todos" or an id.
     */
    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
