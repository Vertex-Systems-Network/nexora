<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Foundation\Capabilities\CapabilityRegistry;
use App\Nexora\Foundation\Runtime\CapabilityDefinition;
use App\Nexora\Foundation\Runtime\CapabilityRisk;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CapabilityRegistryTest extends TestCase
{
    public function test_identical_registration_is_idempotent(): void
    {
        $registry = new CapabilityRegistry();
        $capability = new CapabilityDefinition('content.read', 'Read Content', 'content');

        $registry->register($capability);
        $registry->register($capability);

        self::assertCount(1, $registry->all());
    }

    public function test_conflicting_capability_definition_is_rejected(): void
    {
        $registry = new CapabilityRegistry();
        $registry->register(new CapabilityDefinition('content.read', 'Read Content', 'content'));

        $this->expectException(InvalidArgumentException::class);
        $registry->register(new CapabilityDefinition('content.read', 'Read Content', 'content', CapabilityRisk::Critical));
    }
}
