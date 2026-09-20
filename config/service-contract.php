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

];
