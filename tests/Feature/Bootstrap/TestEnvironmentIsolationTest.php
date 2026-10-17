<?php

declare(strict_types=1);

namespace Tests\Feature\Bootstrap;

use Tests\TestCase;

final class TestEnvironmentIsolationTest extends TestCase
{
    public function test_phpunit_uses_disposable_cache_session_and_queue_even_without_an_install_lock(): void
    {
        self::assertSame('testing', app()->environment());
        self::assertSame('.env.testing', app()->environmentFile(), 'PHPUnit must load a real non-secret testing profile without attempting to read a missing .env.');
        self::assertSame('array', config('cache.default'), 'PHPUnit must not leak throttle/cache state between test cases or use a persistent file cache. Diagnostics: cached_config='.(app()->configurationIsCached() ? 'yes' : 'no').'; process_cache='.(getenv('CACHE_STORE') ?: 'unset').'; environment_cache='.(string) env('CACHE_STORE', 'unset').'; app_env='.app()->environment().'; bypass='.(getenv('NEXORA_INSTALLER_BYPASS') ?: 'unset'));
        self::assertSame('array', config('session.driver'), 'PHPUnit must use disposable test sessions.');
        self::assertSame('sync', config('queue.default'), 'PHPUnit must use the declared synchronous test queue.');
        self::assertFalse(filter_var(getenv('NEXORA_CI_BROWSER_E2E') ?: false, FILTER_VALIDATE_BOOL), 'Ordinary PHPUnit execution must not inherit the browser opt-in.');
    }
}
