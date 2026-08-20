<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100Rc21FrontendTypeContractArchitectureTest extends TestCase
{
    #[Test]
    public function rc21_inertia_frontend_type_contracts_are_current(): void
    {
        $root=base_path();
        require_once $root.'/scripts/lib/inertia-frontend-contracts.php';
        $result=\nexoraAnalyzeInertiaFrontendContracts($root);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));
        self::assertSame(11,$result['metrics']['laragon_error_files']);
        self::assertSame(0,$result['metrics']['transform_chains']);
        self::assertSame(0,$result['metrics']['unsafe_router_payloads']);
        self::assertSame(0,$result['metrics']['navlink_children']);
        self::assertSame(0,$result['metrics']['unsafe_useform_unknown']);
    }
}
