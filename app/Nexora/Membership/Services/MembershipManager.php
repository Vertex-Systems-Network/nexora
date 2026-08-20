<?php

declare(strict_types=1);

namespace App\Nexora\Membership\Services;

use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Nexora\Membership\Contracts\MembershipManagerContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final readonly class MembershipManager implements MembershipManagerContract
{
    public function __construct(private MembershipEventRecorder $events) {}

    public function grant(MembershipPlan $plan, ?User $user, array $attributes = [], ?int $actorId = null): Membership
    {
        if ($plan->status !== 'active') throw new InvalidArgumentException('Only active membership plans can be granted.');
        return DB::transaction(function () use ($plan,$user,$attributes,$actorId): Membership {
            $startedAt=$attributes['started_at']??now();
            $endsAt=$attributes['ends_at']??($plan->duration_days ? Carbon::parse($startedAt)->addDays((int)$plan->duration_days) : null);
            $membership=Membership::query()->create([
                'plan_id'=>$plan->id,'user_id'=>$user?->id,'commerce_customer_id'=>$attributes['commerce_customer_id']??null,'commerce_subscription_id'=>$attributes['commerce_subscription_id']??null,
                'status'=>$attributes['status']??'active','started_at'=>$startedAt,'trial_ends_at'=>$attributes['trial_ends_at']??null,'ends_at'=>$endsAt,'metadata'=>$attributes['metadata']??[],
            ]);
            $this->events->record($membership,'granted',['source'=>$attributes['source']??'admin'],$actorId);
            return $membership;
        });
    }

    public function setStatus(Membership $membership, string $status, ?int $actorId = null): Membership
    {
        if (! in_array($status,['trial','active','paused','cancelled','expired'],true)) throw new InvalidArgumentException('Unsupported membership status.');
        return DB::transaction(function () use ($membership,$status,$actorId): Membership {
            $locked=Membership::query()->lockForUpdate()->findOrFail($membership->id);
            $from=$locked->status;
            if ($from===$status) return $locked;
            $locked->status=$status;
            if ($status==='cancelled') $locked->cancelled_at=now();
            if ($status==='expired' && ! $locked->ends_at) $locked->ends_at=now();
            $locked->save();
            $this->events->record($locked,'status_changed',['from'=>$from,'to'=>$status],$actorId);
            return $locked->refresh();
        });
    }
}
