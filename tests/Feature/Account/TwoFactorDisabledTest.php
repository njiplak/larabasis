<?php

use App\Contract\Auth\TwoFactorContract;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    config(['service-contract.auth.two_factor' => false]);
    $this->user = User::factory()->create();
    $this->service = app(TwoFactorContract::class);
});

test('the feature reports itself unavailable', function () {
    expect($this->service->isAvailable())->toBeFalse();
});

test('every two-factor endpoint 404s', function (string $name, string $method) {
    $this->actingAs($this->user)
        ->call($method, route($name))
        ->assertNotFound();
})->with([
    ['two-factor.show', 'GET'],
    ['two-factor.enable', 'POST'],
    ['two-factor.disable', 'DELETE'],
    ['two-factor.confirm', 'POST'],
    ['two-factor.qr-code', 'GET'],
    ['two-factor.secret-key', 'GET'],
    ['two-factor.recovery-codes', 'GET'],
    ['two-factor.regenerate-recovery-codes', 'POST'],
]);

test('the login challenge screen 404s', function () {
    $this->get(route('two-factor.login'))->assertNotFound();
    $this->post(route('two-factor.verify'), ['code' => '123456'])->assertNotFound();
});

test('the settings tab is hidden', function () {
    $this->actingAs($this->user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('features.twoFactor', false));
});

test('login never challenges', function () {
    $user = User::factory()->create([
        'email' => 'plain@example.com',
        'password' => Hash::make('the-right-password'),
    ]);

    $this->post(route('attempt'), [
        'email' => 'plain@example.com',
        'password' => 'the-right-password',
    ])->assertRedirect(route('backoffice.index'));

    $this->assertAuthenticatedAs($user);
});

test('a user who had already enrolled is not locked out', function () {
    // Enrol while the feature is on, then switch it off under them.
    config(['service-contract.auth.two_factor' => true]);

    $enrolled = User::factory()->create([
        'email' => 'enrolled@example.com',
        'password' => Hash::make('the-right-password'),
    ]);
    $this->service->enable($enrolled);
    $this->service->confirm($enrolled, app(Google2FA::class)->getCurrentOtp(
        Crypt::decryptString($enrolled->fresh()->two_factor_secret)
    ));
    expect($this->service->isEnabled($enrolled->fresh()))->toBeTrue();

    config(['service-contract.auth.two_factor' => false]);

    $this->post(route('attempt'), [
        'email' => 'enrolled@example.com',
        'password' => 'the-right-password',
    ])->assertRedirect(route('backoffice.index'));

    $this->assertAuthenticatedAs($enrolled);
});

test('an enrolled secret survives being switched off and is demanded again when switched back on', function () {
    config(['service-contract.auth.two_factor' => true]);

    $enrolled = User::factory()->create([
        'email' => 'enrolled@example.com',
        'password' => Hash::make('the-right-password'),
    ]);
    $this->service->enable($enrolled);
    $this->service->confirm($enrolled, app(Google2FA::class)->getCurrentOtp(
        Crypt::decryptString($enrolled->fresh()->two_factor_secret)
    ));
    $secret = $enrolled->fresh()->two_factor_secret;

    config(['service-contract.auth.two_factor' => false]);
    expect($this->service->isEnabled($enrolled->fresh()))->toBeFalse();
    expect($enrolled->fresh()->two_factor_secret)->toBe($secret);

    config(['service-contract.auth.two_factor' => true]);
    expect($this->service->isEnabled($enrolled->fresh()))->toBeTrue();

    $this->post(route('attempt'), [
        'email' => 'enrolled@example.com',
        'password' => 'the-right-password',
    ])->assertRedirect(route('two-factor.login'));

    $this->assertGuest();
});

test('the rest of the account settings still work', function () {
    $this->actingAs($this->user)->get(route('profile.edit'))->assertOk();
    $this->actingAs($this->user)->get(route('user-password.edit'))->assertOk();
    $this->actingAs($this->user)->get(route('appearance.edit'))->assertOk();
});
