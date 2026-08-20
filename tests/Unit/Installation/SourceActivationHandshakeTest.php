<?php

declare(strict_types=1);

namespace Tests\Unit\Installation;

use App\Nexora\Installation\SourceActivationHandshake;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SourceActivationHandshakeTest extends TestCase
{
    #[Test]
    public function web_acknowledgement_requires_the_current_one_time_token_and_runtime_generation(): void
    {
        $receipt = storage_path('framework/testing-source-activation-receipt.json');
        $ack = storage_path('framework/testing-source-activation-web-ack.json');
        $tokenPath = storage_path('framework/testing-source-activation-web-ack.token');
        @unlink($receipt);
        @unlink($ack);
        @unlink($tokenPath);
        config()->set('installer.source.activation_receipt_path', $receipt);
        config()->set('installer.source.web_ack_path', $ack);
        config()->set('installer.source.web_ack_token_path', $tokenPath);
        config()->set('installer.source.activation_ttl_seconds', 900);

        $source = [
            'status' => 'pass',
            'platform_version' => '1.0.0-rc.69',
            'running_protocol' => 'v5.4',
            'running_generation' => 'n1-v5.4',
            'source_set_fingerprint' => str_repeat('a', 64),
            'runtime_class_fingerprint' => str_repeat('c', 64),
            'runtime_classes_matched' => 12,
            'runtime_classes_total' => 12,
            'critical_source_files' => 14,
        ];

        try {
            $handshake = app(SourceActivationHandshake::class);
            $issued = $handshake->issueCliActivation($source);
            self::assertSame('pending-web-ack', $issued['status']);
            self::assertSame('pending', $handshake->inspect($source)['status']);

            $token = $handshake->webAckToken($source);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $token);

            $denied = $handshake->acknowledgeWeb($source, str_repeat('0', 64));
            self::assertFalse($denied['acknowledgement_authorized']);
            self::assertSame('pending', $denied['status']);
            self::assertFileDoesNotExist($ack);

            $acknowledged = $handshake->acknowledgeWeb($source, $token);
            self::assertSame('pass', $acknowledged['status']);
            self::assertTrue($acknowledged['web_ack_valid']);
            self::assertTrue($acknowledged['acknowledgement_authorized']);
            self::assertFileDoesNotExist($tokenPath, 'The bearer token must be single-use.');

            $otherRuntime = [...$source, 'runtime_class_fingerprint' => str_repeat('d', 64)];
            self::assertSame('pending', $handshake->inspect($otherRuntime)['status']);
        } finally {
            @unlink($receipt);
            @unlink($ack);
            @unlink($tokenPath);
        }
    }
}
