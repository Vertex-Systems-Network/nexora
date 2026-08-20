<?php

declare(strict_types=1);

namespace App\Nexora\Seo\Services;

use App\Models\Document;
use App\Models\SeoInternalLinkSuggestion;
use Illuminate\Support\Str;

final class InternalLinkAnalyzer
{
    public function refresh(Document $source): int
    {
        $plain = Str::lower($this->plainText((array) $source->content));
        if ($plain === '') return 0;

        SeoInternalLinkSuggestion::query()->where('source_document_id', $source->id)->where('status', 'suggested')->delete();
        $created = 0;
        Document::query()->whereKeyNot($source->id)->whereNotNull('title')->where('status', 'published')->orderByDesc('updated_at')->limit(250)->get(['id', 'title', 'slug'])->each(function (Document $target) use ($source, $plain, &$created): void {
            $anchor = trim((string) $target->title);
            if (mb_strlen($anchor) < 4 || ! str_contains($plain, Str::lower($anchor))) return;
            SeoInternalLinkSuggestion::query()->create([
                'source_document_id' => $source->id,
                'target_document_id' => $target->id,
                'anchor_text' => $anchor,
                'status' => 'suggested',
                'reason' => 'The target document title appears naturally in this document body.',
                'confidence' => 0.90,
            ]);
            $created++;
        });
        return $created;
    }

    private function plainText(array $content): string
    {
        $parts = [];
        foreach ((array) ($content['blocks'] ?? []) as $block) {
            if (! is_array($block)) continue;
            $data = (array) ($block['data'] ?? []);
            foreach (['text', 'code', 'attribution'] as $key) if (isset($data[$key]) && is_string($data[$key])) $parts[] = $data[$key];
            foreach ((array) ($data['items'] ?? []) as $item) if (is_string($item)) $parts[] = $item;
        }
        return trim(implode("\n", $parts));
    }
}
