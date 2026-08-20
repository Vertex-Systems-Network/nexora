<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Crm;

use App\Nexora\Enterprise\Validation\TenantMemberExists;
use App\Nexora\Enterprise\Validation\TenantExists;
use App\Http\Controllers\Controller;
use App\Models\CrmContact;
use App\Models\CrmOpportunity;
use App\Models\CrmOpportunityStageHistory;
use App\Models\CrmOrganization;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Models\User;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use App\Nexora\Commerce\Services\CurrencyManager;
use App\Nexora\Crm\Contracts\CrmOpportunityManagerContract;
use App\Nexora\Crm\Contracts\CrmTimelineContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;

final class OpportunityController extends Controller
{
    public function index(Request $request, CurrencyManager $currencies): Response
    {
        $query=CrmOpportunity::query()->with(['pipeline:id,name','stage:id,name','organization:id,name','contact:id,display_name','owner:id,name'])->latest();
        $status=(string)$request->query('status',''); if ($status!=='') $query->where('status',$status);
        $pipelineId=(string)$request->query('pipeline',''); if ($pipelineId!=='') $query->where('pipeline_id',$pipelineId);
        $opportunities=$query->paginate(20)->withQueryString()->through(fn(CrmOpportunity $o)=>[
            'id'=>$o->id,'name'=>$o->name,'status'=>$o->status,'pipeline'=>$o->pipeline?->name,'stage'=>$o->stage?->name,'stage_id'=>$o->stage_id,'organization'=>$o->organization?->name,'contact'=>$o->contact?->display_name,'owner'=>$o->owner?->name,
            'amount'=>$o->currency?$currencies->format((int)$o->amount_minor,$o->currency):'—','probability'=>$o->probability,'expected_close_at'=>$o->expected_close_at?->format('Y-m-d\\TH:i'),'updated_at'=>$o->updated_at?->toIso8601String(),
        ]);
        return Inertia::render('Admin/Crm/Opportunities',[
            'opportunities'=>$opportunities,'filters'=>['status'=>$status,'pipeline'=>$pipelineId],'pipelines'=>$this->pipelines(),'organizations'=>$this->organizations(),'contacts'=>$this->contacts(),'owners'=>$this->owners(),
            'defaultCurrency'=>$currencies->defaultCode(),'canManage'=>$request->user()?->hasPermission('crm.opportunities.manage')??false,
        ]);
    }

    public function store(Request $request, CurrencyManager $currencies, CrmTimelineContract $timeline, AutomationEventBusContract $automation): RedirectResponse
    {
        $data=$request->validate([
            'name'=>['required','string','max:220'],'pipeline_id'=>['required','uuid',new TenantExists('nx_crm_pipelines')],'stage_id'=>['required','uuid',TenantExists::through('nx_crm_pipeline_stages','nx_crm_pipelines','pipeline_id')],'organization_id'=>['nullable','uuid',new TenantExists('nx_crm_organizations')],
            'contact_id'=>['nullable','uuid',new TenantExists('nx_crm_contacts')],'currency'=>['nullable','string','size:3'],'amount'=>['nullable','numeric','min:0'],'source'=>['nullable','string','max:120'],'owner_id'=>['nullable','integer',new TenantMemberExists()],'expected_close_at'=>['nullable','date'],
        ]);
        $stage=CrmPipelineStage::query()->findOrFail($data['stage_id']); if ($stage->pipeline_id!==$data['pipeline_id']) throw ValidationException::withMessages(['stage_id'=>'The selected stage belongs to a different CRM pipeline.']);
        $currency=$data['currency']?:$currencies->defaultCode();
        $status=$stage->is_won?'won':($stage->is_lost?'lost':'open');
        $opportunity=CrmOpportunity::query()->create([
            'name'=>$data['name'],'pipeline_id'=>$data['pipeline_id'],'stage_id'=>$stage->id,'organization_id'=>$data['organization_id']??null,'contact_id'=>$data['contact_id']??null,
            'status'=>$status,'currency'=>$currency,'amount_minor'=>$currencies->toMinor((string)($data['amount']??'0'),$currency),'probability'=>$stage->probability,'source'=>$data['source']??null,'owner_id'=>$data['owner_id']??null,
            'expected_close_at'=>$data['expected_close_at']??null,'won_at'=>$stage->is_won?now():null,'lost_at'=>$stage->is_lost?now():null,
        ]);
        CrmOpportunityStageHistory::query()->create(['opportunity_id'=>$opportunity->id,'from_stage_id'=>null,'to_stage_id'=>$stage->id,'changed_by'=>$request->user()?->id,'changed_at'=>now()]);
        $timeline->record('opportunity',$opportunity->id,'opportunity.created','Opportunity created',null,[], $request->user()?->id);
        $automation->emit('crm.opportunity.created',['opportunity'=>['id'=>$opportunity->id,'name'=>$opportunity->name,'pipeline_id'=>$opportunity->pipeline_id,'stage_id'=>$opportunity->stage_id,'status'=>$opportunity->status,'amount_minor'=>$opportunity->amount_minor,'currency'=>$opportunity->currency]],'crm.opportunity',$opportunity->id);
        return back()->with('success','Opportunity created.');
    }

