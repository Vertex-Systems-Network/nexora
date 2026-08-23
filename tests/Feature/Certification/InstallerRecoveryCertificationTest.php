<?php

declare(strict_types=1);

namespace Tests\Feature\Certification;

use App\Nexora\Installation\InstallationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallerRecoveryCertificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function completed_installation_locks_all_installer_controls(): void
    {
        $path = storage_path('framework/rc7-installed.lock');
        @unlink($path);
        config()->set('installer.bypass', false);
        config()->set('installer.lock_path', $path);
        app(InstallationState::class)->markInstalled(['installation_id'=>'rc7-test','version'=>'1.0.0-rc.63']);

        try {
            // A committed install must finish the post-install runtime identity
            // handoff before login. All mutation/status installer controls stay
            // locked immediately once installed.lock exists.
            $this->get('/install')->assertRedirect('/install/runtime-handoff');
            $this->postJson('/install/database/test', [])->assertStatus(409);
            $this->postJson('/install/cancel', ['run_id'=>str_repeat('a', 24)])->assertStatus(409);
            $this->postJson('/install/status', ['run_id'=>str_repeat('a', 24)])->assertStatus(409);
        } finally {
            @unlink($path);
        }
    }
}
