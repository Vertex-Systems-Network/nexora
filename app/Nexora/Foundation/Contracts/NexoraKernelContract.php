<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Contracts;

interface NexoraKernelContract
{
    public function registerConfiguredModules(): void;

    public function boot(): void;

    public function isBooted(): bool;

    /** @return array<string, array<string, mixed>> */
    public function snapshot(): array;
}
