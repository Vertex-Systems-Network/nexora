<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100Rc17TransferArchitectureTest extends TestCase
{
    #[Test]
    public function rc17_large_file_transfer_contracts_are_present_and_current(): void
    {
        $root=base_path();
        require_once $root.'/scripts/lib/transfer-contracts.php';
        $result=nexoraAnalyzeTransferContracts($root);
        self::assertTrue($result['ok'],implode("\n",$result['errors']));
        self::assertSame(0,$result['metrics']['unsafe_backup_full_loads']);
        self::assertSame(0,$result['metrics']['unbounded_archive_extracts']);
        self::assertSame(7,$result['metrics']['transfer_surfaces']);

    }
}
