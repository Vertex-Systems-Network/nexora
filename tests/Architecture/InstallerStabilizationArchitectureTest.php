<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallerStabilizationArchitectureTest extends TestCase
{
    #[Test]
    public function frontend_uses_inertia_v3_page_resolver_instead_of_legacy_manual_resolution(): void
    {
        $app = (string) file_get_contents(resource_path('js/app.tsx'));

        self::assertStringContainsString('pages:', $app);
        self::assertStringContainsString('withApp(app', $app);
        self::assertStringNotContainsString('resolvePageComponent', $app);
    }

    #[Test]
    public function source_deployment_bootstrap_does_not_use_database_credentials_as_its_unlock_secret(): void
    {
        $bootstrap = (string) file_get_contents(public_path('nexora-bootstrap.php'));

        self::assertStringNotContainsString('name="db_host"', $bootstrap);
        self::assertStringNotContainsString('name="db_password"', $bootstrap);
        self::assertStringContainsString('deployment-access.key', $bootstrap);
    }

    #[Test]
    public function installer_requires_backup_download_and_explicit_reset_consent_for_existing_databases(): void
    {
        $controller = (string) file_get_contents(app_path('Http/Controllers/Install/InstallerController.php'));
        $installer = (string) file_get_contents(app_path('Nexora/Installation/Installer.php'));
        $backups = (string) file_get_contents(app_path('Nexora/Installation/DatabaseBackupManager.php'));
        $view = (string) file_get_contents(resource_path('views/install/index.blade.php'));

        self::assertStringContainsString('install.database.backup.stream', $view);
        self::assertStringContainsString('db_reset_existing', $controller);
        self::assertStringContainsString('db_backup_confirmed', $installer);
        self::assertStringContainsString("'downloaded_at'", $backups);
        self::assertStringContainsString('authorize Nexora to empty this database', $view);
    }

    #[Test]
    public function installer_identity_exposes_password_visibility_strength_consent_and_live_confirmation(): void
    {
        $view = (string) file_get_contents(resource_path('views/install/index.blade.php'));

        self::assertStringContainsString('data-password-toggle', $view);
        self::assertStringContainsString('password-strength-bar', $view);
        self::assertStringContainsString('password_strength_consent', $view);
        self::assertStringContainsString('Passwords match.', $view);
        self::assertStringContainsString('name="language"', $view);
    }
}
