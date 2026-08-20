<?php

declare(strict_types=1);

namespace Tests\Feature\Certification;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallationLockIntegrityCertificationTest extends TestCase
{
    #[Test]
    public function corrupt_installation_lock_returns_fail_closed_service_unavailable(): void
    {
        $path = storage_path('framework/testing-corrupt-web-install.lock');
        @unlink($path);
        config()->set('installer.bypass', false);
        config()->set('installer.lock_path', $path);

        try {
            file_put_contents($path, '{"installation_id":"broken"}');

            $this->get('/install')
                ->assertStatus(503)
                ->assertHeader('X-Nexora-Installation-Lock', 'invalid');
        } finally {
            @unlink($path);
        }
    }
}
