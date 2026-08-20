<?php

declare(strict_types=1);

namespace App\Nexora\Crm\Services;

use App\Models\CrmOpportunity;
use App\Models\CrmOpportunityStageHistory;
use App\Models\CrmPipelineStage;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use App\Nexora\Crm\Contracts\CrmOpportunityManagerContract;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CrmOpportunityService implements CrmOpportunityManagerContract
{
    public function __construct(private CrmTimelineService $timeline, private AutomationEventBusContract $automation) {}

    public function moveStage(CrmOpportunity $opportunity, CrmPipelineStage $stage, ?int $actorId = null): CrmOpportunity
    {
        return DB::transaction(function () use ($opportunity,$stage,$actorId): CrmOpportunity {
            /** @var CrmOpportunity $locked */
            $locked=CrmOpportunity::query()->lockForUpdate()->findOrFail($opportunity->id);
            /** @var CrmPipelineStage $target */
            $target=CrmPipelineStage::query()->findOrFail($stage->id);
            if ($target->pipeline_id !== $locked->pipeline_id) throw new InvalidArgumentException('The selected stage belongs to a different CRM pipeline.');
            $from=$locked->stage_id;
            if ($from === $target->id) return $locked->refresh();
            $status=$target->is_won ? 'won' : ($target->is_lost ? 'lost' : 'open');
            $locked->update([
                'stage_id'=>$target->id,'probability'=>$target->probability,'status'=>$status,
                'won_at'=>$target->is_won ? now() : null,'lost_at'=>$target->is_lost ? now() : null,
            ]);
            CrmOpportunityStageHistory::query()->create(['opportunity_id'=>$locked->id,'from_stage_id'=>$from,'to_stage_id'=>$target->id,'changed_by'=>$actorId,'changed_at'=>now()]);
            $this->timeline->record('opportunity',$locked->id,'opportunity.stage_changed','Stage changed to '.$target->name,null,['from_stage_id'=>$from,'to_stage_id'=>$target->id,'status'=>$status],$actorId);
            $payload=['opportunity'=>['id'=>$locked->id,'name'=>$locked->name,'pipeline_id'=>$locked->pipeline_id,'stage_id'=>$target->id,'stage'=>$target->name,'status'=>$status,'amount_minor'=>$locked->amount_minor,'currency'=>$locked->currency,'owner_id'=>$locked->owner_id]];
            $this->automation->emit('crm.opportunity.stage_changed',$payload,'crm.opportunity',$locked->id);
            if ($status==='won') $this->automation->emit('crm.opportunity.won',$payload,'crm.opportunity',$locked->id);
            if ($status==='lost') $this->automation->emit('crm.opportunity.lost',$payload,'crm.opportunity',$locked->id);
            return $locked->refresh();
        });
    }
}
