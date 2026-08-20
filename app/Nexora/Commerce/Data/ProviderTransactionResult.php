<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Data;

final readonly class ProviderTransactionResult
{
    /** @param array<string,mixed> $metadata */
    public function __construct(
        public bool $successful,
        public string $status,
        public ?string $providerReference = null,
        public ?string $message = null,
        public array $metadata = [],
    ) {}
}
