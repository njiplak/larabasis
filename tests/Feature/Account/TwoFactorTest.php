<?php

use App\Contract\Auth\TwoFactorContract;
use App\Models\User;
use App\Service\Auth\TwoFactorService;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->service = app(TwoFactorContract::class);
});

function currentCodeFor(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp(
        Crypt::decryptString($user->fresh()->two_factor_secret)
    );
}

test('the two-factor page renders', function () {
    $this->actingAs($this->user)
        ->get(route('two-factor.show'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/two-factor')
            ->where('twoFactorEnabled', false)
        );
});

test('enabling generates a secret and recovery codes but does not confirm', function () {
    $this->actingAs($this->user)->post(route('two-factor.enable'));

    $this->user->refresh();

    expect($this->user->two_factor_secret)->not->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
    expect($this->service->recoveryCodes($this->user))
        ->toHaveCount(TwoFactorService::RECOVERY_CODE_COUNT);
    expect($this->service->isEnabled($this->user))->toBeFalse();
    expect($this->service->isPending($this->user))->toBeTrue();
});

test('a correct code confirms two-factor', function () {
    $this->actingAs($this->user)->post(route('two-factor.enable'));

    $this->actingAs($this->user)
        ->post(route('two-factor.confirm'), ['code' => currentCodeFor($this->user)])
        ->assertSessionHasNoErrors();

    expect($this->service->isEnabled($this->user->fresh()))->toBeTrue();
});

test('a wrong code does not confirm and reports in the modals error bag', function () {
    $this->actingAs($this->user)->post(route('two-factor.enable'));

    $response = $this->actingAs($this->user)
        ->post(route('two-factor.confirm'), ['code' => '000000']);

    expect($response->getSession()->get('errors')->getBags())
        ->toHaveKey('confirmTwoFactorAuthentication');
    expect($this->service->isEnabled($this->user->fresh()))->toBeFalse();
});

test('disabling clears every two-factor column', function () {
    $this->actingAs($this->user)->post(route('two-factor.enable'));
    $this->actingAs($this->user)->post(route('two-factor.confirm'), ['code' => currentCodeFor($this->user)]);

    $this->actingAs($this->user)->delete(route('two-factor.disable'));

    $this->user->refresh();

    expect($this->user->two_factor_secret)->toBeNull();
    expect($this->user->two_factor_recovery_codes)->toBeNull();
    expect($this->user->two_factor_confirmed_at)->toBeNull();
});

test('the qr code and secret key are served once setup has started', function () {
    $this->actingAs($this->user)->post(route('two-factor.enable'));

    $this->actingAs($this->user)
        ->getJson(route('two-factor.qr-code'))
        ->assertOk()
        ->assertJsonStructure(['svg']);

    $this->actingAs($this->user)
        ->getJson(route('two-factor.secret-key'))
        ->assertOk()
        ->assertJsonStructure(['secretKey']);
});

test('the qr code, secret key and recovery codes 404 before setup starts', function (string $name) {
    $this->actingAs($this->user)
        ->getJson(route($name))
        ->assertNotFound();
})->with(['two-factor.qr-code', 'two-factor.secret-key', 'two-factor.recovery-codes']);

test('recovery codes can be regenerated', function () {
    $this->actingAs($this->user)->post(route('two-factor.enable'));
    $before = $this->service->recoveryCodes($this->user->fresh());

    $this->actingAs($this->user)->post(route('two-factor.regenerate-recovery-codes'));

    $after = $this->service->recoveryCodes($this->user->fresh());

    expect($after)->toHaveCount(TwoFactorService::RECOVERY_CODE_COUNT);
    expect($after)->not->toBe($before);
    expect(array_intersect($before, $after))->toBeEmpty();
});

test('a guest cannot touch any two-factor endpoint', function () {
    $this->get(route('two-factor.show'))->assertRedirect(route('login'));
    $this->post(route('two-factor.enable'))->assertRedirect(route('login'));
    $this->delete(route('two-factor.disable'))->assertRedirect(route('login'));
});

test('two-factor secrets never reach the shared inertia props', function () {
    $this->actingAs($this->user)->post(route('two-factor.enable'));

    $this->actingAs($this->user)
        ->get(route('backoffice.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('auth.user.two_factor_secret')
            ->missing('auth.user.two_factor_recovery_codes')
            ->missing('auth.user.password')
            ->where('auth.user.two_factor_enabled', false)
        );
});

test('a user cannot read another users recovery codes', function () {
    $other = User::factory()->create();
    $this->actingAs($other)->post(route('two-factor.enable'));
    $otherCodes = $this->service->recoveryCodes($other->fresh());

    $this->actingAs($this->user)->post(route('two-factor.enable'));

    $mine = $this->actingAs($this->user)
        ->getJson(route('two-factor.recovery-codes'))
        ->assertOk()
        ->json();

    expect(array_intersect($mine, $otherCodes))->toBeEmpty();
});

test('the feature is available by default', function () {
    expect($this->service->isAvailable())->toBeTrue();
});

test('the settings tab is shown when the feature is on', function () {
    $this->actingAs($this->user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('features.twoFactor', true));
});
