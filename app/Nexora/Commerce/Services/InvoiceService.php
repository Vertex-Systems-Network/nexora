<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Models\CommerceInvoice;
use App\Models\CommerceOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class InvoiceService
{
    public function __construct(private BillingEventRecorder $events) {}

    public function createFromOrder(CommerceOrder $order, ?\DateTimeInterface $dueAt = null): CommerceInvoice
    {
        return DB::transaction(function () use ($order, $dueAt): CommerceInvoice {
            $locked = CommerceOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'cancelled') {
                throw new InvalidArgumentException('Cancelled orders cannot be invoiced.');
            }

            $existing = CommerceInvoice::query()
                ->where('order_id', $locked->id)
                ->whereNotIn('status', ['void'])
                ->first();
            if ($existing) {
                return $existing;
            }

            $invoice = CommerceInvoice::query()->create([
                'number' => $this->nextNumber(),
                'order_id' => $locked->id,
                'customer_id' => $locked->customer_id,
                'status' => 'open',
                'currency' => $locked->currency,
                'subtotal_minor' => $locked->subtotal_minor,
                'discount_minor' => $locked->discount_minor,
                'tax_minor' => $locked->tax_minor,
                'total_minor' => $locked->total_minor,
                'amount_due_minor' => max(0, (int) $locked->total_minor - (int) $locked->paid_minor),
                'amount_paid_minor' => $locked->paid_minor,
                'issued_at' => now(),
                'due_at' => $dueAt,
            ]);
            $this->events->record('commerce.invoice.created', 'invoice', $invoice->id, payload: [
                'number' => $invoice->number,
                'order_id' => $locked->id,
                'amount_due_minor' => $invoice->amount_due_minor,
            ]);

            return $invoice;
        });
    }

    private function nextNumber(): string
    {
        return 'INV-'.now()->format('Ymd').'-'.strtoupper(substr((string) Str::ulid(), -10));
    }
}
