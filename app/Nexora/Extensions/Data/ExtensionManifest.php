<?php

declare(strict_types=1);

namespace App\Nexora\Extensions\Data;

final readonly class ExtensionManifest
{
    /** @param list<string> $capabilities @param array<string,string> $dependencies */
    public function __construct(
        public string $identifier,
        public string $name,
        public string $type,
        public string $version,
        public string $description,
        public string $nexoraConstraint,
        public string $runtimeMode,
        public array $capabilities,
        public array $dependencies,
        public string $migrationPolicy,
        public bool $schemaCompatibleRollback,
        public array $raw,
    ) {}
}
