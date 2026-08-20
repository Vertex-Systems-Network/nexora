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
        $currency=$this->currencies->ensureEnabled($currency)->code;
        if ($items === []) throw new InvalidArgumentException('An order requires at least one item.');
        return DB::transaction(function () use ($customer,$currency,$items): CommerceOrder {
            $order=CommerceOrder::query()->create([
                'number'=>$this->nextNumber('ORD'),'customer_id'=>$customer?->id,'status'=>'draft','currency'=>$currency,
                'billing_address'=>$customer?->billing_address,'shipping_address'=>$customer?->shipping_address,
            ]);
            $subtotal=0;$tax=0;$total=0;
            foreach ($items as $item) {
                $price=CommercePrice::query()->with('product')->whereKey((string)$item['price_id'])->where('active',true)->first();
                if (! $price || ! $price->product) throw new InvalidArgumentException('One selected price is no longer available.');
                if ($price->currency !== $currency) throw new InvalidArgumentException('Every order item must use the order currency.');
                $quantity=max(1,(int)($item['quantity']??1));
                $lineSubtotal=(int)$price->amount_minor*$quantity;
                $taxResult=$this->taxes->calculate($lineSubtotal, null, null, $price->product->tax_code);
                $lineTotal=$taxResult['inclusive'] ? $lineSubtotal : $taxResult['total_minor'];
                CommerceOrderItem::query()->create([
                    'order_id'=>$order->id,'product_id'=>$price->product_id,'price_id'=>$price->id,'name'=>$price->product->name,
                    'sku'=>$price->product->sku,'quantity'=>$quantity,'unit_amount_minor'=>$price->amount_minor,
                    'subtotal_minor'=>$lineSubtotal,'tax_minor'=>$taxResult['tax_minor'],'total_minor'=>$lineTotal,
                    'metadata'=>['tax_rate_id'=>$taxResult['tax_rate_id'],'rate_basis_points'=>$taxResult['rate_basis_points'],'tax_inclusive'=>$taxResult['inclusive']],
                ]);
                $subtotal+=$lineSubtotal;$tax+=$taxResult['tax_minor'];$total+=$lineTotal;
            }
            $order->update(['subtotal_minor'=>$subtotal,'tax_minor'=>$tax,'total_minor'=>$total]);
            $this->events->record('commerce.order.created','order',$order->id,payload:['order_number'=>$order->number,'currency'=>$currency,'total_minor'=>$total]);
            $this->automation->emit('commerce.order.created',['order'=>['id'=>$order->id,'number'=>$order->number,'currency'=>$currency,'total_minor'=>$total,'customer_id'=>$customer?->id]]);
            return $order->refresh();
        });
    }

    public function place(CommerceOrder $order): CommerceOrder
    {
        if ($order->status !== 'draft') throw new InvalidArgumentException('Only draft orders can be placed.');
        $order->update(['status'=>'pending_payment','placed_at'=>now()]);
        $this->events->record('commerce.order.placed','order',$order->id,payload:['number'=>$order->number]);
        $this->automation->emit('commerce.order.placed',['order'=>['id'=>$order->id,'number'=>$order->number,'currency'=>$order->currency,'total_minor'=>$order->total_minor]]);
        return $order->refresh();
    }

    private function nextNumber(string $prefix): string { return $prefix.'-'.now()->format('Ymd').'-'.strtoupper(substr((string)Str::ulid(), -10)); }
}
