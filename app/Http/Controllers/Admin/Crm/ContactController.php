<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmContact;
use App\Models\CrmOrganization;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use App\Nexora\Crm\Contracts\CrmTimelineContract;
use App\Nexora\Enterprise\Services\TenantMemberDirectory;
use App\Nexora\Enterprise\Validation\TenantExists;
use App\Nexora\Enterprise\Validation\TenantMemberExists;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ContactController extends Controller
{
    public function index(Request $request, TenantMemberDirectory $members): Response
    {
        $q=trim((string)$request->query('q',''));
        $query=CrmContact::query()->with(['organization:id,name','owner:id,name'])->withCount(['opportunities','commerceLinks'])->latest();
        if ($q!=='') $query->where(fn($builder)=>$builder->where('display_name','like','%'.$q.'%')->orWhere('email','like','%'.$q.'%')->orWhere('job_title','like','%'.$q.'%'));
        $contacts=$query->paginate(20)->withQueryString()->through(fn(CrmContact $c)=>[
            'id'=>$c->id,'display_name'=>$c->display_name,'email'=>$c->email,'phone'=>$c->phone,'job_title'=>$c->job_title,'organization'=>$c->organization?->name,'lifecycle_stage'=>$c->lifecycle_stage,
            'owner'=>$c->owner?->name,'opportunities_count'=>$c->opportunities_count,'commerce_links_count'=>$c->commerce_links_count,'created_at'=>$c->created_at?->toIso8601String(),
        ]);
        return Inertia::render('Admin/Crm/Contacts',['contacts'=>$contacts,'filters'=>['q'=>$q],'organizations'=>$this->organizations(),'owners'=>$members->options(),'canManage'=>$request->user()?->hasPermission('crm.contacts.manage')??false]);
    }

    public function store(Request $request, CrmTimelineContract $timeline, AutomationEventBusContract $automation): RedirectResponse
    {
        $data=$request->validate([
            'organization_id'=>['nullable','uuid',new TenantExists('nx_crm_organizations')],'first_name'=>['required','string','max:120'],'last_name'=>['nullable','string','max:120'],'email'=>['nullable','email','max:255'],
            'phone'=>['nullable','string','max:80'],'mobile'=>['nullable','string','max:80'],'job_title'=>['nullable','string','max:160'],'lifecycle_stage'=>['required','in:lead,prospect,customer,partner,inactive'],
            'source'=>['nullable','string','max:120'],'owner_id'=>['nullable','integer',new TenantMemberExists()],
        ]);
        $data['display_name']=trim($data['first_name'].' '.($data['last_name']??''));
        $contact=CrmContact::query()->create($data);
        $timeline->record('contact',$contact->id,'contact.created','Contact created',null,[], $request->user()?->id);
        $automation->emit('crm.contact.created',['contact'=>['id'=>$contact->id,'display_name'=>$contact->display_name,'email'=>$contact->email,'organization_id'=>$contact->organization_id,'owner_id'=>$contact->owner_id]],'crm.contact',$contact->id);
        return back()->with('success','Contact created.');
    }

    public function show(Request $request, CrmContact $contact, CrmTimelineContract $timeline): Response
    {
        $contact->load(['organization:id,name','owner:id,name,email','opportunities.stage:id,name','commerceLinks.customer:id,name,email']);
        return Inertia::render('Admin/Crm/ContactShow',[
            'contact'=>[
                'id'=>$contact->id,'display_name'=>$contact->display_name,'first_name'=>$contact->first_name,'last_name'=>$contact->last_name,'email'=>$contact->email,'phone'=>$contact->phone,'mobile'=>$contact->mobile,
                'job_title'=>$contact->job_title,'lifecycle_stage'=>$contact->lifecycle_stage,'source'=>$contact->source,'organization'=>$contact->organization?->only(['id','name']),'owner'=>$contact->owner?->only(['id','name','email']),
                'opportunities'=>$contact->opportunities->map(fn($o)=>['id'=>$o->id,'name'=>$o->name,'status'=>$o->status,'stage'=>$o->stage?->name]),
                'commerce_links'=>$contact->commerceLinks->map(fn($l)=>['id'=>$l->id,'customer_id'=>$l->customer?->id,'customer'=>$l->customer?->name,'email'=>$l->customer?->email]),
            ],
            'timeline'=>$timeline->for('contact',$contact->id)->map(fn($e)=>['id'=>$e->id,'event_type'=>$e->event_type,'title'=>$e->title,'summary'=>$e->summary,'actor'=>$e->actor?->name,'occurred_at'=>$e->occurred_at?->toIso8601String()]),
            'canActivity'=>$request->user()?->hasPermission('crm.activities.manage')??false,'canLink'=>$request->user()?->hasPermission('crm.commerce.link')??false,
        ]);
    }

    private function organizations(): array { return CrmOrganization::query()->orderBy('name')->get(['id','name'])->map(fn($o)=>['id'=>$o->id,'name'=>$o->name])->all(); }
}
