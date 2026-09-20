<?php

use App\Models\User;

test('the public landing page renders', function () {
    $this->get(route('home'))->assertOk();
});

test('the backoffice requires authentication', function () {
    $this->get(route('backoffice.index'))->assertRedirect(route('login'));
});

test('an authenticated user reaches the backoffice', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('backoffice.index'))
        ->assertOk();
});

test('the health check responds', function () {
    $this->get('/up')->assertOk();
});
