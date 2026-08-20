<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V46TenantExecutionBoundaryArchitectureTest extends TestCase
{
    #[Test]
    public function tenant_execution_boundary_remains_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-tenant-execution-contracts.php');

        $result = \nexoraAnalyzeTenantExecutionContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(4, $result['metrics']['tenant_queue_jobs_scoped']);
        self::assertSame(6, $result['metrics']['tenant_regression_tests']);
        self::assertSame(9, $result['metrics']['c4_tenant_execution_checks']);
        self::assertSame(0, $result['metrics']['automatic_cross_tenant_fallback']);
    }
}
