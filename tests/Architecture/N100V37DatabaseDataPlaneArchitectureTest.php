<?php

declare(strict_types=1);
namespace Tests\Architecture;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
final class N100V37DatabaseDataPlaneArchitectureTest extends TestCase
{
    #[Test] public function database_data_plane_and_schema_drift_boundaries_are_fail_closed(): void
    {
        $root=base_path();require_once $root.'/scripts/lib/n1-target-database-data-plane-contracts.php';$r=\nexoraAnalyzeDatabaseDataPlaneContracts($root);self::assertSame([],$r['errors'],implode("\n",$r['errors']));self::assertSame(1,$r['metrics']['data_plane_identity']);self::assertSame(1,$r['metrics']['schema_attestation']);self::assertGreaterThanOrEqual(7,$r['metrics']['queue_payload_schema']);self::assertSame(1,$r['metrics']['c2_schema_round_trip']);self::assertSame(5,$r['metrics']['c3_driver_schema_round_trip']);self::assertSame(0,$r['metrics']['automatic_database_mutation']);
    }
}
