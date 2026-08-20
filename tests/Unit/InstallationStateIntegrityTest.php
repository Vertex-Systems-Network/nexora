<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Installation\InstallationState;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class InstallationStateIntegrityTest extends TestCase
{
    #[Test]
    public function fresh_installation_lock_is_sealed_and_valid(): void
    {
        $path = storage_path('framework/testing-nexora-sealed-install.lock');
        @unlink($path);
        config()->set('installer.bypass', false);
        config()->set('installer.lock_path', $path);
        config()->set('installer.lock_schema', 2);

        try {
            $state = app(InstallationState::class);
            $state->markInstalled([
                'installation_id' => 'sealed-install',
                'version' => '1.0.0-rc.63',
            ]);

            $inspection = $state->inspect();
            self::assertSame('sealed-valid', $inspection['status']);
            self::assertTrue($inspection['valid']);
            self::assertTrue($inspection['sealed']);
            self::assertSame(2, $inspection['schema']);

            $raw = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) ($raw['_lock_sha256'] ?? ''));
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function tampered_lock_keeps_installer_closed_and_metadata_fails_closed(): void
    {
        $path = storage_path('framework/testing-nexora-tampered-install.lock');
        @unlink($path);
        config()->set('installer.bypass', false);
        config()->set('installer.lock_path', $path);
        config()->set('installer.lock_schema', 2);

        try {
            $state = app(InstallationState::class);
            $state->markInstalled([
                'installation_id' => 'tampered-install',
                'version' => '1.0.0-rc.63',
            ]);

            $raw = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $raw['version'] = 'tampered';
            file_put_contents($path, json_encode($raw, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            self::assertTrue($state->isInstalled(), 'A corrupt lock must not reopen the installer.');
            self::assertSame('invalid', $state->inspect()['status']);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('failed integrity validation');
            $state->metadata();
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function legacy_unsealed_lock_remains_readable_until_next_metadata_write(): void
    {
        $path = storage_path('framework/testing-nexora-legacy-install.lock');
        @unlink($path);
        config()->set('installer.bypass', false);
        config()->set('installer.lock_path', $path);

        try {
            file_put_contents($path, json_encode([
                'installation_id' => 'legacy-install',
                'version' => '1.0.0-rc.62',
                'installed_at' => '2026-08-19T00:00:00+00:00',
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            $state = app(InstallationState::class);
            $inspection = $state->inspect();
            self::assertSame('legacy-unsealed', $inspection['status']);
            self::assertTrue($inspection['valid']);
            self::assertFalse($inspection['sealed']);
            self::assertSame('legacy-install', $state->metadata()['installation_id'] ?? null);

            $state->updateMetadata(['version' => '1.0.0-rc.63']);
            self::assertSame('sealed-valid', $state->inspect()['status']);
        } finally {
            @unlink($path);
        }
    }
}
