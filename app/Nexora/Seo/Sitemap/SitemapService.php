<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Sitemap;

use App\Models\AuthorProfile;
use App\Models\ContentSeries;
use App\Models\Document;
use App\Models\SeoEntry;
use App\Models\TaxonomyTerm;
use App\Nexora\Publishing\Services\PublicDocumentVisibility;

final readonly class SitemapService
{
    public function __construct(private PublicDocumentVisibility $visibility) {}

    /** @return list<array{loc:string,lastmod:?string}> */
    public function urls(): array
    {
        $base = rtrim((string) config('app.url', ''), '/');
        $protected = $this->visibility->protectedDocumentIds();
        $entries = SeoEntry::query()
            ->where('sitemap_include', true)
            ->where('robots_index', true)
            ->where(function ($query): void { $query->whereNotNull('canonical_url')->orWhereNotNull('url_path'); })
            ->orderBy('id')
            ->get(['resource_type', 'resource_id', 'canonical_url', 'url_path', 'updated_at']);
        $documentIds = $entries->where('resource_type', 'document')->pluck('resource_id')->map(static fn ($id): int => (int) $id)->unique()->values();
        $publishedQuery = Document::query()->whereIn('id', $documentIds)->where('status', 'published');
        $publishedDocuments = $this->visibility->apply($publishedQuery)->pluck('id')->mapWithKeys(static fn ($id): array => [(int) $id => true])->all();

        $urls = $entries
            ->map(function (SeoEntry $entry) use ($base, $publishedDocuments): ?array {
                if ($entry->resource_type === 'document' && ! isset($publishedDocuments[(int) $entry->resource_id])) return null;
                $raw = trim((string) ($entry->canonical_url ?: $entry->url_path));
                if ($raw === '') return null;
                $loc = filter_var($raw, FILTER_VALIDATE_URL) ? $raw : ($base !== '' ? $base.'/'.ltrim($raw, '/') : '');
                if ($loc === '') return null;
                return ['loc' => $loc, 'lastmod' => $entry->updated_at?->toAtomString()];
            })
            ->filter()
            ->values()
            ->all();

        $publicArticleQuery = Document::query()->whereIn('type', ['article', 'blog_post'])->where('status', 'published');
        if ($protected !== []) $publicArticleQuery->whereNotIn('nx_documents.id', $protected);
        if ($base !== '' && $publicArticleQuery->exists()) {
            $latestPublicArticle = Document::query()->whereIn('type', ['article', 'blog_post'])->where('status', 'published');
            if ($protected !== []) $latestPublicArticle->whereNotIn('nx_documents.id', $protected);
            $urls[] = ['loc' => $base.'/blog', 'lastmod' => $latestPublicArticle->max('updated_at')];
        }

        if ($base !== '') {
            TaxonomyTerm::query()
                ->whereHas('documents', function ($query) use ($protected): void {
                    $query->whereIn('type', ['article', 'blog_post'])->where('status', 'published');
                    if ($protected !== []) $query->whereNotIn('nx_documents.id', $protected);
                })
                ->get(['taxonomy', 'slug', 'updated_at'])
                ->each(function (TaxonomyTerm $term) use (&$urls, $base): void {
                    $segment = $term->taxonomy === 'category' ? 'category' : ($term->taxonomy === 'topic' ? 'topic' : 'tag');
                    $urls[] = ['loc' => $base.'/blog/'.$segment.'/'.rawurlencode((string) $term->slug), 'lastmod' => $term->updated_at?->toAtomString()];
                });

            ContentSeries::query()->where('status', 'active')
                ->whereHas('documents', function ($query) use ($protected): void {
                    $query->where('status', 'published');
                    if ($protected !== []) $query->whereNotIn('nx_documents.id', $protected);
                })
                ->get(['slug', 'updated_at'])
                ->each(function (ContentSeries $series) use (&$urls, $base): void {
                    $urls[] = ['loc' => $base.'/blog/series/'.rawurlencode((string) $series->slug), 'lastmod' => $series->updated_at?->toAtomString()];
                });

            AuthorProfile::query()->where('is_public', true)
                ->whereHas('documents', function ($query) use ($protected): void {
                    $query->whereIn('type', ['article', 'blog_post'])->where('status', 'published');
                    if ($protected !== []) $query->whereNotIn('nx_documents.id', $protected);
                })
                ->get(['slug', 'updated_at'])
                ->each(function (AuthorProfile $author) use (&$urls, $base): void {
                    $urls[] = ['loc' => $base.'/authors/'.rawurlencode((string) $author->slug), 'lastmod' => $author->updated_at?->toIso8601String()];
                });
        }

        $deduplicated = [];
        foreach ($urls as $url) {
            $lastmod = $url['lastmod'];
            if ($lastmod instanceof \DateTimeInterface) $lastmod = $lastmod->format(DATE_ATOM);
            $deduplicated[(string) $url['loc']] = ['loc' => (string) $url['loc'], 'lastmod' => $lastmod ? (string) $lastmod : null];
        }
        ksort($deduplicated);
        return array_values($deduplicated);
    }

    public function xml(): string
    {
        $rows = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];
        foreach ($this->urls() as $url) {
            $rows[] = '  <url>';
            $rows[] = '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>';
            if ($url['lastmod']) $rows[] = '    <lastmod>'.htmlspecialchars($url['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</lastmod>';
            $rows[] = '  </url>';
        }
        $rows[] = '</urlset>';
        return implode("\n", $rows)."\n";
    }
}
