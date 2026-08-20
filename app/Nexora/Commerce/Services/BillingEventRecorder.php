<?php

declare(strict_types=1);

namespace App\Nexora\Commerce\Services;

use App\Models\CommerceBillingEvent;

final class BillingEventRecorder
{
    /** @param array<string,mixed> $payload */
    public function record(string $type, ?string $resourceType = null, ?string $resourceId = null, ?string $providerKey = null, ?string $providerEventId = null, array $payload = []): CommerceBillingEvent
    {
        if ($providerKey !== null && $providerEventId !== null) {
            $existing=CommerceBillingEvent::query()->where('provider_key',$providerKey)->where('provider_event_id',$providerEventId)->first();
            if ($existing) return $existing;
        }
        return CommerceBillingEvent::query()->create([
            'event_type'=>$type,'resource_type'=>$resourceType,'resource_id'=>$resourceId,'provider_key'=>$providerKey,
            'provider_event_id'=>$providerEventId,'payload'=>$payload,'occurred_at'=>now(),
        ]);
    }
}
