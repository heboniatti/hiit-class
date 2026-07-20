<?php

namespace App\Http\Requests\Management;

use App\Enums\CheckInStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', Rule::enum(CheckInStatus::class)],
        ];
    }

    /**
     * Get the attendance marks keyed by check-in id.
     *
     * @return array<int, string>
     */
    public function statuses(): array
    {
        /** @var array<int, string> $statuses */
        $statuses = $this->validated('statuses');

        return $statuses;
    }
}
