<?php

declare(strict_types=1);

namespace Tests\Feature\Certification;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SourceStatusRedactionCertificationTest extends TestCase
{
    private string $installationLockPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->installationLockPath = storage_path('framework/source-status-redaction-installed.lock');
        @unlink($this->installationLockPath);
        config()->set('installer.bypass', false);
        config()->set('installer.lock_path', $this->installationLockPath);
    }

    protected function tearDown(): void
    {
        @unlink($this->installationLockPath);
        parent::tearDown();
    }

    #[Test]
    public function public_source_status_is_redacted_and_cannot_acknowledge_without_a_token(): void
    {
        $response = $this->getJson('/install/source-status');

        self::assertContains($response->status(), [200, 409]);
        $response
            ->assertJsonPath('diagnostic_detail', 'redacted')
            ->assertJsonPath('acknowledgement_token_required', true)
            ->assertJsonMissingPath('installer_path')
            ->assertJsonMissingPath('installer_sha256')
            ->assertJsonMissingPath('source_set_fingerprint')
            ->assertJsonMissingPath('critical_source_file_results')
            ->assertJsonMissingPath('runtime_class_results')
            ->assertJsonMissingPath('activation_handshake.nonce')
            ->assertHeader('X-Nexora-Source-Ack', 'token-required');
    }

    #[Test]
    public function invalid_activation_token_is_rejected_without_detailed_source_disclosure(): void
    {
        $response = $this
            ->withHeader('X-Nexora-Activation-Token', str_repeat('0', 64))
            ->getJson('/install/source-status');

        $response
            ->assertForbidden()
            ->assertJsonPath('diagnostic_detail', 'redacted')
            ->assertJsonMissingPath('installer_path')
            ->assertJsonMissingPath('source_set_fingerprint')
            ->assertHeader('X-Nexora-Source-Ack', 'denied');
    }
}
