<?php

declare(strict_types=1);

namespace App\Nexora\Crm\Services;

use App\Models\CrmLead;
use App\Models\CrmOpportunity;
use App\Models\CrmOpportunityStageHistory;
use App\Models\CrmPipeline;
use App\Models\CrmPipelineStage;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CrmLeadConversionService
{
    public function __construct(private CrmTimelineService $timeline, private AutomationEventBusContract $automation) {}

    public function convert(CrmLead $lead, ?CrmPipeline $pipeline = null, ?CrmPipelineStage $stage = null, ?int $actorId = null): CrmOpportunity
    {
        return DB::transaction(function () use ($lead,$pipeline,$stage,$actorId): CrmOpportunity {
            /** @var CrmLead $locked */
            $locked=CrmLead::query()->lockForUpdate()->findOrFail($lead->id);
            if ($locked->converted_at !== null || $locked->converted_opportunity_id !== null) throw new InvalidArgumentException('This lead has already been converted.');
            $pipeline ??= CrmPipeline::query()->where('active',true)->orderByDesc('is_default')->first();
            if (! $pipeline) throw new InvalidArgumentException('Create an active CRM pipeline before converting a lead.');
            $stage ??= CrmPipelineStage::query()->where('pipeline_id',$pipeline->id)->orderBy('position')->first();
            if (! $stage || $stage->pipeline_id !== $pipeline->id) throw new InvalidArgumentException('The selected CRM pipeline has no valid starting stage.');
            $opportunity=CrmOpportunity::query()->create([
                'pipeline_id'=>$pipeline->id,'stage_id'=>$stage->id,'organization_id'=>$locked->organization_id,'contact_id'=>$locked->contact_id,
                'name'=>$locked->title,'status'=>$stage->is_won?'won':($stage->is_lost?'lost':'open'),'currency'=>$locked->currency,
                'amount_minor'=>$locked->estimated_value_minor,'probability'=>$stage->probability,'source'=>$locked->source,'owner_id'=>$locked->owner_id,
                'won_at'=>$stage->is_won?now():null,'lost_at'=>$stage->is_lost?now():null,'metadata'=>['converted_from_lead_id'=>$locked->id],
            ]);
            CrmOpportunityStageHistory::query()->create(['opportunity_id'=>$opportunity->id,'from_stage_id'=>null,'to_stage_id'=>$stage->id,'changed_by'=>$actorId,'changed_at'=>now()]);
            $locked->update(['status'=>'converted','converted_opportunity_id'=>$opportunity->id,'converted_at'=>now()]);
            $this->timeline->record('lead',$locked->id,'lead.converted','Lead converted to opportunity',null,['opportunity_id'=>$opportunity->id],$actorId);
            $this->timeline->record('opportunity',$opportunity->id,'opportunity.created','Opportunity created from lead',null,['lead_id'=>$locked->id],$actorId);
            $this->automation->emit('crm.lead.converted',['lead'=>['id'=>$locked->id,'title'=>$locked->title],'opportunity'=>['id'=>$opportunity->id,'name'=>$opportunity->name,'stage_id'=>$stage->id,'amount_minor'=>$opportunity->amount_minor,'currency'=>$opportunity->currency]],'crm.lead',$locked->id);
            $this->automation->emit('crm.opportunity.created',['opportunity'=>['id'=>$opportunity->id,'name'=>$opportunity->name,'pipeline_id'=>$pipeline->id,'stage_id'=>$stage->id,'status'=>$opportunity->status,'amount_minor'=>$opportunity->amount_minor,'currency'=>$opportunity->currency]],'crm.opportunity',$opportunity->id);
            return $opportunity;
        });
    }
}
