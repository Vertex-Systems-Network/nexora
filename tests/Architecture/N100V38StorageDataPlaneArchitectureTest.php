<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\TestCase;

final class N100V38StorageDataPlaneArchitectureTest extends TestCase
{
    public function test_v38_storage_data_plane_contracts_remain_fail_closed(): void
    {
        $root=base_path();require_once $root.'/scripts/lib/n1-target-storage-data-plane-contracts.php';$r=\nexoraAnalyzeStorageDataPlaneContracts($root);self::assertSame([],$r['errors'],implode("\n",$r['errors']));self::assertSame(1,$r['metrics']['storage_data_plane_identity']);self::assertSame(3,$r['metrics']['deep_roundtrip_roles']);self::assertGreaterThanOrEqual(8,$r['metrics']['queue_payload_schema']);self::assertSame(1,$r['metrics']['backup_storage_binding']);self::assertSame(0,$r['metrics']['automatic_storage_migration']);
    }
}
