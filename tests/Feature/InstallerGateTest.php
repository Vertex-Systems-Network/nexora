<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallerGateTest extends TestCase
{
    #[Test]
    public function uninstalled_application_redirects_normal_web_requests_to_the_installer(): void
    {
        config()->set('installer.bypass', false);
        config()->set('installer.lock_path', storage_path('framework/non-existent-install.lock'));
        @unlink((string) config('installer.lock_path'));

        $this->get('/')->assertRedirect('/install');
    }

    #[Test]
    public function installed_application_closes_setup_only_installer_endpoints(): void
    {
        config()->set('installer.bypass', true);

        $this->postJson('/install/data-service/test', [
            'driver' => 'redis',
            'endpoint' => '127.0.0.1:6379',
        ])->assertNotFound();
    }
}
