<?php

declare(strict_types=1);

namespace App\Nexora\Membership\Services;

use App\Models\CommerceSubscription;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Nexora\Enterprise\Services\TenantExecutionScope;
use InvalidArgumentException;

final readonly class MembershipCommerceSyncService
{
    public function __construct(
        private MembershipManager $memberships,
        private TenantExecutionScope $tenantScope,
    ) {}

    public function sync(CommerceSubscription $subscription): ?Membership
    {
        $tenantId = trim((string) $subscription->tenant_id);

        return $this->tenantScope->runRequired($tenantId, 'membership Commerce synchronization', function () use ($subscription): ?Membership {
            $scopedSubscription = CommerceSubscription::query()->find($subscription->id);
            if ($scopedSubscription === null) {
                throw new InvalidArgumentException('The Commerce subscription does not belong to the active synchronization tenant.');
            }

            $plan = MembershipPlan::query()
                ->where('commerce_price_id', $scopedSubscription->price_id)
                ->where('status', 'active')
                ->first();
            if ($plan === null) {
                return null;
            }

            $scopedSubscription->loadMissing('customer.user');
            $membership = Membership::query()->where('commerce_subscription_id', $scopedSubscription->id)->first();
            $status = match ($scopedSubscription->status) {
                'active' => 'active',
                'trialing' => 'trial',
                'paused' => 'paused',
                'cancelled', 'canceled' => 'cancelled',
                default => 'paused',
            };

            if ($membership === null) {
                return $this->memberships->grant($plan, $scopedSubscription->customer?->user, [
                    'commerce_customer_id' => $scopedSubscription->customer_id,
                    'commerce_subscription_id' => $scopedSubscription->id,
                    'status' => $status,
                    'started_at' => $scopedSubscription->current_period_start ?? now(),
                    'ends_at' => $scopedSubscription->current_period_end,
                    'source' => 'commerce_subscription',
                ]);
            }

            $membership->fill([
                'plan_id' => $plan->id,
                'user_id' => $scopedSubscription->customer?->user_id,
                'commerce_customer_id' => $scopedSubscription->customer_id,
                'started_at' => $scopedSubscription->current_period_start ?? $membership->started_at,
                'ends_at' => $scopedSubscription->current_period_end,
            ]);
            $membership->save();
            if ($membership->status !== $status) {
                $membership = $this->memberships->setStatus($membership, $status);
            }

            return $membership;
        });
    }
}
