<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Models\CommerceCustomer;
use App\Models\CommercePrice;
use App\Models\CommerceSubscription;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use InvalidArgumentException;

final readonly class SubscriptionService
{
    public function __construct(private BillingEventRecorder $events, private AutomationEventBusContract $automation) {}

    /** @param array<string,mixed> $metadata */
    public function record(CommerceCustomer $customer, CommercePrice $price, string $providerKey, string $status, ?string $providerReference, ?\DateTimeInterface $periodStart = null, ?\DateTimeInterface $periodEnd = null, bool $cancelAtPeriodEnd = false, array $metadata = []): CommerceSubscription
    {
        if (! $price->billing_interval) throw new InvalidArgumentException('Subscriptions require a recurring Commerce price.');
        $subscription=$providerReference
            ? CommerceSubscription::query()->firstOrNew(['provider_key'=>$providerKey,'provider_reference'=>$providerReference])
            : new CommerceSubscription();
        $subscription->fill([
            'customer_id'=>$customer->id,'product_id'=>$price->product_id,'price_id'=>$price->id,'provider_key'=>$providerKey,'provider_reference'=>$providerReference,
            'status'=>$status,'currency'=>$price->currency,'amount_minor'=>$price->amount_minor,'billing_interval'=>$price->billing_interval,
            'interval_count'=>$price->interval_count,'current_period_start'=>$periodStart,'current_period_end'=>$periodEnd,'cancel_at_period_end'=>$cancelAtPeriodEnd,'metadata'=>$metadata,
        ]);
        if (in_array($status,['cancelled','canceled'],true)) $subscription->cancelled_at=now();
        $subscription->save();
        $this->events->record('commerce.subscription.updated','subscription',$subscription->id,$providerKey,null,payload:['status'=>$status,'provider_reference'=>$providerReference,'customer_id'=>$customer->id]);
        $this->automation->emit('commerce.subscription.updated',['subscription'=>['id'=>$subscription->id,'status'=>$status,'provider'=>$providerKey,'customer_id'=>$customer->id,'product_id'=>$price->product_id]]);
        return $subscription->refresh();
    }
}
