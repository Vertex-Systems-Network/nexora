<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V45TenantSeedTypeScriptArchitectureTest extends TestCase
{
    #[Test]
    public function tenant_seed_and_frontend_regression_boundaries_remain_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-tenant-seed-typescript-contracts.php');

        $result = \nexoraAnalyzeTenantSeedTypeScriptContracts(base_path());

        self::assertSame([], $result['errors'], implode(PHP_EOL, $result['errors']));
        self::assertSame(6, $result['metrics']['tenant_regression_tests']);
        self::assertSame(11, $result['metrics']['historical_typescript_targets']);
        self::assertSame(4, $result['metrics']['tenant_queue_jobs_scoped']);
        self::assertSame(1, $result['metrics']['tenant_seed_transactional']);
        self::assertSame(0, $result['metrics']['frontend_contract_errors']);
    }
}
