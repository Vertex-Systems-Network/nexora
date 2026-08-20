<?php

declare(strict_types=1);

namespace App\Nexora\Discovery\Search;

use App\Models\Document;
use App\Models\MediaAsset;
use App\Models\SearchIndexEntry;
use App\Models\SeoEntry;
use Illuminate\Support\Collection;

final class SearchIndexer
{
    public function __construct(private DocumentTextExtractor $extractor) {}

    public function indexDocument(Document $document, ?string $locale = null): SearchIndexEntry
    {
        $locale ??= app()->getLocale();
        $document->loadMissing(['taxonomyTerms:id,name', 'authorProfiles:id,display_name']);
        $seo = SeoEntry::query()->where('resource_type', 'document')->where('resource_id', $document->id)->where('locale', $locale)->first();
        $body = $this->extractor->extract($document->content);
        $keywords = collect($document->taxonomyTerms)->pluck('name')
            ->merge(collect($document->authorProfiles)->pluck('display_name'))
            ->filter()->map(static fn ($value): string => trim((string) $value))->unique()->implode(', ');
        $urlPath = $this->urlFor($document, $seo?->url_path, $seo?->canonical_url);
        $hashPayload = json_encode([
            'title'=>$document->title,'excerpt'=>$document->excerpt,'body'=>$body,'keywords'=>$keywords,
            'status'=>$document->status,'url'=>$urlPath,'updated_at'=>$document->updated_at?->toAtomString(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';

        return SearchIndexEntry::query()->updateOrCreate(
            ['resource_type'=>'document','resource_id'=>$document->id,'locale'=>$locale],
            [
                'status'=>(string) $document->status,
                'title'=>(string) $document->title,
                'excerpt'=>$document->excerpt,
                'body_text'=>$body,
                'keywords'=>$keywords !== '' ? $keywords : null,
                'url_path'=>$urlPath,
                'content_hash'=>hash('sha256', $hashPayload),
                'published_at'=>$document->published_at,
                'indexed_at'=>now(),
            ],
        );
    }

    public function indexMedia(MediaAsset $asset, ?string $locale = null): SearchIndexEntry
    {
        $locale ??= app()->getLocale();
        $title = trim((string) ($asset->title ?: $asset->original_name));
        $body = trim(implode(' ', array_filter([(string) $asset->alt_text, (string) $asset->caption, (string) $asset->description])));
        $url = $asset->publicUrl();
        $hashPayload = json_encode(['title'=>$title,'body'=>$body,'type'=>$asset->media_type,'visibility'=>$asset->visibility,'updated_at'=>$asset->updated_at?->toAtomString()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        return SearchIndexEntry::query()->updateOrCreate(
            ['resource_type'=>'media','resource_id'=>$asset->id,'locale'=>$locale],
            [
                'status'=>$asset->trashed() ? 'trashed' : 'active','title'=>$title !== '' ? $title : 'Untitled media',
                'excerpt'=>$asset->caption ?: $asset->description,'body_text'=>$body,'keywords'=>(string) $asset->media_type,
                'url_path'=>$url,'content_hash'=>hash('sha256',$hashPayload),'published_at'=>null,'indexed_at'=>now(),
            ],
        );
    }

    public function removeMedia(MediaAsset $asset): void
    {
        SearchIndexEntry::query()->where('resource_type','media')->where('resource_id',$asset->id)->delete();
    }

    public function removeDocument(Document $document): void
    {
        SearchIndexEntry::query()->where('resource_type', 'document')->where('resource_id', $document->id)->delete();
    }

    /** @return array{indexed:int,removed:int} */
    public function rebuild(): array
    {
        $indexed = 0;
        Document::query()->orderBy('id')->chunkById(100, function ($documents) use (&$indexed): void {
            foreach ($documents as $document) {
                $this->indexDocument($document);
                $indexed++;
            }
        });
        $valid = Document::query()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $query = SearchIndexEntry::query()->where('resource_type', 'document');
        $removed = $valid === [] ? $query->delete() : $query->whereNotIn('resource_id', $valid)->delete();
        MediaAsset::query()->orderBy('id')->chunkById(100, function ($assets) use (&$indexed): void {
            foreach ($assets as $asset) { $this->indexMedia($asset); $indexed++; }
        });
        $validMedia = MediaAsset::query()->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $mediaQuery = SearchIndexEntry::query()->where('resource_type','media');
        $removed += $validMedia === [] ? $mediaQuery->delete() : $mediaQuery->whereNotIn('resource_id',$validMedia)->delete();
        return ['indexed'=>$indexed,'removed'=>$removed];
    }

    /** @return Collection<int,array<string,mixed>> */
    public function search(string $query, bool $publicOnly = false, int $limit = 20, ?string $locale = null): Collection
    {
        $needle = $this->normalize($query);
        if (mb_strlen($needle) < 2) return collect();
        $locale ??= app()->getLocale();
        $like = '%'.$needle.'%';

        $candidates = SearchIndexEntry::query()
            ->where('locale', $locale)
            ->when($publicOnly, fn ($builder) => $builder->where('resource_type','document')->where('status', 'published'))
            ->where(function ($builder) use ($like): void {
                $builder->whereRaw('LOWER(title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(excerpt, ?)) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(COALESCE(body_text, ?)) LIKE ?', ['', $like])
                    ->orWhereRaw('LOWER(COALESCE(keywords, ?)) LIKE ?', ['', $like]);
            })
            ->limit(max(50, $limit * 5))
            ->get();

        return $candidates->map(function (SearchIndexEntry $entry) use ($needle): array {
            $title = $this->normalize((string) $entry->title);
            $excerpt = $this->normalize((string) ($entry->excerpt ?? ''));
            $body = $this->normalize((string) ($entry->body_text ?? ''));
            $keywords = $this->normalize((string) ($entry->keywords ?? ''));
            $score = 0;
            if ($title === $needle) $score += 120;
            elseif (str_starts_with($title, $needle)) $score += 80;
            elseif (str_contains($title, $needle)) $score += 55;
            if (str_contains($keywords, $needle)) $score += 30;
            if (str_contains($excerpt, $needle)) $score += 22;
            if (str_contains($body, $needle)) $score += 10;
            if ($entry->status === 'published') $score += 3;

            return [
                'id'=>$entry->id,
                'resource_type'=>$entry->resource_type,
                'resource_id'=>$entry->resource_id,
                'title'=>$entry->title,
                'excerpt'=>$entry->excerpt,
                'status'=>$entry->status,
                'url_path'=>$entry->url_path,
                'published_at'=>$entry->published_at?->toIso8601String(),
                'score'=>$score,
            ];
        })->sortByDesc('score')->take($limit)->values();
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return preg_replace('/\s+/u', ' ', $value) ?: $value;
    }

    private function urlFor(Document $document, ?string $path, ?string $canonical): string
    {
        if (is_string($canonical) && filter_var($canonical, FILTER_VALIDATE_URL)) return $canonical;
        if (is_string($path) && trim($path) !== '') return '/'.ltrim($path, '/');
        return match ((string) $document->type) {
            'article' => '/articles/'.rawurlencode((string) $document->slug),
            'blog_post' => '/blog/'.rawurlencode((string) $document->slug),
            default => '/content/'.rawurlencode((string) $document->slug),
        };
    }
}
