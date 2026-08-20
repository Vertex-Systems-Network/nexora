<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Services;

use App\Models\Document;
use App\Models\SeoEntry;

final class SeoAuditService
{
    /** @return list<array{severity:string,code:string,title:string,description:string}> */
    public function document(Document $document, SeoEntry $entry): array
    {
        $issues = [];
        $title = trim((string) ($entry->seo_title ?: $document->title));
        $description = trim((string) ($entry->meta_description ?: $document->excerpt));
        $canonical = trim((string) ($entry->canonical_url ?? ''));
        $publicPath = trim((string) ($entry->url_path ?? ''));

        if ($title === '') $issues[] = $this->issue('high', 'title_missing', 'Search title is missing', 'Add a concise human-readable search title. Nexora does not use a synthetic SEO score.');
        if ($description === '') $issues[] = $this->issue('medium', 'description_missing', 'Meta description is missing', 'Add a useful summary for search snippets and distribution previews.');
        if ($canonical !== '' && filter_var($canonical, FILTER_VALIDATE_URL) === false) $issues[] = $this->issue('high', 'canonical_invalid', 'Canonical URL is invalid', 'Use an absolute http or https canonical URL.');
        if ($document->status === 'published' && $canonical === '' && $publicPath === '') $issues[] = $this->issue('high', 'public_url_missing', 'Published content has no public URL', 'Set a canonical URL or public URL path before expecting sitemap inclusion.');
        if ($document->status === 'published' && ! $entry->robots_index) $issues[] = $this->issue('medium', 'published_noindex', 'Published content is excluded from indexing', 'Keep noindex only when exclusion is intentional.');
        if ($entry->sitemap_include && ! $entry->robots_index) $issues[] = $this->issue('high', 'sitemap_noindex_conflict', 'Sitemap and noindex conflict', 'Nexora excludes noindex entries from generated sitemaps even if the sitemap switch is enabled.');
        if (($entry->schema_type ?? 'WebPage') === '') $issues[] = $this->issue('medium', 'schema_type_missing', 'Schema type is missing', 'Choose the semantic Schema.org type that accurately represents this page.');

        return $issues;
    }

    /** @return array{severity:string,code:string,title:string,description:string} */
    private function issue(string $severity, string $code, string $title, string $description): array
    {
        return compact('severity', 'code', 'title', 'description');
    }
}
