<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N100V41ResourceEnvelopeArchitectureTest extends TestCase
{
    public function test_resource_envelope_contract_is_present(): void
    {
        $root = dirname(__DIR__, 2);
        require_once $root.'/scripts/lib/n1-target-resource-envelope-contracts.php';

        $result = nexoraAnalyzeResourceEnvelopeContracts($root);

        self::assertSame([], $result['errors'], implode("\n", $result['errors']));
        self::assertSame(13, $result['metrics']['queue_payload_schema']);
        self::assertSame(0, $result['metrics']['automatic_resource_mutation']);
    }
}
