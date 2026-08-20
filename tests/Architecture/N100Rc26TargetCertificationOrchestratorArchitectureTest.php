<?php

declare(strict_types=1);
namespace Tests\Architecture;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
final class N100Rc26TargetCertificationOrchestratorArchitectureTest extends TestCase
{
    #[Test]
    public function rc26_one_command_target_orchestrator_is_fail_closed_and_non_destructive(): void
    {
        $root=base_path();
        require_once $root.'/scripts/lib/target-orchestrator-contracts.php';
        $result=\nexoraAnalyzeTargetOrchestratorContracts($root);
        self::assertSame([],$result['errors'],implode("\n",$result['errors']));
        self::assertSame(3,$result['metrics']['wrappers']);
        self::assertSame(0,$result['metrics']['automatic_lock_acceptance']);
        self::assertSame(0,$result['metrics']['direct_destructive_db_commands']);
    }
}
