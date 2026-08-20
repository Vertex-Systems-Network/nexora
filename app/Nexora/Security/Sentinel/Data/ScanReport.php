<?php

declare(strict_types=1);

namespace App\Nexora\Security\Sentinel\Data;

use App\Nexora\Security\Sentinel\Enums\FindingSeverity;
use App\Nexora\Security\Sentinel\Enums\ScanDecision;

final readonly class ScanReport
{
    /** @param list<SecurityFinding> $findings @param array<string, mixed> $manifest @param array<string, mixed> $metrics */
    public function __construct(
        public string $sourceName,
        public string $sourceSha256,
        public ScanDecision $decision,
        public int $riskScore,
        public array $findings,
        public array $manifest = [],
        public array $metrics = [],
    ) {
    }

    /** @return array<string, int> */
    public function severityCounts(): array
    {
        $counts = array_fill_keys(array_map(static fn (FindingSeverity $severity): string => $severity->value, FindingSeverity::cases()), 0);

        foreach ($this->findings as $finding) {
            $counts[$finding->severity->value]++;
        }

        return $counts;
    }
}
