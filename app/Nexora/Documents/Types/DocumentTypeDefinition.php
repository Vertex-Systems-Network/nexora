<?php

declare(strict_types=1);

namespace App\Nexora\Documents\Types;

use InvalidArgumentException;

final readonly class DocumentTypeDefinition
{
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public string $icon = 'file-text',
        public int $schemaVersion = 1,
    ) {
        if (preg_match('/^[a-z][a-z0-9._-]{1,63}$/', $key) !== 1) {
            throw new InvalidArgumentException("Invalid document type key [{$key}].");
        }
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'schema_version' => $this->schemaVersion,
        ];
    }
}
