<?php

declare(strict_types=1);

namespace App\Nexora\Publishing\Services;

use App\Models\ArticleMetadata;
use App\Models\AuthorProfile;
use App\Models\ContentSeries;
use App\Models\Document;
use App\Models\MediaAsset;
use App\Nexora\Media\Services\MediaUsageManager;
use App\Nexora\Foundation\Database\ConcurrencyGuard;
use App\Models\SeoEntry;
use Illuminate\Support\Str;
use App\Nexora\Documents\Services\DocumentRevisionManager;

final class ArticlePublishingManager
{
    public function __construct(private DocumentRevisionManager $revisions, private MediaUsageManager $mediaUsage, private ConcurrencyGuard $concurrency) {}

    public function registerDocumentLifecycle(): void
    {
        $publishing = $this;
        Document::saved(static function (Document $document) use ($publishing): void {
            if (! in_array($document->type, ['article', 'blog_post'], true)) {
                return;
            }

            $publishing->ensureSeoDefaults($document);
            if ($document->status === 'published') {
                $document->articleMetadata()->whereNotNull('scheduled_at')->update(['scheduled_at' => null]);
            }
        });
    }

    /** @param array<string,mixed> $data */
    public function save(Document $document, array $data): void
    {
        $this->concurrency->transaction(function () use ($document, $data): void {
            $document = Document::query()->lockForUpdate()->findOrFail($document->id);
            ArticleMetadata::query()->updateOrCreate(
                ['document_id' => $document->id],
                [
                    'scheduled_at' => $data['scheduled_at'] ?: null,
                    'is_featured' => (bool) ($data['is_featured'] ?? false),
                    'featured_until' => $data['featured_until'] ?: null,
                    'hero_image_url' => $data['hero_image_url'] ?: null,
                    'hero_media_id' => ! empty($data['hero_media_id']) ? (int) $data['hero_media_id'] : null,
                    'source_url' => $data['source_url'] ?: null,
                    'allow_comments' => (bool) ($data['allow_comments'] ?? false),
                    'is_sponsored' => (bool) ($data['is_sponsored'] ?? false),
                    'metadata' => [],
                ]
            );

            $authors = array_values(array_unique(array_map('intval', (array) ($data['author_profile_ids'] ?? []))));
            $authorSync = [];
            foreach ($authors as $index => $authorId) {
                $authorSync[$authorId] = ['role' => $index === 0 ? 'author' : 'co_author', 'position' => $index + 1];
            }
            $document->authorProfiles()->sync($authorSync);

            $terms = array_values(array_unique(array_map('intval', (array) ($data['term_ids'] ?? []))));
            $termSync = [];
            foreach ($terms as $index => $termId) {
                $termSync[$termId] = ['is_primary' => $index === 0, 'position' => $index + 1];
            }
            $document->taxonomyTerms()->sync($termSync);

            $heroAsset = ! empty($data['hero_media_id']) ? MediaAsset::query()->where('media_type', 'image')->find((int) $data['hero_media_id']) : null;
            $this->mediaUsage->assign($heroAsset, 'document', (int) $document->id, 'hero_image');

            $seriesId = isset($data['series_id']) && $data['series_id'] !== '' ? (int) $data['series_id'] : null;
            if ($seriesId) {
                $position = max(1, (int) ($data['series_position'] ?? 1));
                $document->series()->sync([$seriesId => ['position' => $position]]);
            } else {
                $document->series()->detach();
            }

            $this->ensureSeoDefaults($document);
        });
    }

    public function publishScheduled(): int
    {
        $documents = Document::query()
            ->whereIn('type', ['article', 'blog_post'])
            ->where('status', 'draft')
            ->whereHas('articleMetadata', fn ($query) => $query->whereNotNull('scheduled_at')->where('scheduled_at', '<=', now()))
            ->with('articleMetadata')
            ->get();

        $published = 0;
        foreach ($documents as $document) {
            $changed = $this->concurrency->transaction(function () use ($document): bool {
                $locked = Document::query()->with('articleMetadata')->whereKey($document->id)->lockForUpdate()->first();
                if (! $locked || $locked->status !== 'draft') return false;
                $scheduledAt = $locked->articleMetadata?->scheduled_at;
                if ($scheduledAt === null || $scheduledAt->isFuture()) return false;

                $locked->forceFill([
                    'status' => 'published',
                    'published_at' => $scheduledAt,
                    'workflow_status' => 'published',
                    'lock_version' => ((int) $locked->lock_version) + 1,
                ])->save();
                $this->revisions->record($locked, null);
                $this->ensureSeoDefaults($locked);
                return true;
            });
            if ($changed) $published++;
        }

        return $published;
    }

    public function ensureSeoDefaults(Document $document): SeoEntry
    {
        $entry = SeoEntry::query()->firstOrNew([
            'resource_type' => 'document',
            'resource_id' => $document->id,
            'locale' => app()->getLocale(),
        ]);
        $prefix = $document->type === 'blog_post' ? '/blog/' : '/articles/';
        $newSlug = $document->slug ?: Str::slug($document->title);
        $newDefaultPath = $prefix.$newSlug;
        $oldType = (string) ($document->getRawOriginal('type') ?: $document->type);
        $oldSlug = (string) ($document->getRawOriginal('slug') ?: $newSlug);
        $oldPrefix = $oldType === 'blog_post' ? '/blog/' : '/articles/';
        $oldDefaultPath = $oldPrefix.$oldSlug;
        if (! $entry->exists || ! $entry->url_path || ($document->wasChanged(['slug', 'type']) && $entry->url_path === $oldDefaultPath)) {
            $entry->url_path = $newDefaultPath;
        }
        if (! $entry->schema_type || $entry->schema_type === 'WebPage') {
            $entry->schema_type = $document->type === 'blog_post' ? 'BlogPosting' : 'Article';
        }
        $entry->robots_index = true;
        $entry->robots_follow = true;
        $entry->sitemap_include = true;
        $entry->indexing_state = $document->status === 'published' ? 'eligible' : 'not_published';
        $entry->save();
        return $entry;
    }
}
