<?php

declare(strict_types=1);

namespace App\Nexora\Foundation\Runtime;

use InvalidArgumentException;

final readonly class ModuleManifest
{
    /**
     * @param array<int, string> $capabilities
     * @param array<int, ModuleDependency> $dependencies
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $identifier,
        public string $name,
        public string $version,
        public string $description = '',
        public bool $core = false,
        public int $loadOrder = 100,
        public array $capabilities = [],
        public array $dependencies = [],
        public array $metadata = [],
    ) {
        if (! preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)+$/', $identifier)) {
            throw new InvalidArgumentException("Invalid Nexora module identifier [{$identifier}].");
        }

        if ($name === '' || $version === '') {
            throw new InvalidArgumentException('Nexora module name and version are required.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'core' => $this->core,
            'load_order' => $this->loadOrder,
            'capabilities' => array_values(array_unique($this->capabilities)),
            'dependencies' => array_map(static fn (ModuleDependency $dependency): array => $dependency->toArray(), $this->dependencies),
            'metadata' => $this->metadata,
        ];
    }

    public function hash(): string
    {
        return hash('sha256', json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
}
