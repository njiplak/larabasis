<?php

use App\Models\User;

test('an authenticated user can log out', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('logging out invalidates the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('backoffice.index'));
    $sessionId = session()->getId();

    $this->post(route('logout'));

    expect(session()->getId())->not->toBe($sessionId);
});

test('a guest cannot reach the logout route', function () {
    $this->post(route('logout'))->assertRedirect(route('login'));
});
