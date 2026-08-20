<?php

declare(strict_types=1);

namespace Database\Seeders\Core;

use App\Models\Theme;
use App\Models\ThemeActivation;
use Illuminate\Database\Seeder;

final class NexoraThemeSeeder extends Seeder
{
    public function run(): void
    {
        $root = base_path('themes/nexora-base');
        $themeJson = file_get_contents($root.'/theme.json');
        if (! is_string($themeJson)) {
            throw new \RuntimeException('Built-in Nexora Base theme manifest is missing.');
        }
        $manifest = json_decode($themeJson, true, 64, JSON_THROW_ON_ERROR);
        $hashMaterial = $themeJson;
        foreach (['templates/home.html', 'templates/document.html', 'assets/theme.css'] as $relative) {
            $contents = file_get_contents($root.'/'.$relative);
            if (! is_string($contents)) throw new \RuntimeException("Built-in theme file [{$relative}] is missing.");
            $hashMaterial .= $contents;
        }
        $sha256 = hash('sha256', $hashMaterial);

        $theme = Theme::query()->updateOrCreate(
            ['identifier' => 'nexora.base'],
            ['name' => 'Nexora Base', 'description' => (string) ($manifest['description'] ?? ''), 'is_builtin' => true],
        );
        $version = $theme->versions()->updateOrCreate(
            ['version' => '1.0.0'],
            [
                'engine' => 'nexora-safe-html',
                'install_path' => $root,
                'asset_base_path' => '/nexora-themes/nexora.base/1.0.0',
                'sha256' => $sha256,
                'manifest' => $manifest,
                'source_type' => 'builtin',
                'installed_at' => now(),
            ],
        );

        $publicRoot = public_path('nexora-themes/nexora.base/1.0.0');
        if (! is_dir($publicRoot) && ! mkdir($publicRoot, 0755, true) && ! is_dir($publicRoot)) {
            throw new \RuntimeException('Unable to publish Nexora Base theme assets.');
        }
        foreach (['theme.css', 'screenshot.svg'] as $asset) {
            copy($root.'/assets/'.$asset, $publicRoot.'/'.$asset);
        }

        if (Theme::query()->where('status', 'active')->doesntExist()) {
            $theme->forceFill(['status' => 'active', 'current_version_id' => $version->id, 'activated_at' => now()])->save();
            ThemeActivation::query()->firstOrCreate(
                ['theme_id' => $theme->id, 'theme_version_id' => $version->id, 'action' => 'initial'],
                ['reason' => 'Initial safe fallback theme activation.'],
            );
        } elseif ($theme->current_version_id === null) {
            $theme->forceFill(['current_version_id' => $version->id])->save();
        }
    }
}
