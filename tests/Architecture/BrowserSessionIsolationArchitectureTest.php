<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BrowserSessionIsolationArchitectureTest extends TestCase
{
    #[Test]
    public function only_disposable_loopback_ci_browser_tests_can_persist_session_between_requests(): void
    {
        $source = (string) file_get_contents(base_path('bootstrap/nexora-installer-bootstrap.php'));

        self::assertStringContainsString("'NEXORA_CI_BROWSER_E2E'", $source);
        self::assertStringContainsString("'nexora_testing'", $source);
        self::assertStringContainsString("'DB_CONNECTION'", $source);
        self::assertStringContainsString("['127.0.0.1', 'localhost']", $source);
        self::assertStringContainsString("http://127", $source);
        self::assertStringContainsString("preg_match(", $source);
        self::assertStringContainsString("$"."browserSessionOptIn ? 'file' : 'array'", $source);
        self::assertStringContainsString("$"."exportEnv('CACHE_STORE', 'array', true)", $source);
    }
}
