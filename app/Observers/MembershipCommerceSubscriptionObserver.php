<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CommerceSubscription;
use App\Nexora\Membership\Services\MembershipCommerceSyncService;

final readonly class MembershipCommerceSubscriptionObserver
{
    public function __construct(private MembershipCommerceSyncService $sync) {}
    public function saved(CommerceSubscription $subscription): void { $this->sync->sync($subscription); }
}
