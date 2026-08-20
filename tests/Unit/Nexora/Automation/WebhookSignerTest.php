<?php

declare(strict_types=1);

namespace Tests\Unit\Nexora\Automation;

use App\Nexora\Automation\Services\WebhookSigner;
use PHPUnit\Framework\TestCase;

final class WebhookSignerTest extends TestCase
{
    public function test_hmac_signature_is_deterministic_and_tamper_evident(): void
    {
        $signer = new WebhookSigner();
        $signature = $signer->signature('secret-key','1700000000','{"ok":true}');
        self::assertStringStartsWith('v1=', $signature);
        self::assertTrue($signer->verify('secret-key','1700000000','{"ok":true}', $signature));
        self::assertFalse($signer->verify('secret-key','1700000000','{"ok":false}', $signature));
    }
}
