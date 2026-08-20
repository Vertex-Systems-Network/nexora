<?php

declare(strict_types=1);

namespace Tests\Unit\Discovery;

use App\Nexora\Discovery\Crawler\PageInspector;
use PHPUnit\Framework\TestCase;

final class PageInspectorTest extends TestCase
{
    public function test_it_extracts_search_and_link_signals_without_executing_markup(): void
    {
        $html = <<<'HTML'
<!doctype html><html><head>
<title>Example article</title>
<meta name="description" content="A useful article description.">
<meta name="robots" content="index,follow">
<link rel="canonical" href="https://example.test/articles/example">
<script type="application/ld+json">{"@context":"https://schema.org","@type":"Article"}</script>
</head><body><h1>Example article</h1><p>This is visible content for the crawler inspection test.</p>
<a href="/blog">Blog</a><a href="https://external.test/source">Source</a></body></html>
HTML;
        $result = (new PageInspector())->inspect($html, 'https://example.test/articles/example', 'example.test');
        self::assertSame('Example article', $result['title']);
        self::assertSame('A useful article description.', $result['meta_description']);
        self::assertSame('https://example.test/articles/example', $result['canonical_url']);
        self::assertSame(1, $result['h1_count']);
        self::assertTrue($result['has_schema']);
        self::assertContains('https://example.test/blog', $result['internal_links']);
        self::assertContains('https://external.test/source', $result['external_links']);
    }
}
