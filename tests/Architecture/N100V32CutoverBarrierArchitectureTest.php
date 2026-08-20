<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V32CutoverBarrierArchitectureTest extends TestCase
{
    #[Test]
    public function runtime_admission_is_atomic_with_the_platform_upgrade_barrier(): void
    {
        $leases=(string)file_get_contents(base_path('app/Nexora/Cloud/Services/RuntimeLeaseManager.php'));
        $activity=(string)file_get_contents(base_path('app/Nexora/Cloud/Services/RuntimeActivityTracker.php'));
        $middleware=(string)file_get_contents(base_path('app/Http/Middleware/RuntimeNodeHeartbeat.php'));
        self::assertStringContainsString('acquireActivityUnlessBarrierActive',$leases);
        self::assertStringContainsString("where('name',\$barrierName)->lockForUpdate()",$leases);
        self::assertStringContainsString('acquireActivityUnlessBarrierActive',$activity);
        self::assertStringContainsString('X-Nexora-Cutover-Barrier',$middleware);
    }

    #[Test]
    public function frontend_inertia_v3_regression_gate_remains_green(): void
    {
        $command=escapeshellarg(PHP_BINARY).' '.escapeshellarg(base_path('scripts/frontend-contract-verify.php'));
        exec($command,$output,$exitCode);
        self::assertSame(0,$exitCode,implode("\n",$output));
    }
}
