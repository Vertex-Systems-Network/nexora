<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100Rc18RuntimeSafetyArchitectureTest extends TestCase
{
    #[Test]
    public function rc18_runtime_limits_queue_and_cancellation_contracts_are_present_and_current(): void
    {
        $root=base_path();
        require_once $root.'/scripts/lib/runtime-safety-contracts.php';
        $result=nexoraAnalyzeRuntimeSafetyContracts($root);
        self::assertTrue($result['ok'],implode("\n",$result['errors']));
        self::assertSame(4,$result['metrics']['queue_jobs']);
        self::assertSame(1800,$result['metrics']['max_job_timeout']);
        self::assertSame(0,$result['metrics']['jobs_without_backoff']);
        self::assertSame(0,$result['metrics']['jobs_without_fail_on_timeout']);

    }
}
