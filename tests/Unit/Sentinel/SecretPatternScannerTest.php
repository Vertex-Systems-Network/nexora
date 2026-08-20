<?php

declare(strict_types=1);

namespace Tests\Unit\Sentinel;

use App\Nexora\Security\Sentinel\Scanning\SecretPatternScanner;
use PHPUnit\Framework\TestCase;

final class SecretPatternScannerTest extends TestCase
{
    public function test_private_key_material_is_hard_blocked(): void
    {
        $contents = "-----BEGIN PRIVATE KEY-----\nnot-a-real-key\n-----END PRIVATE KEY-----\n";
        $findings = (new SecretPatternScanner())->scan('config/key.txt', $contents);

        self::assertNotEmpty($findings);
        self::assertSame('NEX-SEC-0001', $findings[0]->ruleId);
        self::assertTrue($findings[0]->hardBlock);
    }
}
