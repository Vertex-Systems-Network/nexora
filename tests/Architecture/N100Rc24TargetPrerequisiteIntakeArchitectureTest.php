<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100Rc24TargetPrerequisiteIntakeArchitectureTest extends TestCase
{
    #[Test]
    public function rc24_target_prerequisite_and_reviewed_lock_intake_is_fail_closed(): void
    {
        $root=base_path();
        require_once $root.'/scripts/lib/target-intake-contracts.php';
        $result=\nexoraAnalyzeTargetIntakeContracts($root);
        self::assertSame([], $result['errors'], implode("\n",$result['errors']));
        self::assertSame(3,$result['metrics']['intake_wrappers']);
        self::assertSame(3,$result['metrics']['lock_review_wrappers']);
        self::assertSame(4,$result['metrics']['attestation_hash_bindings']);
        self::assertFileExists($root.'/config/nexora.php');
        self::assertStringContainsString('dependency-lock-review.php',(string)file_get_contents($root.'/scripts/certification-preflight.php'));
        $review=(string)file_get_contents($root.'/scripts/dependency-lock-review.php');
        self::assertStringContainsString('--confirm=REVIEWED',$review);
        self::assertStringContainsString('--verify-attestation',$review);
    }
}
