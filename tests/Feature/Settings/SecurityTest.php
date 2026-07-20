<?php

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('security settings page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('security.edit'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/security'));
});

test('PIN can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => UserFactory::PIN,
            'password' => '654321',
            'password_confirmation' => '654321',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));

    expect(Hash::check('654321', $user->refresh()->password))->toBeTrue();
});

test('correct current PIN must be provided to update the PIN', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => '000000',
            'password' => '654321',
            'password_confirmation' => '654321',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('security.edit'));

    expect(Hash::check(UserFactory::PIN, $user->refresh()->password))->toBeTrue();
});

test('the PIN must be exactly six digits', function (string $pin) {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => UserFactory::PIN,
            'password' => $pin,
            'password_confirmation' => $pin,
        ]);

    $response->assertSessionHasErrors('password');

    expect(Hash::check(UserFactory::PIN, $user->refresh()->password))->toBeTrue();
})->with([
    'too short' => '12345',
    'too long' => '1234567',
    'letters' => 'abcdef',
    'mixed digits and letters' => '12ab56',
]);
