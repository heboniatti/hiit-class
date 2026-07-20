<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'phone' => '11988887777',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->phone)->toBe('11988887777');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('a formatted phone number is stored as digits', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'phone' => '(11) 98888-7777',
            'email' => $user->email,
        ]);

    $response->assertSessionHasNoErrors();

    expect($user->refresh()->phone)->toBe('11988887777');
});

test('the phone number must be unique', function () {
    $existing = User::factory()->create(['phone' => '11988887777']);
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'phone' => $existing->phone,
            'email' => $user->email,
        ]);

    $response->assertSessionHasErrors('phone');
});

test('the email address is optional', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => '',
        ]);

    $response->assertSessionHasNoErrors();

    expect($user->refresh()->email)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'phone' => $user->phone,
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

// Accounts are deactivated (status = inactive), never deleted: deleting a user
// would cascade away their check-ins and silently rewrite attendance history,
// reports and collective goals.
test('account deletion route no longer exists', function () {
    expect(Route::has('profile.destroy'))->toBeFalse();
});
