<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc4LaravelRuntimeArchitectureTest extends TestCase
{
    public function test_laravel_runtime_entry_points_are_guarded_before_framework_boot(): void
    {
        $root=dirname(__DIR__,2);
        require_once $root.'/scripts/lib/laravel-runtime-contracts.php';
        $result=\nexoraAnalyzeLaravelRuntimeContracts($root);

        self::assertTrue($result['ok'], implode("\n", $result['errors']));
        self::assertGreaterThanOrEqual(8, $result['checks']['middleware_entries']);
        self::assertSame(4, $result['checks']['route_middleware_aliases']);
        self::assertGreaterThanOrEqual(10, $result['checks']['scheduled_commands']);
        self::assertSame(2, $result['checks']['scheduled_callbacks']);
        self::assertSame(5, $result['checks']['queue_jobs']);
        self::assertSame(10, $result['checks']['service_providers']);

        $certifier=(string)file_get_contents($root.'/scripts/certify-release.php');
        self::assertStringContainsString('laravel-runtime-contract-verify.php',$certifier);
    }
}
