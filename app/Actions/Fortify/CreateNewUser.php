<?php

namespace App\Actions\Fortify;

use App\Concerns\PhoneValidationRules;
use App\Concerns\PinValidationRules;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PhoneValidationRules, PinValidationRules;

    /**
     * How many accounts one address may create per hour.
     */
    private const MAX_PER_HOUR = 5;

    private const DECAY_SECONDS = 3600;

    /**
     * Register a new student.
     *
     * The role and the status are set here and never read from the request:
     * anything else would let a stranger POST role=admin and grant themselves
     * the run of the system.
     *
     * New students deliberately start with no recurring plan. When an active
     * experimental plan has capacity, they may use its single check-in before
     * a teacher links the recurring plan shown in the management queue.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $this->assertNotFlooding();

        $input['phone'] = User::normalizePhone($input['phone'] ?? '');

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'phone' => $this->phoneRules(),
            'password' => $this->pinRules(),
        ], [
            'name.required' => 'Informe seu nome completo.',
            'phone.required' => 'Informe seu telefone.',
            'phone.digits_between' => 'Informe um telefone válido com DDD.',
            'phone.unique' => 'Este telefone já está cadastrado. Tente entrar ou procure o professor.',
            'password.required' => 'Escolha um PIN de 6 números.',
            'password.digits' => 'O PIN deve ter exatamente 6 números.',
            'password.confirmed' => 'A confirmação do PIN não confere.',
        ])->validate();

        RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

        return User::create([
            'name' => $input['name'],
            'phone' => $input['phone'],
            'password' => $input['password'],
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
        ]);
    }

    /**
     * Keep one address from creating an endless stream of accounts.
     *
     * Sign-up is open to the public and there is no email to verify, so the
     * unique phone number and this limit are the only things in the way.
     *
     * @throws ValidationException
     */
    private function assertNotFlooding(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_PER_HOUR)) {
            return;
        }

        throw ValidationException::withMessages([
            'phone' => 'Muitos cadastros seguidos. Tente novamente mais tarde.',
        ]);
    }

    /**
     * Get the rate limiter key for the caller.
     */
    private function throttleKey(): string
    {
        return 'register|'.request()->ip();
    }
}
