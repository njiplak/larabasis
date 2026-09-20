<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->user = User::factory()->create(['password' => Hash::make('current-password-1')]);
});

test('the password page renders', function () {
    $this->actingAs($this->user)
        ->get(route('user-password.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/password'));
});

test('a user can change their own password', function () {
    $this->actingAs($this->user)
        ->put(route('user-password.update'), [
            'current_password' => 'current-password-1',
            'password' => 'a-fresh-password-2',
            'password_confirmation' => 'a-fresh-password-2',
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('a-fresh-password-2', $this->user->fresh()->password))->toBeTrue();
});

test('the current password must be correct', function () {
    $this->actingAs($this->user)
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'a-fresh-password-2',
            'password_confirmation' => 'a-fresh-password-2',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('current-password-1', $this->user->fresh()->password))->toBeTrue();
});

test('the new password must be confirmed', function () {
    $this->actingAs($this->user)
        ->put(route('user-password.update'), [
            'current_password' => 'current-password-1',
            'password' => 'a-fresh-password-2',
            'password_confirmation' => 'something-else',
        ])
        ->assertSessionHasErrors('password');
});

test('the new password must meet the minimum length', function () {
    $this->actingAs($this->user)
        ->put(route('user-password.update'), [
            'current_password' => 'current-password-1',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSessionHasErrors('password');
});

test('changing the password clears the remember token', function () {
    $this->user->forceFill(['remember_token' => 'stale-token'])->save();

    $this->actingAs($this->user)->put(route('user-password.update'), [
        'current_password' => 'current-password-1',
        'password' => 'a-fresh-password-2',
        'password_confirmation' => 'a-fresh-password-2',
    ]);

    expect($this->user->fresh()->remember_token)->toBeNull();
});

test('a guest cannot change a password here', function () {
    $this->put(route('user-password.update'), [])->assertRedirect(route('login'));
});
