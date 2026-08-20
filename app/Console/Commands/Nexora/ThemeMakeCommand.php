<?php

declare(strict_types=1);

namespace App\Console\Commands\Nexora;

use Illuminate\Console\Command;

final class ThemeMakeCommand extends Command
{
    protected $signature = 'nexora:make:theme {identifier : Lowercase vendor.theme identifier} {--name= : Human-readable theme name}';
    protected $description = 'Scaffold a safe Nexora theme package for development.';

    public function handle(): int
    {
        $identifier = strtolower(trim((string) $this->argument('identifier')));
        if (preg_match('/^[a-z0-9](?:[a-z0-9._-]{1,126})[a-z0-9]$/', $identifier) !== 1) {
            $this->error('Theme identifier must be lowercase and contain letters, numbers, dots, underscores or hyphens.');
            return self::FAILURE;
        }
        $name = trim((string) $this->option('name')) ?: ucwords(str_replace(['.', '_', '-'], ' ', $identifier));
        $root = base_path('packages/themes/'.$identifier);
        if (file_exists($root)) {
            $this->error("Theme scaffold already exists: {$root}");
            return self::FAILURE;
        }
        foreach (['templates', 'assets'] as $directory) {
            if (! mkdir($root.'/'.$directory, 0755, true) && ! is_dir($root.'/'.$directory)) {
                $this->error('Unable to create theme scaffold directory.');
                return self::FAILURE;
            }
        }

        file_put_contents($root.'/nexora.json', json_encode([
            'schema' => '1.0', 'id' => $identifier, 'name' => $name, 'type' => 'theme', 'version' => '1.0.0',
            'requires' => ['nexora' => '>=0.34 <2.0'], 'capabilities' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        file_put_contents($root.'/theme.json', json_encode([
            'id' => $identifier, 'name' => $name, 'version' => '1.0.0', 'description' => 'A safe Nexora theme.', 'engine' => 'nexora-safe-html',
            'templates' => ['home' => 'templates/home.html', 'document' => 'templates/document.html'],
            'stylesheet' => 'assets/theme.css',
            'design_tokens' => [
                'brand.primary' => ['label' => 'Primary color', 'type' => 'color', 'default' => '#7C3AED'],
                'layout.max_width' => ['label' => 'Content width', 'type' => 'select', 'default' => '72rem', 'options' => ['64rem', '72rem', '80rem']],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
        file_put_contents($root.'/templates/home.html', "<!doctype html>\n<html><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">{{ nx_head }}{{ nx_theme_assets }}{{ nx_schema }}</head><body><main><h1>{{ page_title }}</h1><p>{{ tagline }}</p>{{ nx_content }}</main></body></html>\n");
        file_put_contents($root.'/templates/document.html', "<!doctype html>\n<html><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">{{ nx_head }}{{ nx_theme_assets }}{{ nx_schema }}</head><body><main><article><h1>{{ page_title }}</h1>{{ nx_content }}</article></main></body></html>\n");
        file_put_contents($root.'/assets/theme.css', ":root{--theme-brand:var(--nx-theme-brand-primary,#7C3AED)}body{margin:0;font-family:system-ui,sans-serif}main{max-width:var(--nx-theme-layout-max-width,72rem);margin:auto;padding:3rem 1.5rem}a{color:var(--theme-brand)}\n");
        file_put_contents($root.'/README.md', "# {$name}\n\nSafe Nexora theme scaffold. Zip the contents of this directory (with `nexora.json` and `theme.json` at the archive root), then install it from Admin → Themes. Themes cannot ship PHP/JavaScript in the N0.20 runtime.\n");

        $this->info("Created safe Nexora theme scaffold: {$root}");
        return self::SUCCESS;
    }
}
