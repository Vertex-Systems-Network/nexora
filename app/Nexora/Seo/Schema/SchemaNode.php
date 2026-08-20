<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Schema;

final readonly class SchemaNode
{
    /** @param array<string,mixed> $properties */
    public function __construct(
        public string $id,
        public string $type,
        public array $properties = [],
        public string $source = 'core',
        public int $priority = 100,
    ) {
        if ($this->id === '' || $this->type === '') {
            throw new \InvalidArgumentException('Schema nodes require non-empty id and type values.');
        }
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return ['@id' => $this->id, '@type' => $this->type, ...$this->properties];
    }
}
