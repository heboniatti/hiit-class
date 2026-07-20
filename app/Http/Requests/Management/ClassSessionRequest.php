<?php

namespace App\Http\Requests\Management;

use App\Enums\ClassSessionStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassSessionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'teacher_id' => [
                'required',
                Rule::exists(User::class, 'id')->whereIn('role', [
                    UserRole::Teacher->value,
                    UserRole::Admin->value,
                ]),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'max_students' => ['required', 'integer', 'min:1', 'max:200'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::enum(ClassSessionStatus::class)],

            'is_recurring' => ['required', 'boolean'],

            // A one-off class needs a date; a recurrence needs weekdays and a
            // window to repeat inside.
            'date' => ['required_if:is_recurring,false', 'nullable', 'date'],
            'days_of_week' => ['required_if:is_recurring,true', 'nullable', 'array', 'min:1'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'starts_on' => ['required_if:is_recurring,true', 'nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_recurring' => $this->boolean('is_recurring'),
        ]);
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Dê um nome para a aula.',
            'teacher_id.required' => 'Escolha o professor responsável.',
            'teacher_id.exists' => 'Escolha um professor válido.',
            'end_time.after' => 'O horário de término deve ser depois do início.',
            'max_students.min' => 'A aula precisa de pelo menos 1 vaga.',
            'date.required_if' => 'Informe a data da aula.',
            'days_of_week.required_if' => 'Escolha ao menos um dia da semana.',
            'starts_on.required_if' => 'Informe a partir de quando a aula se repete.',
            'ends_on.after_or_equal' => 'A data final deve ser igual ou depois da inicial.',
        ];
    }

    /**
     * Determine whether the teacher asked for a repeating class.
     */
    public function isRecurring(): bool
    {
        return (bool) $this->validated('is_recurring');
    }

    /**
     * Get the ISO weekdays a recurrence runs on.
     *
     * @return array<int, int>
     */
    public function daysOfWeek(): array
    {
        /** @var array<int, int> $days */
        $days = $this->validated('days_of_week') ?? [];

        return array_values(array_map(intval(...), $days));
    }
}
