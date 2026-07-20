<?php

namespace Database\Factories;

use App\Enums\CheckInStatus;
use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckIn>
 */
class CheckInFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_session_id' => ClassSession::factory(),
            'user_id' => User::factory()->student(),
            'status' => CheckInStatus::CheckedIn,
            'checked_in_at' => now(),
            'cancelled_at' => null,
            'attendance_marked_by' => null,
            'attendance_marked_at' => null,
        ];
    }

    /**
     * Indicate that the student holds a seat in a class yet to happen.
     */
    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckInStatus::CheckedIn,
        ]);
    }

    /**
     * Indicate that the student attended the class.
     */
    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckInStatus::Present,
            'attendance_marked_at' => now(),
        ]);
    }

    /**
     * Indicate that the student held a seat but did not show up.
     */
    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckInStatus::Absent,
            'attendance_marked_at' => now(),
        ]);
    }

    /**
     * Indicate that the student's absence was excused.
     */
    public function justified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckInStatus::JustifiedAbsence,
            'attendance_marked_at' => now(),
        ]);
    }

    /**
     * Indicate that the seat was given back.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckInStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
