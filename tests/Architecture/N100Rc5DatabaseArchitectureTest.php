<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc5DatabaseArchitectureTest extends TestCase
{
    public function test_database_migration_seeder_and_tenant_contracts_are_guarded_before_framework_boot(): void
    {
        $root=dirname(__DIR__,2);
        require_once $root.'/scripts/lib/database-contracts.php';
        $result=\nexoraAnalyzeDatabaseContracts($root);

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(38, $result['metrics']['migrations']);
        self::assertSame(135, $result['metrics']['tables']);
        self::assertGreaterThanOrEqual(70, $result['metrics']['foreign_targets']);
        self::assertSame(51, $result['metrics']['tenant_tables']);
        self::assertSame(51, $result['metrics']['tenant_models']);
        self::assertSame(11, $result['metrics']['portable_nullable_unique']);

        $certifier=(string)file_get_contents($root.'/scripts/certify-release.php');
        foreach(['database-contract-verify.php','seed-idempotency','migration-reset','migration-rebuild','seed-rebuild'] as $marker) {
            self::assertStringContainsString($marker,$certifier);
        }
    }
}
