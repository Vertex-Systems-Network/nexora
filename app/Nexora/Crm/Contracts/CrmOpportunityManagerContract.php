<?php

declare(strict_types=1);

namespace App\Nexora\Crm\Contracts;

use App\Models\CrmOpportunity;
use App\Models\CrmPipelineStage;

interface CrmOpportunityManagerContract
{
    public function moveStage(CrmOpportunity $opportunity, CrmPipelineStage $stage, ?int $actorId = null): CrmOpportunity;
}
