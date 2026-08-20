<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Data;

final readonly class RefundRequest
{
    /** @param array<string,mixed> $context */
    public function __construct(
        public string $paymentReference,
        public string $currency,
        public int $amountMinor,
        public string $idempotencyKey,
        public ?string $reason = null,
        public array $context = [],
    ) {}
}
