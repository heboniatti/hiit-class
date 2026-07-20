<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Date;

/**
 * Encouragement for the student's dashboard.
 *
 * Every message speaks to the student's own journey. None of them compares
 * students, names a best or worst, or implies a competition: "melhor aluno",
 * "pior desempenho", "último colocado", "vencedor" and "perdedor" have no place
 * here by design.
 */
class MotivationalMessages
{
    /**
     * @var array<int, string>
     */
    private const MESSAGES = [
        'Você está construindo uma ótima rotina!',
        'Mais uma aula concluída. Continue assim!',
        'Cada treino conta para a sua evolução.',
        'Seu ritmo é único. O importante é continuar.',
        'Todo treino começa com a decisão de aparecer.',
        'Constância vale mais do que intensidade.',
        'Você está cuidando de você. Isso é o que importa.',
    ];

    /**
     * Pick a message for the student today.
     *
     * The choice is deterministic for a given student and day, so the message
     * does not flicker on every refresh.
     */
    public function forToday(User $student): string
    {
        $seed = $student->id + (int) Date::today()->format('Ymd');

        return self::MESSAGES[$seed % count(self::MESSAGES)];
    }

    /**
     * Welcome the student by name, matching the time of day.
     */
    public function greeting(User $student): string
    {
        $hour = (int) now()->format('G');
        $firstName = explode(' ', trim($student->name))[0];

        $period = match (true) {
            $hour < 12 => 'Bom dia',
            $hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };

        return "{$period}, {$firstName}!";
    }
}
