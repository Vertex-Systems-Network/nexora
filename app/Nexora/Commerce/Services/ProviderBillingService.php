<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Models\CommerceInvoice;
use App\Models\CommercePaymentProviderConfig;
use App\Models\CommercePaymentTransaction;
use App\Models\CommerceRefund;
use App\Nexora\Commerce\Contracts\PaymentProviderContract;
use App\Nexora\Commerce\Data\PaymentRequest;
use App\Nexora\Commerce\Data\ProviderTransactionResult;
use App\Nexora\Commerce\Data\RefundRequest;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use InvalidArgumentException;

final readonly class ProviderBillingService
{
    public function __construct(
        private PaymentProviderRegistry $providers,
        private PaymentService $payments,
        private RefundService $refunds,
        private ConcurrencyGuard $concurrency,
    ) {}

    public function collectInvoice(CommerceInvoice $invoice, string $providerKey, string $idempotencyKey): CommercePaymentTransaction
    {
        $idempotencyKey = $this->requireIdempotencyKey($idempotencyKey);

        return $this->concurrency->mutex(
            'commerce.billing.invoice.'.hash('sha256', $invoice->id.'|'.$idempotencyKey),
            function () use ($invoice, $providerKey, $idempotencyKey): CommercePaymentTransaction {
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
                    context: [
                        'order_number' => $order->number,
                        'invoice_number' => $lockedInvoice->number,
                    ],
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
                    metadata: [
                        'provider_successful' => $result->successful,
                        'provider_message' => $result->message,
                        'provider_metadata' => $result->metadata,
                    ],
                );
            },
        );
    }

    public function refundPayment(CommercePaymentTransaction $payment, int $amountMinor, ?string $reason, ?int $actorId, string $idempotencyKey): CommerceRefund
    {
        if ($amountMinor <= 0) {
            throw new InvalidArgumentException('Refund amount must be greater than zero.');
        }
        $idempotencyKey = $this->requireIdempotencyKey($idempotencyKey);

        return $this->concurrency->mutex(
            'commerce.billing.refund.'.hash('sha256', $payment->id.'|'.$idempotencyKey),
            function () use ($payment, $amountMinor, $reason, $actorId, $idempotencyKey): CommerceRefund {
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
                    context: [
                        'payment_id' => $lockedPayment->id,
                        'order_id' => $lockedPayment->order_id,
                        'invoice_id' => $lockedPayment->invoice_id,
                    ],
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
                    metadata: [
                        'provider_successful' => $result->successful,
                        'provider_message' => $result->message,
                        'provider_metadata' => $result->metadata,
                    ],
                );
            },
        );
    }

    private function provider(string $providerKey, string $capability): PaymentProviderContract
    {
        $providerKey = trim($providerKey);
        $config = CommercePaymentProviderConfig::query()
            ->where('provider_key', $providerKey)
            ->where('enabled', true)
            ->first();
        if ($config === null) {
            throw new InvalidArgumentException('The selected payment provider is not enabled.');
        }

        $provider = $this->providers->get($providerKey);
        if ($provider === null) {
            throw new InvalidArgumentException('The selected payment provider extension is not currently registered.');
        }
        if (! in_array($capability, $provider->capabilities(), true)) {
            throw new InvalidArgumentException('The selected payment provider does not support this billing operation.');
        }

        $health = $provider->health((array) $config->configuration);
        $config->update([
            'last_health_checked_at' => now(),
            'last_health_status' => $health['ok'] ? 'healthy' : 'unhealthy',
            'last_health_message' => $health['message'],
        ]);
        if (! $health['ok']) {
            throw new InvalidArgumentException('The selected payment provider is unhealthy: '.$health['message']);
        }

        return $provider;
    }

    private function validatedResultStatus(ProviderTransactionResult $result, string $operation): string
    {
        $status = strtolower(trim($result->status));
        if (preg_match('/^[a-z0-9][a-z0-9._-]{0,31}$/', $status) !== 1) {
            throw new InvalidArgumentException('Payment provider returned an invalid '.$operation.' status.');
        }
        if (! $result->successful && in_array($status, ['succeeded', 'paid', 'captured', 'refunded'], true)) {
            throw new InvalidArgumentException('Payment provider returned an inconsistent '.$operation.' result.');
        }

        return $status;
    }

    private function providerReference(?string $reference): ?string
    {
        if ($reference === null) {
            return null;
        }
        $reference = trim($reference);
        if ($reference === '') {
            return null;
        }
        if (mb_strlen($reference) > 255) {
            throw new InvalidArgumentException('Payment provider reference exceeds the supported length.');
        }

        return $reference;
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
