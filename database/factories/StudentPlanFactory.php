<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<StudentPlan>
 */
class StudentPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'plan_id' => Plan::factory(),
            'started_at' => Carbon::today()->subMonth(),
            'ended_at' => null,
            'payment_due_day' => null,
        ];
    }

    /**
     * Indicate that the enrollment has finished.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => Carbon::today()->subDay(),
        ]);
    }
}
