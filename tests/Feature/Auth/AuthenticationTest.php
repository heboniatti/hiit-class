<?php

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\RateLimiter;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate with their phone and PIN', function () {
    $user = User::factory()->create(['phone' => '11999990000']);

    $response = $this->post(route('login.store'), [
        'phone' => '11999990000',
        'password' => UserFactory::PIN,
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('a formatted phone number authenticates the same account', function (string $typed) {
    $user = User::factory()->create(['phone' => '11999990000']);

    $this->post(route('login.store'), [
        'phone' => $typed,
        'password' => UserFactory::PIN,
    ]);

    $this->assertAuthenticatedAs($user);
})->with([
    'masked' => '(11) 99999-0000',
    'plain digits' => '11999990000',
    'spaced' => '11 99999 0000',
    'pasted with country code' => '+55 (11) 99999-0000',
]);

test('area code 55 is not mistaken for the country code', function () {
    // 55 is Santa Maria's area code, so a national number starting with it must
    // survive normalisation intact.
    $user = User::factory()->create(['phone' => '55999990000']);

    $this->post(route('login.store'), [
        'phone' => '(55) 99999-0000',
        'password' => UserFactory::PIN,
    ]);

    $this->assertAuthenticatedAs($user);
});

test('normalising a phone number reduces it to a national number', function (string $input, string $expected) {
    expect(User::normalizePhone($input))->toBe($expected);
})->with([
    'masked mobile' => ['(11) 99999-0000', '11999990000'],
    'masked landline' => ['(11) 3333-4444', '1133334444'],
    'country code' => ['+55 11 99999-0000', '11999990000'],
    'area code 55 kept' => ['(55) 99999-0000', '55999990000'],
    'landline in area 55 kept' => ['(55) 3333-4444', '5533334444'],
    'letters dropped' => ['11a99999b0000', '11999990000'],
]);

test('users can not authenticate with an invalid PIN', function () {
    User::factory()->create(['phone' => '11999990000']);

    $this->post(route('login.store'), [
        'phone' => '11999990000',
        'password' => '000000',
    ]);

    $this->assertGuest();
});

test('users can not authenticate with an unknown phone number', function () {
    $this->post(route('login.store'), [
        'phone' => '11900000000',
        'password' => UserFactory::PIN,
    ]);

    $this->assertGuest();
});

test('deactivated users can not authenticate', function () {
    User::factory()->inactive()->create(['phone' => '11999990000']);

    $this->post(route('login.store'), [
        'phone' => '11999990000',
        'password' => UserFactory::PIN,
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are temporarily locked out after repeated invalid PINs', function () {
    $user = User::factory()->create(['phone' => '11999990000']);

    RateLimiter::increment(md5('login'.implode('|', [$user->phone, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'phone' => '11999990000',
        'password' => '000000',
    ]);

    $response->assertTooManyRequests();
});

test('password reset routes are not registered', function () {
    expect(Route::has('password.request'))->toBeFalse()
        ->and(Route::has('password.reset'))->toBeFalse();
});
