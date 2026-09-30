<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Models\CommerceCustomer;
use App\Models\CommerceOrder;
use App\Models\CommerceOrderItem;
use App\Models\CommercePrice;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use OverflowException;

final readonly class CommerceOrderService
{
    public function __construct(
        private CurrencyManager $currencies,
        private TaxCalculator $taxes,
        private BillingEventRecorder $events,
        private AutomationEventBusContract $automation,
    ) {}

    /** @param list<array{price_id:string,quantity?:int}> $items */
    public function createDraft(?CommerceCustomer $customer, string $currency, array $items): CommerceOrder
    {
        $currency = $this->currencies->ensureEnabled($currency)->code;
        if ($items === []) {
            throw new InvalidArgumentException('An order requires at least one item.');
        }

        return DB::transaction(function () use ($customer, $currency, $items): CommerceOrder {
            $order = CommerceOrder::query()->create([
                'number' => $this->nextNumber('ORD'),
                'customer_id' => $customer?->id,
                'status' => 'draft',
                'currency' => $currency,
                'billing_address' => $customer?->billing_address,
                'shipping_address' => $customer?->shipping_address,
            ]);

            $subtotal = 0;
            $tax = 0;
            $total = 0;
            $now = now();

            foreach ($items as $item) {
                $price = CommercePrice::query()
                    ->with('product')
                    ->whereKey((string) $item['price_id'])
                    ->where('active', true)
                    ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                    ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
                    ->whereHas('product', fn ($query) => $query->where('status', 'active'))
                    ->first();

                if (! $price || ! $price->product) {
                    throw new InvalidArgumentException('One selected price is no longer available from an active product.');
                }
                if ($price->currency !== $currency) {
                    throw new InvalidArgumentException('Every order item must use the order currency.');
                }

                $quantity = (int) ($item['quantity'] ?? 1);
                if ($quantity < 1 || $quantity > 999) {
                    throw new InvalidArgumentException('Commerce order quantity must be between 1 and 999.');
                }

                $unitAmount = $this->supportedMinorValue($price->amount_minor);
                if ($unitAmount > intdiv(PHP_INT_MAX, $quantity)) {
                    throw new OverflowException('Commerce order line exceeds the supported monetary range.');
                }
                $lineSubtotal = $unitAmount * $quantity;
                $taxResult = $this->taxes->calculate($lineSubtotal, null, null, $price->product->tax_code);
                $lineTotal = $taxResult['inclusive'] ? $lineSubtotal : $taxResult['total_minor'];

                CommerceOrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $price->product_id,
                    'price_id' => $price->id,
                    'name' => $price->product->name,
                    'sku' => $price->product->sku,
                    'quantity' => $quantity,
                    'unit_amount_minor' => $unitAmount,
                    'subtotal_minor' => $lineSubtotal,
                    'tax_minor' => $taxResult['tax_minor'],
                    'total_minor' => $lineTotal,
                    'metadata' => [
                        'tax_rate_id' => $taxResult['tax_rate_id'],
                        'rate_basis_points' => $taxResult['rate_basis_points'],
                        'tax_inclusive' => $taxResult['inclusive'],
                    ],
                ]);

                $subtotal = $this->checkedAdd($subtotal, $lineSubtotal);
                $tax = $this->checkedAdd($tax, $taxResult['tax_minor']);
                $total = $this->checkedAdd($total, $lineTotal);
            }

            $order->update([
                'subtotal_minor' => $subtotal,
                'tax_minor' => $tax,
                'total_minor' => $total,
            ]);
            $this->events->record('commerce.order.created', 'order', $order->id, payload: [
                'order_number' => $order->number,
                'currency' => $currency,
                'total_minor' => $total,
            ]);
            $this->automation->emit('commerce.order.created', [
                'order' => [
                    'id' => $order->id,
                    'number' => $order->number,
                    'currency' => $currency,
                    'total_minor' => $total,
                    'customer_id' => $customer?->id,
                ],
            ]);

            return $order->refresh();
        });
    }

    public function place(CommerceOrder $order): CommerceOrder
    {
        $placed = DB::transaction(function () use ($order): CommerceOrder {
            $locked = CommerceOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'draft') {
                throw new InvalidArgumentException('Only draft orders can be placed.');
            }
            if (! $locked->items()->exists()) {
                throw new InvalidArgumentException('An empty draft order cannot be placed.');
            }

            $locked->update(['status' => 'pending_payment', 'placed_at' => now()]);
            $this->events->record('commerce.order.placed', 'order', $locked->id, payload: ['number' => $locked->number]);

            return $locked->refresh();
        });

        $this->automation->emit('commerce.order.placed', [
            'order' => [
                'id' => $placed->id,
                'number' => $placed->number,
                'currency' => $placed->currency,
                'total_minor' => $placed->total_minor,
            ],
        ]);

        return $placed;
    }

    private function supportedMinorValue(mixed $value): int
    {
        $digits = ltrim((string) $value, '0');
        $digits = $digits === '' ? '0' : $digits;
        $maximum = (string) PHP_INT_MAX;

        if (preg_match('/^\d+$/', $digits) !== 1
            || strlen($digits) > strlen($maximum)
            || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
            throw new OverflowException('Commerce price exceeds the supported monetary range.');
        }

        return (int) $digits;
    }

    private function checkedAdd(int $left, int $right): int
    {
        if ($left < 0 || $right < 0 || $right > PHP_INT_MAX - $left) {
            throw new OverflowException('Commerce order total exceeds the supported monetary range.');
        }

        return $left + $right;
    }

    private function nextNumber(string $prefix): string
    {
        return $prefix.'-'.now()->format('Ymd').'-'.strtoupper(substr((string) Str::ulid(), -10));
    }
}
