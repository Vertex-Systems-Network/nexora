<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Contracts;

use App\Nexora\Foundation\Runtime\ModuleManifest;

interface ModuleRegistryContract
{
    /** @param class-string<ModuleContract> $moduleClass */
    public function register(string $moduleClass): void;

    /** @return array<string, class-string<ModuleContract>> */
    public function classes(): array;

    /** @return array<string, ModuleManifest> */
    public function manifests(): array;

    public function manifest(string $identifier): ?ModuleManifest;

    /** @return array<int, string> */
    public function bootOrder(): array;
}
