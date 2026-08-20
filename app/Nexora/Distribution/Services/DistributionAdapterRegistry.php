<?php

declare(strict_types=1);

namespace App\Nexora\Distribution\Services;

use App\Nexora\Distribution\Contracts\DistributionAdapterContract;
use InvalidArgumentException;

final class DistributionAdapterRegistry
{
    /** @var array<string,DistributionAdapterContract> */
    private array $adapters = [];

    public function register(DistributionAdapterContract $adapter): void
    {
        if (isset($this->adapters[$adapter->key()])) throw new InvalidArgumentException("Distribution adapter [{$adapter->key()}] is already registered.");
        $this->adapters[$adapter->key()] = $adapter;
    }

    /** @return array<string,DistributionAdapterContract> */
    public function all(): array { return $this->adapters; }
    public function get(string $key): ?DistributionAdapterContract { return $this->adapters[$key] ?? null; }
}
