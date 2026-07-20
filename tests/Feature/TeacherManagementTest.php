<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('an administrator sees the teacher registration page and teaching team', function () {
    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->teacher()->create(['name' => 'Bruna Professora']);
    User::factory()->student()->create(['name' => 'Aluno Oculto']);

    $this->actingAs($admin)
        ->get(route('management.teachers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('management/teachers/index')
            ->has('teachers', 1)
            ->where('teachers.0.id', $teacher->id)
            ->where('teachers.0.name', 'Bruna Professora')
            ->where('teachers.0.is_active', true),
        );
});

test('an administrator creates an active teacher with a phone and pin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('management.teachers.store'), [
        'name' => 'Carla Professora',
        'phone' => '(11) 98888-7777',
        'email' => 'carla@example.com',
        'password' => '654321',
        'password_confirmation' => '654321',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('management.teachers.index'));

    $teacher = User::query()->where('phone', '11988887777')->sole();

    expect($teacher->name)->toBe('Carla Professora')
        ->and($teacher->email)->toBe('carla@example.com')
        ->and($teacher->role)->toBe(UserRole::Teacher)
        ->and($teacher->status)->toBe(UserStatus::Active)
        ->and(Hash::check('654321', $teacher->password))->toBeTrue();
});

test('role and status can not be forged during teacher registration', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('management.teachers.store'), [
        'name' => 'Professor Seguro',
        'phone' => '11977776666',
        'password' => '123456',
        'password_confirmation' => '123456',
        'role' => UserRole::Admin->value,
        'status' => UserStatus::Inactive->value,
    ])->assertSessionHasNoErrors();

    $teacher = User::query()->where('phone', '11977776666')->sole();

    expect($teacher->role)->toBe(UserRole::Teacher)
        ->and($teacher->status)->toBe(UserStatus::Active);
});

test('teacher registration validates unique phones and pin confirmation', function () {
    $admin = User::factory()->admin()->create();
    $existing = User::factory()->student()->create(['phone' => '11999998888']);

    $this->actingAs($admin)
        ->from(route('management.teachers.index'))
        ->post(route('management.teachers.store'), [
            'name' => 'Professor Inválido',
            'phone' => $existing->phone,
            'email' => 'nao-e-email',
            'password' => '123456',
            'password_confirmation' => '654321',
        ])->assertSessionHasErrors(['phone', 'email', 'password']);

    expect(User::query()->where('name', 'Professor Inválido')->exists())->toBeFalse();
});

test('teachers and students can not create teacher accounts', function (string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)
        ->get(route('management.teachers.index'))
        ->assertForbidden();

    $this->actingAs($user)->post(route('management.teachers.store'), [
        'name' => 'Conta Indevida',
        'phone' => '11911112222',
        'password' => '123456',
        'password_confirmation' => '123456',
    ])->assertForbidden();

    expect(User::query()->where('phone', '11911112222')->exists())->toBeFalse();
})->with(['teacher', 'student']);
