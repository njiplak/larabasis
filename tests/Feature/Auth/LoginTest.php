<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

function credentials(array $overrides = []): array
{
    return array_merge([
        'email' => 'member@example.com',
        'password' => 'correct-horse-battery',
    ], $overrides);
}

beforeEach(function () {
    User::factory()->create([
        'email' => 'member@example.com',
        'password' => Hash::make('correct-horse-battery'),
    ]);
});

test('the login screen renders for guests', function () {
    $this->get(route('login'))->assertOk();
});

test('an authenticated user is redirected away from the login screen', function () {
    $this->actingAs(User::first())
        ->get(route('login'))
        ->assertRedirect(route('backoffice.index'));
});

test('valid credentials log the user in', function () {
    $response = $this->post(route('attempt'), credentials());

    $response->assertRedirect(route('backoffice.index'));
    $this->assertAuthenticatedAs(User::first());
});

test('the session id is regenerated on login', function () {
    $this->get(route('login'));
    $before = session()->getId();

    $this->post(route('attempt'), credentials());

    expect(session()->getId())->not->toBe($before);
});

test('remember me issues a remember cookie', function () {
    $this->post(route('attempt'), credentials(['remember' => true]))
        ->assertCookie(Auth::getRecallerName());
});

test('invalid credentials do not authenticate', function () {
    $this->post(route('attempt'), credentials(['password' => 'wrong']));

    $this->assertGuest();
});

test('an unknown email and a wrong password are indistinguishable', function () {
    $unknown = $this->post(route('attempt'), credentials(['email' => 'nobody@example.com']))
        ->assertSessionHasErrors('errors');
    $wrongPassword = $this->post(route('attempt'), credentials(['password' => 'wrong']))
        ->assertSessionHasErrors('errors');

    $unknownMessage = $unknown->getSession()->get('errors')->first('errors');
    $wrongPasswordMessage = $wrongPassword->getSession()->get('errors')->first('errors');

    expect($unknownMessage)
        ->toBe($wrongPasswordMessage)
        ->not->toContain('registered')
        ->not->toContain('Incorrect');
});

test('login is rate limited after five failed attempts', function () {
    foreach (range(1, 5) as $ignored) {
        $this->post(route('attempt'), credentials(['password' => 'wrong']));
    }

    $response = $this->post(route('attempt'), credentials(['password' => 'wrong']));

    expect($response->getSession()->get('errors')->first('email'))
        ->toContain('Too many login attempts');
});

test('login validates its input', function () {
    $this->post(route('attempt'), ['email' => 'not-an-email', 'password' => ''])
        ->assertSessionHasErrors(['email', 'password']);
});
