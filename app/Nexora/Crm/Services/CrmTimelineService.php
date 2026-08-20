<?php

declare(strict_types=1);

namespace App\Nexora\Crm\Services;

use App\Models\CrmTimelineEvent;
use App\Nexora\Crm\Contracts\CrmTimelineContract;
use App\Nexora\Crm\Support\CrmEntityTypes;
use Illuminate\Support\Collection;

final class CrmTimelineService implements CrmTimelineContract
{
    public function record(string $subjectType, string $subjectId, string $eventType, string $title, ?string $summary = null, array $payload = [], ?int $actorId = null): CrmTimelineEvent
    {
        $subjectType=CrmEntityTypes::assert($subjectType);
        CrmEntityTypes::findOrFail($subjectType,$subjectId);
        return CrmTimelineEvent::query()->create([
            'subject_type'=>$subjectType,'subject_id'=>$subjectId,'event_type'=>$eventType,'title'=>$title,
            'summary'=>$summary,'payload'=>$payload,'actor_id'=>$actorId,'occurred_at'=>now(),'created_at'=>now(),
        ]);
    }

    public function for(string $subjectType, string $subjectId, int $limit = 100): Collection
    {
        $subjectType=CrmEntityTypes::assert($subjectType);
        return CrmTimelineEvent::query()->with('actor:id,name,email')->where('subject_type',$subjectType)->where('subject_id',$subjectId)->orderByDesc('occurred_at')->limit(max(1,min(250,$limit)))->get();
    }
}
