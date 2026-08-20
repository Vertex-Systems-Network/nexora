<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V36RuntimeEngineArchitectureTest extends TestCase
{
    #[Test]
    public function runtime_engine_identity_and_queue_fence_are_source_certified(): void
    {
        require_once base_path('scripts/lib/n1-target-runtime-engine-contracts.php');
        $result=\nexoraAnalyzeRuntimeEngineContracts(base_path());
        self::assertSame([], $result['errors']);
        self::assertSame(1, $result['metrics']['engine_identity']);
        self::assertGreaterThanOrEqual(6, $result['metrics']['queue_payload_schema']);
        self::assertSame(0, $result['metrics']['automatic_php_runtime_mutation']);
    }
}
