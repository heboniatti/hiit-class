<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait PinValidationRules
{
    /**
     * Get the validation rules used to validate PINs.
     *
     * The PIN is stored in the "password" column so that Fortify, the "hashed"
     * cast and the "current_password" rule keep working untouched. Password::defaults()
     * does not apply: the spec requires exactly six digits.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function pinRules(): array
    {
        return ['required', 'string', 'digits:6', 'confirmed'];
    }

    /**
     * Get the validation rules used to validate the current PIN.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function currentPinRules(): array
    {
        return ['required', 'string', 'current_password'];
    }
}
