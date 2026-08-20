<?php

declare(strict_types=1);
namespace Tests\Architecture;
use PHPUnit\Framework\Attributes\Test;use Tests\TestCase;
final class N100V34RuntimeEnvironmentArchitectureTest extends TestCase
{
    #[Test] public function runtime_environment_and_key_rotation_contracts_remain_fail_closed(): void
    { $root=base_path();require_once $root.'/scripts/lib/n1-target-runtime-environment-contracts.php';$r=\nexoraAnalyzeRuntimeEnvironmentContracts($root);self::assertSame([],$r['errors'],implode("\n",$r['errors']));self::assertSame(1,$r['metrics']['runtime_environment_fingerprint']);self::assertGreaterThanOrEqual(4,$r['metrics']['queue_payload_schema']);self::assertSame(1,$r['metrics']['key_rotation_workflow']);self::assertSame(0,$r['metrics']['raw_secret_output']);self::assertSame(0,$r['metrics']['automatic_key_mutation']);self::assertSame(0,$r['metrics']['automatic_traffic_change']); }
}
