<?php

namespace Database\Factories;

use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
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
            'created_by' => User::factory()->teacher(),
            'title' => 'Participar de 8 aulas no mês',
            'description' => null,
            'type' => GoalType::ClassesInPeriod,
            'target_value' => 8,
            'starts_on' => Carbon::today()->startOfMonth(),
            'ends_on' => Carbon::today()->endOfMonth(),
            'status' => GoalStatus::Active,
        ];
    }

    /**
     * Indicate that the goal is shared by the whole group.
     */
    public function collective(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'title' => 'O grupo completar 100 aulas no mês',
            'target_value' => 100,
        ]);
    }
}
