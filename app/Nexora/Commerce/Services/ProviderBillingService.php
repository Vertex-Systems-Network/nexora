<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Models\CommerceCustomer;
use App\Models\CommerceInvoice;
use App\Models\CommercePaymentProviderConfig;
use App\Models\CommercePaymentTransaction;
use App\Models\CommercePrice;
use App\Models\CommerceRefund;
use App\Models\CommerceSubscription;
use App\Nexora\Commerce\Contracts\PaymentProviderContract;
use App\Nexora\Commerce\Data\PaymentRequest;
use App\Nexora\Commerce\Data\ProviderTransactionResult;
use App\Nexora\Commerce\Data\RefundRequest;
use App\Nexora\Commerce\Data\SubscriptionRequest;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use InvalidArgumentException;

final readonly class ProviderBillingService
{
    public function __construct(
        private PaymentProviderRegistry $providers,
        private PaymentService $payments,
        private RefundService $refunds,
        private SubscriptionService $subscriptions,
        private BillingEventRecorder $events,
        private ConcurrencyGuard $concurrency,
    ) {}

    public function collectInvoice(CommerceInvoice $invoice, string $providerKey, string $idempotencyKey): CommercePaymentTransaction
    {
        $idempotencyKey = $this->requireIdempotencyKey($idempotencyKey);

        return $this->concurrency->mutex(
            'commerce.billing.invoice.'.hash('sha256', $invoice->id.'|'.$idempotencyKey),
            function () use ($invoice, $providerKey, $idempotencyKey): CommercePaymentTransaction {
                $existing = CommercePaymentTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing !== null) {
                    if ($existing->invoice_id !== $invoice->id || $existing->provider_key !== trim($providerKey)) {
                        throw new InvalidArgumentException('Billing idempotency key is already bound to a different payment operation.');
                    }
                    return $existing;
                }

                $lockedInvoice = CommerceInvoice::query()->with('order')->findOrFail($invoice->id);
                if (in_array($lockedInvoice->status, ['void', 'paid'], true) || (int) $lockedInvoice->amount_due_minor <= 0) {
                    throw new InvalidArgumentException('This invoice has no collectible balance.');
                }

                $order = $lockedInvoice->order;
                if ($order === null || $order->status === 'cancelled') {
                    throw new InvalidArgumentException('Payments require an active order linked to the invoice.');
                }

                $provider = $this->provider($providerKey, PaymentProviderContract::CAPABILITY_PAYMENTS);
                $amountMinor = (int) $lockedInvoice->amount_due_minor;
                $result = $provider->createPayment(new PaymentRequest(
                    orderId: $order->id,
                    invoiceId: $lockedInvoice->id,
                    customerId: (string) ($lockedInvoice->customer_id ?? $order->customer_id ?? ''),
                    currency: $lockedInvoice->currency,
                    amountMinor: $amountMinor,
                    idempotencyKey: $idempotencyKey,
                    context: ['order_number' => $order->number, 'invoice_number' => $lockedInvoice->number],
                ));
                $status = $this->validatedResultStatus($result, 'payment');

                return $this->payments->record(
                    providerKey: $provider->key(),
                    type: 'payment',
                    status: $status,
                    amountMinor: $amountMinor,
                    currency: $lockedInvoice->currency,
                    order: $order,
                    invoice: $lockedInvoice,
                    providerReference: $this->providerReference($result->providerReference),
                    idempotencyKey: $idempotencyKey,
                    metadata: $this->providerMetadata($result),
                );
            },
        );
    }

    public function refundPayment(CommercePaymentTransaction $payment, int $amountMinor, ?string $reason, ?int $actorId, string $idempotencyKey): CommerceRefund
    {
        if ($amountMinor <= 0) throw new InvalidArgumentException('Refund amount must be greater than zero.');
        $idempotencyKey = $this->requireIdempotencyKey($idempotencyKey);

        return $this->concurrency->mutex(
            'commerce.billing.refund.'.hash('sha256', $payment->id.'|'.$idempotencyKey),
            function () use ($payment, $amountMinor, $reason, $actorId, $idempotencyKey): CommerceRefund {
                $existing = CommerceRefund::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing !== null) {
                    if ($existing->payment_transaction_id !== $payment->id || (int) $existing->amount_minor !== $amountMinor) {
                        throw new InvalidArgumentException('Billing idempotency key is already bound to a different refund operation.');
                    }
                    return $existing;
                }

                $lockedPayment = CommercePaymentTransaction::query()->findOrFail($payment->id);
                if (! in_array($lockedPayment->type, ['payment', 'capture'], true)
                    || ! in_array($lockedPayment->status, ['succeeded', 'paid', 'captured'], true)) {
                    throw new InvalidArgumentException('Only successful payment transactions can be refunded.');
                }
                if ($lockedPayment->provider_reference === null || trim($lockedPayment->provider_reference) === '') {
                    throw new InvalidArgumentException('This payment has no provider reference and cannot be refunded through its provider.');
                }

                $alreadyRefunded = (int) CommerceRefund::query()
                    ->where('payment_transaction_id', $lockedPayment->id)
                    ->whereIn('status', ['succeeded', 'refunded'])
                    ->sum('amount_minor');
                $remaining = max(0, (int) $lockedPayment->amount_minor - $alreadyRefunded);
                if ($amountMinor > $remaining) {
                    throw new InvalidArgumentException('Refund amount exceeds the remaining refundable payment balance.');
                }

                $provider = $this->provider($lockedPayment->provider_key, PaymentProviderContract::CAPABILITY_REFUNDS);
                $result = $provider->refund(new RefundRequest(
                    paymentReference: $lockedPayment->provider_reference,
                    currency: $lockedPayment->currency,
                    amountMinor: $amountMinor,
                    idempotencyKey: $idempotencyKey,
                    reason: $reason,
                    context: ['payment_id' => $lockedPayment->id, 'order_id' => $lockedPayment->order_id, 'invoice_id' => $lockedPayment->invoice_id],
                ));
                $status = $this->validatedResultStatus($result, 'refund');

                return $this->refunds->record(
                    payment: $lockedPayment,
                    amountMinor: $amountMinor,
                    status: $status,
                    reason: $reason,
                    actorId: $actorId,
                    providerReference: $this->providerReference($result->providerReference),
                    idempotencyKey: $idempotencyKey,
                    metadata: $this->providerMetadata($result),
                );
            },
        );
    }

    public function createSubscription(CommerceCustomer $customer, CommercePrice $price, string $providerKey, string $idempotencyKey): CommerceSubscription
    {
        $idempotencyKey = $this->requireIdempotencyKey($idempotencyKey);
        $providerKey = trim($providerKey);

        return $this->concurrency->mutex(
            'commerce.billing.subscription.'.hash('sha256', $customer->id.'|'.$price->id.'|'.$idempotencyKey),
            function () use ($customer, $price, $providerKey, $idempotencyKey): CommerceSubscription {
                $existing = CommerceSubscription::query()
                    ->where('metadata->provider_idempotency_key', $idempotencyKey)
                    ->first();
                if ($existing !== null) {
                    if ($existing->customer_id !== $customer->id || $existing->price_id !== $price->id || $existing->provider_key !== $providerKey) {
                        throw new InvalidArgumentException('Billing idempotency key is already bound to a different subscription operation.');
                    }
                    return $existing;
                }

                $now = now();
                $activePrice = CommercePrice::query()
                    ->with('product')
                    ->whereKey($price->id)
                    ->where('active', true)
                    ->whereNotNull('billing_interval')
                    ->whereHas('product', fn ($query) => $query->where('status', 'active'))
                    ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                    ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
                    ->first();
                if ($activePrice === null || $activePrice->product === null) {
                    throw new InvalidArgumentException('Subscriptions require an active recurring product price.');
                }
                $activeCustomer = CommerceCustomer::query()->findOrFail($customer->id);
                $provider = $this->provider($providerKey, PaymentProviderContract::CAPABILITY_SUBSCRIPTIONS);
                $result = $provider->createSubscription(new SubscriptionRequest(
                    customerId: $activeCustomer->id,
                    productId: $activePrice->product_id,
                    priceId: $activePrice->id,
                    currency: $activePrice->currency,
                    amountMinor: (int) $activePrice->amount_minor,
                    billingInterval: (string) $activePrice->billing_interval,
                    intervalCount: (int) $activePrice->interval_count,
                    idempotencyKey: $idempotencyKey,
                    context: ['customer_email' => $activeCustomer->email, 'product_name' => $activePrice->product->name],
                ));
                $status = $this->validatedResultStatus($result, 'subscription');

                return $this->subscriptions->record(
                    customer: $activeCustomer,
                    price: $activePrice,
                    providerKey: $provider->key(),
                    status: $status,
                    providerReference: $this->providerReference($result->providerReference),
                    metadata: $this->providerMetadata($result) + ['provider_idempotency_key' => $idempotencyKey],
                );
            },
        );
    }

    public function cancelSubscription(CommerceSubscription $subscription, string $idempotencyKey): CommerceSubscription
    {
        $idempotencyKey = $this->requireIdempotencyKey($idempotencyKey);

        return $this->concurrency->mutex(
            'commerce.billing.subscription.cancel.'.hash('sha256', $subscription->id.'|'.$idempotencyKey),
            function () use ($subscription, $idempotencyKey): CommerceSubscription {
                $locked = CommerceSubscription::query()->with(['customer', 'price'])->findOrFail($subscription->id);
                $metadata = (array) $locked->metadata;
                if (($metadata['last_cancel_idempotency_key'] ?? null) === $idempotencyKey) return $locked;
                if (in_array($locked->status, ['cancelled', 'canceled'], true)) return $locked;
                if ($locked->provider_reference === null || trim($locked->provider_reference) === '') {
                    throw new InvalidArgumentException('This subscription has no provider reference and cannot be cancelled through its provider.');
                }
                if ($locked->customer === null || $locked->price === null) {
                    throw new InvalidArgumentException('Subscription customer or price is no longer available.');
                }

                $provider = $this->provider($locked->provider_key, PaymentProviderContract::CAPABILITY_SUBSCRIPTIONS);
                $result = $provider->cancelSubscription($locked->provider_reference, [
                    'subscription_id' => $locked->id,
                    'idempotency_key' => $idempotencyKey,
                ]);
                $status = $this->validatedResultStatus($result, 'subscription cancellation');
                $metadata['last_cancel_idempotency_key'] = $idempotencyKey;
                $metadata['last_cancel_provider_result'] = $this->providerMetadata($result);

                if (! $result->successful) {
                    $locked->update(['metadata' => $metadata]);
                    $this->events->record('commerce.subscription.cancel_failed', 'subscription', $locked->id, $locked->provider_key, null, payload: [
                        'provider_reference' => $locked->provider_reference,
                        'status' => $status,
                        'message' => $result->message,
                    ]);
                    return $locked->refresh();
                }

                return $this->subscriptions->record(
                    customer: $locked->customer,
                    price: $locked->price,
                    providerKey: $provider->key(),
                    status: $status,
                    providerReference: $locked->provider_reference,
                    periodStart: $locked->current_period_start,
                    periodEnd: $locked->current_period_end,
                    cancelAtPeriodEnd: false,
                    metadata: $metadata,
                );
            },
        );
    }

    private function provider(string $providerKey, string $capability): PaymentProviderContract
    {
        $providerKey = trim($providerKey);
        $config = CommercePaymentProviderConfig::query()->where('provider_key', $providerKey)->where('enabled', true)->first();
        if ($config === null) throw new InvalidArgumentException('The selected payment provider is not enabled.');

        $provider = $this->providers->get($providerKey);
        if ($provider === null) throw new InvalidArgumentException('The selected payment provider extension is not currently registered.');
        if (! in_array($capability, $provider->capabilities(), true)) {
            throw new InvalidArgumentException('The selected payment provider does not support this billing operation.');
        }

        $health = $provider->health((array) $config->configuration);
        $config->update([
            'last_health_checked_at' => now(),
            'last_health_status' => $health['ok'] ? 'healthy' : 'unhealthy',
            'last_health_message' => $health['message'],
        ]);
        if (! $health['ok']) throw new InvalidArgumentException('The selected payment provider is unhealthy: '.$health['message']);

        return $provider;
    }

    private function validatedResultStatus(ProviderTransactionResult $result, string $operation): string
    {
        $status = strtolower(trim($result->status));
        if (preg_match('/^[a-z0-9][a-z0-9._-]{0,31}$/', $status) !== 1) {
            throw new InvalidArgumentException('Payment provider returned an invalid '.$operation.' status.');
        }
        if (! $result->successful && in_array($status, ['succeeded', 'paid', 'captured', 'refunded', 'active', 'cancelled', 'canceled'], true)) {
            throw new InvalidArgumentException('Payment provider returned an inconsistent '.$operation.' result.');
        }
        return $status;
    }

    private function providerReference(?string $reference): ?string
    {
        if ($reference === null) return null;
        $reference = trim($reference);
        if ($reference === '') return null;
        if (mb_strlen($reference) > 255) throw new InvalidArgumentException('Payment provider reference exceeds the supported length.');
        return $reference;
    }

    /** @return array<string,mixed> */
    private function providerMetadata(ProviderTransactionResult $result): array
    {
        return [
            'provider_successful' => $result->successful,
            'provider_message' => $result->message,
            'provider_metadata' => $result->metadata,
        ];
    }

    private function requireIdempotencyKey(string $key): string
    {
        $key = trim($key);
        if ($key === '' || mb_strlen($key) > 180) {
            throw new InvalidArgumentException('Billing operations require an idempotency key of at most 180 characters.');
        }
        return $key;
    }
}
