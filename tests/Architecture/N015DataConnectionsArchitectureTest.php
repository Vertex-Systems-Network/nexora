<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N015DataConnectionsArchitectureTest extends TestCase
{
    #[Test]
    public function installer_uses_premium_selects_aws_primary_presets_and_auxiliary_data_services(): void
    {
        $view = (string) file_get_contents(resource_path('views/install/index.blade.php'));
        $select = (string) file_get_contents(resource_path('views/components/ui/select.blade.php'));
        $installerUi = (string) file_get_contents(public_path('installer/nexora-ui.js'));
        $registry = (string) file_get_contents(app_path('Nexora/Installation/Database/DatabaseDriverRegistry.php'));
        $catalog = (string) file_get_contents(app_path('Nexora/Data/ConnectionCatalog.php'));

        self::assertStringContainsString('<x-ui.select name="db_driver"', $view);
        self::assertStringContainsString('kind="database"', $view);
        self::assertStringContainsString('<x-ui.select name="language"', $view);
        self::assertStringContainsString('kind="language"', $view);
        self::assertStringContainsString('data-nx-select="{{ $kind }}"', $select);
        self::assertStringContainsString("querySelectorAll('select[data-nx-select]')", $installerUi);
        self::assertStringContainsString("select.dataset.nxSelect === 'database'", $installerUi);
        self::assertStringContainsString('.nx-select-trigger', $view);
        self::assertStringContainsString('Additional data services', $view);
        self::assertStringContainsString('.driver-health-icon svg', $view);
        self::assertStringContainsString("'aws_rds_mysql'", $registry);
        self::assertStringContainsString("'aws_aurora_pgsql'", $registry);
        self::assertStringContainsString("'mongodb'", $catalog);
        self::assertStringContainsString("'redis'", $catalog);
        self::assertStringContainsString("'aws_dynamodb'", $catalog);
    }

    #[Test]
    public function first_installer_super_admin_is_explicitly_verified_and_data_connections_are_encrypted(): void
    {
        $installer = (string) file_get_contents(app_path('Nexora/Installation/Installer.php'));
        $model = (string) file_get_contents(app_path('Models/DataConnection.php'));

        self::assertStringContainsString("forceFill(['email_verified_at' => now()])", $installer);
        self::assertStringContainsString("'secret_payload' => 'encrypted:array'", $model);
    }

    #[Test]
    public function react_admin_feature_pages_do_not_use_native_select_controls(): void
    {
        $pages = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('js/admin/pages'), \FilesystemIterator::SKIP_DOTS));
        foreach ($pages as $file) {
            if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['tsx', 'ts'], true)) {
                continue;
            }
            self::assertStringNotContainsString('<select', (string) file_get_contents($file->getPathname()), $file->getPathname().' uses a native select.');
        }
    }
}
