<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Contracts;

use App\Nexora\Foundation\Runtime\CapabilityDefinition;

interface CapabilityRegistryContract
{
    public function register(CapabilityDefinition $capability): void;

    public function has(string $slug): bool;

    public function get(string $slug): ?CapabilityDefinition;

    /** @return array<string, CapabilityDefinition> */
    public function all(): array;
}
