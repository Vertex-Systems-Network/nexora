<?php

declare(strict_types=1);

namespace App\Nexora\Documents\Blocks;

final readonly class BlockDefinition
{
    /** @param array<string,mixed> $schema */
    public function __construct(
        public string $type,
        public string $name,
        public string $category,
        public array $schema = [],
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return ['type' => $this->type, 'name' => $this->name, 'category' => $this->category, 'schema' => $this->schema];
    }
}
