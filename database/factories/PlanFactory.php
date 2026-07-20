<?php

namespace Database\Factories;

use App\Enums\PlanType;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Plano '.fake()->numberBetween(2, 5).'x por semana',
            'description' => fake()->sentence(),
            'type' => PlanType::Weekly,
            'checkins_per_week' => fake()->numberBetween(2, 5),
            'max_students' => null,
            'price' => fake()->randomFloat(2, 80, 300),
            'payment_due_day' => 10,
            'allows_rollover' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate a weekly plan with the given quota.
     */
    public function weekly(int $checkInsPerWeek): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PlanType::Weekly,
            'checkins_per_week' => $checkInsPerWeek,
        ]);
    }

    /**
     * Indicate a weekly plan with no quota at all.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Plano ilimitado',
            'type' => PlanType::Weekly,
            'checkins_per_week' => null,
        ]);
    }

    /**
     * Indicate a trial plan, which grants one check-in per enrollment.
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Aula experimental',
            'type' => PlanType::Trial,
            'checkins_per_week' => null,
            'price' => 0,
        ]);
    }

    /**
     * Indicate that unused check-ins carry over to the next week.
     */
    public function withRollover(): static
    {
        return $this->state(fn (array $attributes) => [
            'allows_rollover' => true,
        ]);
    }

    /**
     * Indicate that the plan is no longer offered.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
