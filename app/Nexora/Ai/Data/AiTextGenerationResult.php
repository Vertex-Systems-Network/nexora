<?php

declare(strict_types=1);

namespace App\Nexora\Ai\Data;

final readonly class AiTextGenerationResult
{
    public function __construct(
        public string $text,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?string $providerRequestId = null,
    ) {}
}
