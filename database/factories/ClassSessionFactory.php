<?php

namespace Database\Factories;

use App\Enums\ClassSessionStatus;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_schedule_id' => null,
            'name' => 'HIIT '.fake()->randomElement(['Manhã', 'Tarde', 'Noite']),
            'date' => Carbon::today()->addDay(),
            'start_time' => '19:00',
            'end_time' => '20:00',
            'max_students' => 20,
            'teacher_id' => User::factory()->teacher(),
            'status' => ClassSessionStatus::Scheduled,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the class happens on the given date.
     */
    public function on(Carbon $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date->toDateString(),
        ]);
    }

    /**
     * Indicate that the class has room for the given number of students.
     */
    public function withCapacity(int $maxStudents): static
    {
        return $this->state(fn (array $attributes) => [
            'max_students' => $maxStudents,
        ]);
    }

    /**
     * Indicate that the class was called off.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClassSessionStatus::Cancelled,
        ]);
    }

    /**
     * Indicate that the class already happened.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => Carbon::today()->subWeek(),
            'status' => ClassSessionStatus::Completed,
        ]);
    }
}
