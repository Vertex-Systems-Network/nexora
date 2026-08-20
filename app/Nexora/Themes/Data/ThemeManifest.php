<?php

declare(strict_types=1);

namespace App\Nexora\Themes\Data;

final readonly class ThemeManifest
{
    /** @param array<string,string> $templates @param array<string,array<string,mixed>> $designTokens */
    public function __construct(
        public string $identifier,
        public string $name,
        public string $version,
        public string $nexoraConstraint,
        public string $description,
        public string $engine,
        public array $templates,
        public array $designTokens,
        public ?string $stylesheet,
        public ?string $screenshot,
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'name' => $this->name,
            'version' => $this->version,
            'requires' => ['nexora' => $this->nexoraConstraint],
            'description' => $this->description,
            'engine' => $this->engine,
            'templates' => $this->templates,
            'design_tokens' => $this->designTokens,
            'stylesheet' => $this->stylesheet,
            'screenshot' => $this->screenshot,
        ];
    }
}
