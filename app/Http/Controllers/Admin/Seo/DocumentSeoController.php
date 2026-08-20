<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seo;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\MediaAsset;
use App\Models\SeoInternalLinkSuggestion;
use App\Nexora\Enterprise\Validation\TenantExists;
use App\Nexora\Media\Services\MediaUsageManager;
use App\Nexora\Seo\Contracts\SeoRepositoryContract;
use App\Nexora\Seo\Schema\SchemaGraphBuilder;
use App\Nexora\Seo\Services\SeoAuditService;
use App\Nexora\Seo\Services\SeoMetadataFactory;
use App\Nexora\Security\Audit\AuditManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class DocumentSeoController extends Controller
{
    /** @var list<string> */
    private const SCHEMA_TYPES = ['WebPage', 'Article', 'BlogPosting', 'NewsArticle', 'TechArticle', 'ProfilePage', 'AboutPage', 'ContactPage'];

    public function edit(Document $document, SeoRepositoryContract $seo, SeoMetadataFactory $metadata, SeoAuditService $audit, SchemaGraphBuilder $schema): Response
    {
        $entry = $seo->forDocument($document);
        $social = (array) ($entry->social ?? []);
        $socialAssetId = isset($social['image_media_id']) ? (int) $social['image_media_id'] : null;
        $socialAsset = $socialAssetId ? MediaAsset::query()->find($socialAssetId) : null;
        $suggestions = SeoInternalLinkSuggestion::query()->with('targetDocument:id,title,slug')->where('source_document_id', $document->id)->latest()->limit(30)->get();

        return Inertia::render('Admin/Seo/Document', [
            'document' => ['id' => $document->id, 'title' => $document->title, 'slug' => $document->slug, 'status' => $document->status, 'excerpt' => $document->excerpt],
            'seo' => [
                'seo_title' => (string) ($entry->seo_title ?? ''),
                'meta_description' => (string) ($entry->meta_description ?? ''),
                'canonical_url' => (string) ($entry->canonical_url ?? ''),
                'url_path' => (string) ($entry->url_path ?? ''),
                'robots_index' => (bool) ($entry->robots_index ?? true),
                'robots_follow' => (bool) ($entry->robots_follow ?? true),
                'robots_directives' => array_values((array) ($entry->robots_directives ?? [])),
                'schema_type' => (string) ($entry->schema_type ?: 'WebPage'),
                'sitemap_include' => (bool) ($entry->sitemap_include ?? true),
                'social_title' => (string) ($social['title'] ?? ''),
                'social_description' => (string) ($social['description'] ?? ''),
                'social_image' => (string) ($social['image'] ?? ''),
                'social_image_media_id' => $socialAssetId,
            ],
            'socialImageMedia' => $this->mediaSelection($socialAsset),
            'metadata' => $metadata->forDocument($document, $entry)->toArray(),
            'issues' => $audit->document($document, $entry),
            'schemaGraph' => $schema->forDocument($document, $entry)->toArray(),
            'schemaTypes' => array_map(static fn (string $value): array => ['value' => $value, 'label' => preg_replace('/(?<!^)([A-Z])/', ' $1', $value) ?: $value], self::SCHEMA_TYPES),
            'internalLinks' => $suggestions->map(static fn (SeoInternalLinkSuggestion $suggestion): array => [
                'id' => $suggestion->id,
                'target_title' => $suggestion->targetDocument?->title ?? 'Deleted document',
                'target_slug' => $suggestion->targetDocument?->slug,
                'anchor_text' => $suggestion->anchor_text,
                'status' => $suggestion->status,
                'reason' => $suggestion->reason,
                'confidence' => (float) $suggestion->confidence,
            ])->values(),
        ]);
    }

    public function update(Request $request, Document $document, SeoRepositoryContract $seo, AuditManager $audit, MediaUsageManager $mediaUsage): RedirectResponse
    {
        $data = $request->validate([
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'canonical_url' => ['nullable', 'url:http,https', 'max:2048'],
            'url_path' => ['nullable', 'string', 'max:2048', 'regex:/^\/.+/'],
            'robots_index' => ['required', 'boolean'],
            'robots_follow' => ['required', 'boolean'],
            'robots_directives' => ['nullable', 'array'],
            'robots_directives.*' => ['string', Rule::in(['noarchive', 'nosnippet', 'noimageindex', 'notranslate'])],
            'schema_type' => ['required', Rule::in(self::SCHEMA_TYPES)],
            'sitemap_include' => ['required', 'boolean'],
            'social_title' => ['nullable', 'string', 'max:255'],
            'social_description' => ['nullable', 'string', 'max:1000'],
            'social_image' => ['nullable', 'url:http,https', 'max:2048'],
            'social_image_media_id' => ['nullable', 'integer', new TenantExists('nx_media_assets')],
        ]);

        $socialAsset = null;
        if (! empty($data['social_image_media_id'])) {
            $socialAsset = MediaAsset::query()
                ->whereKey((int) $data['social_image_media_id'])
                ->where('media_type', 'image')
                ->where('visibility', 'public')
                ->whereNull('deleted_at')
                ->first();
            if ($socialAsset === null) {
                return back()->withErrors(['social_image_media_id' => 'Choose an active public image from this organization Media Library.']);
            }
        }

        $seo->saveForDocument($document, [
            'seo_title' => $data['seo_title'] ?: null,
            'meta_description' => $data['meta_description'] ?: null,
            'canonical_url' => $data['canonical_url'] ?: null,
            'url_path' => $data['url_path'] ?: null,
            'robots_index' => (bool) $data['robots_index'],
            'robots_follow' => (bool) $data['robots_follow'],
            'robots_directives' => array_values((array) ($data['robots_directives'] ?? [])),
            'schema_type' => $data['schema_type'],
            'schema_overrides' => [],
            'social' => array_filter([
                'title' => $data['social_title'] ?: null,
                'description' => $data['social_description'] ?: null,
                'image' => $data['social_image'] ?: null,
                'image_media_id' => $socialAsset?->id,
            ], static fn ($value): bool => $value !== null && $value !== ''),
            'sitemap_include' => (bool) $data['sitemap_include'],
            'indexing_state' => (bool) $data['robots_index'] ? ($document->status === 'published' ? 'eligible' : 'not_published') : 'excluded_noindex',
        ], $request->user()?->id);
        $mediaUsage->assign($socialAsset, 'document', (int) $document->id, 'social_image');
        $audit->record('seo.document.updated', $document, ['schema_type' => $data['schema_type'], 'robots_index' => (bool) $data['robots_index'], 'social_image_media_id' => $socialAsset?->id]);

        return back()->with('success', 'SEO metadata saved. Theme output will consume this through Nexora SEO contracts.');
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
}