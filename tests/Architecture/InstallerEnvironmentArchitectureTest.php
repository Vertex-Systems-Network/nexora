<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallerEnvironmentArchitectureTest extends TestCase
{
    #[Test]
    public function source_package_contains_an_environment_template(): void
    {
        $path = base_path('.env.example');

        self::assertFileExists($path);
        self::assertGreaterThan(0, filesize($path));
        self::assertStringContainsString('DB_CONNECTION=mysql', (string) file_get_contents($path));
    }

    #[Test]
    public function installer_bootstrap_can_use_protected_environment_storage(): void
    {
        $source = (string) file_get_contents(base_path('bootstrap/nexora-installer-bootstrap.php'));

        self::assertStringContainsString('storage/app/nexora/environment', $source);
        self::assertStringContainsString('NEXORA_ENV_FALLBACK_PATH', $source);
        self::assertStringContainsString('NEXORA_ENV_ACTIVE_MODE', $source);
        self::assertStringNotContainsString('cannot create or write the .env file', $source);
    }

    #[Test]
    public function both_cli_and_http_load_the_installer_environment_bootstrap(): void
    {
        foreach ([base_path('artisan'), public_path('index.php')] as $entryPoint) {
            self::assertStringContainsString(
                'nexora-installer-bootstrap.php',
                (string) file_get_contents($entryPoint),
            );
        }
    }

    #[Test]
    public function environment_writer_persists_the_active_location_marker(): void
    {
        $source = (string) file_get_contents(app_path('Nexora/Installation/EnvironmentWriter.php'));

        self::assertStringContainsString('environment_fallback_path', $source);
        self::assertStringContainsString('environment_marker_path', $source);
        self::assertStringContainsString('writeActiveMarker', $source);
    }
}
