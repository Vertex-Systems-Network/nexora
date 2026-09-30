<?php

declare(strict_types=1);

namespace App\Nexora\Studio\Services;

use Illuminate\Validation\ValidationException;

final readonly class StudioCanvasValidator
{
    private const MAX_NODES = 500;
    private const MAX_DEPTH = 20;
    private const ALLOWED_STYLE_KEYS = [
        'direction', 'gap', 'columns', 'padding', 'margin', 'maxWidth', 'minHeight', 'textAlign',
        'fontSize', 'fontWeight', 'color', 'backgroundColor', 'borderRadius', 'width', 'height',
    ];

    public function __construct(
        private StudioElementRegistry $elements,
        private StudioBindingRegistry $bindings,
    ) {
    }

    /** @param array<string,mixed>|null $content @return array<string,mixed> */
    public function validate(?array $content): array
    {
        $content ??= ['version' => 1, 'children' => []];
        if ((int) ($content['version'] ?? 1) !== 1) {
            throw ValidationException::withMessages(['content' => 'Unsupported Studio canvas schema version.']);
        }
        $children = $content['children'] ?? [];
        if (! is_array($children)) {
            throw ValidationException::withMessages(['content' => 'Studio canvas children must be an array.']);
        }
        $count = 0;
        $normalized = [];
        foreach ($children as $node) {
            if (! is_array($node)) continue;
            $normalized[] = $this->node($node, 0, $count);
        }
        return ['version' => 1, 'children' => $normalized];
    }

    /** @param array<string,mixed> $node @return array<string,mixed> */
    private function node(array $node, int $depth, int &$count): array
    {
        $count++;
        if ($count > self::MAX_NODES) {
            throw ValidationException::withMessages(['content' => 'Studio canvases are limited to 500 elements.']);
        }
        if ($depth > self::MAX_DEPTH) {
            throw ValidationException::withMessages(['content' => 'Studio element nesting is too deep.']);
        }

        $type = (string) ($node['type'] ?? '');
        $definition = $this->elements->get($type);
        if ($definition === null) {
            throw ValidationException::withMessages(['content' => "Unknown Studio element [{$type}]."]);
        }
        $id = (string) ($node['id'] ?? '');
        if (preg_match('/^[a-zA-Z0-9_-]{8,80}$/', $id) !== 1) {
            throw ValidationException::withMessages(['content' => 'Each Studio element requires a stable safe id.']);
        }

        $props = is_array($node['props'] ?? null) ? $node['props'] : [];
        $props = $this->sanitizeProps($type, $props);
        $styles = is_array($node['styles'] ?? null) ? $node['styles'] : [];
        $styles = $this->sanitizeStyles($styles);
        $bindings = is_array($node['bindings'] ?? null) ? $node['bindings'] : [];
        $bindings = $this->sanitizeBindings($bindings, $definition->bindableProps);
        $children = [];
        if ($definition->acceptsChildren) {
            foreach ((array) ($node['children'] ?? []) as $child) {
                if (is_array($child)) $children[] = $this->node($child, $depth + 1, $count);
            }
        }

        return [
            'id' => $id,
            'type' => $type,
            'props' => $props,
            'styles' => $styles,
            'bindings' => $bindings,
            'children' => $children,
        ];
    }

    /** @param array<string,mixed> $props @return array<string,mixed> */
    private function sanitizeProps(string $type, array $props): array
    {
        $allowed = match ($type) {
            'heading' => ['text', 'level'],
            'text' => ['text'],
            'button' => ['text', 'href', 'target'],
            'section' => ['label'],
            'stack' => ['label', 'direction'],
            'grid' => ['label', 'columns'],
            'divider' => [],
            'spacer' => ['size'],
            default => [],
        };
        $clean = [];
        foreach ($allowed as $key) {
            if (! array_key_exists($key, $props)) continue;
            $value = $props[$key];
            if ($key === 'level') $clean[$key] = max(1, min(6, (int) $value));
            elseif ($key === 'columns') $clean[$key] = max(1, min(12, (int) $value));
            elseif ($key === 'direction') $clean[$key] = in_array($value, ['vertical', 'horizontal'], true) ? $value : 'vertical';
            elseif ($key === 'size') $clean[$key] = max(4, min(240, (int) $value));
            elseif ($key === 'target') $clean[$key] = in_array($value, ['_self', '_blank'], true) ? $value : '_self';
            elseif ($key === 'href') {
                $href = trim((string) $value);
                $clean[$key] = preg_match('~^(https?://|/|#)~i', $href) === 1 ? mb_substr($href, 0, 1000) : '#';
            } else $clean[$key] = mb_substr((string) $value, 0, $key === 'text' ? 20_000 : 255);
        }
        return $clean;
    }

    /** @param array<string,mixed> $styles @return array<string,mixed> */
    private function sanitizeStyles(array $styles): array
    {
        $clean = [];
        foreach (['base', 'tablet', 'mobile'] as $breakpoint) {
            $values = is_array($styles[$breakpoint] ?? null) ? $styles[$breakpoint] : [];
            $breakpointValues = [];
            foreach ($values as $key => $value) {
                if (! in_array((string) $key, self::ALLOWED_STYLE_KEYS, true)) continue;
                $string = trim((string) $value);
                if (strlen($string) > 100 || preg_match('/[<>;{}]/', $string) === 1) continue;
                $breakpointValues[(string) $key] = $string;
            }
            $clean[$breakpoint] = $breakpointValues;
        }
        return $clean;
    }

    /** @param array<string,mixed> $bindings @param list<string> $bindableProps @return array<string,string> */
    private function sanitizeBindings(array $bindings, array $bindableProps): array
    {
        $clean = [];
        foreach ($bindings as $prop => $source) {
            if (! in_array((string) $prop, $bindableProps, true)) continue;
            $source = (string) $source;
            if ($source !== '' && $this->bindings->exists($source)) $clean[(string) $prop] = $source;
        }
        return $clean;
    }
}
