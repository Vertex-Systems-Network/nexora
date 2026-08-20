<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N025MediaDistributionArchitectureTest extends TestCase
{
    public function test_media_and_distribution_use_platform_contracts_and_ui_library(): void
    {
        $root=dirname(__DIR__,2);
        $config=(string) file_get_contents($root.'/config/nexora.php');
        $plan=(string) file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        $media=(string) file_get_contents($root.'/resources/js/admin/pages/Admin/Media/Index.tsx');
        $distribution=(string) file_get_contents($root.'/resources/js/admin/pages/Admin/Distribution/Index.tsx');
        self::assertStringContainsString('MediaDistributionModule::class',$config);
        self::assertStringContainsString('media.assets.read',$config);
        self::assertStringContainsString('distribution.newsletter.send',$config);
        self::assertStringContainsString('@nexora/admin-ui',$media);
        self::assertStringContainsString('@nexora/admin-ui',$distribution);
        self::assertDoesNotMatchRegularExpression('/<(button|input|select|textarea)\b/',$media);
        self::assertDoesNotMatchRegularExpression('/<(button|input|select|textarea)\b/',$distribution);
        self::assertStringContainsString('| N0.25 | Media, newsletter, syndication and distribution adapters | DONE |',$plan);
        self::assertStringContainsString('| N0.26 | Search, content analytics, SEO crawler/content audit | DONE |',$plan);
        self::assertStringContainsString('| N0.27 | Automation/workflow engine, triggers/conditions/actions/webhooks |',$plan);
    }
}
