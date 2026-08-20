<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LocalizationFoundationArchitectureTest extends TestCase
{
    #[Test]
    public function localization_is_available_before_laravel_and_through_the_admin_runtime(): void
    {
        self::assertFileExists(base_path('bootstrap/nexora-locales.php'));
        self::assertFileExists(config_path('localization.php'));
        self::assertFileExists(app_path('Http/Middleware/SetLocale.php'));
        self::assertFileExists(app_path('Http/Controllers/LocaleController.php'));
        self::assertFileExists(resource_path('js/admin/components/LanguageSwitcher.tsx'));

        $config = require config_path('localization.php');
        self::assertSame(['en', 'ur', 'tr', 'ar', 'ru'], array_keys($config['supported']));
        self::assertSame('rtl', $config['supported']['ur']['dir']);
        self::assertSame('rtl', $config['supported']['ar']['dir']);

        $bootstrap = (string) file_get_contents(public_path('nexora-bootstrap.php'));
        self::assertStringContainsString('nexora-locales.php', $bootstrap);
        self::assertStringContainsString('bootstrap-language', $bootstrap);
        self::assertStringContainsString('dir="<?= nxh($nxDirection) ?>"', $bootstrap);
        self::assertStringContainsString('upload-surface', $bootstrap);
    }
}
