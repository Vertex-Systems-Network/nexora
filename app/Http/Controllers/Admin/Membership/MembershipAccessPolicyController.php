<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Membership;

use App\Nexora\Enterprise\Validation\TenantExists;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\MembershipAccessPolicy;
use App\Models\MembershipPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MembershipAccessPolicyController extends Controller
{
    public function index(Request $request): Response
    {
        $policies=MembershipAccessPolicy::query()->latest()->paginate(20)->withQueryString()->through(function(MembershipAccessPolicy $p){$resource=$p->resource_type==='document'?Document::query()->find($p->resource_id):null;return ['id'=>$p->id,'name'=>$p->name,'resource_type'=>$p->resource_type,'resource'=>$resource?->title??$p->resource_id,'evaluation'=>$p->evaluation,'required_plan_ids'=>$p->required_plan_ids??[],'required_entitlements'=>$p->required_entitlements??[],'active'=>$p->active];});
        return Inertia::render('Admin/Membership/AccessPolicies',[
            'policies'=>$policies,'documents'=>Document::query()->where('status','published')->orderBy('title')->get(['id','title'])->map(fn(Document $d)=>['id'=>(string)$d->id,'name'=>$d->title]),
            'plans'=>MembershipPlan::query()->where('status','active')->with(['entitlements'=>fn($q)=>$q->where('active',true)])->orderBy('name')->get()->map(fn(MembershipPlan $p)=>['id'=>$p->id,'name'=>$p->name,'entitlements'=>$p->entitlements->pluck('key')->values()]),
            'canManage'=>$request->user()?->hasPermission('membership.access.manage')??false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:180'],'resource_type'=>['required','in:document'],'resource_id'=>['required','string','max:64',new TenantExists('nx_documents')],'evaluation'=>['required','in:any,all'],'required_plan_ids'=>['array'],'required_plan_ids.*'=>['uuid',new TenantExists('nx_membership_plans')],'required_entitlements'=>['array'],'required_entitlements.*'=>['string','max:160'],'active'=>['required','boolean']]);
        MembershipAccessPolicy::query()->updateOrCreate(['resource_type'=>$data['resource_type'],'resource_id'=>$data['resource_id']],['name'=>$data['name'],'evaluation'=>$data['evaluation'],'required_plan_ids'=>$data['required_plan_ids']??[],'required_entitlements'=>$data['required_entitlements']??[],'unauthenticated_action'=>'deny','active'=>$data['active'],'metadata'=>[]]);
        return back()->with('success','Access policy saved.');
    }
}
