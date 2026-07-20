<?php

namespace App\Exceptions;

use Exception;

/**
 * Raised when a check-in or a cancellation is refused.
 *
 * The message is always written for the student, in plain Portuguese with no
 * technical terms, because it is flashed straight to the screen.
 */
class CheckInDeniedException extends Exception
{
    public static function inactiveAccount(): self
    {
        return new self('Seu cadastro está inativo. Procure o professor.');
    }

    public static function classCancelled(): self
    {
        return new self('Esta aula foi cancelada.');
    }

    public static function classAlreadyStarted(): self
    {
        return new self('O período de check-in para esta aula já encerrou.');
    }

    public static function windowNotOpenYet(): self
    {
        return new self('O check-in para esta aula ainda não está aberto.');
    }

    public static function noActivePlan(): self
    {
        return new self('Você não possui um plano ativo. Procure o professor.');
    }

    public static function paymentOverdue(): self
    {
        return new self('Seu plano está temporariamente bloqueado por pagamento pendente. Procure o professor para regularizar sua mensalidade.');
    }

    public static function alreadyCheckedIn(): self
    {
        return new self('Você já fez check-in nesta aula.');
    }

    public static function noCheckInsLeft(): self
    {
        return new self('Você já usou todos os seus check-ins desta semana.');
    }

    public static function trialAlreadyUsed(): self
    {
        return new self('Você já usou seu check-in experimental. Procure o professor para escolher um plano.');
    }

    public static function classFull(): self
    {
        return new self('Aula lotada');
    }

    public static function checkInNotFound(): self
    {
        return new self('Você não tem check-in nesta aula.');
    }

    public static function cancellationDeadlinePassed(): self
    {
        return new self('O prazo para cancelar esta aula já encerrou.');
    }
}
