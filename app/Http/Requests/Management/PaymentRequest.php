<?php

namespace App\Http\Requests\Management;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                Rule::exists(User::class, 'id')->where('role', UserRole::Student->value),
            ],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'due_date' => ['required', 'date'],
            'paid_at' => ['nullable', 'date'],
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'notes' => ['nullable', 'string', 'max:500'],
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
            'user_id.required' => 'Escolha o aluno.',
            'user_id.exists' => 'Escolha um aluno válido.',
            'amount.required' => 'Informe o valor.',
            'due_date.required' => 'Informe a data de vencimento.',
        ];
    }

    /**
     * Get the attributes to write to the payment.
     *
     * recorded_by is never taken from the request: the action stamps it from
     * the signed-in staff member, so it cannot be forged.
     *
     * @return array<string, mixed>
     */
    public function paymentAttributes(): array
    {
        return [
            'user_id' => $this->validated('user_id'),
            'amount' => $this->validated('amount'),
            'due_date' => $this->validated('due_date'),
            'paid_at' => $this->validated('paid_at'),
            'method' => $this->validated('method'),
            'status' => $this->validated('status'),
            'notes' => $this->validated('notes'),
        ];
    }
}
