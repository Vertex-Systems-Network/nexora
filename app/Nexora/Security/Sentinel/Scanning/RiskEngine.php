<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Scanning;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\ScanDecision;

final class RiskEngine
{
    /** @param list<SecurityFinding> $findings @return array{score:int, decision:ScanDecision} */
    public function evaluate(array $findings): array
    {
        $score = 0;
        $hardBlock = false;

        foreach ($findings as $finding) {
            $score += $finding->severity->weight();
            $hardBlock = $hardBlock || $finding->hardBlock;
        }

        $score = min(100, $score);
        $decision = match (true) {
            $hardBlock, $score >= 60 => ScanDecision::Block,
            $score >= 30 => ScanDecision::Review,
            default => ScanDecision::Allow,
        };

        return ['score' => $score, 'decision' => $decision];
    }
}
