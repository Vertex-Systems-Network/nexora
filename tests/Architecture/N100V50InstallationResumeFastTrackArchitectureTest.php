<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V50InstallationResumeFastTrackArchitectureTest extends TestCase
{
    #[Test]
    public function installation_resume_and_fast_track_boundaries_are_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-installation-resume-fast-track-contracts.php');
        $result = \nexoraAnalyzeN10InstallationResumeFastTrackContracts(base_path());

        self::assertSame([], $result['errors']);
        self::assertSame(0, $result['metrics']['automatic_lock_review']);
        self::assertSame(0, $result['metrics']['automatic_destructive_reset']);
    }
}
