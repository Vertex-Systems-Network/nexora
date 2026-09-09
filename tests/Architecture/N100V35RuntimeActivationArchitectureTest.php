<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N100V35RuntimeActivationArchitectureTest extends TestCase
{
    #[Test]
    public function runtime_activation_epoch_cache_and_process_fences_are_source_certified(): void
    {
        $root=base_path();require_once $root.'/scripts/lib/n1-target-runtime-activation-contracts.php';$r=\nexoraAnalyzeRuntimeActivationContracts($root);self::assertSame([],$r['errors'],implode("\n",$r['errors']));self::assertSame(1,$r['metrics']['activation_identity']);self::assertGreaterThanOrEqual(6,$r['metrics']['queue_payload_schema']);self::assertSame(1,$r['metrics']['process_epoch_fence']);self::assertSame(1,$r['metrics']['framework_cache_fingerprint']);self::assertSame(0,$r['metrics']['automatic_php_fpm_restart']);self::assertSame(0,$r['metrics']['automatic_traffic_restore']);
    }
}
