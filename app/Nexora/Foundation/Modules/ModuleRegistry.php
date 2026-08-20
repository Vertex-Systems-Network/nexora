<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Modules;

use App\Nexora\Foundation\Contracts\ModuleContract;
use App\Nexora\Foundation\Contracts\ModuleRegistryContract;
use App\Nexora\Foundation\Exceptions\ModuleDependencyException;
use App\Nexora\Foundation\Runtime\ModuleManifest;
use App\Nexora\Foundation\Runtime\VersionConstraintMatcher;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

final class ModuleRegistry implements ModuleRegistryContract
{
    /** @var array<string, class-string<ModuleContract>> */
    private array $classes = [];

    /** @var array<string, ModuleManifest> */
    private array $manifests = [];

    public function __construct(
        private readonly Container $container,
        private readonly VersionConstraintMatcher $versions,
    ) {
    }

    public function register(string $moduleClass): void
    {
        if (! is_a($moduleClass, ModuleContract::class, true)) {
            throw new InvalidArgumentException("Module [{$moduleClass}] must implement ".ModuleContract::class.'.');
        }

        /** @var ModuleContract $module */
        $module = $this->container->make($moduleClass);
        $manifest = $module->manifest();

        if (isset($this->classes[$manifest->identifier]) && $this->classes[$manifest->identifier] !== $moduleClass) {
            throw new InvalidArgumentException("Duplicate Nexora module identifier [{$manifest->identifier}].");
        }

        $this->classes[$manifest->identifier] = $moduleClass;
        $this->manifests[$manifest->identifier] = $manifest;
    }

    public function classes(): array
    {
        return $this->classes;
    }

    public function manifests(): array
    {
        return $this->manifests;
    }

    public function manifest(string $identifier): ?ModuleManifest
    {
        return $this->manifests[$identifier] ?? null;
    }

    public function bootOrder(): array
    {
        $temporary = [];
        $permanent = [];
        $result = [];

        $visit = function (string $identifier) use (&$visit, &$temporary, &$permanent, &$result): void {
            if (isset($permanent[$identifier])) {
                return;
            }

            if (isset($temporary[$identifier])) {
                throw new ModuleDependencyException("Circular Nexora module dependency detected at [{$identifier}].");
            }

            $manifest = $this->manifest($identifier);
            if ($manifest === null) {
                throw new ModuleDependencyException("Unknown Nexora module [{$identifier}].");
            }

            $temporary[$identifier] = true;
            foreach ($manifest->dependencies as $dependency) {
                $dependencyManifest = $this->manifest($dependency->identifier);
                if ($dependencyManifest === null) {
                    if ($dependency->optional) {
                        continue;
                    }
                    throw new ModuleDependencyException("Module [{$identifier}] requires missing module [{$dependency->identifier}].");
                }
                if (! $this->versions->matches($dependencyManifest->version, $dependency->constraint)) {
                    if ($dependency->optional) {
                        continue;
                    }
                    throw new ModuleDependencyException("Module [{$identifier}] requires [{$dependency->identifier}] {$dependency->constraint}, installed {$dependencyManifest->version}.");
                }
                $visit($dependency->identifier);
            }

            unset($temporary[$identifier]);
            $permanent[$identifier] = true;
            $result[] = $identifier;
        };

        $ids = array_keys($this->manifests);
        usort($ids, fn (string $a, string $b): int => ($this->manifests[$a]->loadOrder <=> $this->manifests[$b]->loadOrder) ?: ($a <=> $b));
        foreach ($ids as $identifier) {
            $visit($identifier);
        }

        return array_values(array_unique($result));
    }
}
