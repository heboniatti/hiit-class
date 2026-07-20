<?php

namespace App\Http\Requests\Management;

use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoalRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['is_collective' => $this->boolean('is_collective')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::enum(GoalType::class)],
            'target_value' => ['required', 'integer', 'min:1', 'max:1000'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'status' => ['nullable', Rule::enum(GoalStatus::class)],
            'is_collective' => ['required', 'boolean'],
            // An individual goal needs its student; a collective one must not
            // carry a single owner.
            'user_id' => [
                'required_if:is_collective,false',
                'nullable',
                Rule::exists(User::class, 'id')->where('role', UserRole::Student->value),
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
            'title.required' => 'Dê um nome à meta.',
            'target_value.required' => 'Informe a quantidade necessária.',
            'ends_on.after_or_equal' => 'A data final deve ser igual ou depois da inicial.',
            'user_id.required_if' => 'Escolha o aluno da meta individual.',
            'user_id.exists' => 'Escolha um aluno válido.',
        ];
    }

    /**
     * Get the attributes to write to the goal.
     *
     * @return array<string, mixed>
     */
    public function goalAttributes(): array
    {
        return [
            // Null owner is what makes a goal collective.
            'user_id' => $this->boolean('is_collective') ? null : $this->validated('user_id'),
            'title' => $this->validated('title'),
            'description' => $this->validated('description'),
            'type' => $this->validated('type'),
            'target_value' => $this->validated('target_value'),
            'starts_on' => $this->validated('starts_on'),
            'ends_on' => $this->validated('ends_on'),
            'status' => $this->validated('status') ?? GoalStatus::Active,
        ];
    }
}