    public function stage(Request $request, CrmOpportunity $opportunity, CrmOpportunityManagerContract $manager): RedirectResponse
    {
        $data=$request->validate(['stage_id'=>['required','uuid',TenantExists::through('nx_crm_pipeline_stages','nx_crm_pipelines','pipeline_id')]]);
        try { $manager->moveStage($opportunity,CrmPipelineStage::query()->findOrFail($data['stage_id']),$request->user()?->id); } catch (InvalidArgumentException $exception) { throw ValidationException::withMessages(['stage_id'=>$exception->getMessage()]); }
        return back()->with('success','Opportunity stage updated.');
    }

    public function show(Request $request, CrmOpportunity $opportunity, CurrencyManager $currencies, CrmTimelineContract $timeline): Response
    {
        $opportunity->load(['pipeline:id,name','stage:id,name','organization:id,name','contact:id,display_name,email','owner:id,name,email','stageHistory.fromStage:id,name','stageHistory.toStage:id,name','stageHistory.changer:id,name']);
        return Inertia::render('Admin/Crm/OpportunityShow',[
            'opportunity'=>[
                'id'=>$opportunity->id,'name'=>$opportunity->name,'status'=>$opportunity->status,'pipeline'=>$opportunity->pipeline?->only(['id','name']),'stage'=>$opportunity->stage?->only(['id','name']),
                'organization'=>$opportunity->organization?->only(['id','name']),'contact'=>$opportunity->contact?->only(['id','display_name','email']),'owner'=>$opportunity->owner?->only(['id','name','email']),
                'amount'=>$opportunity->currency?$currencies->format((int)$opportunity->amount_minor,$opportunity->currency):'—','probability'=>$opportunity->probability,'expected_close_at'=>$opportunity->expected_close_at?->format('Y-m-d\\TH:i'),
                'stage_history'=>$opportunity->stageHistory->map(fn($h)=>['id'=>$h->id,'from'=>$h->fromStage?->name,'to'=>$h->toStage?->name,'changed_by'=>$h->changer?->name,'changed_at'=>$h->changed_at?->toIso8601String()]),
            ],
            'timeline'=>$timeline->for('opportunity',$opportunity->id)->map(fn($e)=>['id'=>$e->id,'event_type'=>$e->event_type,'title'=>$e->title,'summary'=>$e->summary,'actor'=>$e->actor?->name,'occurred_at'=>$e->occurred_at?->toIso8601String()]),
            'stages'=>CrmPipelineStage::query()->where('pipeline_id',$opportunity->pipeline_id)->orderBy('position')->get(['id','name','probability','is_won','is_lost']),
            'canManage'=>$request->user()?->hasPermission('crm.opportunities.manage')??false,'canActivity'=>$request->user()?->hasPermission('crm.activities.manage')??false,
        ]);
    }

    private function pipelines(): array { return CrmPipeline::query()->with('stages')->where('active',true)->orderByDesc('is_default')->get()->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'stages'=>$p->stages->map(fn($s)=>['id'=>$s->id,'name'=>$s->name,'probability'=>$s->probability,'is_won'=>$s->is_won,'is_lost'=>$s->is_lost])->values()])->all(); }
    private function organizations(): array { return CrmOrganization::query()->orderBy('name')->get(['id','name'])->map(fn($o)=>['id'=>$o->id,'name'=>$o->name])->all(); }
    private function contacts(): array { return CrmContact::query()->orderBy('display_name')->get(['id','display_name'])->map(fn($c)=>['id'=>$c->id,'name'=>$c->display_name])->all(); }
    private function owners(): array { return User::query()->where('status','active')->orderBy('name')->get(['id','name'])->map(fn($u)=>['id'=>$u->id,'name'=>$u->name])->all(); }
}
