<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait PhoneValidationRules
{
    /**
     * Get the validation rules used to validate phone numbers.
     *
     * The phone number is the login identifier, so it must be unique. Requests
     * normalise it to digits before validating, otherwise "(11) 99999-9999" and
     * "11999999999" would pass as two different accounts.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function phoneRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'digits_between:10,11',
            $userId === null
                ? Rule::unique(User::class, 'phone')
                : Rule::unique(User::class, 'phone')->ignore($userId),
        ];
    }
}
