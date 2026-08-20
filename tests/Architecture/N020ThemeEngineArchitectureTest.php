<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N020ThemeEngineArchitectureTest extends TestCase
{
    public function test_theme_engine_is_sentinel_gated_non_executable_and_registered(): void
    {
        $root = dirname(__DIR__, 2);
        $config = (string) file_get_contents($root.'/config/nexora.php');
        $routes = (string) file_get_contents($root.'/routes/web.php');
        $installer = (string) file_get_contents($root.'/app/Nexora/Themes/Services/ThemePackageInstaller.php');

        self::assertStringContainsString('ThemeEngineModule::class', $config);
        self::assertStringContainsString('themes.registry.read', $config);
        self::assertStringContainsString('/appearance/themes', $routes);
        self::assertStringContainsString("decision !== 'allow'", $installer);
        self::assertStringContainsString('unsupported executable or undeclared file', $installer);
        self::assertFileExists($root.'/themes/nexora-base/theme.json');
        self::assertFileExists($root.'/database/migrations/2026_08_15_000900_add_nexora_theme_engine.php');
    }
}
