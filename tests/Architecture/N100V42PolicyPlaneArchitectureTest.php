<?php

declare(strict_types=1);
namespace Tests\Architecture;
use Tests\TestCase;
final class N100V42PolicyPlaneArchitectureTest extends TestCase
{
    public function test_effective_policy_plane_contracts_are_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-policy-plane-contracts.php');$r=\nexoraAnalyzePolicyPlaneContracts(base_path());self::assertSame([],$r['errors'],implode("\n",$r['errors']));self::assertGreaterThanOrEqual(13,$r['metrics']['queue_payload_schema']);self::assertSame(0,$r['metrics']['automatic_policy_mutation']);
    }
}
