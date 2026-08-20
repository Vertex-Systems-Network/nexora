<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V51TargetProgressVisibilityArchitectureTest extends TestCase
{
    #[Test]
    public function progress_visibility_does_not_confuse_source_remediation_with_real_target_evidence(): void
    {
        require_once base_path('scripts/lib/n1-target-progress-visibility-contracts.php');
        $result = \nexoraAnalyzeTargetProgressVisibilityContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(105, $result['metrics']['granular_target_gates']);
        self::assertSame(76, $result['metrics']['historical_typescript_errors']);
        self::assertSame(0, $result['metrics']['automatic_source_to_target_promotion']);
    }
}
