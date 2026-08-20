<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Models\CommerceInvoice;
use App\Models\CommerceOrder;
use App\Models\CommercePaymentTransaction;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

final readonly class PaymentService
{
    public function __construct(
        private CurrencyManager $currencies,
        private BillingEventRecorder $events,
        private AutomationEventBusContract $automation,
        private ConcurrencyGuard $concurrency,
    ) {}

    /** @param array<string,mixed> $metadata */
    public function record(string $providerKey, string $type, string $status, int $amountMinor, string $currency, ?CommerceOrder $order = null, ?CommerceInvoice $invoice = null, ?string $providerReference = null, ?string $idempotencyKey = null, array $metadata = []): CommercePaymentTransaction
    {
        if ($amountMinor < 0) throw new InvalidArgumentException('Payment amount cannot be negative.');
        $currency = $this->currencies->ensureEnabled($currency)->code;
        $idempotencyKey = $this->idempotencyKey($idempotencyKey);

        try {
            return $this->concurrency->transaction(function () use ($providerKey, $type, $status, $amountMinor, $currency, $order, $invoice, $providerReference, $idempotencyKey, $metadata): CommercePaymentTransaction {
                if ($idempotencyKey !== null) {
                    $existing = CommercePaymentTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
                    if ($existing) return $existing;
                }

                // Lock mutable payment aggregates in a stable order so concurrent provider
                // callbacks cannot overwrite each other's paid totals.
                $lockedOrder = $order ? CommerceOrder::query()->lockForUpdate()->findOrFail($order->id) : null;
                $lockedInvoice = $invoice ? CommerceInvoice::query()->lockForUpdate()->findOrFail($invoice->id) : null;

                $tx = CommercePaymentTransaction::query()->create([
                    'order_id' => $lockedOrder?->id,
                    'invoice_id' => $lockedInvoice?->id,
                    'customer_id' => $lockedOrder?->customer_id ?? $lockedInvoice?->customer_id,
                    'provider_key' => $providerKey,
                    'provider_reference' => $providerReference,
                    'type' => $type,
                    'status' => $status,
                    'currency' => $currency,
                    'amount_minor' => $amountMinor,
                    'idempotency_key' => $idempotencyKey,
                    'metadata' => $metadata,
                    'processed_at' => now(),
                ]);

                if (in_array($status, ['succeeded', 'paid', 'captured'], true) && in_array($type, ['payment', 'capture'], true)) {
                    if ($lockedOrder) {
                        $paid = min((int) $lockedOrder->total_minor, (int) $lockedOrder->paid_minor + $amountMinor);
                        $lockedOrder->update([
                            'paid_minor' => $paid,
                            'status' => $paid >= (int) $lockedOrder->total_minor ? 'paid' : $lockedOrder->status,
                            'completed_at' => $paid >= (int) $lockedOrder->total_minor ? now() : $lockedOrder->completed_at,
                        ]);
                    }
                    if ($lockedInvoice) {
                        $paid = min((int) $lockedInvoice->total_minor, (int) $lockedInvoice->amount_paid_minor + $amountMinor);
                        $lockedInvoice->update([
                            'amount_paid_minor' => $paid,
                            'amount_due_minor' => max(0, (int) $lockedInvoice->total_minor - $paid),
                            'status' => $paid >= (int) $lockedInvoice->total_minor ? 'paid' : $lockedInvoice->status,
                            'paid_at' => $paid >= (int) $lockedInvoice->total_minor ? now() : $lockedInvoice->paid_at,
                        ]);
                    }
                    $this->automation->emit('commerce.payment.succeeded', [
                        'payment' => [
                            'id' => $tx->id,
                            'provider' => $providerKey,
                            'amount_minor' => $amountMinor,
                            'currency' => $currency,
                            'order_id' => $lockedOrder?->id,
                            'invoice_id' => $lockedInvoice?->id,
                        ],
                    ], idempotencyKey: 'commerce-payment-succeeded:'.$tx->id);
                }

                $this->events->record('commerce.payment.'.$status, 'payment', $tx->id, $providerKey, null, payload: [
                    'type' => $type,
                    'amount_minor' => $amountMinor,
                    'currency' => $currency,
                    'provider_reference' => $providerReference,
                ]);

                return $tx;
            });
        } catch (QueryException $exception) {
            // Check-then-insert alone is not concurrency-safe. The DB unique constraint is
            // the final arbiter and duplicate callbacks converge on the committed row.
            if ($idempotencyKey !== null && $this->concurrency->isUniqueViolation($exception)) {
                $existing = CommercePaymentTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) return $existing;
            }
            throw $exception;
        }
    }

    private function idempotencyKey(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : mb_substr($value, 0, 180);
    }
}
