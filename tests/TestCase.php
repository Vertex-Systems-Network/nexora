<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests exercise server-side behavior before the production
        // frontend build step runs in development QA. Disable Vite rendering
        // inside Laravel's test harness so HTTP assertions do not depend on a
        // pre-existing public/build/manifest.json.
        $this->withoutVite();
    }
}
