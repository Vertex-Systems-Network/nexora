<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V49InstallerConsentFlowArchitectureTest extends TestCase
{
    #[Test]
    public function installer_consent_and_final_cta_contract_is_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-installer-consent-flow-contracts.php');

        $result = \nexoraAnalyzeInstallerConsentFlowContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(2, $result['metrics']['database_existing_actions']);
        self::assertSame(3, $result['metrics']['password_consent_levels']);
        self::assertSame(1, $result['metrics']['dependency_preflight_before_database']);
    }
}
