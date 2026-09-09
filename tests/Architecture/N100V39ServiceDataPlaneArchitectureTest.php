<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\TestCase;

final class N100V39ServiceDataPlaneArchitectureTest extends TestCase
{
    public function test_service_network_data_plane_contracts_are_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-service-data-plane-contracts.php');$r=\nexoraAnalyzeServiceDataPlaneContracts(base_path());
        self::assertSame([],$r['errors'],implode("\n",$r['errors']));self::assertGreaterThanOrEqual(13,$r['metrics']['queue_payload_schema']);self::assertSame(0,$r['metrics']['direct_http_bypasses']);self::assertGreaterThanOrEqual(4,$r['metrics']['approved_http_call_sites']);
    }
}
