<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V59ExactResumeCommitArchitectureTest extends TestCase
{
    #[Test]
    public function exact_resume_commit_and_runtime_recovery_remain_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-exact-resume-commit-contracts.php');
        $result = \nexoraAnalyzeExactResumeCommitContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(2, $result['metrics']['resume_provenance_schema']);
        self::assertSame(0, $result['metrics']['post_commit_login_redirect_on_handoff_failure']);
        self::assertSame(0, $result['metrics']['target_denominator_change']);
    }
}
