<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\Capability;
use App\Models\Module;
use App\Models\ModuleDependency;
use App\Nexora\Foundation\Contracts\CapabilityRegistryContract;
use App\Nexora\Foundation\Contracts\ModuleRegistryContract;
use App\Nexora\Foundation\Runtime\RuntimeSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RuntimeSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_runtime_synchronizes_to_database_idempotently(): void
    {
        $synchronizer = app(RuntimeSynchronizer::class);
        $moduleRegistry = app(ModuleRegistryContract::class);
        $capabilityRegistry = app(CapabilityRegistryContract::class);
        $expectedModules = count($moduleRegistry->manifests());
        $expectedCapabilities = count($capabilityRegistry->all());
        $expectedDependencies = collect($moduleRegistry->manifests())->sum(static fn ($manifest): int => count($manifest->dependencies));

        $first = $synchronizer->sync();
        $second = $synchronizer->sync();

        self::assertSame($expectedModules, $first['modules']);
        self::assertSame($expectedCapabilities, $first['capabilities']);
        self::assertSame($first, $second);
        self::assertSame($expectedModules, Module::query()->count());
        self::assertSame($expectedCapabilities, Capability::query()->count());
        self::assertSame($expectedDependencies, ModuleDependency::query()->count());
        $this->assertDatabaseHas('nx_modules', ['identifier' => 'nexora.runtime', 'version' => '0.5.0', 'status' => 'active']);
        $this->assertDatabaseHas('nx_modules', ['identifier' => 'nexora.discovery', 'version' => '0.26.0', 'status' => 'active']);
        $this->assertDatabaseHas('nx_module_capabilities', ['mode' => 'requested']);
    }
}
