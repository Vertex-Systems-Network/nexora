<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Contracts;

use App\Nexora\Commerce\Data\PaymentRequest;
use App\Nexora\Commerce\Data\ProviderTransactionResult;
use App\Nexora\Commerce\Data\RefundRequest;
use App\Nexora\Commerce\Data\SubscriptionRequest;

interface PaymentProviderContract
{
    public const CAPABILITY_PAYMENTS = 'payments';
    public const CAPABILITY_REFUNDS = 'refunds';
    public const CAPABILITY_SUBSCRIPTIONS = 'subscriptions';

    public function key(): string;
    public function label(): string;
    /** @return list<string> */
    public function capabilities(): array;
    /** @return array{ok:bool,message:string,details?:array<string,mixed>} */
    public function health(array $configuration = []): array;
    public function createPayment(PaymentRequest $request): ProviderTransactionResult;
    public function refund(RefundRequest $request): ProviderTransactionResult;
    public function createSubscription(SubscriptionRequest $request): ProviderTransactionResult;
    public function cancelSubscription(string $providerReference, array $context = []): ProviderTransactionResult;
}
