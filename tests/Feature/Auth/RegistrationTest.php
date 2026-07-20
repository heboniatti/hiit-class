<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\ClassSession;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Carbon::setTestNow('2026-07-13 08:00:00'));

test('the registration screen can be rendered', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/register'));
});

test('a student can sign themselves up and is logged straight in', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Carla Aluna',
        'phone' => '(11) 98888-7777',
        'password' => '123456',
        'password_confirmation' => '123456',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();

    $user = User::firstWhere('phone', '11988887777');

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Carla Aluna')
        // Stored as digits, so the formatted number signs in later.
        ->and($user->phone)->toBe('11988887777')
        ->and(Hash::check('123456', $user->password))->toBeTrue();
});

test('a self-registered account is always a student, never staff', function (string $role) {
    $this->post(route('register.store'), [
        'name' => 'Pessoa Esperta',
        'phone' => '11988887777',
        'password' => '123456',
        'password_confirmation' => '123456',
        // Smuggled in on the request; must be ignored.
        'role' => $role,
        'status' => 'inactive',
    ]);

    $user = User::firstWhere('phone', '11988887777');

    expect($user->role)->toBe(UserRole::Student)
        ->and($user->status)->toBe(UserStatus::Active);
})->with(['admin', 'teacher']);

test('a new student starts with no plan', function () {
    $this->post(route('register.store'), [
        'name' => 'Carla Aluna',
        'phone' => '11988887777',
        'password' => '123456',
        'password_confirmation' => '123456',
    ]);

    $user = User::firstWhere('phone', '11988887777');

    expect($user->currentStudentPlan()->exists())->toBeFalse();
});

test('a new student sees the experimental plan and can choose a class', function () {
    Plan::factory()->trial()->create(['name' => 'Experimental HIIT']);
    ClassSession::factory()->create([
        'date' => Carbon::today(),
        'start_time' => '19:00',
        'end_time' => '20:00',
    ]);

    $this->post(route('register.store'), [
        'name' => 'Carla Aluna',
        'phone' => '11988887777',
        'password' => '123456',
        'password_confirmation' => '123456',
    ]);

    $this->get(route('classes.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('classes.0.can_check_in', true)
            ->where('classes.0.denial_reason', null)
            ->where('plan.has_plan', true)
            ->where('plan.plan_name', 'Experimental HIIT')
            ->where('plan.available_check_ins', 1)
            ->where('plan.is_trial_offer', true),
        );

    $student = User::firstWhere('phone', '11988887777');

    // Merely viewing the offer does not consume one of the plan's places.
    expect($student->currentStudentPlan()->exists())->toBeFalse();
});

test('the phone number must not already be taken', function () {
    User::factory()->create(['phone' => '11988887777']);

    $this->post(route('register.store'), [
        'name' => 'Outra Pessoa',
        'phone' => '(11) 98888-7777',
        'password' => '123456',
        'password_confirmation' => '123456',
    ])->assertSessionHasErrors('phone');

    $this->assertGuest();
});

test('the PIN must be exactly six digits', function (string $pin) {
    $this->post(route('register.store'), [
        'name' => 'Carla Aluna',
        'phone' => '11988887777',
        'password' => $pin,
        'password_confirmation' => $pin,
    ])->assertSessionHasErrors('password');

    expect(User::wherePhone('11988887777')->exists())->toBeFalse();
})->with([
    'too short' => '12345',
    'too long' => '1234567',
    'letters' => 'abcdef',
]);

test('the PIN confirmation must match', function () {
    $this->post(route('register.store'), [
        'name' => 'Carla Aluna',
        'phone' => '11988887777',
        'password' => '123456',
        'password_confirmation' => '654321',
    ])->assertSessionHasErrors('password');
});

test('one address can not create an endless stream of accounts', function () {
    foreach (range(1, 5) as $i) {
        $this->post(route('register.store'), [
            'name' => "Aluno {$i}",
            'phone' => '1198888000'.$i,
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);

        auth()->logout();
    }

    $this->post(route('register.store'), [
        'name' => 'Aluno 6',
        'phone' => '11988880006',
        'password' => '123456',
        'password_confirmation' => '123456',
    ])->assertSessionHasErrors('phone');

    expect(User::wherePhone('11988880006')->exists())->toBeFalse();
});
