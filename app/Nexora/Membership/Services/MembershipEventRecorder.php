<?php

declare(strict_types=1);

namespace App\Nexora\Membership\Services;

use App\Models\Membership;
use App\Models\MembershipEvent;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;

final readonly class MembershipEventRecorder
{
    public function __construct(private AutomationEventBusContract $automation) {}

    public function record(?Membership $membership, string $eventType, array $payload = [], ?int $actorId = null): MembershipEvent
    {
        $event=MembershipEvent::query()->create([
            'membership_id'=>$membership?->id,'event_type'=>$eventType,'payload'=>$payload,'actor_id'=>$actorId,'occurred_at'=>now(),
        ]);
        $this->automation->emit('membership.'.$eventType,[
            'membership'=>$membership ? ['id'=>$membership->id,'plan_id'=>$membership->plan_id,'user_id'=>$membership->user_id,'status'=>$membership->status] : null,
            'event'=>$payload,
        ],'membership',$membership?->id);
        return $event;
    }
}
