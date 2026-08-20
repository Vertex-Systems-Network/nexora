<?php

declare(strict_types=1);
namespace App\Nexora\Enterprise\Services;
use App\Models\EnterpriseAuditEvent;use Illuminate\Support\Str;
final class EnterpriseAuditRecorder { public function record(string $eventType,?string $organizationId=null,?int $actorId=null,?string $subjectType=null,?string $subjectId=null,array $payload=[]):EnterpriseAuditEvent{return EnterpriseAuditEvent::query()->create(['id'=>(string)Str::uuid(),'organization_id'=>$organizationId,'event_type'=>$eventType,'actor_user_id'=>$actorId,'subject_type'=>$subjectType,'subject_id'=>$subjectId,'payload'=>$payload,'occurred_at'=>now()]);}}
