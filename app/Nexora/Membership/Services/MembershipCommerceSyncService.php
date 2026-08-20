<?php

declare(strict_types=1);

namespace App\Nexora\Membership\Services;

use App\Models\CommerceSubscription;
use App\Models\Membership;
use App\Models\MembershipPlan;

final readonly class MembershipCommerceSyncService
{
    public function __construct(private MembershipManager $memberships) {}

    public function sync(CommerceSubscription $subscription): ?Membership
    {
        $plan=MembershipPlan::query()->where('commerce_price_id',$subscription->price_id)->where('status','active')->first();
        if ($plan===null) return null;
        $subscription->loadMissing('customer.user');
        $membership=Membership::query()->where('commerce_subscription_id',$subscription->id)->first();
        $status=match($subscription->status){'active'=>'active','trialing'=>'trial','paused'=>'paused','cancelled','canceled'=>'cancelled',default=>'paused'};
        if ($membership===null) {
            return $this->memberships->grant($plan,$subscription->customer?->user,[
                'commerce_customer_id'=>$subscription->customer_id,'commerce_subscription_id'=>$subscription->id,'status'=>$status,
                'started_at'=>$subscription->current_period_start??now(),'ends_at'=>$subscription->current_period_end,'source'=>'commerce_subscription',
            ]);
        }
        $membership->fill(['plan_id'=>$plan->id,'user_id'=>$subscription->customer?->user_id,'commerce_customer_id'=>$subscription->customer_id,'started_at'=>$subscription->current_period_start??$membership->started_at,'ends_at'=>$subscription->current_period_end]);
        $membership->save();
        if ($membership->status!==$status) $membership=$this->memberships->setStatus($membership,$status);
        return $membership;
    }
}
