<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallerDatabasePortabilityTest extends TestCase
{
    #[Test]
    public function installer_supports_laravel_database_drivers_and_optional_backup_consent(): void
    {
        $view = (string) file_get_contents(resource_path('views/install/index.blade.php'));
        $routes = (string) file_get_contents(base_path('routes/web.php'));
        $installerConfig = (string) file_get_contents(config_path('installer.php'));

        self::assertStringContainsString('name="db_driver"', $view);
        self::assertStringContainsString('db_skip_backup_consent', $view);
        self::assertStringContainsString('db_skip_backup_database', $view);
        self::assertStringContainsString("route('install.cancel')", $view);
        self::assertStringContainsString("throttle:12,1", $routes);
        self::assertStringNotContainsString("throttle:4,10", $routes);
        self::assertStringNotContainsString("'pdo_mysql'", $installerConfig);
    }

    #[Test]
    public function migrations_do_not_use_mysql_only_after_column_modifiers(): void
    {
        foreach (glob(database_path('migrations/*.php')) ?: [] as $migration) {
            self::assertStringNotContainsString('->after(', (string) file_get_contents($migration), basename($migration).' uses a MySQL-specific after() modifier.');
        }
    }
}
