<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('the command creates a teacher who can sign in', function () {
    $this->artisan('hiit:staff', [
        '--name' => 'Professor Teste',
        '--phone' => '(11) 98888-1111',
        '--role' => 'teacher',
        '--pin' => '246810',
    ])->assertSuccessful();

    $user = User::firstWhere('phone', '11988881111');

    expect($user->role)->toBe(UserRole::Teacher)
        ->and($user->status)->toBe(UserStatus::Active)
        // The masked number is stored as digits, like everywhere else.
        ->and($user->phone)->toBe('11988881111')
        ->and(Hash::check('246810', $user->password))->toBeTrue();

    $this->post(route('login.store'), ['phone' => '11988881111', 'password' => '246810']);
    $this->assertAuthenticatedAs($user);
});

test('the command creates an admin', function () {
    $this->artisan('hiit:staff', [
        '--name' => 'Admin Teste',
        '--phone' => '11988882222',
        '--role' => 'admin',
        '--pin' => '135790',
    ])->assertSuccessful();

    expect(User::firstWhere('phone', '11988882222')->role)->toBe(UserRole::Admin);
});

test('the command refuses a phone that is already taken', function () {
    User::factory()->create(['phone' => '11988881111']);

    $this->artisan('hiit:staff', [
        '--name' => 'Outro',
        '--phone' => '11988881111',
        '--role' => 'teacher',
        '--pin' => '246810',
    ])->assertFailed();

    expect(User::where('phone', '11988881111')->count())->toBe(1);
});

test('the command refuses an invalid PIN or role', function (array $options) {
    $this->artisan('hiit:staff', [
        '--name' => 'Alguém',
        '--phone' => '11988883333',
        ...$options,
    ])->assertFailed();

    expect(User::where('phone', '11988883333')->exists())->toBeFalse();
})->with([
    'PIN too short' => [['--role' => 'teacher', '--pin' => '123']],
    'PIN with letters' => [['--role' => 'teacher', '--pin' => 'abcdef']],
    // A student is not staff: the command must not be a back door to the role.
    'role is not staff' => [['--role' => 'student', '--pin' => '246810']],
    'made up role' => [['--role' => 'owner', '--pin' => '246810']],
]);
