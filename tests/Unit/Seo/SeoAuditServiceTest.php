<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use App\Models\Document;
use App\Models\SeoEntry;
use App\Nexora\Seo\Services\SeoAuditService;
use PHPUnit\Framework\TestCase;

final class SeoAuditServiceTest extends TestCase
{
    public function test_published_noindex_sitemap_conflict_is_reported_without_fake_score(): void
    {
        $document = new Document(['title' => 'Test', 'status' => 'published']);
        $entry = new SeoEntry([
            'seo_title' => 'Test',
            'meta_description' => 'Description',
            'canonical_url' => 'https://example.test/test',
            'robots_index' => false,
            'robots_follow' => true,
            'sitemap_include' => true,
            'schema_type' => 'WebPage',
        ]);

        $issues = (new SeoAuditService())->document($document, $entry);
        $codes = array_column($issues, 'code');
        self::assertContains('published_noindex', $codes);
        self::assertContains('sitemap_noindex_conflict', $codes);
    }
}
