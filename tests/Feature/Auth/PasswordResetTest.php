<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'forgetful@example.com',
        'password' => Hash::make('the-old-password'),
    ]);
});

test('the forgot password screen renders', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/forgot-password'));
});

test('a reset link is sent for a known address', function () {
    Notification::fake();

    $this->post(route('password.email'), ['email' => 'forgetful@example.com'])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($this->user, ResetPassword::class);
});

test('an unknown address gets the same response and no email', function () {
    Notification::fake();

    $known = $this->post(route('password.email'), ['email' => 'forgetful@example.com']);
    $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

    expect($unknown->getSession()->get('status'))->toBe($known->getSession()->get('status'));
    $unknown->assertSessionHasNoErrors();
    Notification::assertSentTimes(ResetPassword::class, 1);
});

test('the reset screen renders with the token', function () {
    $this->get(route('password.reset', ['token' => 'a-token']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/reset-password')
            ->where('token', 'a-token')
        );
});

test('a valid token resets the password', function () {
    Notification::fake();
    $this->post(route('password.email'), ['email' => 'forgetful@example.com']);

    $token = null;
    Notification::assertSentTo($this->user, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => 'forgetful@example.com',
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ])->assertRedirect(route('login'));

    expect(Hash::check('a-brand-new-password', $this->user->fresh()->password))->toBeTrue();
});

test('the new password is usable at login', function () {
    Notification::fake();
    $this->post(route('password.email'), ['email' => 'forgetful@example.com']);

    $token = null;
    Notification::assertSentTo($this->user, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => 'forgetful@example.com',
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ]);

    $this->post(route('attempt'), [
        'email' => 'forgetful@example.com',
        'password' => 'a-brand-new-password',
    ])->assertRedirect(route('backoffice.index'));

    $this->assertAuthenticated();
});

test('an invalid token is rejected', function () {
    $this->post(route('password.store'), [
        'token' => 'not-a-real-token',
        'email' => 'forgetful@example.com',
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('the-old-password', $this->user->fresh()->password))->toBeTrue();
});

test('a token cannot be reused', function () {
    Notification::fake();
    $this->post(route('password.email'), ['email' => 'forgetful@example.com']);

    $token = null;
    Notification::assertSentTo($this->user, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $payload = [
        'token' => $token,
        'email' => 'forgetful@example.com',
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'a-brand-new-password',
    ];

    $this->post(route('password.store'), $payload);
    $this->post(route('password.store'), $payload)->assertSessionHasErrors('email');
});

test('the reset requires a confirmed password', function () {
    $this->post(route('password.store'), [
        'token' => 'any',
        'email' => 'forgetful@example.com',
        'password' => 'a-brand-new-password',
        'password_confirmation' => 'mismatch',
    ])->assertSessionHasErrors('password');
});

test('an authenticated user is redirected away from the reset screens', function () {
    $this->actingAs($this->user)
        ->get(route('password.request'))
        ->assertRedirect(route('backoffice.index'));
});
