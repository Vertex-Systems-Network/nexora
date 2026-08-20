<?php

declare(strict_types=1);

namespace App\Nexora\Discovery\Search;

final class DocumentTextExtractor
{
    /** @param array<string,mixed>|null $content */
    public function extract(?array $content): string
    {
        $parts = [];
        foreach ((array) ($content['blocks'] ?? []) as $block) {
            if (! is_array($block)) continue;
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            foreach (['text', 'content', 'alt_text', 'caption'] as $key) {
                $value = $data[$key] ?? null;
                if (is_string($value) && trim($value) !== '') $parts[] = trim($value);
            }
            foreach ((array) ($data['items'] ?? []) as $item) {
                if (is_scalar($item) && trim((string) $item) !== '') $parts[] = trim((string) $item);
            }
            if (isset($block['children']) && is_array($block['children'])) {
                $parts[] = $this->extract(['blocks' => $block['children']]);
            }
        }

        $text = preg_replace('/\s+/u', ' ', implode(' ', array_filter($parts))) ?: '';
        return trim($text);
    }
}
