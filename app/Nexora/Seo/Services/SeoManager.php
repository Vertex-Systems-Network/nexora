<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Services;

use App\Models\Document;
use App\Models\MediaAsset;
use App\Nexora\Seo\Contracts\SeoManagerContract;
use App\Nexora\Seo\Contracts\SeoRepositoryContract;
use App\Nexora\Seo\Schema\SchemaGraphBuilder;

final readonly class SeoManager implements SeoManagerContract
{
    public function __construct(
        private SeoRepositoryContract $repository,
        private SeoMetadataFactory $metadata,
        private SchemaGraphBuilder $schema,
    ) {
    }

    public function documentPayload(Document $document, string $locale = 'en'): array
    {
        $entry = $this->repository->forDocument($document, $locale);
        $metadata = $this->metadata->forDocument($document, $entry)->toArray();
        $social = (array) ($entry->social ?? []);
        $image = null;

        $mediaId = isset($social['image_media_id']) ? (int) $social['image_media_id'] : 0;
        if ($mediaId > 0) {
            $image = MediaAsset::query()
                ->whereKey($mediaId)
                ->where('media_type', 'image')
                ->where('visibility', 'public')
                ->whereNull('deleted_at')
                ->first()?->publicUrl();
        }

        if (! $image) {
            $external = trim((string) ($social['image'] ?? ''));
            $image = filter_var($external, FILTER_VALIDATE_URL) ? $external : null;
        }

        return [
            'metadata' => $metadata,
            'schema' => $this->schema->forDocument($document, $entry)->toArray(),
            'social' => [
                'title' => trim((string) ($social['title'] ?? '')) ?: (string) ($metadata['title'] ?? $document->title),
                'description' => trim((string) ($social['description'] ?? '')) ?: ($metadata['description'] ?? $document->excerpt),
                'image' => $image,
                'type' => in_array($document->type, ['article', 'blog_post'], true) ? 'article' : 'website',
                'twitter_card' => $image ? 'summary_large_image' : 'summary',
            ],
        ];
    }
}
