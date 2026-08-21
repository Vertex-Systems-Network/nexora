<?php

declare(strict_types=1);

namespace App\Nexora\Ai\Data;

final readonly class AiTextGenerationRequest
{
    /**
     * @param array<string,mixed> $credentials
     * @param array<string,mixed> $settings
     */
    public function __construct(
        public string $model,
        public string $prompt,
        public int $maxOutputTokens,
        public array $credentials = [],
        public array $settings = [],
    ) {}
}
