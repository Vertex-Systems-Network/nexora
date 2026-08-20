<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Services;

use App\Models\Document;
use App\Models\SeoEntry;
use App\Nexora\Seo\Data\SeoMetadata;

final class SeoMetadataFactory
{
    public function forDocument(Document $document, SeoEntry $entry): SeoMetadata
    {
        $directives = array_values(array_filter(array_map('strval', (array) ($entry->robots_directives ?? []))));
        $indexingState = ! $entry->robots_index ? 'excluded_noindex' : (($document->status === 'published') ? 'eligible' : 'not_published');

        return new SeoMetadata(
            title: trim((string) ($entry->seo_title ?: $document->title)),
            description: $entry->meta_description ?: $document->excerpt,
            canonicalUrl: $entry->canonical_url ?: ($entry->url_path ? url((string) $entry->url_path) : null),
            index: (bool) $entry->robots_index,
            follow: (bool) $entry->robots_follow,
            robotsDirectives: $directives,
            schemaType: (string) ($entry->schema_type ?: 'WebPage'),
            sitemapInclude: (bool) $entry->sitemap_include,
            indexingState: $indexingState,
        );
    }
}
