<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Models\CommercePaymentTransaction;
use App\Models\CommerceRefund;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

final readonly class RefundService
{
    public function __construct(
        private BillingEventRecorder $events,
        private AutomationEventBusContract $automation,
        private ConcurrencyGuard $concurrency,
    ) {}

    public function record(CommercePaymentTransaction $payment, int $amountMinor, string $status, ?string $reason, ?int $actorId, ?string $providerReference = null, ?string $idempotencyKey = null): CommerceRefund
    {
        if ($amountMinor <= 0) throw new InvalidArgumentException('Refund amount must be greater than zero.');
        $idempotencyKey = $this->idempotencyKey($idempotencyKey);

        try {
            return $this->concurrency->transaction(function () use ($payment, $amountMinor, $status, $reason, $actorId, $providerReference, $idempotencyKey): CommerceRefund {
                if ($idempotencyKey !== null) {
                    $existing = CommerceRefund::query()->where('idempotency_key', $idempotencyKey)->first();
                    if ($existing) return $existing;
                }

                $lockedPayment = CommercePaymentTransaction::query()->lockForUpdate()->findOrFail($payment->id);
                $alreadyRefunded = (int) CommerceRefund::query()
                    ->where('payment_transaction_id', $lockedPayment->id)
                    ->whereIn('status', ['succeeded', 'refunded'])
                    ->sum('amount_minor');

                if (($alreadyRefunded + $amountMinor) > (int) $lockedPayment->amount_minor) {
                    throw new InvalidArgumentException('Cumulative refunds cannot exceed the original payment amount.');
                }

                $refund = CommerceRefund::query()->create([
                    'order_id' => $lockedPayment->order_id,
                    'payment_transaction_id' => $lockedPayment->id,
                    'provider_key' => $lockedPayment->provider_key,
                    'provider_reference' => $providerReference,
                    'status' => $status,
                    'currency' => $lockedPayment->currency,
                    'amount_minor' => $amountMinor,
                    'reason' => $reason,
                    'idempotency_key' => $idempotencyKey,
                    'created_by' => $actorId,
                    'processed_at' => now(),
                ]);

                if (in_array($status, ['succeeded', 'refunded'], true) && $lockedPayment->order_id) {
                    $order = $lockedPayment->order()->lockForUpdate()->first();
                    if ($order) {
                        $newRefunded = min((int) $order->paid_minor, (int) $order->refunded_minor + $amountMinor);
                        $order->update([
                            'refunded_minor' => $newRefunded,
                            'status' => $newRefunded >= (int) $order->paid_minor ? 'refunded' : $order->status,
                        ]);
                    }
                }

                $this->events->record('commerce.refund.'.$status, 'refund', $refund->id, $lockedPayment->provider_key, null, payload: [
                    'payment_id' => $lockedPayment->id,
                    'amount_minor' => $amountMinor,
                    'currency' => $lockedPayment->currency,
                    'provider_reference' => $providerReference,
                ]);
                $this->automation->emit('commerce.refund.created', [
                    'refund' => [
                        'id' => $refund->id,
                        'payment_id' => $lockedPayment->id,
                        'amount_minor' => $amountMinor,
                        'currency' => $lockedPayment->currency,
                        'status' => $status,
                    ],
                ], idempotencyKey: 'commerce-refund-created:'.$refund->id);

                return $refund;
            });
        } catch (QueryException $exception) {
            if ($idempotencyKey !== null && $this->concurrency->isUniqueViolation($exception)) {
                $existing = CommerceRefund::query()->where('idempotency_key', $idempotencyKey)->first();
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
