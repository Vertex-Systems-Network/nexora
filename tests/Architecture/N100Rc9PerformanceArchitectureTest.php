<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc9PerformanceArchitectureTest extends TestCase
{
    public function test_rc9_performance_and_production_packaging_contracts_are_present(): void
    {
        $root=dirname(__DIR__,2);
        require_once $root.'/scripts/lib/performance-contracts.php';
        $result=\nexoraAnalyzePerformanceContracts($root);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));

        $config=(string)file_get_contents($root.'/config/nexora.php');
        $runner=(string)file_get_contents($root.'/scripts/certify-release.php');
        $builder=(string)file_get_contents($root.'/scripts/build-production-release.php');
        $headers=(string)file_get_contents($root.'/app/Http/Middleware/ApplyPerformanceHeaders.php');
        $htaccess=(string)file_get_contents($root.'/public/.htaccess');

        self::assertStringContainsString('performance-contract-verify.php',$runner);
        self::assertStringContainsString('performance-build-verify.php',$runner);
        self::assertStringContainsString("'artisan-optimize-boot'",$runner);
        self::assertStringContainsString('build-assets.json',$builder);
        self::assertStringContainsString('forbidden_archive_prefixes',$builder);
        self::assertStringContainsString("'Cache-Control', 'no-store, private'",$headers);
        self::assertStringContainsString('Strict-Transport-Security',$headers);
        self::assertStringContainsString('max-age=31536000, immutable',$htaccess);
        self::assertStringContainsString('BROTLI_COMPRESS',$htaccess);
    }
}
