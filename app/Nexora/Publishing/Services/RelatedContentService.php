<?php

declare(strict_types=1);

namespace App\Nexora\Publishing\Services;

use App\Models\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class RelatedContentService
{
    /** @return Collection<int,Document> */
    public function forDocument(Document $document, int $limit = 4): Collection
    {
        $termIds = $document->taxonomyTerms()->pluck('nx_taxonomy_terms.id');
        if ($termIds->isEmpty()) return collect();

        $scores = DB::table('nx_document_terms')
            ->select('document_id', DB::raw('COUNT(*) as overlap_count'))
            ->whereIn('term_id', $termIds)
            ->where('document_id', '!=', $document->id)
            ->groupBy('document_id')
            ->orderByDesc('overlap_count')
            ->limit(max($limit * 3, $limit))
            ->pluck('overlap_count', 'document_id');

        if ($scores->isEmpty()) return collect();
        $documents = Document::query()
            ->whereIn('id', $scores->keys())
            ->whereIn('type', ['article', 'blog_post'])
            ->where('status', 'published')
            ->get();

        return $documents->sortByDesc(static fn (Document $item): int => (int) ($scores[$item->id] ?? 0))->take($limit)->values();
    }
}
