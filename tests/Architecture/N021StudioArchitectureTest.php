<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N021StudioArchitectureTest extends TestCase
{
    public function test_studio_is_registered_and_feature_ui_uses_nexora_library(): void
    {
        $root = dirname(__DIR__, 2);
        $config = (string) file_get_contents($root.'/config/nexora.php');
        $plan = (string) file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        $editor = (string) file_get_contents($root.'/resources/js/admin/pages/Admin/Studio/Editor.tsx');

        self::assertStringContainsString('StudioModule::class', $config);
        self::assertStringContainsString('studio.canvas.read', $config);
        self::assertStringContainsString('| N0.21 | Nexora Studio visual builder + responsive/dynamic data foundations | DONE |', $plan);
        self::assertStringContainsString('@nexora/admin-ui', $editor);
        self::assertDoesNotMatchRegularExpression('/<(button|input|select|textarea)\b/', $editor);
    }
}
