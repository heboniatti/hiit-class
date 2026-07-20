<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The PIN every generated user signs in with.
     */
    public const PIN = '123456';

    /**
     * The current hashed PIN being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('119########'),
            'role' => UserRole::Student,
            'status' => UserStatus::Active,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make(self::PIN),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user has no email address, as most students will not.
     */
    public function withoutEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => null,
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }

    /**
     * Indicate that the user is a teacher.
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Teacher,
        ]);
    }

    /**
     * Indicate that the user is a student.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Student,
        ]);
    }

    /**
     * Indicate that the account is deactivated.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Inactive,
        ]);
    }
}
