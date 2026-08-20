<?php

declare(strict_types=1);

namespace App\Nexora\Themes\Services;

use App\Nexora\Themes\Data\ThemeManifest;

final class ThemeManifestValidator
{
    public function parse(string $contents, array $packageManifest): ThemeManifest
    {
        try {
            $manifest = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('theme.json is invalid JSON: '.$exception->getMessage());
        }

        if (! is_array($manifest)) {
            throw new \InvalidArgumentException('theme.json must contain a JSON object.');
        }

        $identifier = trim((string) ($manifest['id'] ?? ''));
        $name = trim((string) ($manifest['name'] ?? ''));
        $version = trim((string) ($manifest['version'] ?? ''));
        $engine = trim((string) ($manifest['engine'] ?? 'nexora-safe-html'));

        if ($identifier === '' || preg_match('/^[a-z0-9](?:[a-z0-9._-]{1,126})[a-z0-9]$/', $identifier) !== 1) {
            throw new \InvalidArgumentException('theme.json requires a valid lowercase theme id.');
        }
        if ($name === '') {
            throw new \InvalidArgumentException('theme.json requires a human-readable name.');
        }
        if (preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $version) !== 1) {
            throw new \InvalidArgumentException('theme.json version must use semantic versioning.');
        }
        if ($engine !== 'nexora-safe-html') {
            throw new \InvalidArgumentException('N0.20 supports the non-executable nexora-safe-html theme engine only.');
        }
        if (($packageManifest['type'] ?? null) !== 'theme') {
            throw new \InvalidArgumentException('The scanned package is not declared as a Nexora theme.');
        }
        $nexoraConstraint = $packageManifest['requires']['nexora'] ?? null;
        if (! is_string($nexoraConstraint) || trim($nexoraConstraint) === '') {
            throw new \InvalidArgumentException('Theme package nexora.json must declare requires.nexora compatibility.');
        }
        if (($packageManifest['id'] ?? null) !== $identifier || ($packageManifest['version'] ?? null) !== $version) {
            throw new \InvalidArgumentException('nexora.json and theme.json package identity/version must match exactly.');
        }

        $templates = $manifest['templates'] ?? [];
        if (! is_array($templates) || ! isset($templates['home'], $templates['document'])) {
            throw new \InvalidArgumentException('Theme manifest must declare home and document templates.');
        }
        $normalizedTemplates = [];
        foreach ($templates as $key => $path) {
            if (! is_string($key) || ! is_string($path)) {
                throw new \InvalidArgumentException('Theme template mappings must contain string keys and paths.');
            }
            $this->assertRelativePath($path, ['html']);
            $normalizedTemplates[$key] = $path;
        }

        $stylesheet = isset($manifest['stylesheet']) ? (string) $manifest['stylesheet'] : null;
        if ($stylesheet !== null && $stylesheet !== '') {
            $this->assertRelativePath($stylesheet, ['css']);
        } else {
            $stylesheet = null;
        }

        $screenshot = isset($manifest['screenshot']) ? (string) $manifest['screenshot'] : null;
        if ($screenshot !== null && $screenshot !== '') {
            $this->assertRelativePath($screenshot, ['png', 'jpg', 'jpeg', 'webp', 'svg']);
        } else {
            $screenshot = null;
        }

        $tokens = $manifest['design_tokens'] ?? [];
        if (! is_array($tokens)) {
            throw new \InvalidArgumentException('design_tokens must be an object.');
        }
        foreach ($tokens as $key => $definition) {
            if (! is_string($key) || preg_match('/^[a-z][a-z0-9._-]{1,100}$/', $key) !== 1 || ! is_array($definition)) {
                throw new \InvalidArgumentException('Every design token requires a stable lowercase key and object definition.');
            }
            $type = (string) ($definition['type'] ?? 'text');
            if (! in_array($type, ['color', 'text', 'number', 'select'], true)) {
                throw new \InvalidArgumentException("Unsupported design token type [{$type}] for [{$key}].");
            }
            if (! array_key_exists('default', $definition)) {
                throw new \InvalidArgumentException("Design token [{$key}] requires a default value.");
            }
            $default = $definition['default'];
            if ($type === 'color' && (! is_string($default) || preg_match('/^#[0-9a-fA-F]{6}$/', $default) !== 1)) {
                throw new \InvalidArgumentException("Design token [{$key}] requires a six-digit hex color default.");
            }
            if ($type === 'number' && ! is_numeric($default)) {
                throw new \InvalidArgumentException("Design token [{$key}] requires a numeric default.");
            }
            if ($type === 'select') {
                $options = array_values(array_map('strval', (array) ($definition['options'] ?? [])));
                foreach ($options as $option) {
                    if (preg_match('/^[A-Za-z0-9 .,%_-]{1,120}$/', $option) !== 1) {
                        throw new \InvalidArgumentException("Design token [{$key}] contains an unsafe select option.");
                    }
                }
                if ($options === [] || ! in_array((string) $default, $options, true)) {
                    throw new \InvalidArgumentException("Design token [{$key}] select default must be one of its declared options.");
                }
            }
            if ($type === 'text' && (! is_string($default) || preg_match('/^[A-Za-z0-9 .,_-]{0,120}$/', $default) !== 1)) {
                throw new \InvalidArgumentException("Design token [{$key}] text default contains unsafe CSS characters.");
            }
        }

        return new ThemeManifest(
            identifier: $identifier,
            name: $name,
            version: $version,
            nexoraConstraint: trim($nexoraConstraint),
            description: trim((string) ($manifest['description'] ?? '')),
            engine: $engine,
            templates: $normalizedTemplates,
            designTokens: $tokens,
            stylesheet: $stylesheet,
            screenshot: $screenshot,
        );
    }

    /** @param list<string> $extensions */
    private function assertRelativePath(string $path, array $extensions): void
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '' || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\//', $path) === 1 || in_array('..', explode('/', $path), true)) {
            throw new \InvalidArgumentException("Unsafe theme path [{$path}].");
        }
        if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $extensions, true)) {
            throw new \InvalidArgumentException("Theme path [{$path}] has an unsupported file type.");
        }
    }
}
