<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InstallerVisualRegressionArchitectureTest extends TestCase
{
    #[Test]
    public function requirement_cards_and_inputs_remain_shrinkable(): void
    {
        $view = (string) file_get_contents(resource_path('views/install/index.blade.php'));

        self::assertStringContainsString('grid-template-columns:repeat(2,minmax(0,1fr))', $view);
        self::assertStringContainsString('.check>div{min-width:0;overflow-wrap:anywhere}', $view);
        self::assertStringContainsString('.check>span{flex:0 0 auto;white-space:nowrap}', $view);
    }

    #[Test]
    public function dark_theme_uses_configurable_control_and_text_colors(): void
    {
        $view = (string) file_get_contents(resource_path('views/install/index.blade.php'));

        self::assertStringContainsString('background:var(--control);color:var(--text);padding:0 15px', $view);
        self::assertStringContainsString('.advanced summary{cursor:pointer;font-size:12px;font-weight:700;color:var(--text-strong)}', $view);
    }

    #[Test]
    public function narrow_installation_header_wraps_without_clipping_theme_controls(): void
    {
        $view = (string) file_get_contents(resource_path('views/install/index.blade.php'));

        self::assertStringContainsString('@media(max-width:360px)', $view);
        self::assertStringContainsString('.top{height:auto;min-height:68px;flex-wrap:wrap;gap:8px;padding:12px 16px}', $view);
        self::assertStringContainsString('.header-actions{width:100%;justify-content:space-between}', $view);
    }

    #[Test]
    public function password_feedback_tracks_autofill_and_readiness(): void
    {
        $view = (string) file_get_contents(resource_path('views/install/index.blade.php'));

        self::assertStringContainsString('aria-label="Super Admin password strength"', $view);
        self::assertStringContainsString("window.addEventListener('pageshow',syncPassword)", $view);
        self::assertStringContainsString('function passwordReady(){syncPassword();', $view);
    }
}
