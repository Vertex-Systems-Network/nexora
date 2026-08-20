<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V47FreshInstallDependencyTrustArchitectureTest extends TestCase
{
    #[Test]
    public function fresh_install_dependency_trust_boundary_remains_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-fresh-install-dependency-trust-contracts.php');

        $result = \nexoraAnalyzeFreshInstallDependencyTrustContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(2, $result['metrics']['fresh_install_trust_modes']);
        self::assertSame(0, $result['metrics']['automatic_human_review_fabrication']);
    }
}
