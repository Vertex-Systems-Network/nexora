<?php

declare(strict_types=1);

namespace App\Nexora\Themes\Services;

use App\Models\ThemeVersion;
use App\Nexora\Themes\Contracts\ThemeManagerContract;
use App\Nexora\Themes\Contracts\ThemeRendererContract;

final readonly class SafeThemeRenderer implements ThemeRendererContract
{
    public function __construct(private ThemeManagerContract $themes)
    {
    }

    public function render(string $template, array $payload = [], ?ThemeVersion $version = null): string
    {
        $version ??= $this->themes->active();
        if ($version === null) throw new \RuntimeException('No active Nexora theme is available.');
        if ($version->engine !== 'nexora-safe-html') throw new \RuntimeException('Unsupported theme renderer engine.');

        $manifest = (array) $version->manifest;
        $relative = (array) ($manifest['templates'] ?? []);
        $file = $relative[$template] ?? $relative['home'] ?? null;
        if (! is_string($file)) throw new \RuntimeException("Theme template [{$template}] is not declared.");
        $path = rtrim($version->install_path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file);
        if (! is_file($path)) throw new \RuntimeException("Theme template [{$template}] is missing.");
        $html = file_get_contents($path);
        if (! is_string($html)) throw new \RuntimeException('Unable to read active theme template.');

        $tokens = $this->themes->tokens($version);
        $cssVars = [];
        foreach ($tokens as $key => $value) {
            $cssKey = '--nx-theme-'.preg_replace('/[^a-z0-9-]+/', '-', str_replace('.', '-', strtolower((string) $key)));
            $cssVars[] = $cssKey.':'.htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').';';
        }
        $stylesheet = is_string($manifest['stylesheet'] ?? null) ? $manifest['stylesheet'] : null;
        $styleLink = '';
        if ($stylesheet !== null && $version->asset_base_path !== null) {
            $asset = preg_replace('#^assets/#', '', str_replace('\\', '/', $stylesheet)) ?: basename($stylesheet);
            $styleLink = '<link rel="stylesheet" href="'.htmlspecialchars(rtrim($version->asset_base_path, '/').'/'.$asset, ENT_QUOTES, 'UTF-8').'">';
        }

        $rawSlots = [
            'nx_head' => (string) ($payload['nx_head'] ?? ''),
            'nx_content' => (string) ($payload['nx_content'] ?? ''),
            'nx_schema' => (string) ($payload['nx_schema'] ?? ''),
            'nx_theme_assets' => $styleLink.'<style>:root{'.implode('', $cssVars).'}</style>',
        ];
        foreach ($rawSlots as $key => $value) {
            $html = str_replace('{{ '.$key.' }}', $value, $html);
        }

        foreach ($payload as $key => $value) {
            if (isset($rawSlots[$key]) || ! is_scalar($value)) continue;
            $html = str_replace('{{ '.$key.' }}', htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $html);
        }

        return preg_replace('/\{\{\s*[a-zA-Z0-9_.-]+\s*\}\}/', '', $html) ?? $html;
    }
}
