<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Membership;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\MembershipAccessPolicy;
use App\Models\MembershipPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MembershipDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Admin/Membership/Index',[
            'summary'=>[
                'plans'=>MembershipPlan::query()->count(),
                'active_plans'=>MembershipPlan::query()->where('status','active')->count(),
                'members'=>Membership::query()->count(),
                'active_members'=>Membership::query()->whereIn('status',['active','trial'])->count(),
                'protected_resources'=>MembershipAccessPolicy::query()->where('active',true)->count(),
                'commerce_linked'=>Membership::query()->whereNotNull('commerce_subscription_id')->count(),
            ],
            'recent'=>Membership::query()->with(['plan:id,name','user:id,name,email'])->latest()->limit(8)->get()->map(fn(Membership $m)=>[
                'id'=>$m->id,'plan'=>$m->plan?->name,'member'=>$m->user?->name??$m->user?->email??'Unlinked member','status'=>$m->status,'ends_at'=>$m->ends_at?->toIso8601String(),'created_at'=>$m->created_at?->toIso8601String(),
            ]),
        ]);
    }
}
