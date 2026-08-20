<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Membership;

use App\Nexora\Enterprise\Validation\TenantExists;
use App\Http\Controllers\Controller;
use App\Models\CommercePrice;
use App\Models\MembershipEntitlement;
use App\Models\MembershipPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class MembershipPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $plans=MembershipPlan::query()->withCount(['memberships','entitlements'])->with('commercePrice.product:id,name')->latest()->paginate(20)->withQueryString()->through(fn(MembershipPlan $p)=>[
            'id'=>$p->id,'name'=>$p->name,'slug'=>$p->slug,'status'=>$p->status,'duration_days'=>$p->duration_days,'memberships_count'=>$p->memberships_count,'entitlements_count'=>$p->entitlements_count,
            'commerce_price'=>$p->commercePrice?->product?->name ? $p->commercePrice->product->name.' · '.$p->commercePrice->billing_interval : null,
        ]);
        return Inertia::render('Admin/Membership/Plans',[
            'plans'=>$plans,
            'prices'=>CommercePrice::query()->with('product:id,name')->where('active',true)->orderBy('amount_minor')->get()->map(fn(CommercePrice $p)=>['id'=>$p->id,'name'=>($p->product?->name??'Product').' · '.$p->currency.' '.$p->amount_minor.' minor'.($p->billing_interval?' / '.$p->billing_interval:'')]),
            'canManage'=>$request->user()?->hasPermission('membership.plans.manage')??false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate(['name'=>['required','string','max:180'],'slug'=>['nullable','string','max:190','regex:/^[a-z0-9][a-z0-9-]*$/','unique:nx_membership_plans,slug'],'description'=>['nullable','string','max:5000'],'status'=>['required','in:active,archived'],'duration_days'=>['nullable','integer','min:1','max:36500'],'commerce_price_id'=>['nullable','uuid',TenantExists::through('nx_commerce_prices','nx_commerce_products','product_id'),'unique:nx_membership_plans,commerce_price_id']]);
        MembershipPlan::query()->create(['name'=>$data['name'],'slug'=>$data['slug']?:Str::slug($data['name']).'-'.Str::lower(Str::random(5)),'description'=>$data['description']??null,'status'=>$data['status'],'duration_days'=>$data['duration_days']??null,'commerce_price_id'=>$data['commerce_price_id']??null,'metadata'=>[]]);
        return back()->with('success','Membership plan created.');
    }

    public function entitlement(Request $request, MembershipPlan $plan): RedirectResponse
    {
        $data=$request->validate(['key'=>['required','string','max:160','regex:/^[a-z0-9][a-z0-9._-]+$/'],'label'=>['required','string','max:180'],'value_type'=>['required','in:boolean,integer,string'],'value'=>['nullable'],'active'=>['required','boolean']]);
        $value=match($data['value_type']){'boolean'=>(bool)$data['value'],'integer'=>(int)($data['value']??0),default=>(string)($data['value']??'')};
        MembershipEntitlement::query()->updateOrCreate(['plan_id'=>$plan->id,'key'=>$data['key']],['label'=>$data['label'],'value_type'=>$data['value_type'],'value'=>$value,'active'=>$data['active']]);
        return back()->with('success','Entitlement saved.');
    }
}
