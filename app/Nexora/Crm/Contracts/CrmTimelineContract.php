<?php

declare(strict_types=1);

namespace App\Nexora\Crm\Contracts;

use App\Models\CrmTimelineEvent;
use Illuminate\Support\Collection;

interface CrmTimelineContract
{
    /** @param array<string,mixed> $payload */
    public function record(string $subjectType, string $subjectId, string $eventType, string $title, ?string $summary = null, array $payload = [], ?int $actorId = null): CrmTimelineEvent;
    /** @return Collection<int,CrmTimelineEvent> */
    public function for(string $subjectType, string $subjectId, int $limit = 100): Collection;
}
