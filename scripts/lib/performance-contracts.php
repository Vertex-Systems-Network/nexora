<?php

declare(strict_types=1);

/**
 * Dependency-free performance / packaging source contract analysis.
 *
 * @return array{ok:bool,errors:list<string>,warnings:list<string>,metrics:array<string,int|string>}
 */
function nexoraAnalyzePerformanceContracts(string $root): array
{
    $errors = [];
    $warnings = [];
    $read = static fn (string $relative): string => is_file($root.'/'.$relative) ? (string) file_get_contents($root.'/'.$relative) : '';

    foreach ([
        'config/nexora-performance.php',
        'config/nexora-release.php',
        'app/Http/Middleware/ApplyPerformanceHeaders.php',
        'scripts/performance-build-verify.php',
        'scripts/performance-contract-verify.php',
        'docs/n1-0-rc9-performance-packaging-stabilization.md',
    ] as $relative) {
        if (! is_file($root.'/'.$relative) || filesize($root.'/'.$relative) === 0) {
            $errors[] = 'missing-artifact: '.$relative;
        }
    }

    $bootstrap = $read('bootstrap/app.php');
    if (! str_contains($bootstrap, 'ApplyPerformanceHeaders::class')) {
        $errors[] = 'performance middleware is not registered in the web stack.';
    }

    $middleware = $read('app/Http/Middleware/ApplyPerformanceHeaders.php');
    foreach ([
        "'X-Content-Type-Options', 'nosniff'",
        "'Referrer-Policy', 'strict-origin-when-cross-origin'",
        "'X-Frame-Options', 'SAMEORIGIN'",
        "'Permissions-Policy', 'camera=(), microphone=(), geolocation=()'",
        "'Cache-Control', 'no-store, private'",
        'Strict-Transport-Security',
        'Server-Timing',
    ] as $marker) {
        if (! str_contains($middleware, $marker)) $errors[] = 'response header/cache contract missing: '.$marker;
    }

    $vite = $read('vite.config.ts');
    foreach (['sourcemap: false', 'cssCodeSplit: true', 'reportCompressedSize: false', 'chunkSizeWarningLimit: 900'] as $marker) {
        if (! str_contains($vite, $marker)) $errors[] = 'Vite production contract missing: '.$marker;
    }

    $htaccess = $read('public/.htaccess');
    foreach (['BROTLI_COMPRESS', 'DEFLATE', 'NEXORA_IMMUTABLE_ASSET', 'max-age=31536000, immutable', 'X-Content-Type-Options'] as $marker) {
        if (! str_contains($htaccess, $marker)) $errors[] = 'Apache production delivery contract missing: '.$marker;
    }

    $package = json_decode($read('package.json'), true);
    $buildWrapper = $read('scripts/pkg1-build.php');
    $directBuild = is_array($package)
        && ($package['scripts']['build'] ?? null) === 'tsc --noEmit && vite build';
    $provenanceBuild = is_array($package)
        && ($package['scripts']['build'] ?? null) === 'php scripts/pkg1-build.php'
        && ($package['scripts']['build:raw'] ?? null) === 'tsc --noEmit && vite build'
        && str_contains($buildWrapper, 'NEXORA_BUILD_IDENTITY')
        && str_contains($buildWrapper, 'npm run build:raw');
    if (! $directBuild && ! $provenanceBuild) {
        $errors[] = 'package build must retain semantic TypeScript check before Vite build.';
    }

    $cert = $read('scripts/certify-release.php');
    foreach (['performance-contract-verify.php', 'performance-build-verify.php', "'artisan-optimize-boot'", "'production-package'"] as $marker) {
        if (! str_contains($cert, $marker)) $errors[] = 'certification performance gate missing: '.$marker;
    }

    $builder = $read('scripts/build-production-release.php');
    foreach (['config/nexora-release.php', 'build-assets.json', 'performance_report_sha256', 'forbidden_archive_prefixes'] as $marker) {
        if (! str_contains($builder, $marker)) $errors[] = 'production release policy integration missing: '.$marker;
    }

    $performanceConfig = is_file($root.'/config/nexora-performance.php') ? require $root.'/config/nexora-performance.php' : [];
    $budgets = is_array($performanceConfig) ? (array) ($performanceConfig['budgets'] ?? []) : [];
    foreach (['build_total_bytes','javascript_total_bytes','javascript_asset_bytes','css_total_bytes','css_asset_bytes','font_asset_bytes','image_asset_bytes'] as $budget) {
        if ((int) ($budgets[$budget] ?? 0) <= 0) $errors[] = 'invalid performance budget: '.$budget;
    }

    $releaseConfig = is_file($root.'/config/nexora-release.php') ? require $root.'/config/nexora-release.php' : [];
    foreach (['excluded_top','excluded_files','excluded_prefixes','required_archive_entries','forbidden_archive_entries','forbidden_archive_prefixes'] as $key) {
        if (! isset($releaseConfig[$key]) || ! is_array($releaseConfig[$key])) $errors[] = 'release policy array missing: '.$key;
    }
    foreach (['.env','public/hot'] as $forbidden) {
        if (! in_array($forbidden, (array) ($releaseConfig['forbidden_archive_entries'] ?? []), true)) {
            $errors[] = 'release policy must forbid '.$forbidden;
        }
    }

    $publicAssetCount = 0;
    $largestPublicAsset = 0;
    $largestPublicAssetName = '';
    $assetLimit = (int) ($budgets['static_public_asset_bytes'] ?? 1750000);
    $publicRoot = $root.'/public';
    if (is_dir($publicRoot)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($publicRoot, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (! $file->isFile()) continue;
            $relative = str_replace('\\','/',substr($file->getPathname(), strlen($root)+1));
            if (str_starts_with($relative, 'public/build/')) continue;
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['png','jpg','jpeg','gif','webp','avif','svg','ico','woff','woff2','ttf','otf','js','css'], true)) continue;
            $publicAssetCount++;
            $size = $file->getSize();
            if ($size > $largestPublicAsset) { $largestPublicAsset = $size; $largestPublicAssetName = $relative; }
            if ($size > $assetLimit) $errors[] = "static public asset exceeds {$assetLimit} bytes: {$relative} ({$size})";
        }
    }

    $lazyPreviewFiles = [
        'resources/js/admin/components/writer/BlockEditor.tsx',
        'resources/js/admin/pages/Admin/Publishing/ArticleSettings.tsx',
        'resources/js/admin/pages/Admin/Appearance/Themes.tsx',
        'resources/js/admin/pages/Admin/Media/Index.tsx',
    ];
    foreach ($lazyPreviewFiles as $relative) {
        $source = $read($relative);
        if (! str_contains($source, 'loading="lazy"') || ! str_contains($source, 'decoding="async"')) {
            $errors[] = 'non-critical preview image must be lazy/async: '.$relative;
        }
    }

    return [
        'ok' => $errors === [],
        'errors' => array_values(array_unique($errors)),
        'warnings' => $warnings,
        'metrics' => [
            'static_public_assets' => $publicAssetCount,
            'largest_static_public_asset_bytes' => $largestPublicAsset,
            'largest_static_public_asset' => $largestPublicAssetName,
            'release_required_entries' => count((array) ($releaseConfig['required_archive_entries'] ?? [])),
            'release_forbidden_prefixes' => count((array) ($releaseConfig['forbidden_archive_prefixes'] ?? [])),
        ],
    ];
}
