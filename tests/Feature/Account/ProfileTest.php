<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Original', 'email' => 'me@example.com']);
});

test('the profile page renders', function () {
    $this->actingAs($this->user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/profile'));
});

test('a guest cannot reach the profile page', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});

test('a user can update their own name and email', function () {
    $this->actingAs($this->user)
        ->patch(route('profile.update'), ['name' => 'Renamed', 'email' => 'new@example.com'])
        ->assertSessionHasNoErrors();

    $this->user->refresh();

    expect($this->user->name)->toBe('Renamed');
    expect($this->user->email)->toBe('new@example.com');
});

test('a user may keep their own email', function () {
    $this->actingAs($this->user)
        ->patch(route('profile.update'), ['name' => 'Renamed', 'email' => 'me@example.com'])
        ->assertSessionHasNoErrors();
});

test('an email already taken by someone else is rejected', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->user)
        ->patch(route('profile.update'), ['name' => 'Renamed', 'email' => 'taken@example.com'])
        ->assertSessionHasErrors('email');
});

test('the profile update validates its input', function () {
    $this->actingAs($this->user)
        ->patch(route('profile.update'), ['name' => '', 'email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email']);
});

test('a user cannot edit another users profile through this endpoint', function () {
    $other = User::factory()->create(['name' => 'Untouched']);

    $this->actingAs($this->user)
        ->patch(route('profile.update'), ['name' => 'Hijacked', 'email' => 'me@example.com']);

    expect($other->fresh()->name)->toBe('Untouched');
    expect($this->user->fresh()->name)->toBe('Hijacked');
});
