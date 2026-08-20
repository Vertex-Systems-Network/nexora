<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V48InstallationCommitBoundaryArchitectureTest extends TestCase
{
    #[Test]
    public function installation_commit_boundary_is_fail_closed_and_crash_safe(): void
    {
        require_once base_path('scripts/lib/n1-target-installation-commit-contracts.php');
        $result = \nexoraAnalyzeInstallationCommitContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(2, $result['metrics']['install_lock_schema']);
        self::assertSame(0, $result['metrics']['automatic_corrupt_lock_reopen']);
    }
}
