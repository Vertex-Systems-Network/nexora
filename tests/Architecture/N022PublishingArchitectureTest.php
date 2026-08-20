<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N022PublishingArchitectureTest extends TestCase
{
    public function test_blog_publishing_reuses_document_seo_theme_and_ui_contracts(): void
    {
        $root = dirname(__DIR__, 2);
        $config = (string) file_get_contents($root.'/config/nexora.php');
        $plan = (string) file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        $articles = (string) file_get_contents($root.'/resources/js/admin/pages/Admin/Publishing/Articles.tsx');
        $settings = (string) file_get_contents($root.'/resources/js/admin/pages/Admin/Publishing/ArticleSettings.tsx');

        self::assertStringContainsString('PublishingModule::class', $config);
        self::assertStringContainsString('publishing.articles.read', $config);
        self::assertStringContainsString('publishing.taxonomy.manage', $config);
        self::assertStringContainsString('@nexora/admin-ui', $articles);
        self::assertStringContainsString('@nexora/admin-ui', $settings);
        self::assertDoesNotMatchRegularExpression('/<(button|input|select|textarea)\b/', $articles);
        self::assertDoesNotMatchRegularExpression('/<(button|input|select|textarea)\b/', $settings);
        self::assertStringContainsString('| N0.22 | Blog & Article publishing, authors, taxonomy, series, scheduling and archives | DONE |', $plan);
        foreach (['EXT-B01', 'EXT-P01', 'EXT-L01', 'EXT-BK01', 'EXT-PR01'] as $external) {
            self::assertStringContainsString($external, $plan);
        }
    }
}
