<?php

namespace Database\Seeders;

use App\Enums\CheckInStatus;
use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The PIN every demo account signs in with.
     */
    private const PIN = '123456';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AchievementSeeder::class);

        User::factory()->admin()->create([
            'name' => 'Ana Administradora',
            'phone' => '11999990001',
            'email' => 'admin@hiit.test',
            'password' => Hash::make(self::PIN),
        ]);

        // $teacher = User::factory()->teacher()->create([
        //     'name' => 'Bruno Professor',
        //     'phone' => '11999990002',
        //     'email' => 'professor@hiit.test',
        //     'password' => Hash::make(self::PIN),
        // ]);

        $plans = $this->createPlans();
        // $classes = $this->createClasses($teacher);

        // $this->createStudents($plans, $classes);

        $this->command->info('Admin 11999990001');
        $this->command->info('PIN de todos: '.self::PIN);
    }

    /**
     * Create the plans the gym offers.
     *
     * @return array<string, Plan>
     */
    private function createPlans(): array
    {
        return [
            'trial' => Plan::factory()->trial()->create([
                'description' => 'Uma aula para conhecer o treino.',
            ]),
            'once' => Plan::factory()->weekly(2)->create([
                'name' => 'Plano 1x por semana',
                'description' => 'Um treino por semana.',
                'price' => 0,
            ]),
            'twice' => Plan::factory()->weekly(2)->create([
                'name' => 'Plano 2x por semana',
                'description' => 'Dois treinos por semana.',
                'price' => 0,
            ])
        ];
    }

    /**
     * Create classes across three weeks, on Monday, Wednesday and Friday.
     *
     * @return array<int, ClassSession>
     */
    private function createClasses(User $teacher): array
    {
        $classes = [];
        // Date::today() is immutable; Carbon::today() is NOT, despite
        // Date::use(CarbonImmutable::class). Using the mutable one here would
        // make addDays() mutate the accumulator and the dates would drift.
        $monday = Date::today()->startOfWeek(CarbonInterface::MONDAY)->subWeek();

        for ($day = 0; $day < 28; $day++) {
            $current = $monday->addDays($day);

            if (! in_array($current->dayOfWeekIso, [1, 3, 5], true)) {
                continue;
            }

            foreach ([['07:00', '08:00', 'HIIT Manhã'], ['19:00', '20:00', 'HIIT Noite']] as [$start, $end, $name]) {
                $classes[] = ClassSession::factory()->create([
                    'name' => $name,
                    'date' => $current,
                    'start_time' => $start,
                    'end_time' => $end,
                    'max_students' => 12,
                    'teacher_id' => $teacher->id,
                ]);
            }
        }

        return $classes;
    }

    /**
     * Create students with plans and some attendance history.
     *
     * @param  array<string, Plan>  $plans
     * @param  array<int, ClassSession>  $classes
     */
    private function createStudents(array $plans, array $classes): void
    {
        $student = User::factory()->student()->withoutEmail()->create([
            'name' => 'Carla Aluna',
            'phone' => '11999990003',
            'password' => Hash::make(self::PIN),
        ]);

        StudentPlan::factory()->create([
            'user_id' => $student->id,
            'plan_id' => $plans['thrice']->id,
            'started_at' => Carbon::today()->subMonths(2),
        ]);

        $this->giveAttendanceHistory($student, $classes);

        User::factory()->student()->count(8)->create()->each(
            function (User $other) use ($plans, $classes): void {
                StudentPlan::factory()->create([
                    'user_id' => $other->id,
                    'plan_id' => fake()->randomElement($plans)->id,
                    'started_at' => Carbon::today()->subWeeks(fake()->numberBetween(1, 8)),
                ]);

                $this->giveAttendanceHistory($other, $classes);
            },
        );
    }

    /**
     * Mark the student present in a few classes that already happened.
     *
     * @param  array<int, ClassSession>  $classes
     */
    private function giveAttendanceHistory(User $student, array $classes): void
    {
        foreach ($classes as $class) {
            if ($class->date->isAfter(Carbon::today()) || fake()->boolean(45)) {
                continue;
            }

            CheckIn::factory()->create([
                'user_id' => $student->id,
                'class_session_id' => $class->id,
                'status' => fake()->boolean(85) ? CheckInStatus::Present : CheckInStatus::Absent,
                'checked_in_at' => $class->startsAt()->subDay(),
                'attendance_marked_at' => $class->startsAt(),
            ]);
        }
    }
}
