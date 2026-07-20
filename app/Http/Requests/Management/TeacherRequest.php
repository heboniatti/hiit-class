<?php

namespace App\Http\Requests\Management;

use App\Concerns\PhoneValidationRules;
use App\Concerns\PinValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TeacherRequest extends FormRequest
{
    use PhoneValidationRules, PinValidationRules;

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => User::normalizePhone((string) $this->input('phone'))]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => $this->phoneRules(),
            'email' => ['nullable', 'string', 'email', 'max:255'],
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
            'name.required' => 'Informe o nome do professor.',
            'phone.required' => 'Informe o telefone.',
            'phone.digits_between' => 'Informe um telefone válido com DDD.',
            'phone.unique' => 'Este telefone já está cadastrado.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Escolha um PIN de 6 números.',
            'password.digits' => 'O PIN deve ter exatamente 6 números.',
            'password.confirmed' => 'A confirmação do PIN não confere.',
        ];
    }
}
