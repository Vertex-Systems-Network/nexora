<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\SeoEntry;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Membership\Contracts\MembershipAccessContract;
use App\Nexora\Publishing\Services\PublicDocumentVisibility;
use App\Nexora\Publishing\Services\RelatedContentService;
use App\Nexora\Seo\Contracts\SeoManagerContract;
use App\Nexora\Studio\Services\StudioCanvasRenderer;
use App\Nexora\Themes\Contracts\ThemeRendererContract;
use App\Nexora\Themes\Services\DocumentHtmlRenderer;
use Illuminate\Http\Response;

final class ThemePageController extends Controller
{
    public function __construct(
        private ThemeRendererContract $themes,
        private SettingsContract $settings,
        private SeoManagerContract $seo,
        private DocumentHtmlRenderer $documents,
        private StudioCanvasRenderer $studio,
        private RelatedContentService $related,
        private MembershipAccessContract $membershipAccess,
        private PublicDocumentVisibility $visibility,
    ) {
    }

    public function home(): Response
    {
        $siteName = (string) $this->settings->get('seo.site_name', $this->settings->get('app.name', 'Nexora'));
        $featuredQuery = Document::query()
            ->whereIn('type', ['article', 'blog_post'])
            ->where('status', 'published')
            ->whereHas('articleMetadata', fn ($query) => $query->where('is_featured', true)->where(function ($expiry): void {
                $expiry->whereNull('featured_until')->orWhere('featured_until', '>', now());
            }))
            ->with('articleMetadata')
            ->latest('published_at')
            ->limit(3);
        $featured = $this->visibility->apply($featuredQuery)->get(['nx_documents.id', 'type', 'title', 'slug', 'excerpt', 'published_at']);

        $latestQuery = Document::query()
            ->where('status', 'published')
            ->when($featured->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $featured->pluck('id')))
            ->latest('published_at')
            ->limit(6);
        $latest = $this->visibility->apply($latestQuery)->get(['nx_documents.id', 'type', 'title', 'slug', 'excerpt', 'published_at']);

        $content = '';
        if ($featured->isNotEmpty()) {
            $content .= '<section class="nx-featured"><header><h1>Featured</h1></header><div class="nx-featured-grid">';
            foreach ($featured as $document) {
                $content .= '<article><h2><a href="'.e($this->documentUrl($document)).'">'.e((string) $document->title).'</a></h2>';
                if ($document->excerpt) $content .= '<p>'.e((string) $document->excerpt).'</p>';
                $content .= '</article>';
            }
            $content .= '</div></section>';
        }

        $content .= '<section class="nx-home-list"><header><h1>Latest</h1></header>';
        foreach ($latest as $document) {
            $content .= '<article><h2><a href="'.e($this->documentUrl($document)).'">'.e((string) $document->title).'</a></h2>';
            if ($document->excerpt) $content .= '<p>'.e((string) $document->excerpt).'</p>';
            $content .= '</article>';
        }
        if ($latest->isEmpty() && $featured->isEmpty()) $content .= '<p class="nx-empty">Your Nexora site is ready. Publish a document to see it here.</p>';
        $content .= '</section>';

        $html = $this->themes->render('home', [
            'site_name' => $siteName,
            'page_title' => $siteName,
            'tagline' => 'Secure modular publishing, powered by Nexora.',
            'nx_head' => '<title>'.e($siteName).'</title><meta name="robots" content="index,follow">',
            'nx_schema' => '',
            'nx_content' => $content,
        ]);
        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function resolve(string $path): Response
    {
        $normalized = '/'.ltrim($path, '/');
        $entry = SeoEntry::query()
            ->where('resource_type', 'document')
            ->where('url_path', $normalized)
            ->first();
        if ($entry === null) abort(404);

        $document = Document::query()->find($entry->resource_id);
        if ($document === null) abort(404);
        return $this->document($document);
    }

