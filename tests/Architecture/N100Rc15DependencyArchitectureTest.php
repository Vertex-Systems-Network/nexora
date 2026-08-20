<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100Rc15DependencyArchitectureTest extends TestCase
{
    public function test_rc15_dependency_reproducibility_contract_is_fail_closed(): void
    {
        $root=dirname(__DIR__,2);
        require_once $root.'/scripts/lib/dependency-contracts.php';
        $result=\nexoraAnalyzeDependencyContracts($root,false);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));
        self::assertGreaterThanOrEqual(10, $result['metrics']['direct_prod_dependencies']);

        $config=(string)file_get_contents($root.'/config/nexora.php');
        $plan=(string)file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        $final=(string)file_get_contents($root.'/scripts/final-target-run.php');
        self::assertStringContainsString("['npm','ci'",$final);
        self::assertStringNotContainsString("['npm','install'",$final);
    }
}
