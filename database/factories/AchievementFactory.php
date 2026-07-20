<?php

namespace Database\Factories;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'name' => 'Primeira aula concluída',
            'description' => 'Você concluiu sua primeira aula. Que comece a jornada!',
            'icon' => 'sparkles',
            'threshold' => 1,
            'is_active' => true,
        ];
    }
}
