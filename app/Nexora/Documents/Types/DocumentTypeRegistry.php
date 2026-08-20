<?php

declare(strict_types=1);

namespace App\Nexora\Documents\Types;

use InvalidArgumentException;

final class DocumentTypeRegistry
{
    /** @var array<string,DocumentTypeDefinition> */
    private array $types = [];

    public function register(DocumentTypeDefinition $definition): void
    {
        if (isset($this->types[$definition->key])) {
            throw new InvalidArgumentException("Document type [{$definition->key}] is already registered.");
        }
        $this->types[$definition->key] = $definition;
    }

    public function get(string $key): DocumentTypeDefinition
    {
        return $this->types[$key] ?? throw new InvalidArgumentException("Unknown Nexora document type [{$key}].");
    }

    /** @return array<string,DocumentTypeDefinition> */
    public function all(): array
    {
        ksort($this->types);
        return $this->types;
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->types);
    }
}
