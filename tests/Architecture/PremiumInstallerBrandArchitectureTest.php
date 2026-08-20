<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PremiumInstallerBrandArchitectureTest extends TestCase
{
    #[Test]
    public function nexora_brand_assets_are_real_non_empty_files(): void
    {
        foreach ([
            'public/brand/nexora-mark.svg',
            'public/brand/nexora-logo.svg',
            'public/favicon.svg',
            'public/favicon.ico',
            'public/apple-touch-icon.png',
            'public/site.webmanifest',
        ] as $relative) {
            $path = base_path($relative);
            self::assertFileExists($path, "Missing Nexora brand artifact: {$relative}");
            self::assertGreaterThan(0, filesize($path), "Empty Nexora brand artifact: {$relative}");
        }
    }

    #[Test]
    public function react_admin_uses_lucide_react_through_the_icon_compatibility_layer(): void
    {
        $package = (string) file_get_contents(base_path('package.json'));
        $icon = (string) file_get_contents(resource_path('js/admin/components/Icon.tsx'));

        self::assertStringContainsString('"lucide-react"', $package);
        self::assertStringNotContainsString('"@untitledui/icons"', $package);
        self::assertStringContainsString('lucide-react', $icon);
    }

    #[Test]
    public function installer_is_branded_and_uses_icon_status_language(): void
    {
        $view = (string) file_get_contents(resource_path('views/install/index.blade.php'));

        self::assertStringContainsString('/brand/nexora-mark.svg', $view);
        self::assertStringContainsString('<x-lucide', $view);
        self::assertStringNotContainsString('<x-lucide-', $view);
        self::assertStringContainsString('install-stage-icon', $view);

        preg_match_all('/<x-([A-Za-z0-9_.-]+)/', $view, $matches);
        foreach (array_unique($matches[1] ?? []) as $componentName) {
            if ($componentName === 'slot') {
                continue;
            }

            $relative = 'views/components/'.str_replace('.', '/', $componentName).'.blade.php';
            self::assertFileExists(resource_path($relative), "Installer references unresolved Blade component [{$componentName}].");
        }
    }
}
