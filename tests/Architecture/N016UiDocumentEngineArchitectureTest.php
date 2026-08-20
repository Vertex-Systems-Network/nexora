<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class N016UiDocumentEngineArchitectureTest extends TestCase
{
    #[Test]
    public function installer_uses_stable_driver_keys_and_ui_library_controls(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Install/InstallerController.php'));
        $view = file_get_contents(resource_path('views/install/index.blade.php'));

        self::assertStringContainsString("'value' => (string) \$driver['key']", $controller);
        self::assertStringNotContainsString("groupBy('group')", $view);
        self::assertStringContainsString('<x-ui.select name="db_driver"', $view);
        self::assertDoesNotMatchRegularExpression('/<(button|input|select|textarea)\\b/i', $view);
    }

    #[Test]
    public function document_engine_has_portable_tables_and_public_admin_surface(): void
    {
        self::assertFileExists(app_path('Nexora/Modules/Core/DocumentEngineModule.php'));
        self::assertFileExists(database_path('migrations/2026_08_15_000600_add_nexora_document_engine.php'));
        self::assertFileExists(resource_path('js/admin/pages/Admin/Documents/Index.tsx'));
        self::assertFileExists(resource_path('js/admin/pages/Admin/Documents/Form.tsx'));
    }
}
