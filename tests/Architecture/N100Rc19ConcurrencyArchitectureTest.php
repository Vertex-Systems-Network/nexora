<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100Rc19ConcurrencyArchitectureTest extends TestCase
{
    #[Test]
    public function rc19_concurrency_deadlock_idempotency_and_optimistic_lock_contracts_are_current(): void
    {
        $root=base_path();
        require_once $root.'/scripts/lib/concurrency-contracts.php';
        $result=nexoraAnalyzeConcurrencyContracts($root);
        self::assertTrue($result['ok'],implode("\n",$result['errors']));
        self::assertSame(0,$result['metrics']['critical_direct_transactions']);
        self::assertSame(1,$result['metrics']['portable_mutexes']);
        self::assertSame(0,$result['metrics']['external_effect_exactly_once_claims']);

    }
}
