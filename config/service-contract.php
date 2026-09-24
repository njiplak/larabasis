<?php

return [
    'default_paginated' => true,
    'pagination_per_page' => 10,
    'seeder_faker' => env('SEEDER_FAKER', false),
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL', 'admin@kawakib.test'),
        // Left empty on purpose: the seeder generates one and prints it once.
        'password' => env('ADMIN_PASSWORD'),
    ],

    'auth' => [
        // Turn two-factor auth off for projects that do not want it: the
        // screens 404, the settings tab disappears and no one is challenged
        // at login. Users who already enrolled keep their secret and are
        // challenged again if it is switched back on.
        'two_factor' => env('AUTH_TWO_FACTOR', true),
    ],

];
