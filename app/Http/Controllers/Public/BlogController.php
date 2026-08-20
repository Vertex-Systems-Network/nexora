<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AuthorProfile;
use App\Models\ContentSeries;
use App\Models\Document;
use App\Models\TaxonomyTerm;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Themes\Contracts\ThemeRendererContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Response;

final class BlogController extends Controller
{
    public function __construct(private ThemeRendererContract $themes, private SettingsContract $settings) {}

    public function index(): Response
    {
        return $this->listing(
            'Blog & Articles',
            Document::query()->whereIn('type', ['article', 'blog_post'])->where('status', 'published')->latest('published_at'),
            'Latest published writing from this site.',
        );
    }

    public function category(TaxonomyTerm $term): Response
    {
        abort_unless($term->taxonomy === 'category', 404);
        return $this->listing('Category: '.$term->name, $this->termQuery($term), $term->description);
    }

    public function topic(TaxonomyTerm $term): Response
    {
        abort_unless($term->taxonomy === 'topic', 404);
        return $this->listing('Topic: '.$term->name, $this->termQuery($term), $term->description);
    }

    public function tag(TaxonomyTerm $term): Response
    {
        abort_unless($term->taxonomy === 'tag', 404);
        return $this->listing('Tag: '.$term->name, $this->termQuery($term), $term->description);
    }

    public function series(ContentSeries $series): Response
    {
        abort_unless($series->status === 'active', 404);
        $query = $series->documents()->whereIn('type', ['article', 'blog_post'])->where('status', 'published')->orderByPivot('position');
        return $this->listing('Series: '.$series->name, $query, $series->description);
    }

    public function author(AuthorProfile $author): Response
    {
        abort_unless($author->is_public, 404);
        $query = $author->documents()->whereIn('type', ['article', 'blog_post'])->where('status', 'published')->latest('published_at');
        $personId = url('/authors/'.$author->slug).'#person';
        return $this->listing($author->display_name, $query, $author->bio, [
            [
                '@id' => $personId,
                '@type' => 'Person',
                'name' => $author->display_name,
                'url' => url('/authors/'.$author->slug),
                'description' => $author->bio,
                'image' => $author->avatar_url,
                'sameAs' => array_values(array_filter(array_map('strval', (array) ($author->social_links ?? [])), static fn (string $url): bool => filter_var($url, FILTER_VALIDATE_URL) !== false)),
            ],
            [
                '@id' => url('/authors/'.$author->slug).'#profile',
                '@type' => 'ProfilePage',
                'name' => $author->display_name,
                'url' => url('/authors/'.$author->slug),
                'mainEntity' => ['@id' => $personId],
            ],
        ]);
    }

    private function termQuery(TaxonomyTerm $term): BelongsToMany
    {
        return $term->documents()->whereIn('type', ['article', 'blog_post'])->where('status', 'published')->latest('published_at');
    }

    /** @param Builder<Document>|BelongsToMany<Document,TaxonomyTerm|ContentSeries|AuthorProfile> $query
     *  @param list<array<string,mixed>> $extraSchema */
    private function listing(string $title, $query, ?string $intro = null, array $extraSchema = []): Response
    {
        $items = $query->limit(24)->get(['nx_documents.id', 'type', 'title', 'slug', 'excerpt', 'published_at']);
        $content = '<section class="nx-archive"><header><h1>'.e($title).'</h1>'.($intro ? '<p>'.e($intro).'</p>' : '').'</header><div class="nx-archive-list">';
        foreach ($items as $document) {
            $content .= '<article><h2><a href="'.e($this->documentUrl($document)).'">'.e((string) $document->title).'</a></h2>';
            if ($document->excerpt) $content .= '<p>'.e((string) $document->excerpt).'</p>';
            if ($document->published_at) $content .= '<time datetime="'.e($document->published_at->toAtomString()).'">'.e($document->published_at->format('M j, Y')).'</time>';
            $content .= '</article>';
        }
        if ($items->isEmpty()) $content .= '<p class="nx-empty">No published content found.</p>';
        $content .= '</div></section>';

        $siteName = (string) $this->settings->get('seo.site_name', $this->settings->get('app.name', 'Nexora'));
        $canonical = request()->url();
        $graph = [[
            '@id' => $canonical.'#collection',
            '@type' => 'CollectionPage',
            'name' => $title,
            'description' => $intro,
            'url' => $canonical,
            'isPartOf' => ['@id' => rtrim((string) config('app.url', ''), '/').'/#website'],
        ], ...$extraSchema];
        $schema = json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $head = '<title>'.e($title).' · '.e($siteName).'</title><link rel="canonical" href="'.e($canonical).'"><meta name="robots" content="index,follow">';
        if ($intro) $head .= '<meta name="description" content="'.e($intro).'">';

        $html = $this->themes->render('home', [
            'site_name' => $siteName,
            'page_title' => $title,
            'tagline' => $intro ?? '',
            'nx_head' => $head,
            'nx_schema' => is_string($schema) ? '<script type="application/ld+json">'.$schema.'</script>' : '',
            'nx_content' => $content,
        ]);
        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function documentUrl(Document $document): string
    {
        return $document->type === 'article'
            ? '/articles/'.rawurlencode((string) $document->slug)
            : '/blog/'.rawurlencode((string) $document->slug);
    }
}
