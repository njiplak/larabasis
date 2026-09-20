<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests assert on Inertia props, not on built assets; without
        // this a missing `npm run build` fails the suite for the wrong reason.
        $this->withoutVite();
    }
}
