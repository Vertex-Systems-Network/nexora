<?php

declare(strict_types=1);

namespace App\Nexora\Membership\Services;

use App\Models\Membership;
use App\Models\MembershipAccessPolicy;
use App\Models\User;
use App\Nexora\Membership\Contracts\MembershipAccessContract;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class MembershipAccessManager implements MembershipAccessContract
{
    public function canAccess(?User $user, string $resourceType, string $resourceId): bool
    {
        $policy=MembershipAccessPolicy::query()->where('resource_type',$resourceType)->where('resource_id',$resourceId)->where('active',true)->first();
        if ($policy===null) return true;
        if ($user===null) return false;

        $memberships=Membership::query()->with(['plan.entitlements'=>fn($q)=>$q->where('active',true)])
            ->where('user_id',$user->id)->whereIn('status',['active','trial'])->get()->filter(fn(Membership $membership)=>$membership->isEffective());
        if ($memberships->isEmpty()) return false;

        $requiredPlans=array_values(array_filter((array)$policy->required_plan_ids,'is_string'));
        $requiredEntitlements=array_values(array_filter((array)$policy->required_entitlements,'is_string'));
        if ($requiredPlans===[] && $requiredEntitlements===[]) return true;

        $planIds=$memberships->pluck('plan_id')->all();
        $entitlements=$memberships->flatMap(fn(Membership $membership)=>$membership->plan?->entitlements?->pluck('key')??collect())->unique()->values()->all();
        $checks=[];
        foreach ($requiredPlans as $planId) $checks[]=in_array($planId,$planIds,true);
        foreach ($requiredEntitlements as $key) $checks[]=in_array($key,$entitlements,true);
        if ($checks===[]) return true;
        return $policy->evaluation==='all' ? ! in_array(false,$checks,true) : in_array(true,$checks,true);
    }

    public function assertCanAccess(?User $user, string $resourceType, string $resourceId): void
    {
        if (! $this->canAccess($user,$resourceType,$resourceId)) throw new AccessDeniedHttpException('An active membership is required to access this content.');
    }
}
