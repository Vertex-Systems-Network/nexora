<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Nexora\Installation\InstallationState;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallationStateTest extends TestCase
{
    #[Test]
    public function installation_lock_is_the_persistent_source_of_truth_when_bypass_is_off(): void
    {
        $path = storage_path('framework/testing-nexora-install.lock');
        @unlink($path);
        config()->set('installer.bypass', false);
        config()->set('installer.lock_path', $path);

        $state = app(InstallationState::class);
        self::assertFalse($state->isInstalled());

        $state->markInstalled(['installation_id' => 'test-install', 'version' => '1.0.0-rc.63']);
        self::assertTrue($state->isInstalled());
        self::assertSame('test-install', $state->metadata()['installation_id'] ?? null);

        @unlink($path);
    }
}
