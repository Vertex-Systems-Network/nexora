<?php

declare(strict_types=1);

namespace App\Nexora\Crm\Services;

use App\Models\CrmActivity;
use App\Nexora\Automation\Contracts\AutomationEventBusContract;
use App\Nexora\Crm\Support\CrmEntityTypes;

final readonly class CrmActivityService
{
    public function __construct(private CrmTimelineService $timeline, private AutomationEventBusContract $automation) {}

    /** @param array<string,mixed> $attributes */
    public function create(string $subjectType, string $subjectId, array $attributes, ?int $actorId = null): CrmActivity
    {
        $subjectType=CrmEntityTypes::assert($subjectType);
        CrmEntityTypes::findOrFail($subjectType,$subjectId);
        $activity=CrmActivity::query()->create([
            'subject_type'=>$subjectType,'subject_id'=>$subjectId,'type'=>(string)($attributes['type']??'note'),
            'title'=>(string)($attributes['title']??'Activity'),'body'=>$attributes['body']??null,
            'owner_id'=>$attributes['owner_id']??$actorId,'created_by'=>$actorId,'occurred_at'=>$attributes['occurred_at']??now(),
            'due_at'=>$attributes['due_at']??null,'completed_at'=>$attributes['completed_at']??null,
            'external_provider'=>$attributes['external_provider']??null,'external_reference'=>$attributes['external_reference']??null,
            'metadata'=>$attributes['metadata']??null,
        ]);
        $this->timeline->record($subjectType,$subjectId,'activity.created',$activity->title,$activity->body,['activity_id'=>$activity->id,'type'=>$activity->type],$actorId);
        $this->automation->emit('crm.activity.created',['activity'=>['id'=>$activity->id,'subject_type'=>$subjectType,'subject_id'=>$subjectId,'type'=>$activity->type,'title'=>$activity->title]],'crm.activity',$activity->id);
        return $activity;
    }
}
