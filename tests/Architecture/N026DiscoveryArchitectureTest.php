<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class N026DiscoveryArchitectureTest extends TestCase
{
    public function test_search_analytics_and_crawler_remain_first_party_contract_layers_without_external_scope_regression(): void
    {
        $root = dirname(__DIR__, 2);
        $config = (string) file_get_contents($root.'/config/nexora.php');
        $plan = (string) file_get_contents($root.'/docs/NEXORA_PLAN_STATUS.md');
        $dashboard = (string) file_get_contents($root.'/resources/js/admin/pages/Admin/Discovery/Index.tsx');
        $crawl = (string) file_get_contents($root.'/resources/js/admin/pages/Admin/Discovery/Crawl.tsx');
        $migration = (string) file_get_contents($root.'/database/migrations/2026_08_15_001300_add_nexora_search_analytics_crawler.php');

        self::assertStringContainsString('SearchAnalyticsModule::class', $config);
        foreach (['search.index.read','analytics.metrics.read','seo.crawler.run'] as $capability) self::assertStringContainsString($capability, $config);
        self::assertStringContainsString('nx_search_index', $migration);
        self::assertStringContainsString('nx_analytics_events', $migration);
        self::assertStringContainsString('nx_crawl_runs', $migration);
        self::assertStringContainsString('@nexora/admin-ui', $dashboard);
        self::assertStringContainsString('@nexora/admin-ui', $crawl);
        self::assertDoesNotMatchRegularExpression('/<(button|input|select|textarea)\b/', $dashboard);
        self::assertDoesNotMatchRegularExpression('/<(button|input|select|textarea)\b/', $crawl);
        self::assertStringContainsString('| N0.26 | Search, content analytics, SEO crawler/content audit | DONE |', $plan);
        self::assertStringContainsString('| N0.27 | Automation/workflow engine, triggers/conditions/actions/webhooks |', $plan);
        foreach (['EXT-B01','EXT-P01','EXT-L01','EXT-BK01','EXT-PR01'] as $external) self::assertStringContainsString($external, $plan);
    }
}
