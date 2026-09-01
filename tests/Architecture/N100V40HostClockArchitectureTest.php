<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V40HostClockArchitectureTest extends TestCase
{
    #[Test]
    public function host_clock_runtime_boundary_is_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-host-clock-contracts.php');$r=\nexoraAnalyzeHostClockContracts(base_path());self::assertSame([],$r['errors']);self::assertGreaterThanOrEqual(13,$r['metrics']['queue_payload_schema']);self::assertSame(1,$r['metrics']['shared_clock_anchor']);self::assertSame(0,$r['metrics']['automatic_ntp_mutation']);
    }
}
