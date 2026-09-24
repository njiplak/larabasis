<?php

use App\Contract\Auth\TwoFactorContract;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Activitylog\Models\Activity;

function enrol(User $user): User
{
    $service = app(TwoFactorContract::class);
    $service->enable($user);
    $service->confirm($user, app(Google2FA::class)->getCurrentOtp(
        Crypt::decryptString($user->fresh()->two_factor_secret)
    ));

    return $user->fresh();
}

beforeEach(function () {
    $this->actor = superAdmin();
    $this->service = app(TwoFactorContract::class);
});

test('an admin can clear a locked-out users two-factor enrolment', function () {
    $locked = enrol(User::factory()->create());
    expect($this->service->isEnabled($locked))->toBeTrue();

    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.user.reset-two-factor', ['id' => $locked->id]))
        ->assertSessionHasNoErrors();

    $locked->refresh();

    expect($this->service->isEnabled($locked))->toBeFalse();
    expect($locked->two_factor_secret)->toBeNull();
    expect($locked->two_factor_recovery_codes)->toBeNull();
    expect($locked->two_factor_confirmed_at)->toBeNull();
});

test('the reset user can then sign in with their password alone', function () {
    $locked = enrol(User::factory()->create([
        'email' => 'locked@example.com',
        'password' => Hash::make('the-right-password'),
    ]));

    // Before the reset they are stopped at the challenge.
    $this->post(route('attempt'), [
        'email' => 'locked@example.com',
        'password' => 'the-right-password',
    ])->assertRedirect(route('two-factor.login'));
    $this->assertGuest();

    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.user.reset-two-factor', ['id' => $locked->id]));

    auth()->logout();
    session()->flush();

    $this->post(route('attempt'), [
        'email' => 'locked@example.com',
        'password' => 'the-right-password',
    ])->assertRedirect(route('backoffice.index'));

    $this->assertAuthenticatedAs($locked);
});

test('the old recovery codes stop working after a reset', function () {
    $locked = enrol(User::factory()->create());
    $oldCodes = $this->service->recoveryCodes($locked);

    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.user.reset-two-factor', ['id' => $locked->id]));

    expect($this->service->recoveryCodes($locked->fresh()))->toBeEmpty();
    expect($this->service->consumeRecoveryCode($locked->fresh(), $oldCodes[0]))->toBeFalse();
});

test('the user can enrol again afterwards', function () {
    $locked = enrol(User::factory()->create());

    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.user.reset-two-factor', ['id' => $locked->id]));

    $this->actingAs($locked->fresh())->post(route('two-factor.enable'));

    expect($this->service->isPending($locked->fresh()))->toBeTrue();
});

test('the reset is written to the audit trail', function () {
    $locked = enrol(User::factory()->create());

    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.user.reset-two-factor', ['id' => $locked->id]));

    $activity = Activity::where('event', 'two-factor-reset')->latest('id')->firstOrFail();

    expect($activity->subject_id)->toBe($locked->id);
    expect($activity->causer_id)->toBe($this->actor->id);
    expect($activity->properties['was_enrolled'])->toBeTrue();
});

test('the reset needs the user update permission', function () {
    $locked = enrol(User::factory()->create());

    $this->actingAs(userWith(['user.view']))
        ->post(route('backoffice.setting.user.reset-two-factor', ['id' => $locked->id]))
        ->assertForbidden();

    expect($this->service->isEnabled($locked->fresh()))->toBeTrue();
});

test('resetting a user who never enrolled is harmless', function () {
    $plain = User::factory()->create();

    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.user.reset-two-factor', ['id' => $plain->id]))
        ->assertSessionHasNoErrors();

    expect(Activity::where('event', 'two-factor-reset')->latest('id')->firstOrFail()
        ->properties['was_enrolled'])->toBeFalse();
});

test('resetting an unknown user 404s', function () {
    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.user.reset-two-factor', ['id' => 999999]))
        ->assertNotFound();
});

test('the reset endpoint 404s when two-factor is switched off for the project', function () {
    $locked = enrol(User::factory()->create());

    config(['service-contract.auth.two_factor' => false]);

    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.user.reset-two-factor', ['id' => $locked->id]))
        ->assertNotFound();
});

test('a guest cannot reset anyones two-factor', function () {
    $locked = enrol(User::factory()->create());

    $this->post(route('backoffice.setting.user.reset-two-factor', ['id' => $locked->id]))
        ->assertRedirect(route('login'));

    expect($this->service->isEnabled($locked->fresh()))->toBeTrue();
});
