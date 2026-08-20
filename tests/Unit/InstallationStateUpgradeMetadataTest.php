<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Installation\InstallationState;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallationStateUpgradeMetadataTest extends TestCase
{
    #[Test]
    public function upgrade_metadata_preserves_installation_identity_and_original_install_time(): void
    {
        $path=storage_path('framework/testing-nexora-upgrade-install.lock');
        @unlink($path);
        config()->set('installer.bypass',false);
        config()->set('installer.lock_path',$path);
        $state=app(InstallationState::class);
        $state->markInstalled(['installation_id'=>'install-1','version'=>'1.0.0-rc.12']);
        $before=$state->metadata();
        self::assertIsArray($before);

        $state->updateMetadata(['installation_id'=>'replace-me','installed_at'=>'replace-me','previous_version'=>'1.0.0-rc.12','version'=>'1.0.0-rc.13','last_upgrade_id'=>'upgrade-1','upgraded_at'=>'2026-08-17T00:00:00+00:00']);
        $after=$state->metadata();
        self::assertSame('install-1',$after['installation_id']??null);
        self::assertSame($before['installed_at']??null,$after['installed_at']??null);
        self::assertSame('1.0.0-rc.12',$after['previous_version']??null);
        self::assertSame('1.0.0-rc.13',$after['version']??null);
        @unlink($path);
    }
}
