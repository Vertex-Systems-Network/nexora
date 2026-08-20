<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V511TransactionalLockIntakeArchitectureTest extends TestCase
{
    #[Test]
    public function dependency_lock_refresh_and_reviewed_promotion_remain_transactional(): void
    {
        require_once base_path('scripts/lib/n1-target-transactional-lock-intake-contracts.php');
        $result = \nexoraAnalyzeTransactionalLockIntakeContracts(base_path());

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(2, $result['metrics']['transaction_phases']);
        self::assertSame(0, $result['metrics']['root_lockfiles_mutated_during_refresh']);
        self::assertSame(2, $result['metrics']['rollback_root_lockfiles']);
        self::assertSame(6, $result['metrics']['durable_promotion_stages']);
        self::assertSame(1, $result['metrics']['crash_recovery_command']);
        self::assertSame(105, $result['metrics']['target_gate_denominator']);
        self::assertSame(0, $result['metrics']['target_gate_denominator_changed']);
    }
}
