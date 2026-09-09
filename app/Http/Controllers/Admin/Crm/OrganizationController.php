<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmOrganization;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use App\Nexora\Crm\Contracts\CrmTimelineContract;
use App\Nexora\Enterprise\Services\TenantMemberDirectory;
use App\Nexora\Enterprise\Validation\TenantMemberExists;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizationController extends Controller
{
    public function index(Request $request, TenantMemberDirectory $members): Response
    {
        $q=trim((string)$request->query('q',''));
        $query=CrmOrganization::query()->with('owner:id,name')->withCount(['contacts','opportunities','commerceLinks'])->latest();
        if ($q!=='') $query->where(fn($builder)=>$builder->where('name','like','%'.$q.'%')->orWhere('domain','like','%'.$q.'%')->orWhere('industry','like','%'.$q.'%'));
        $organizations=$query->paginate(20)->withQueryString()->through(fn(CrmOrganization $o)=>[
            'id'=>$o->id,'name'=>$o->name,'domain'=>$o->domain,'industry'=>$o->industry,'lifecycle_stage'=>$o->lifecycle_stage,'owner'=>$o->owner?->name,
            'contacts_count'=>$o->contacts_count,'opportunities_count'=>$o->opportunities_count,'commerce_links_count'=>$o->commerce_links_count,'created_at'=>$o->created_at?->toIso8601String(),
        ]);
        return Inertia::render('Admin/Crm/Organizations',['organizations'=>$organizations,'filters'=>['q'=>$q],'owners'=>$members->options(),'canManage'=>$request->user()?->hasPermission('crm.organizations.manage')??false]);
    }

    public function store(Request $request, CrmTimelineContract $timeline, AutomationEventBusContract $automation): RedirectResponse
    {
        $data=$request->validate([
            'name'=>['required','string','max:200'],'domain'=>['nullable','string','max:190'],'website'=>['nullable','url','max:2048'],'industry'=>['nullable','string','max:120'],
            'email'=>['nullable','email','max:255'],'phone'=>['nullable','string','max:80'],'lifecycle_stage'=>['required','in:prospect,customer,partner,inactive'],'owner_id'=>['nullable','integer',new TenantMemberExists()],'description'=>['nullable','string','max:5000'],
        ]);
        $organization=CrmOrganization::query()->create($data);
        $timeline->record('organization',$organization->id,'organization.created','Organization created',null,[], $request->user()?->id);
        $automation->emit('crm.organization.created',['organization'=>['id'=>$organization->id,'name'=>$organization->name,'domain'=>$organization->domain,'owner_id'=>$organization->owner_id]],'crm.organization',$organization->id);
        return back()->with('success','Organization created.');
    }

    public function show(Request $request, CrmOrganization $organization, CrmTimelineContract $timeline, TenantMemberDirectory $members): Response
    {
        $organization->load(['owner:id,name,email','contacts.owner:id,name','opportunities.stage:id,name','opportunities.owner:id,name','commerceLinks.customer:id,name,email']);
        return Inertia::render('Admin/Crm/OrganizationShow',[
            'organization'=>[
                'id'=>$organization->id,'name'=>$organization->name,'domain'=>$organization->domain,'website'=>$organization->website,'industry'=>$organization->industry,'email'=>$organization->email,'phone'=>$organization->phone,
                'lifecycle_stage'=>$organization->lifecycle_stage,'description'=>$organization->description,'owner'=>$organization->owner?->only(['id','name','email']),
                'contacts'=>$organization->contacts->map(fn($c)=>['id'=>$c->id,'display_name'=>$c->display_name,'email'=>$c->email,'job_title'=>$c->job_title,'owner'=>$c->owner?->name]),
                'opportunities'=>$organization->opportunities->map(fn($o)=>['id'=>$o->id,'name'=>$o->name,'status'=>$o->status,'stage'=>$o->stage?->name,'owner'=>$o->owner?->name]),
                'commerce_links'=>$organization->commerceLinks->map(fn($l)=>['id'=>$l->id,'customer_id'=>$l->customer?->id,'customer'=>$l->customer?->name,'email'=>$l->customer?->email]),
            ],
            'timeline'=>$timeline->for('organization',$organization->id)->map(fn($e)=>['id'=>$e->id,'event_type'=>$e->event_type,'title'=>$e->title,'summary'=>$e->summary,'actor'=>$e->actor?->name,'occurred_at'=>$e->occurred_at?->toIso8601String()]),
            'owners'=>$members->options(),'canActivity'=>$request->user()?->hasPermission('crm.activities.manage')??false,'canLink'=>$request->user()?->hasPermission('crm.commerce.link')??false,
        ]);
    }
}
