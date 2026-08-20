<?php

declare(strict_types=1);

namespace App\Nexora\Security\SupplyChain\Services;

use App\Models\SupplyChainArtifact;
use App\Nexora\Security\SupplyChain\Contracts\SandboxAdapterContract;

final class PolicySandboxAdapter implements SandboxAdapterContract
{
    public function evaluate(SupplyChainArtifact $artifact): array
    {
        $tier = (string) $artifact->trust_tier;
        $signatureValid = $artifact->signature_status === 'verified';
        return match ($tier) {
            'core' => ['profile'=>'core','execution_allowed'=>true,'reason'=>'Built-in Nexora code is governed by the core release boundary.'],
            'trusted' => ['profile'=>'capability-gated','execution_allowed'=>$signatureValid,'reason'=>$signatureValid ? 'Trusted publisher signature verified; runtime capabilities remain mandatory.' : 'Trusted execution requires a verified artifact signature.'],
            'verified' => ['profile'=>'restricted','execution_allowed'=>false,'reason'=>'Verified publisher artifacts remain non-executable until a future isolation adapter explicitly authorizes execution.'],
            default => ['profile'=>'deny-execution','execution_allowed'=>false,'reason'=>'Untrusted or unsigned artifacts cannot execute.'],
        };
    }
}
