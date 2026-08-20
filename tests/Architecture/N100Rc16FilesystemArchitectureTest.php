<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100Rc16FilesystemArchitectureTest extends TestCase
{
    #[Test]
    public function rc16_filesystem_portability_contracts_are_present_and_current(): void
    {
        $root=base_path();
        require_once $root.'/scripts/lib/filesystem-contracts.php';
        $result=nexoraAnalyzeFilesystemContracts($root);
        self::assertTrue($result['ok'], implode("\n", $result['errors']));
        self::assertSame(0,$result['metrics']['case_collisions']);
        self::assertSame(0,$result['metrics']['windows_invalid_paths']);

    }
}
