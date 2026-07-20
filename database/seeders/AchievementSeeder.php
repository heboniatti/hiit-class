<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Seed the catalogue of symbolic achievements.
     *
     * Every one of them measures the student against their own history. None
     * compares students to each other, by design.
     */
    public function run(): void
    {
        $achievements = [
            [
                'code' => 'first_class',
                'name' => 'Primeira aula concluída',
                'description' => 'Você concluiu sua primeira aula. Que comece a jornada!',
                'icon' => 'sparkles',
                'threshold' => 1,
            ],
            [
                'code' => 'five_classes',
                'name' => '5 aulas concluídas',
                'description' => 'Cinco treinos no seu histórico. A rotina está nascendo!',
                'icon' => 'flame',
                'threshold' => 5,
            ],
            [
                'code' => 'ten_classes',
                'name' => '10 aulas concluídas',
                'description' => 'Dez treinos concluídos. Que constância!',
                'icon' => 'medal',
                'threshold' => 10,
            ],
            [
                'code' => 'four_week_streak',
                'name' => '4 semanas mantendo frequência',
                'description' => 'Você treinou em quatro semanas seguidas.',
                'icon' => 'calendar-check',
                'threshold' => 4,
            ],
            [
                'code' => 'comeback',
                'name' => 'Retorno aos treinos',
                'description' => 'Você voltou a treinar. Bom te ver de novo!',
                'icon' => 'heart-handshake',
                'threshold' => null,
            ],
            [
                'code' => 'monthly_goal',
                'name' => 'Meta mensal concluída',
                'description' => 'Você alcançou sua meta do mês.',
                'icon' => 'target',
                'threshold' => null,
            ],
            [
                'code' => 'collective_goal',
                'name' => 'Participação em meta coletiva',
                'description' => 'Você ajudou o grupo a alcançar uma meta.',
                'icon' => 'users',
                'threshold' => null,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(
                ['code' => $achievement['code']],
                $achievement + ['is_active' => true],
            );
        }
    }
}
