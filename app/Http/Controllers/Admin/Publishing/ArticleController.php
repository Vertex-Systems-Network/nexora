<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Publishing;

use App\Http\Controllers\Controller;
use App\Models\AuthorProfile;
use App\Models\ContentSeries;
use App\Models\Document;
use App\Models\MediaAsset;
use App\Models\TaxonomyTerm;
use App\Nexora\Enterprise\Validation\TenantExists;
use App\Nexora\Publishing\Services\ArticlePublishingManager;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');
        $status = (string) $request->query('status', '');
        $query = Document::query()
            ->whereIn('type', ['article', 'blog_post'])
            ->with(['articleMetadata', 'authorProfiles:id,display_name,slug', 'taxonomyTerms:id,taxonomy,name,slug'])
            ->latest('updated_at');
        if ($search !== '') $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
        if (in_array($type, ['article', 'blog_post'], true)) $query->where('type', $type);
        if (in_array($status, ['draft', 'published', 'archived'], true)) $query->where('status', $status);
        $articles = $query->paginate(20)->withQueryString();
        $articles->through(static fn (Document $document): array => [
            'id' => $document->id,
            'title' => $document->title,
            'slug' => $document->slug,
            'type' => $document->type,
            'status' => $document->status,
            'workflow_status' => $document->workflow_status,
            'published_at' => $document->published_at?->toIso8601String(),
            'scheduled_at' => $document->articleMetadata?->scheduled_at?->toIso8601String(),
            'featured' => (bool) ($document->articleMetadata?->is_featured ?? false),
            'authors' => $document->authorProfiles->pluck('display_name')->values()->all(),
            'terms' => $document->taxonomyTerms->take(4)->map(static fn ($term): string => $term->name)->values()->all(),
            'updated_at' => $document->updated_at?->toIso8601String(),
        ]);

        return Inertia::render('Admin/Publishing/Articles', [
            'articles' => $articles,
            'filters' => ['search' => $search, 'type' => $type, 'status' => $status],
            'summary' => [
                'total' => Document::query()->whereIn('type', ['article', 'blog_post'])->count(),
                'published' => Document::query()->whereIn('type', ['article', 'blog_post'])->where('status', 'published')->count(),
                'scheduled' => Document::query()->whereIn('type', ['article', 'blog_post'])->whereHas('articleMetadata', fn ($q) => $q->whereNotNull('scheduled_at')->where('scheduled_at', '>', now()))->count(),
                'featured' => Document::query()->whereIn('type', ['article', 'blog_post'])->whereHas('articleMetadata', fn ($q) => $q->where('is_featured', true))->count(),
            ],
        ]);
    }

    public function edit(Document $document): Response
    {
        abort_unless(in_array($document->type, ['article', 'blog_post'], true), 404);
        $document->load(['articleMetadata.heroMedia', 'authorProfiles:id,display_name,slug', 'taxonomyTerms:id,taxonomy,name,slug', 'series:id,name,slug']);
        $heroMedia = $document->articleMetadata?->heroMedia;

        return Inertia::render('Admin/Publishing/ArticleSettings', [
            'document' => [
                'id' => $document->id, 'title' => $document->title, 'type' => $document->type, 'status' => $document->status,
                'slug' => $document->slug, 'scheduled_at' => $document->articleMetadata?->scheduled_at?->format('Y-m-d\TH:i') ?? '',
                'is_featured' => (bool) ($document->articleMetadata?->is_featured ?? false),
                'featured_until' => $document->articleMetadata?->featured_until?->format('Y-m-d\TH:i') ?? '',
                'hero_image_url' => (string) ($document->articleMetadata?->hero_image_url ?? ''),
                'hero_media_id' => $document->articleMetadata?->hero_media_id,
                'hero_media' => $this->mediaSelection($heroMedia),
                'source_url' => (string) ($document->articleMetadata?->source_url ?? ''),
                'allow_comments' => (bool) ($document->articleMetadata?->allow_comments ?? false),
                'is_sponsored' => (bool) ($document->articleMetadata?->is_sponsored ?? false),
                'author_profile_ids' => $document->authorProfiles->pluck('id')->values()->all(),
                'term_ids' => $document->taxonomyTerms->pluck('id')->values()->all(),
                'series_id' => $document->series->first()?->id,
                'series_position' => (int) ($document->series->first()?->pivot?->position ?? 1),
            ],
            'authors' => AuthorProfile::query()->where('is_public', true)->orderBy('display_name')->get(['id','display_name','slug'])->map(fn ($item) => ['id'=>$item->id,'name'=>$item->display_name,'slug'=>$item->slug])->values(),
            'terms' => TaxonomyTerm::query()->orderBy('taxonomy')->orderBy('name')->get(['id','taxonomy','name','slug'])->map(fn ($item) => ['id'=>$item->id,'taxonomy'=>$item->taxonomy,'name'=>$item->name,'slug'=>$item->slug])->values(),
            'series' => ContentSeries::query()->where('status', 'active')->orderBy('name')->get(['id','name','slug'])->values(),
        ]);
    }

    public function update(Request $request, Document $document, ArticlePublishingManager $manager, AuditManager $audit): RedirectResponse
    {
        abort_unless(in_array($document->type, ['article', 'blog_post'], true), 404);
        $data = $request->validate([
            'scheduled_at' => ['nullable', 'date'],
            'is_featured' => ['required', 'boolean'],
            'featured_until' => ['nullable', 'date'],
            'hero_image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'hero_media_id' => ['nullable', 'integer', new TenantExists('nx_media_assets')],
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'allow_comments' => ['required', 'boolean'],
            'is_sponsored' => ['required', 'boolean'],
            'author_profile_ids' => ['array'], 'author_profile_ids.*' => ['integer', new TenantExists('nx_author_profiles')],
            'term_ids' => ['array'], 'term_ids.*' => ['integer', new TenantExists('nx_taxonomy_terms')],
            'series_id' => ['nullable', 'integer', new TenantExists('nx_content_series')],
            'series_position' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);
        if (! empty($data['scheduled_at']) && $document->status !== 'draft') {
            return back()->withErrors(['scheduled_at' => 'Only draft content can be scheduled. Published or archived content must be returned to draft first.']);
        }
        if (! empty($data['hero_media_id']) && ! $this->isPublicImage((int) $data['hero_media_id'])) {
            return back()->withErrors(['hero_media_id' => 'Choose an active public image from this organization Media Library.']);
        }

        $manager->save($document, $data);
        $audit->record('publishing.article.settings.updated', $document, ['scheduled_at' => $data['scheduled_at'] ?? null, 'featured' => (bool) $data['is_featured']]);
        return back()->with('success', 'Publishing settings saved.');
    }

    /** @return array{id:int,title:string,url:?string,alt_text:?string,width:?int,height:?int}|null */
    private function mediaSelection(?MediaAsset $asset): ?array
    {
        if ($asset === null) return null;
        return [
            'id' => (int) $asset->id,
            'title' => (string) ($asset->title ?: $asset->original_name),
            'url' => $asset->publicUrl(),
            'alt_text' => $asset->alt_text ? (string) $asset->alt_text : null,
            'width' => $asset->width ? (int) $asset->width : null,
            'height' => $asset->height ? (int) $asset->height : null,
        ];
    }

    private function isPublicImage(int $assetId): bool
    {
        return MediaAsset::query()
            ->whereKey($assetId)
            ->where('media_type', 'image')
            ->where('visibility', 'public')
            ->whereNull('deleted_at')
            ->exists();
    }
}