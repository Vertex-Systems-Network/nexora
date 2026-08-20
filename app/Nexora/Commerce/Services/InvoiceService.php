<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Models\CommerceInvoice;
use App\Models\CommerceOrder;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class InvoiceService
{
    public function __construct(private BillingEventRecorder $events) {}

    public function createFromOrder(CommerceOrder $order, ?\DateTimeInterface $dueAt = null): CommerceInvoice
    {
        if ($order->status === 'cancelled') throw new InvalidArgumentException('Cancelled orders cannot be invoiced.');
        $existing=CommerceInvoice::query()->where('order_id',$order->id)->whereNotIn('status',['void'])->first();
        if ($existing) return $existing;
        $invoice=CommerceInvoice::query()->create([
            'number'=>$this->nextNumber(),'order_id'=>$order->id,'customer_id'=>$order->customer_id,'status'=>'open','currency'=>$order->currency,
            'subtotal_minor'=>$order->subtotal_minor,'discount_minor'=>$order->discount_minor,'tax_minor'=>$order->tax_minor,'total_minor'=>$order->total_minor,
            'amount_due_minor'=>max(0,(int)$order->total_minor-(int)$order->paid_minor),'amount_paid_minor'=>$order->paid_minor,
            'issued_at'=>now(),'due_at'=>$dueAt,
        ]);
        $this->events->record('commerce.invoice.created','invoice',$invoice->id,payload:['number'=>$invoice->number,'order_id'=>$order->id,'amount_due_minor'=>$invoice->amount_due_minor]);
        return $invoice;
    }

    private function nextNumber(): string { return 'INV-'.now()->format('Ymd').'-'.strtoupper(substr((string)Str::ulid(),-10)); }
}
