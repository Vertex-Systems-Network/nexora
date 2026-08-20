<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallationProgressArchitectureTest extends TestCase
{
    #[Test]
    public function main_installer_exposes_observable_streaming_progress(): void
    {
        $controller = (string) file_get_contents(app_path('Http/Controllers/Install/InstallerController.php'));
        $installer = (string) file_get_contents(app_path('Nexora/Installation/Installer.php'));
        $view = (string) file_get_contents(resource_path('views/install/index.blade.php'));
        $routes = (string) file_get_contents(base_path('routes/web.php'));

        self::assertStringContainsString("Route::post('/stream'", $routes);
        self::assertStringContainsString('application/x-ndjson', $controller);
        self::assertStringContainsString("'Database migrations'", $installer);
        self::assertStringContainsString("'Super Admin account'", $installer);
        self::assertStringContainsString("'Nexora runtime'", $installer);
        self::assertStringContainsString('install-progress', $view);
        self::assertStringContainsString("route('install.stream')", $view);
        self::assertStringContainsString('response.body.getReader()', $view);
        self::assertLessThan(
            strpos($installer, '$this->state->markInstalled(['),
            strpos($installer, "Artisan::call('optimize:clear')"),
            'The permanent installed lock must remain the final mutation after cleanup.',
        );
    }
}
