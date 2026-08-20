<?php

declare(strict_types=1);

namespace Tests\Unit\Sentinel;

use App\Nexora\Security\Sentinel\Data\SecurityFinding;
use App\Nexora\Security\Sentinel\Enums\FindingSeverity;
use App\Nexora\Security\Sentinel\Enums\ScanDecision;
use App\Nexora\Security\Sentinel\Scanning\RiskEngine;
use PHPUnit\Framework\TestCase;

final class RiskEngineTest extends TestCase
{
    public function test_clean_report_is_allowed(): void
    {
        $result = (new RiskEngine())->evaluate([]);
        self::assertSame(0, $result['score']);
        self::assertSame(ScanDecision::Allow, $result['decision']);
    }

    public function test_high_risk_findings_require_review(): void
    {
        $result = (new RiskEngine())->evaluate([
            new SecurityFinding('TEST-1', FindingSeverity::High, 'test', 'High', 'Review me'),
        ]);
        self::assertSame(30, $result['score']);
        self::assertSame(ScanDecision::Review, $result['decision']);
    }

    public function test_hard_block_overrides_numeric_score(): void
    {
        $result = (new RiskEngine())->evaluate([
            new SecurityFinding('TEST-2', FindingSeverity::Low, 'test', 'Blocked', 'Hard-block policy', hardBlock: true),
        ]);
        self::assertSame(4, $result['score']);
        self::assertSame(ScanDecision::Block, $result['decision']);
    }
}