    public function document(Document $document): Response
    {
        abort_unless($document->status === 'published', 404);
        $this->membershipAccess->assertCanAccess(auth()->user(), 'document', (string) $document->id);

        $payload = $this->seo->documentPayload($document, app()->getLocale());
        $metadata = (array) $payload['metadata'];
        $schema = (array) $payload['schema'];
        $social = (array) ($payload['social'] ?? []);
        $title = (string) ($metadata['title'] ?? $document->title);
        $description = $metadata['description'] ?? $document->excerpt;
        $canonical = (string) ($metadata['canonical_url'] ?: url($this->documentUrl($document)));
        $head = $this->documentHead($title, $description ? (string) $description : null, $canonical, (array) ($metadata['robots'] ?? []), $social);
        $schemaJson = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $schemaTag = is_string($schemaJson) ? '<script type="application/ld+json">'.$schemaJson.'</script>' : '';
        $siteName = (string) $this->settings->get('seo.site_name', $this->settings->get('app.name', 'Nexora'));

        $body = $this->studio->renderDocument($document) ?? $this->documents->render($document->content);
        if (in_array($document->type, ['article', 'blog_post'], true)) {
            $document->loadMissing(['authorProfiles', 'taxonomyTerms', 'articleMetadata.heroMedia', 'series']);
            $meta = '<header class="nx-article-meta">';
            $heroMedia = $document->articleMetadata?->heroMedia;
            $heroUrl = $heroMedia?->publicUrl() ?: $document->articleMetadata?->hero_image_url;
            if ($heroUrl) {
                $alt = $heroMedia?->alt_text ?: $document->title;
                $srcset = '';
                if ($heroMedia && is_array($heroMedia->variants)) {
                    $pairs = [];
                    foreach ($heroMedia->variants as $width => $variant) if (is_array($variant) && is_numeric($width)) $pairs[] = e(url('/media/'.$heroMedia->uuid.'/'.$width)).' '.(int) $width.'w';
                    if ($pairs !== []) $srcset = ' srcset="'.implode(', ', $pairs).'" sizes="(max-width: 900px) 100vw, 900px"';
                }
                $meta .= '<img class="nx-article-hero" src="'.e((string) $heroUrl).'"'.$srcset.' alt="'.e((string) $alt).'" loading="eager" fetchpriority="high">';
            }
            $meta .= '<h1>'.e((string) $document->title).'</h1>';
            if ($document->excerpt) $meta .= '<p class="nx-article-excerpt">'.e((string) $document->excerpt).'</p>';
            if ($document->authorProfiles->isNotEmpty()) {
                $meta .= '<p class="nx-byline">By ';
                $links = $document->authorProfiles->map(static fn ($author): string => '<a href="/authors/'.rawurlencode((string) $author->slug).'">'.e((string) $author->display_name).'</a>')->all();
                $meta .= implode(', ', $links).'</p>';
            }
            if ($document->published_at) $meta .= '<time datetime="'.e($document->published_at->toAtomString()).'">'.e($document->published_at->format('F j, Y')).'</time>';
            if ($document->articleMetadata?->is_sponsored) $meta .= '<span class="nx-sponsored">Sponsored</span>';
            $meta .= '</header>';

            $terms = '';
            foreach ($document->taxonomyTerms as $term) {
                $segment = $term->taxonomy === 'category' ? 'category' : ($term->taxonomy === 'topic' ? 'topic' : 'tag');
                $terms .= '<a class="nx-term nx-term-'.e((string) $term->taxonomy).'" href="/blog/'.$segment.'/'.rawurlencode((string) $term->slug).'">'.e((string) $term->name).'</a>';
            }
            if ($terms !== '') $meta .= '<nav class="nx-article-terms" aria-label="Article taxonomy">'.$terms.'</nav>';
            $body = $meta.'<article class="nx-article-body">'.$body.'</article>';

            $series = $document->series->first();
            if ($series) {
                $seriesQuery = $series->documents()->where('status', 'published');
                $items = $this->visibility->apply($seriesQuery)->get(['nx_documents.id', 'type', 'title', 'slug']);
                $current = $items->search(static fn (Document $item): bool => (int) $item->id === (int) $document->id);
                $body .= '<nav class="nx-series-nav" aria-label="Series navigation"><strong>'.e((string) $series->name).'</strong>';
                if ($current !== false && $current > 0) {
                    $previous = $items[$current - 1];
                    $body .= '<a rel="prev" href="'.e($this->documentUrl($previous)).'">← '.e((string) $previous->title).'</a>';
                }
                if ($current !== false && isset($items[$current + 1])) {
                    $next = $items[$current + 1];
                    $body .= '<a rel="next" href="'.e($this->documentUrl($next)).'">'.e((string) $next->title).' →</a>';
                }
                $body .= '</nav>';
            }

            $related = $this->related->forDocument($document);
            if ($related->isNotEmpty()) {
                $body .= '<aside class="nx-related"><h2>Related content</h2><ul>';
                foreach ($related as $item) $body .= '<li><a href="'.e($this->documentUrl($item)).'">'.e((string) $item->title).'</a></li>';
                $body .= '</ul></aside>';
            }
        }

        $html = $this->themes->render('document', [
            'site_name' => $siteName,
            'page_title' => $title,
            'excerpt' => (string) ($document->excerpt ?? ''),
            'nx_head' => $head,
            'nx_schema' => $schemaTag,
            'nx_content' => $body,
        ]);
        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /** @param array<string,mixed> $robots @param array<string,mixed> $social */
    private function documentHead(string $title, ?string $description, string $canonical, array $robots, array $social): string
    {
        $directives = [
            (bool) ($robots['index'] ?? true) ? 'index' : 'noindex',
            (bool) ($robots['follow'] ?? true) ? 'follow' : 'nofollow',
            ...array_values(array_filter(array_map('strval', (array) ($robots['directives'] ?? [])))),
        ];
        $directives = array_values(array_unique($directives));
        $socialTitle = trim((string) ($social['title'] ?? '')) ?: $title;
        $socialDescription = trim((string) ($social['description'] ?? '')) ?: $description;
        $socialType = trim((string) ($social['type'] ?? '')) ?: 'website';
        $socialImage = trim((string) ($social['image'] ?? ''));
        $twitterCard = trim((string) ($social['twitter_card'] ?? '')) ?: ($socialImage !== '' ? 'summary_large_image' : 'summary');

        $head = '<title>'.e($title).'</title>';
        if ($description) $head .= '<meta name="description" content="'.e($description).'">';
        $head .= '<link rel="canonical" href="'.e($canonical).'">';
        $head .= '<meta name="robots" content="'.e(implode(',', $directives)).'">';
        $head .= '<meta property="og:title" content="'.e($socialTitle).'">';
        $head .= '<meta property="og:type" content="'.e($socialType).'">';
        $head .= '<meta property="og:url" content="'.e($canonical).'">';
        if ($socialDescription) $head .= '<meta property="og:description" content="'.e($socialDescription).'">';
        if ($socialImage !== '') $head .= '<meta property="og:image" content="'.e($socialImage).'">';
        $head .= '<meta name="twitter:card" content="'.e($twitterCard).'">';
        $head .= '<meta name="twitter:title" content="'.e($socialTitle).'">';
        if ($socialDescription) $head .= '<meta name="twitter:description" content="'.e($socialDescription).'">';
        if ($socialImage !== '') $head .= '<meta name="twitter:image" content="'.e($socialImage).'">';
        return $head;
    }

    private function documentUrl(Document $document): string
    {
        if ($document->type === 'article') return '/articles/'.rawurlencode((string) $document->slug);
        if ($document->type === 'blog_post') return '/blog/'.rawurlencode((string) $document->slug);
        return '/content/'.rawurlencode((string) $document->slug);
    }
}
