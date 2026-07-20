<?php

namespace App\Actions\Classes;

use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Support\HiitSettings;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

class GenerateClassSessions
{
    public function __construct(private HiitSettings $settings) {}

    /**
     * Turn a recurrence into concrete classes up to the horizon.
     *
     * Classes are materialised rather than computed on the fly because each one
     * carries its own status, capacity and check-ins.
     *
     * Safe to run repeatedly: the unique index on (class_schedule_id, date) is
     * what makes the daily command idempotent, so nothing is ever duplicated.
     *
     * @return int how many classes were created
     */
    public function handle(ClassSchedule $schedule, ?CarbonInterface $until = null): int
    {
        if (! $schedule->is_active) {
            return 0;
        }

        $until ??= Date::today()->addWeeks($this->settings->sessionHorizonWeeks());

        if ($schedule->ends_on && $schedule->ends_on->lt($until)) {
            $until = $schedule->ends_on;
        }

        // Never backfill: a recurrence created today does not invent classes
        // that already happened.
        $date = $schedule->starts_on->greaterThan(Date::today())
            ? $schedule->starts_on
            : Date::today();

        $existing = ClassSession::query()
            ->where('class_schedule_id', $schedule->id)
            ->pluck('date')
            ->map(fn ($value): string => $value instanceof CarbonInterface
                ? $value->toDateString()
                : (string) $value)
            ->all();

        $created = 0;

        while ($date->lte($until)) {
            $isClassDay = in_array($date->dayOfWeekIso, $schedule->days_of_week, true);

            if ($isClassDay && ! in_array($date->toDateString(), $existing, true)) {
                ClassSession::create([
                    'class_schedule_id' => $schedule->id,
                    'name' => $schedule->name,
                    'date' => $date->toDateString(),
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'max_students' => $schedule->max_students,
                    'teacher_id' => $schedule->teacher_id,
                    'notes' => $schedule->notes,
                ]);

                $created++;
            }

            // Reassigned, never mutated: Date::today() is a CarbonImmutable.
            $date = $date->addDay();
        }

        return $created;
    }
}
