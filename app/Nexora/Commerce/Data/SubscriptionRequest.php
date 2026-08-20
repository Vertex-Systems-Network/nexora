<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Data;

final readonly class SubscriptionRequest
{
    /** @param array<string,mixed> $context */
    public function __construct(
        public string $customerId,
        public string $productId,
        public string $priceId,
        public string $currency,
        public int $amountMinor,
        public string $billingInterval,
        public int $intervalCount,
        public string $idempotencyKey,
        public array $context = [],
    ) {}
}
