<?php

namespace Database\Factories;

use App\Models\ClassSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ClassSchedule>
 */
class ClassScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'HIIT Noite',
            // Monday, Wednesday and Friday, as ISO weekday numbers.
            'days_of_week' => [1, 3, 5],
            'start_time' => '19:00',
            'end_time' => '20:00',
            'max_students' => 20,
            'teacher_id' => User::factory()->teacher(),
            'starts_on' => Carbon::today(),
            'ends_on' => null,
            'notes' => null,
            'is_active' => true,
        ];
    }
}
