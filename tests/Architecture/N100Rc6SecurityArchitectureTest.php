<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc6SecurityArchitectureTest extends TestCase
{
    public function test_rc6_security_contracts_are_source_clean(): void
    {
        $root = dirname(__DIR__, 2);
        require_once $root.'/scripts/lib/security-contracts.php';
        $result = \nexoraAnalyzeSecurityContracts($root);
        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(3, $result['metrics']['csrf_exceptions']);
        self::assertGreaterThanOrEqual(3, $result['metrics']['session_rotation_paths']);
        self::assertSame(1, $result['metrics']['tenant_route_binding_guards']);
        self::assertSame(0, $result['metrics']['raw_tenant_exists']);
        self::assertSame(0, $result['metrics']['raw_tenant_member_exists']);
    }
}
