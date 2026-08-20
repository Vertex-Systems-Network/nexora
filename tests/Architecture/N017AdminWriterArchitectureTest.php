<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

final class N017AdminWriterArchitectureTest extends TestCase
{
    #[Test]
    public function admin_interactive_controls_stay_behind_the_nexora_ui_library(): void
    {
        $root = resource_path('js/admin');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! in_array($file->getExtension(), ['ts', 'tsx'], true)) continue;
            $path = $file->getPathname();
            if (str_contains(str_replace('\\', '/', $path), '/ui/')) continue;
            $source = (string) file_get_contents($path);
            self::assertDoesNotMatchRegularExpression('/<(button|input|select|textarea)\\b/', $source, "Raw interactive control found in {$path}");
            self::assertDoesNotMatchRegularExpression('/import\\s+\\{[^}]*\\bLink\\b[^}]*\\}\\s+from\\s+[\"\\\']@inertiajs\\/react[\"\\\']/', $source, "Direct Inertia Link found in {$path}");
        }
    }

    #[Test]
    public function admin_shell_exposes_theme_sidebar_tooltips_and_writer_foundation(): void
    {
        self::assertFileExists(resource_path('js/admin/components/ThemeSwitcher.tsx'));
        self::assertFileExists(resource_path('js/admin/ui/untitled/tooltip.tsx'));
        self::assertFileExists(resource_path('js/admin/ui/untitled/nav-link.tsx'));
        self::assertFileExists(resource_path('js/admin/components/writer/BlockEditor.tsx'));
        self::assertFileExists(app_path('Nexora/Modules/Core/WriterModule.php'));

        $layout = (string) file_get_contents(resource_path('js/admin/layout/AdminLayout.tsx'));
        self::assertStringContainsString('nexora.admin.sidebar.collapsed', $layout);
        self::assertStringContainsString('<ThemeSwitcher', $layout);

        $linkButton = (string) file_get_contents(resource_path('js/admin/ui/untitled/link-button.tsx'));
        self::assertStringContainsString('"children" | "size"', $linkButton);
    }
}
