<?php

namespace App\Http\Requests\Management;

use App\Enums\PlanType;
use App\Models\Plan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PlanRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'allows_rollover' => $this->boolean('allows_rollover'),
            'is_active' => $this->boolean('is_active'),
            // An empty quota means unlimited, which is stored as null.
            'checkins_per_week' => $this->input('checkins_per_week') === ''
                ? null
                : $this->input('checkins_per_week'),
            'max_students' => $this->input('max_students') === ''
                ? null
                : $this->input('max_students'),
        ]);
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
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', Rule::enum(PlanType::class)],
            // Null means unlimited. Trial plans ignore it: they always grant a
            // single check-in per enrollment.
            'checkins_per_week' => ['nullable', 'integer', 'min:1', 'max:14'],
            'max_students' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'payment_due_day' => ['required', 'integer', 'min:1', 'max:28'],
            'allows_rollover' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
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
            'name.required' => 'Dê um nome ao plano.',
            'price.required' => 'Informe o valor da mensalidade.',
            'payment_due_day.max' => 'Escolha um dia entre 1 e 28, para valer em todos os meses.',
            'checkins_per_week.min' => 'O plano precisa de pelo menos 1 check-in por semana.',
            'max_students.min' => 'O limite precisa permitir pelo menos 1 aluno.',
        ];
    }

    /**
     * Prevent an edit from leaving existing enrollments above the new limit.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('max_students')) {
                    return;
                }

                $plan = $this->route('plan');
                $maxStudents = $this->input('max_students');

                if (! $plan instanceof Plan || $maxStudents === null) {
                    return;
                }

                $activeStudents = $plan->studentPlans()->whereNull('ended_at')->count();

                if ((int) $maxStudents < $activeStudents) {
                    $validator->errors()->add(
                        'max_students',
                        "Este plano já possui {$activeStudents} aluno(s) ativo(s).",
                    );
                }
            },
        ];
    }

    /**
     * Get the attributes to write to the plan.
     *
     * @return array<string, mixed>
     */
    public function planAttributes(): array
    {
        $type = PlanType::from((string) $this->validated('type'));

        return [
            'name' => $this->validated('name'),
            'description' => $this->validated('description'),
            'type' => $type,
            // A trial plan's quota is its own rule, so any number typed here is
            // discarded rather than quietly stored and ignored.
            'checkins_per_week' => $type === PlanType::Trial
                ? null
                : $this->validated('checkins_per_week'),
            'max_students' => $this->validated('max_students'),
            'price' => $this->validated('price'),
            'payment_due_day' => $this->validated('payment_due_day'),
            'allows_rollover' => $this->validated('allows_rollover'),
            'is_active' => $this->validated('is_active'),
        ];
    }
}
