<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Capabilities;

use App\Nexora\Foundation\Contracts\CapabilityRegistryContract;
use App\Nexora\Foundation\Runtime\CapabilityDefinition;
use InvalidArgumentException;

final class CapabilityRegistry implements CapabilityRegistryContract
{
    /** @var array<string, CapabilityDefinition> */
    private array $capabilities = [];

    public function register(CapabilityDefinition $capability): void
    {
        $existing = $this->capabilities[$capability->slug] ?? null;
        if ($existing !== null && $existing->toArray() !== $capability->toArray()) {
            throw new InvalidArgumentException("Conflicting Nexora capability definition [{$capability->slug}].");
        }

        $this->capabilities[$capability->slug] = $capability;
        ksort($this->capabilities);
    }

    public function has(string $slug): bool
    {
        return isset($this->capabilities[$slug]);
    }

    public function get(string $slug): ?CapabilityDefinition
    {
        return $this->capabilities[$slug] ?? null;
    }

    public function all(): array
    {
        return $this->capabilities;
    }
}
