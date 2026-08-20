<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

use App\Nexora\Foundation\Contracts\CapabilityRegistryContract;
use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Contracts\ModuleRegistryContract;
use App\Nexora\Foundation\Contracts\NexoraKernelContract;
use App\Nexora\Foundation\Contracts\RuntimeContextContract;
use App\Nexora\Foundation\Exceptions\RuntimeConfigurationException;
use Illuminate\Contracts\Container\Container;

final class NexoraKernel implements NexoraKernelContract
{
    private bool $configured = false;
    private bool $booted = false;

    public function __construct(
        private readonly Container $container,
        private readonly ModuleRegistryContract $modules,
        private readonly CapabilityRegistryContract $capabilities,
        private readonly RuntimeContextContract $context,
    ) {
    }

    public function registerConfiguredModules(): void
    {
        if ($this->configured) {
            return;
        }

        foreach ((array) config('nexora.modules.classes', []) as $moduleClass) {
            if (is_string($moduleClass)) {
                $this->modules->register($moduleClass);
            }
        }

        $this->configured = true;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->registerConfiguredModules();

        foreach ($this->modules->manifests() as $manifest) {
            foreach ($manifest->capabilities as $capability) {
                if (! $this->capabilities->has($capability)) {
                    throw new RuntimeConfigurationException("Module [{$manifest->identifier}] requests unknown capability [{$capability}].");
                }
            }
        }

        foreach ($this->modules->bootOrder() as $identifier) {
            $class = $this->modules->classes()[$identifier];
            /** @var ModuleContract $module */
            $module = $this->container->make($class);
            $this->context->runAs(RuntimeIdentity::module($identifier), static fn () => $module->register());
        }

        foreach ($this->modules->bootOrder() as $identifier) {
            $class = $this->modules->classes()[$identifier];
            /** @var ModuleContract $module */
            $module = $this->container->make($class);
            $this->context->runAs(RuntimeIdentity::module($identifier), static fn () => $module->boot());
        }

        $this->booted = true;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public function snapshot(): array
    {
        $modules = [];
        foreach ($this->modules->manifests() as $identifier => $manifest) {
            $modules[$identifier] = [
                ...$manifest->toArray(),
                'class' => $this->modules->classes()[$identifier],
            ];
        }

        return [
            'modules' => $modules,
            'capabilities' => array_map(static fn (CapabilityDefinition $capability): array => $capability->toArray(), $this->capabilities->all()),
        ];
    }
}
