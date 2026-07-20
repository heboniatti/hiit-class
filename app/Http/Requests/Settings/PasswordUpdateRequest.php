<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PinValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PasswordUpdateRequest extends FormRequest
{
    use PinValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * The field is named "password" because that is the column the PIN is
     * stored in and the field name Fortify reads.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => $this->currentPinRules(),
            'password' => $this->pinRules(),
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => 'O PIN atual está incorreto.',
            'password.digits' => 'O novo PIN deve ter exatamente 6 números.',
            'password.confirmed' => 'A confirmação do novo PIN não confere.',
        ];
    }
}
