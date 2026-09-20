<?php

use App\Contract\Auth\TwoFactorContract;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->service = app(TwoFactorContract::class);

    $this->user = User::factory()->create([
        'email' => 'guarded@example.com',
        'password' => Hash::make('the-right-password'),
    ]);

    $this->service->enable($this->user);
    $this->service->confirm($this->user, app(Google2FA::class)->getCurrentOtp(
        Crypt::decryptString($this->user->fresh()->two_factor_secret)
    ));

    $this->user->refresh();
});

function validCredentials(): array
{
    return ['email' => 'guarded@example.com', 'password' => 'the-right-password'];
}

function currentOtp(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp(Crypt::decryptString($user->two_factor_secret));
}

test('a correct password alone does not authenticate when two-factor is on', function () {
    $this->post(route('attempt'), validCredentials())
        ->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
});

test('the challenge screen renders once credentials are accepted', function () {
    $this->post(route('attempt'), validCredentials());

    $this->get(route('two-factor.login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/two-factor-challenge'));
});

test('the challenge screen is not reachable without passing the password step', function () {
    $this->get(route('two-factor.login'))->assertRedirect(route('login'));
});

test('a valid code completes the login', function () {
    $this->post(route('attempt'), validCredentials());

    $this->post(route('two-factor.verify'), ['code' => currentOtp($this->user)])
        ->assertRedirect(route('backoffice.index'));

    $this->assertAuthenticatedAs($this->user);
});

test('an invalid code does not authenticate', function () {
    $this->post(route('attempt'), validCredentials());

    $this->post(route('two-factor.verify'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('a recovery code completes the login and is then spent', function () {
    $codes = $this->service->recoveryCodes($this->user);

    $this->post(route('attempt'), validCredentials());
    $this->post(route('two-factor.verify'), ['code' => '', 'recovery_code' => $codes[0]])
        ->assertRedirect(route('backoffice.index'));

    $this->assertAuthenticatedAs($this->user);
    expect($this->service->recoveryCodes($this->user->fresh()))
        ->not->toContain($codes[0])
        ->toHaveCount(count($codes) - 1);
});

test('a spent recovery code cannot be reused', function () {
    $codes = $this->service->recoveryCodes($this->user);

    $this->post(route('attempt'), validCredentials());
    $this->post(route('two-factor.verify'), ['code' => '', 'recovery_code' => $codes[0]]);
    $this->post(route('logout'));

    $this->post(route('attempt'), validCredentials());
    $this->post(route('two-factor.verify'), ['code' => '', 'recovery_code' => $codes[0]])
        ->assertSessionHasErrors('recovery_code');

    $this->assertGuest();
});

test('the challenge requires one of the two fields', function () {
    $this->post(route('attempt'), validCredentials());

    $this->post(route('two-factor.verify'), ['code' => '', 'recovery_code' => ''])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('the pending login is cleared once the challenge is passed', function () {
    $this->post(route('attempt'), validCredentials());
    $this->post(route('two-factor.verify'), ['code' => currentOtp($this->user)]);

    expect(session()->has('login.id'))->toBeFalse();
});

test('a user without two-factor logs in without a challenge', function () {
    $plain = User::factory()->create([
        'email' => 'plain@example.com',
        'password' => Hash::make('the-right-password'),
    ]);

    $this->post(route('attempt'), ['email' => 'plain@example.com', 'password' => 'the-right-password'])
        ->assertRedirect(route('backoffice.index'));

    $this->assertAuthenticatedAs($plain);
});

test('an unconfirmed secret does not trigger a challenge', function () {
    $pending = User::factory()->create([
        'email' => 'pending@example.com',
        'password' => Hash::make('the-right-password'),
    ]);
    $this->service->enable($pending);

    $this->post(route('attempt'), ['email' => 'pending@example.com', 'password' => 'the-right-password'])
        ->assertRedirect(route('backoffice.index'));

    $this->assertAuthenticatedAs($pending);
});

test('remember me survives the challenge', function () {
    $this->post(route('attempt'), validCredentials() + ['remember' => true]);

    $this->post(route('two-factor.verify'), ['code' => currentOtp($this->user)])
        ->assertCookie(Auth::getRecallerName());
});
