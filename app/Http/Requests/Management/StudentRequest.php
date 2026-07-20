<?php

namespace App\Http\Requests\Management;

use App\Concerns\PhoneValidationRules;
use App\Concerns\PinValidationRules;
use App\Enums\UserStatus;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $student = $this->route('student');
        $studentId = $student instanceof User ? $student->id : null;
        $isCreating = $studentId === null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => $this->phoneRules($studentId),
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'plan_id' => ['nullable', Rule::exists(Plan::class, 'id')->where('is_active', true)],
            // Staff set an initial PIN only when creating the account. Changing
            // an existing student's PIN goes through the reset action.
            'password' => $isCreating ? $this->pinRules() : ['nullable'],
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
            'phone.digits_between' => 'Informe um telefone válido com DDD.',
            'phone.unique' => 'Este telefone já está cadastrado.',
            'password.digits' => 'O PIN deve ter exatamente 6 números.',
        ];
    }

    /**
     * Get the attributes that can be written straight to the student.
     *
     * The role is never included: it is set by the controller, so no request
     * can promote a student to staff.
     *
     * @return array<string, mixed>
     */
    public function studentAttributes(): array
    {
        return [
            'name' => $this->validated('name'),
            'phone' => $this->validated('phone'),
            'email' => $this->validated('email'),
            'status' => $this->validated('status'),
            'notes' => $this->validated('notes'),
            'emergency_contact_name' => $this->validated('emergency_contact_name'),
            'emergency_contact_phone' => $this->validated('emergency_contact_phone'),
        ];
    }
}
