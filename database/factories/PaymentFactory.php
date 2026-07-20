<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'student_plan_id' => null,
            'amount' => fake()->randomFloat(2, 80, 300),
            'due_date' => Carbon::today()->addDays(10),
            'paid_at' => null,
            'method' => null,
            'status' => PaymentStatus::Pending,
            'notes' => null,
            'recorded_by' => null,
        ];
    }

    /**
     * Indicate that the fee has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'paid_at' => Carbon::today(),
            'method' => PaymentMethod::Pix,
        ]);
    }

    /**
     * Indicate that the fee is pending and its due date has passed.
     *
     * This is the state that blocks new check-ins.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Pending,
            'due_date' => Carbon::today()->subDays(5),
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the student owes nothing for the period.
     */
    public function exempt(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Exempt,
            'amount' => 0,
        ]);
    }
}
