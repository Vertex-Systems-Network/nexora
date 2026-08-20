<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V53SourceSetHandshakeArchitectureTest extends TestCase
{
    #[Test]
    public function source_set_and_web_ack_boundary_stays_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-source-set-handshake-contracts.php');
        $result = \nexoraAnalyzeTargetSourceSetHandshakeContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertGreaterThanOrEqual(14, $result['metrics']['critical_source_files']);
        self::assertSame(0, $result['metrics']['partial_deployment_allowed']);
        self::assertSame(0, $result['metrics']['target_gate_denominator_changed']);
    }
}
