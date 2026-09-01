<?php

declare(strict_types=1);

namespace App\Nexora\Studio\Services;

use App\Models\Document;
use App\Models\StudioCanvas;
use App\Nexora\Foundation\Contracts\SettingsContract;
use App\Nexora\Seo\Contracts\SeoManagerContract;

final readonly class StudioCanvasRenderer
{
    public function __construct(
        private SettingsContract $settings,
        private SeoManagerContract $seo,
    ) {
    }

    public function renderDocument(Document $document): ?string
    {
        $canvas = StudioCanvas::query()
            ->where('scope', 'document')
            ->where('document_id', $document->id)
            ->where('status', 'published')
            ->latest('published_at')
            ->first();
        if ($canvas === null) return null;

        $seoPayload = $this->seo->documentPayload($document, app()->getLocale());
        $context = [
            'document.title' => (string) $document->title,
            'document.excerpt' => (string) ($document->excerpt ?? ''),
            'seo.title' => (string) (($seoPayload['metadata']['title'] ?? null) ?: $document->title),
            'site.name' => (string) $this->settings->get('seo.site_name', $this->settings->get('app.name', 'Nexora')),
        ];
        $children = is_array($canvas->content['children'] ?? null) ? $canvas->content['children'] : [];
        $tablet = [];
        $mobile = [];
        $htmlParts = [];
        foreach ($children as $node) {
            if (is_array($node)) {
                $htmlParts[] = $this->node($node, $context, $tablet, $mobile);
            }
        }
        $html = implode('', $htmlParts);
        $responsive = $this->responsiveCss($tablet, $mobile);
        return $responsive.'<div class="nx-studio-page">'.$html.'</div>';
    }

    /** @param array<string,mixed> $node @param array<string,string> $context @param array<string,string> $tablet @param array<string,string> $mobile */
    private function node(array $node, array $context, array &$tablet, array &$mobile): string
    {
        $type = (string) ($node['type'] ?? 'text');
        $props = is_array($node['props'] ?? null) ? $node['props'] : [];
        $bindings = is_array($node['bindings'] ?? null) ? $node['bindings'] : [];
        foreach ($bindings as $prop => $source) {
            if (isset($context[(string) $source])) $props[(string) $prop] = $context[(string) $source];
        }
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($node['id'] ?? 'node')) ?: 'node';
        $class = 'nx-studio-'.$id;
        $styles = is_array($node['styles'] ?? null) ? $node['styles'] : [];
        $base = is_array($styles['base'] ?? null) ? $styles['base'] : [];
        $tabletStyle = is_array($styles['tablet'] ?? null) ? $styles['tablet'] : [];
        $mobileStyle = is_array($styles['mobile'] ?? null) ? $styles['mobile'] : [];
        if ($tabletStyle !== []) $tablet[$class] = $this->style($tabletStyle, $type);
        if ($mobileStyle !== []) $mobile[$class] = $this->style($mobileStyle, $type);
        $style = $this->style($base, $type);
        $children = is_array($node['children'] ?? null) ? $node['children'] : [];
        $childParts = [];
        foreach ($children as $child) {
            if (is_array($child)) {
                $childParts[] = $this->node($child, $context, $tablet, $mobile);
            }
        }
        $childHtml = implode('', $childParts);
        $text = htmlspecialchars((string) ($props['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $classAttr = ' class="'.$class.'"';
        $styleAttr = $style !== '' ? ' style="'.$style.'"' : '';

        return match ($type) {
            'section' => '<section'.$classAttr.$styleAttr.'>'.$childHtml.'</section>',
            'stack' => '<div'.$classAttr.' style="display:flex;flex-direction:'.(($props['direction'] ?? null) === 'horizontal' ? 'row' : 'column').';'.$style.'">'.$childHtml.'</div>',
            'grid' => '<div'.$classAttr.' style="display:grid;grid-template-columns:repeat('.max(1, min(12, (int) ($base['columns'] ?? $props['columns'] ?? 2))).',minmax(0,1fr));'.$style.'">'.$childHtml.'</div>',
            'heading' => $this->heading($classAttr, $styleAttr, $text, (int) ($props['level'] ?? 2)),
            'button' => '<a'.$classAttr.' href="'.htmlspecialchars((string) ($props['href'] ?? '#'), ENT_QUOTES, 'UTF-8').'" target="'.htmlspecialchars((string) ($props['target'] ?? '_self'), ENT_QUOTES, 'UTF-8').'"'.$styleAttr.'>'.$text.'</a>',
            'divider' => '<hr'.$classAttr.$styleAttr.'>',
            'spacer' => '<div'.$classAttr.' aria-hidden="true" style="height:'.max(4, min(240, (int) ($props['size'] ?? 32))).'px;'.$style.'"></div>',
            default => '<p'.$classAttr.$styleAttr.'>'.$text.'</p>',
        };
    }

    private function heading(string $classAttr, string $styleAttr, string $text, int $level): string
    {
        $level = max(1, min(6, $level));
        return '<h'.$level.$classAttr.$styleAttr.'>'.$text.'</h'.$level.'>';
    }

    /** @param array<string,mixed> $styles */
    private function style(array $styles, string $type): string
    {
        $map = [
            'gap' => 'gap', 'padding' => 'padding', 'margin' => 'margin', 'maxWidth' => 'max-width', 'minHeight' => 'min-height',
            'textAlign' => 'text-align', 'fontSize' => 'font-size', 'fontWeight' => 'font-weight', 'color' => 'color',
            'backgroundColor' => 'background-color', 'borderRadius' => 'border-radius', 'width' => 'width', 'height' => 'height',
        ];
        $parts = [];
        if ($type === 'grid' && isset($styles['columns'])) {
            $parts[] = 'grid-template-columns:repeat('.max(1, min(12, (int) $styles['columns'])).',minmax(0,1fr))';
        }
        if ($type === 'stack' && isset($styles['direction']) && in_array($styles['direction'], ['row', 'column'], true)) {
            $parts[] = 'flex-direction:'.$styles['direction'];
        }
        foreach ($map as $key => $css) {
            $value = trim((string) ($styles[$key] ?? ''));
            if ($value !== '' && preg_match('/[<>;{}]/', $value) !== 1) {
                $parts[] = $css.':'.htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }
        return implode(';', $parts).($parts !== [] ? ';' : '');
    }

    /** @param array<string,string> $tablet @param array<string,string> $mobile */
    private function responsiveCss(array $tablet, array $mobile): string
    {
        $css = '';
        if ($tablet !== []) {
            $css .= '@media(max-width:1024px){'.implode('', array_map(static fn ($class, $rules) => '.'.$class.'{'.$rules.'}', array_keys($tablet), array_values($tablet))).'}';
        }
        if ($mobile !== []) {
            $css .= '@media(max-width:640px){'.implode('', array_map(static fn ($class, $rules) => '.'.$class.'{'.$rules.'}', array_keys($mobile), array_values($mobile))).'}';
        }
        return $css === '' ? '' : '<style>'.$css.'</style>';
    }
}
