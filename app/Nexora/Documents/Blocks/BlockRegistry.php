<?php

declare(strict_types=1);

namespace App\Nexora\Documents\Blocks;

use InvalidArgumentException;

final class BlockRegistry
{
    /** @var array<string,BlockDefinition> */
    private array $blocks = [];

    public function register(BlockDefinition $definition): void
    {
        if (preg_match('/^[a-z][a-z0-9._-]{1,63}$/', $definition->type) !== 1) {
            throw new InvalidArgumentException("Invalid document block type [{$definition->type}].");
        }
        if (isset($this->blocks[$definition->type])) {
            throw new InvalidArgumentException("Document block type [{$definition->type}] is already registered.");
        }
        $this->blocks[$definition->type] = $definition;
    }

    public function get(string $type): BlockDefinition
    {
        return $this->blocks[$type] ?? throw new InvalidArgumentException("Unknown Nexora document block type [{$type}].");
    }

    /** @return array<string,BlockDefinition> */
    public function all(): array
    {
        ksort($this->blocks);
        return $this->blocks;
    }

    public function has(string $type): bool
    {
        return isset($this->blocks[$type]);
    }
}
