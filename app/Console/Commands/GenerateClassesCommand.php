<?php

namespace App\Console\Commands;

use App\Actions\Classes\GenerateClassSessions;
use App\Models\ClassSchedule;
use Illuminate\Console\Command;

class GenerateClassesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'classes:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Materialise recurring classes up to the rolling horizon';

    /**
     * Execute the console command.
     *
     * Runs daily so the schedule always stretches the same distance ahead.
     * Idempotent, so a missed day or a double run costs nothing.
     */
    public function handle(GenerateClassSessions $generate): int
    {
        $total = 0;

        ClassSchedule::query()
            ->where('is_active', true)
            ->each(function (ClassSchedule $schedule) use ($generate, &$total): void {
                $created = $generate->handle($schedule);
                $total += $created;

                if ($created > 0) {
                    $this->line("{$schedule->name}: {$created} aulas criadas");
                }
            });

        $this->info("{$total} aulas criadas.");

        return self::SUCCESS;
    }
}
