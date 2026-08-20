<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Exceptions\ModuleDependencyException;
use App\Nexora\Foundation\Modules\ModuleRegistry;
use App\Nexora\Foundation\Runtime\ModuleDependency;
use App\Nexora\Foundation\Runtime\ModuleManifest;
use App\Nexora\Foundation\Runtime\VersionConstraintMatcher;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

final class ModuleRuntimeTest extends TestCase
{
    public function test_dependencies_boot_before_dependants(): void
    {
        $registry = $this->registry();
        $registry->register(RuntimeDependentModule::class);
        $registry->register(RuntimeFoundationModule::class);

        self::assertSame(['test.foundation', 'test.dependent'], $registry->bootOrder());
    }

    public function test_missing_required_dependency_is_rejected(): void
    {
        $registry = $this->registry();
        $registry->register(RuntimeDependentModule::class);

        $this->expectException(ModuleDependencyException::class);
        $registry->bootOrder();
    }

    public function test_incompatible_dependency_version_is_rejected(): void
    {
        $registry = $this->registry();
        $registry->register(RuntimeFoundationLegacyModule::class);
        $registry->register(RuntimeDependentModule::class);

        $this->expectException(ModuleDependencyException::class);
        $registry->bootOrder();
    }


    public function test_incompatible_optional_dependency_is_ignored(): void
    {
        $registry = $this->registry();
        $registry->register(RuntimeFoundationLegacyModule::class);
        $registry->register(RuntimeOptionalDependentModule::class);

        self::assertSame(['test.foundation', 'test.optional'], $registry->bootOrder());
    }

    public function test_circular_dependency_is_rejected(): void
    {
        $registry = $this->registry();
        $registry->register(RuntimeCycleAModule::class);
        $registry->register(RuntimeCycleBModule::class);

        $this->expectException(ModuleDependencyException::class);
        $registry->bootOrder();
    }

    private function registry(): ModuleRegistry
    {
        return new ModuleRegistry(new Container(), new VersionConstraintMatcher());
    }
}

final class RuntimeFoundationModule implements ModuleContract
{
    public function manifest(): ModuleManifest { return new ModuleManifest('test.foundation', 'Foundation', '0.4.2', core: true, loadOrder: 20); }
    public function register(): void {}
    public function boot(): void {}
}

final class RuntimeFoundationLegacyModule implements ModuleContract
{
    public function manifest(): ModuleManifest { return new ModuleManifest('test.foundation', 'Foundation', '0.3.9', core: true, loadOrder: 20); }
    public function register(): void {}
    public function boot(): void {}
}

final class RuntimeDependentModule implements ModuleContract
{
    public function manifest(): ModuleManifest { return new ModuleManifest('test.dependent', 'Dependent', '1.0.0', loadOrder: 10, dependencies: [new ModuleDependency('test.foundation', '^0.4')]); }
    public function register(): void {}
    public function boot(): void {}
}

final class RuntimeCycleAModule implements ModuleContract
{
    public function manifest(): ModuleManifest { return new ModuleManifest('test.cycle-a', 'A', '1.0.0', dependencies: [new ModuleDependency('test.cycle-b')]); }
    public function register(): void {}
    public function boot(): void {}
}

final class RuntimeCycleBModule implements ModuleContract
{
    public function manifest(): ModuleManifest { return new ModuleManifest('test.cycle-b', 'B', '1.0.0', dependencies: [new ModuleDependency('test.cycle-a')]); }
    public function register(): void {}
    public function boot(): void {}
}


final class RuntimeOptionalDependentModule implements ModuleContract
{
    public function manifest(): ModuleManifest { return new ModuleManifest('test.optional', 'Optional', '1.0.0', dependencies: [new ModuleDependency('test.foundation', '^0.4', true)]); }
    public function register(): void {}
    public function boot(): void {}
}
