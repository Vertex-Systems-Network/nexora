<?php

declare(strict_types=1);

namespace App\Nexora\Themes\Services;

use App\Models\MediaAsset;

final class DocumentHtmlRenderer
{
    /** @param array<string,mixed>|null $content */
    public function render(?array $content): string
    {
        $blocks = is_array($content['blocks'] ?? null) ? $content['blocks'] : [];
        $output = [];
        foreach ($blocks as $block) {
            if (! is_array($block)) continue;
            $type = (string) ($block['type'] ?? 'paragraph');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            $text = htmlspecialchars((string) ($data['text'] ?? $data['content'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $output[] = match ($type) {
                'heading' => $this->heading($text, (int) ($data['level'] ?? 2)),
                'quote' => '<blockquote>'.$text.'</blockquote>',
                'code' => '<pre><code>'.$text.'</code></pre>',
                'divider' => '<hr>',
                'list' => $this->list($data),
                'image' => $this->image($data),
                default => '<p>'.$text.'</p>',
            };
        }
        return implode("\n", $output);
    }


    /** @param array<string,mixed> $data */
    private function image(array $data): string
    {
        $assetId = (int) ($data['media_asset_id'] ?? 0);
        if ($assetId < 1) return '';
        $asset = MediaAsset::query()->find($assetId);
        if (! $asset || $asset->trashed() || $asset->media_type !== 'image' || ! $asset->publicUrl()) return '';
        $alt = htmlspecialchars((string) ($data['alt_text'] ?? $asset->alt_text ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $caption = trim((string) ($data['caption'] ?? ''));
        $srcset = [];
        foreach ((array) $asset->variants as $width => $variant) {
            if (! is_numeric($width) || ! is_array($variant)) continue;
            $srcset[] = htmlspecialchars(url('/media/'.$asset->uuid.'/'.$width), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').' '.(int) $width.'w';
        }
        $srcsetAttribute = $srcset !== [] ? ' srcset="'.implode(', ', $srcset).'" sizes="(max-width: 900px) 100vw, 900px"' : '';
        $figure = '<figure class="nx-media-image"><img src="'.htmlspecialchars((string) $asset->publicUrl(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"'.$srcsetAttribute.' alt="'.$alt.'" loading="lazy" decoding="async">';
        if ($caption !== '') $figure .= '<figcaption>'.htmlspecialchars($caption, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</figcaption>';
        return $figure.'</figure>';
    }

    private function heading(string $text, int $level): string
    {
        $level = max(1, min(6, $level));
        return "<h{$level}>{$text}</h{$level}>";
    }

    /** @param array<string,mixed> $data */
    private function list(array $data): string
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : preg_split('/\r?\n/', (string) ($data['text'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $tag = ($data['ordered'] ?? false) ? 'ol' : 'ul';
        $lis = [];
        foreach ((array) $items as $item) $lis[] = '<li>'.htmlspecialchars((string) $item, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</li>';
        return '<'.$tag.'>'.implode('', $lis).'</'.$tag.'>';
    }
}
