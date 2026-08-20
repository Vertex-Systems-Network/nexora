<?php

declare(strict_types=1);

namespace App\Nexora\Studio\Services;

use App\Nexora\Studio\Data\StudioElementDefinition;
use InvalidArgumentException;

final class StudioElementRegistry
{
    /** @var array<string,StudioElementDefinition> */
    private array $elements = [];

    public function register(StudioElementDefinition $definition): void
    {
        if ($definition->type === '' || preg_match('/^[a-z][a-z0-9-]{1,63}$/', $definition->type) !== 1) {
            throw new InvalidArgumentException('Studio element types require a stable lowercase identifier.');
        }
        if (isset($this->elements[$definition->type])) {
            throw new InvalidArgumentException("Studio element [{$definition->type}] is already registered.");
        }
        $this->elements[$definition->type] = $definition;
    }

    /** @return array<string,StudioElementDefinition> */
    public function all(): array
    {
        return $this->elements;
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->elements);
    }

    public function get(string $type): ?StudioElementDefinition
    {
        return $this->elements[$type] ?? null;
    }
}
