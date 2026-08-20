<?php

declare(strict_types=1);

namespace App\Nexora\Security\SupplyChain\Contracts;

use App\Models\SupplyChainArtifact;

interface SandboxAdapterContract
{
    /** @return array{profile:string,execution_allowed:bool,reason:string} */
    public function evaluate(SupplyChainArtifact $artifact): array;
}
