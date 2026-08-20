<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Foundation\Capabilities\CapabilityGuard;
use App\Nexora\Foundation\Capabilities\CapabilityRegistry;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Modules\ModuleRegistry;
use App\Nexora\Foundation\Runtime\CapabilityDefinition;
use App\Nexora\Foundation\Runtime\ModuleManifest;
use App\Nexora\Foundation\Runtime\RuntimeContext;
use App\Nexora\Foundation\Runtime\RuntimeIdentity;
use App\Nexora\Foundation\Runtime\VersionConstraintMatcher;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

final class RuntimeContextCapabilityTest extends TestCase
{
    public function test_module_can_only_use_declared_registered_capabilities(): void
    {
        $modules = new ModuleRegistry(new Container(), new VersionConstraintMatcher());
        $modules->register(RuntimeCapabilityModule::class);
        $capabilities = new CapabilityRegistry();
        $capabilities->register(new CapabilityDefinition('content.read', 'Read content', 'content'));
        $capabilities->register(new CapabilityDefinition('content.write', 'Write content', 'content'));
        $context = new RuntimeContext();
        $guard = new CapabilityGuard($context, $modules, $capabilities);

        self::assertTrue($guard->allows('content.read')); // platform context
        $context->runAs(RuntimeIdentity::module('test.capability'), function () use ($guard): void {
            self::assertTrue($guard->allows('content.read'));
            self::assertFalse($guard->allows('content.write'));
        });
        self::assertSame('nexora.platform', $context->current()->identifier);
    }
}

final class RuntimeCapabilityModule implements ModuleContract
{
    public function manifest(): ModuleManifest { return new ModuleManifest('test.capability', 'Capability Test', '1.0.0', capabilities: ['content.read']); }
    public function register(): void {}
    public function boot(): void {}
}
