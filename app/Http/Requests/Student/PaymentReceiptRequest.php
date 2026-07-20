<?php

namespace App\Http\Requests\Student;

use App\Enums\UserRole;
use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class PaymentReceiptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $payment = $this->route('payment');
        $user = $this->user();

        return $payment instanceof Payment
            && $user !== null
            && $user->role === UserRole::Student
            && $payment->user_id === $user->id
            && ! $payment->status->isSettled();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receipt' => [
                'required',
                File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5 * 1024),
            ],
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
            'receipt.required' => 'Escolha um comprovante.',
            'receipt.mimes' => 'Envie um arquivo PDF, JPG ou PNG.',
            'receipt.max' => 'O comprovante deve ter no máximo 5 MB.',
        ];
    }
}
