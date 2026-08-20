<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Data;

final readonly class PaymentRequest
{
    /** @param array<string,mixed> $context */
    public function __construct(
        public string $orderId,
        public ?string $invoiceId,
        public string $customerId,
        public string $currency,
        public int $amountMinor,
        public string $idempotencyKey,
        public array $context = [],
    ) {}
}
