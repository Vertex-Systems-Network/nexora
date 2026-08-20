<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Services;

use App\Models\Document;
use App\Models\SeoEntry;
use App\Nexora\Seo\Contracts\SeoRepositoryContract;

final class DatabaseSeoRepository implements SeoRepositoryContract
{
    public function forDocument(Document $document, string $locale = 'en'): SeoEntry
    {
        return SeoEntry::query()->firstOrNew([
            'resource_type' => 'document',
            'resource_id' => $document->id,
            'locale' => $locale,
        ], [
            'seo_title' => $document->title,
            'meta_description' => $document->excerpt,
            'robots_index' => true,
            'robots_follow' => true,
            'robots_directives' => [],
            'schema_type' => 'WebPage',
            'schema_overrides' => [],
            'social' => [],
            'sitemap_include' => true,
            'indexing_state' => 'eligible',
        ]);
    }

    public function saveForDocument(Document $document, array $attributes, ?int $actorId = null, string $locale = 'en'): SeoEntry
    {
        $entry = SeoEntry::query()->firstOrNew([
            'resource_type' => 'document',
            'resource_id' => $document->id,
            'locale' => $locale,
        ]);
        if (! $entry->exists) {
            $entry->created_by = $actorId;
        }
        $entry->fill($attributes);
        $entry->updated_by = $actorId;
        $entry->save();

        return $entry->refresh();
    }
}
