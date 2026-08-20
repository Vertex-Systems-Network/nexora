<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V52SourceActivationArchitectureTest extends TestCase
{
    #[Test]
    public function installer_source_activation_is_exact_and_fail_closed(): void
    {
        require_once base_path('scripts/lib/n1-target-source-activation-contracts.php');
        $result = \nexoraAnalyzeTargetSourceActivationContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(0, $result['metrics']['database_mutations_before_source_assert']);
        self::assertSame(0, $result['metrics']['target_gate_denominator_changed']);
    }
}
