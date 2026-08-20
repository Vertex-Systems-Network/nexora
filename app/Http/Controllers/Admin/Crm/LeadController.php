<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Crm;

use App\Nexora\Enterprise\Validation\TenantExists;
use App\Http\Controllers\Controller;
use App\Models\CrmContact;
use App\Models\CrmLead;
use App\Models\CrmOrganization;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Models\User;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use App\Nexora\Commerce\Services\CurrencyManager;
use App\Nexora\Crm\Contracts\CrmTimelineContract;
use App\Nexora\Crm\Services\CrmLeadConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;

final class LeadController extends Controller
{
    public function index(Request $request, CurrencyManager $currencies): Response
    {
        $query=CrmLead::query()->with(['organization:id,name','contact:id,display_name','owner:id,name','convertedOpportunity:id,name'])->latest();
        $status=(string)$request->query('status',''); if ($status!=='') $query->where('status',$status);
        $leads=$query->paginate(20)->withQueryString()->through(fn(CrmLead $l)=>[
            'id'=>$l->id,'title'=>$l->title,'status'=>$l->status,'source'=>$l->source,'score'=>$l->score,'organization'=>$l->organization?->name,'contact'=>$l->contact?->display_name,'owner'=>$l->owner?->name,
            'estimated_value'=>$l->currency?$currencies->format((int)$l->estimated_value_minor,$l->currency):'—','converted_opportunity'=>$l->convertedOpportunity?->name,'created_at'=>$l->created_at?->toIso8601String(),
        ]);
        return Inertia::render('Admin/Crm/Leads',[
            'leads'=>$leads,'filters'=>['status'=>$status],'organizations'=>$this->organizations(),'contacts'=>$this->contacts(),'owners'=>$this->owners(),'pipelines'=>$this->pipelines(),
            'defaultCurrency'=>$currencies->defaultCode(),'canManage'=>$request->user()?->hasPermission('crm.leads.manage')??false,
        ]);
    }

    public function store(Request $request, CurrencyManager $currencies, CrmTimelineContract $timeline, AutomationEventBusContract $automation): RedirectResponse
    {
        $data=$request->validate([
            'title'=>['required','string','max:220'],'organization_id'=>['nullable','uuid',new TenantExists('nx_crm_organizations')],'contact_id'=>['nullable','uuid',new TenantExists('nx_crm_contacts')],'status'=>['required','in:new,working,qualified,disqualified'],
            'source'=>['nullable','string','max:120'],'score'=>['required','integer','min:0','max:100'],'currency'=>['nullable','string','size:3'],'estimated_value'=>['nullable','numeric','min:0'],'owner_id'=>['nullable','integer','exists:users,id'],'description'=>['nullable','string','max:5000'],
        ]);
        $currency=$data['currency']?:$currencies->defaultCode();
        $lead=CrmLead::query()->create([
            'title'=>$data['title'],'organization_id'=>$data['organization_id']??null,'contact_id'=>$data['contact_id']??null,'status'=>$data['status'],'source'=>$data['source']??null,'score'=>$data['score'],
            'currency'=>$currency,'estimated_value_minor'=>$currencies->toMinor((string)($data['estimated_value']??'0'),$currency),'owner_id'=>$data['owner_id']??null,'description'=>$data['description']??null,
        ]);
        $timeline->record('lead',$lead->id,'lead.created','Lead created',null,[], $request->user()?->id);
        $automation->emit('crm.lead.created',['lead'=>['id'=>$lead->id,'title'=>$lead->title,'status'=>$lead->status,'score'=>$lead->score,'owner_id'=>$lead->owner_id]],'crm.lead',$lead->id);
        return back()->with('success','Lead created.');
    }

    public function convert(Request $request, CrmLead $lead, CrmLeadConversionService $converter): RedirectResponse
    {
        $data=$request->validate(['pipeline_id'=>['nullable','uuid',new TenantExists('nx_crm_pipelines')],'stage_id'=>['nullable','uuid',TenantExists::through('nx_crm_pipeline_stages','nx_crm_pipelines','pipeline_id')]]);
        $pipeline=isset($data['pipeline_id'])?CrmPipeline::query()->findOrFail($data['pipeline_id']):null;
        $stage=isset($data['stage_id'])?CrmPipelineStage::query()->findOrFail($data['stage_id']):null;
        try { $opportunity=$converter->convert($lead,$pipeline,$stage,$request->user()?->id); } catch (InvalidArgumentException $exception) { throw ValidationException::withMessages(['pipeline_id'=>$exception->getMessage()]); }
        return redirect()->route('admin.crm.opportunities.show',$opportunity)->with('success','Lead converted to opportunity.');
    }

    private function organizations(): array { return CrmOrganization::query()->orderBy('name')->get(['id','name'])->map(fn($o)=>['id'=>$o->id,'name'=>$o->name])->all(); }
    private function contacts(): array { return CrmContact::query()->orderBy('display_name')->get(['id','display_name'])->map(fn($c)=>['id'=>$c->id,'name'=>$c->display_name])->all(); }
    private function owners(): array { return User::query()->where('status','active')->orderBy('name')->get(['id','name'])->map(fn($u)=>['id'=>$u->id,'name'=>$u->name])->all(); }
    private function pipelines(): array { return CrmPipeline::query()->with('stages')->where('active',true)->orderByDesc('is_default')->get()->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'stages'=>$p->stages->map(fn($s)=>['id'=>$s->id,'name'=>$s->name])->values()])->all(); }
}
