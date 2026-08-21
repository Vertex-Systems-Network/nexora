<?php

declare(strict_types=1);

namespace App\Nexora\Membership\Services;

use App\Models\CommerceCustomer;
use App\Models\CommerceSubscription;
use App\Models\EnterpriseOrganization;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Nexora\Enterprise\Services\TenantContext;
use App\Nexora\Membership\Contracts\MembershipManagerContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class MembershipManager implements MembershipManagerContract
{
    public function __construct(
        private MembershipEventRecorder $events,
        private TenantContext $tenant,
    ) {}

    public function grant(MembershipPlan $plan, ?User $user, array $attributes = [], ?int $actorId = null): Membership
    {
        $tenantId = $this->assertWritableTenant((string) $plan->tenant_id);
        if ($plan->status !== 'active') {
            throw new InvalidArgumentException('Only active membership plans can be granted.');
        }

        [$customer, $subscription] = $this->assertCommerceReferences($tenantId, $attributes);
        if ($user !== null && $customer?->user_id !== null && (int) $customer->user_id !== (int) $user->id) {
            throw new InvalidArgumentException('The selected Commerce customer belongs to a different Nexora user.');
        }

        return DB::transaction(function () use ($plan, $user, $attributes, $actorId, $customer, $subscription): Membership {
            $startedAt = $attributes['started_at'] ?? now();
            $endsAt = $attributes['ends_at'] ?? ($plan->duration_days ? Carbon::parse($startedAt)->addDays((int) $plan->duration_days) : null);
            $membership = Membership::query()->create([
                'plan_id' => $plan->id,
                'user_id' => $user?->id,
                'commerce_customer_id' => $customer?->id,
                'commerce_subscription_id' => $subscription?->id,
                'status' => $attributes['status'] ?? 'active',
                'started_at' => $startedAt,
                'trial_ends_at' => $attributes['trial_ends_at'] ?? null,
                'ends_at' => $endsAt,
                'metadata' => $attributes['metadata'] ?? [],
            ]);
            $this->events->record($membership, 'granted', ['source' => $attributes['source'] ?? 'admin'], $actorId);

            return $membership;
        });
    }

    public function setStatus(Membership $membership, string $status, ?int $actorId = null): Membership
    {
        $this->assertWritableTenant((string) $membership->tenant_id);
        if (! in_array($status, ['trial', 'active', 'paused', 'cancelled', 'expired'], true)) {
            throw new InvalidArgumentException('Unsupported membership status.');
        }

        return DB::transaction(function () use ($membership, $status, $actorId): Membership {
            $locked = Membership::query()->lockForUpdate()->findOrFail($membership->id);
            $from = $locked->status;
            if ($from === $status) {
                return $locked;
            }

            $locked->status = $status;
            if ($status === 'cancelled') {
                $locked->cancelled_at = now();
            }
            if ($status === 'expired' && ! $locked->ends_at) {
                $locked->ends_at = now();
            }
            $locked->save();
            $this->events->record($locked, 'status_changed', ['from' => $from, 'to' => $status], $actorId);

            return $locked->refresh();
        });
    }

    private function assertWritableTenant(string $recordTenantId): string
    {
        if ($recordTenantId === '') {
            throw new InvalidArgumentException('Membership data must belong to an organization.');
        }

        $activeTenantId = $this->tenant->id();
        if ($activeTenantId !== null) {
            if ($recordTenantId !== $activeTenantId) {
                throw new InvalidArgumentException('Membership data must belong to the current organization.');
            }

            return $activeTenantId;
        }

        $defaultTenantId = EnterpriseOrganization::query()->where('is_default', true)->value('id');
        if (! is_string($defaultTenantId) || $defaultTenantId === '' || $recordTenantId !== $defaultTenantId) {
            throw new InvalidArgumentException('A current organization is required for this membership operation.');
        }

        return $defaultTenantId;
    }

    /** @return array{0: ?CommerceCustomer, 1: ?CommerceSubscription} */
    private function assertCommerceReferences(string $tenantId, array $attributes): array
    {
        $customer = null;
        $subscription = null;

        $customerId = $attributes['commerce_customer_id'] ?? null;
        if (is_string($customerId) && $customerId !== '') {
            $customer = CommerceCustomer::query()->withoutGlobalScope('nexora_tenant')->find($customerId);
            if ($customer === null || (string) $customer->tenant_id !== $tenantId) {
                throw new InvalidArgumentException('The selected Commerce customer must belong to the membership organization.');
            }
        }

        $subscriptionId = $attributes['commerce_subscription_id'] ?? null;
        if (is_string($subscriptionId) && $subscriptionId !== '') {
            $subscription = CommerceSubscription::query()->withoutGlobalScope('nexora_tenant')->find($subscriptionId);
            if ($subscription === null || (string) $subscription->tenant_id !== $tenantId) {
                throw new InvalidArgumentException('The selected Commerce subscription must belong to the membership organization.');
            }
            if ($customer !== null && (string) $subscription->customer_id !== (string) $customer->id) {
                throw new InvalidArgumentException('The selected Commerce subscription belongs to a different customer.');
            }
        }

        return [$customer, $subscription];
    }
}
