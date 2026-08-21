<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$build = $root.'/public/build';
$manifestPath = $build.'/manifest.json';
$reportDir = $root.'/storage/app/nexora/certification';
$config = require $root.'/config/nexora-performance.php';
$platform = require $root.'/config/nexora.php';
require_once $root.'/scripts/lib/source-attestation.php';
$sourceAttestation=nexoraComputeSourceAttestation($root);
$budgets = (array) ($config['budgets'] ?? []);
$errors = [];
$warnings = [];

if (is_file($root.'/public/hot')) $errors[] = 'public/hot must not exist for a production build.';
if (! is_file($manifestPath)) $errors[] = 'public/build/manifest.json is missing. Run npm run build first.';
if ($errors !== []) {
    fwrite(STDERR, "[Nexora Build Assets] FAILED\n - ".implode("\n - ",$errors)."\n");
    exit(1);
}

try {
    $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    fwrite(STDERR, "[Nexora Build Assets] Invalid manifest JSON: {$e->getMessage()}\n");
    exit(1);
}
if (! is_array($manifest)) $errors[] = 'Vite manifest must decode to an object.';
foreach (['resources/js/app.tsx','resources/css/app.css'] as $entry) {
    if (! array_key_exists($entry, $manifest)) $errors[] = 'Vite manifest missing entry: '.$entry;
}

$files = [];
$totals = ['build'=>0,'js'=>0,'css'=>0,'font'=>0,'image'=>0,'js_gzip'=>0,'initial_js_gzip'=>0];
$counts = ['js'=>0,'css'=>0,'font'=>0,'image'=>0,'map'=>0,'initial_js'=>0];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($build, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (! $file->isFile()) continue;
    $absolute = $file->getPathname();
    $relative = str_replace('\\','/',substr($absolute, strlen($root)+1));
    $ext = strtolower($file->getExtension());
    $size = $file->getSize();
    $totals['build'] += $size;
    $category = null;
    if (in_array($ext,['js','mjs'],true)) $category='js';
    elseif ($ext==='css') $category='css';
    elseif (in_array($ext,['woff','woff2','ttf','otf'],true)) $category='font';
    elseif (in_array($ext,['png','jpg','jpeg','gif','webp','avif','svg','ico'],true)) $category='image';
    elseif ($ext==='map') $category='map';

    if ($category !== null) $counts[$category]++;
    if (in_array($category,['js','css','font','image'],true)) $totals[$category] += $size;
    if ($category === 'js' && function_exists('gzencode')) {
        $encoded = gzencode((string) file_get_contents($absolute), 9);
        if (is_string($encoded)) $totals['js_gzip'] += strlen($encoded);
    }
    if ($category === 'map') $errors[] = 'source map must not ship in production build: '.$relative;
    if (in_array($category,['js','css'],true) && $relative !== 'public/build/manifest.json' && preg_match('/-[A-Za-z0-9_-]{8,}\.(?:js|mjs|css)$/', basename($relative)) !== 1) {
        $errors[] = 'production JS/CSS asset is not content-hashed: '.$relative;
    }
    if ($category === 'js' && $size > (int)$budgets['javascript_asset_bytes']) $errors[] = "JS asset budget exceeded: {$relative} {$size} > {$budgets['javascript_asset_bytes']}";
    if ($category === 'css' && $size > (int)$budgets['css_asset_bytes']) $errors[] = "CSS asset budget exceeded: {$relative} {$size} > {$budgets['css_asset_bytes']}";
    if ($category === 'font' && $size > (int)$budgets['font_asset_bytes']) $errors[] = "Font asset budget exceeded: {$relative} {$size} > {$budgets['font_asset_bytes']}";
    if ($category === 'image' && $size > (int)$budgets['image_asset_bytes']) $errors[] = "Image asset budget exceeded: {$relative} {$size} > {$budgets['image_asset_bytes']}";
    if (in_array($category,['js','css'],true)) {
        $source=(string)file_get_contents($absolute);
        foreach (['localhost:5173','127.0.0.1:5173','D:\\laragon\\'] as $leak) {
            if (str_contains($source,$leak)) $errors[]="local development path leaked in {$relative}: {$leak}";
        }
        if (preg_match('#/Users/[A-Za-z0-9._-]+/#', $source) === 1) {
            $errors[]="local macOS user-home path leaked in {$relative}";
        }
        if (str_contains($source,'sourceMappingURL=')) $errors[]='sourceMappingURL leaked in production asset: '.$relative;
    }
    $files[] = ['path'=>$relative,'bytes'=>$size,'sha256'=>hash_file('sha256',$absolute),'category'=>$category];
}

foreach ([
    ['build','build_total_bytes'],
    ['js','javascript_total_bytes'],
    ['css','css_total_bytes'],
] as [$totalKey,$budgetKey]) {
    if ($totals[$totalKey] > (int)$budgets[$budgetKey]) $errors[] = "{$totalKey} total budget exceeded: {$totals[$totalKey]} > {$budgets[$budgetKey]}";
}
if (function_exists('gzencode') && $totals['js_gzip'] > (int)$budgets['javascript_gzip_total_bytes']) $errors[] = "gzip JS total budget exceeded: {$totals['js_gzip']} > {$budgets['javascript_gzip_total_bytes']}";
if ($counts['js'] > (int)$budgets['max_javascript_assets']) $errors[] = "too many JS assets: {$counts['js']} > {$budgets['max_javascript_assets']}";
if ($counts['css'] > (int)$budgets['max_css_assets']) $errors[] = "too many CSS assets: {$counts['css']} > {$budgets['max_css_assets']}";

foreach ($manifest as $source => $entry) {
    if (! is_array($entry) || ! isset($entry['file'])) continue;
    $target=$build.'/'.ltrim((string)$entry['file'],'/');
    if (! is_file($target)) $errors[]='manifest points to missing asset: '.$source.' -> '.$entry['file'];
    foreach ((array)($entry['css']??[]) as $css) if (! is_file($build.'/'.ltrim((string)$css,'/'))) $errors[]='manifest points to missing CSS: '.$css;
    foreach ((array)($entry['imports']??[]) as $import) if (! array_key_exists((string)$import,$manifest)) $errors[]='manifest import key missing: '.$import;
}

// Budget only the application entry's static import graph. Vite dynamic imports
// represent lazy route/component chunks and are intentionally excluded from the
// first-load budget while still remaining covered by total/per-asset ceilings.
$initialAssets = [];
$visitedEntries = [];
$walkStaticImports = null;
$walkStaticImports = static function (string $key) use (&$walkStaticImports, &$initialAssets, &$visitedEntries, $manifest, $build, &$errors): void {
    if (isset($visitedEntries[$key])) return;
    $visitedEntries[$key] = true;
    $entry = $manifest[$key] ?? null;
    if (! is_array($entry)) {
        $errors[] = 'initial JS graph references missing manifest entry: '.$key;
        return;
    }
    $file = (string) ($entry['file'] ?? '');
    if ($file !== '') {
        $absolute = $build.'/'.ltrim($file, '/');
        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        if (in_array($ext, ['js','mjs'], true)) {
            if (! is_file($absolute)) {
                $errors[] = 'initial JS graph references missing asset: '.$file;
            } else {
                $initialAssets[$file] = $absolute;
            }
        }
    }
    foreach ((array) ($entry['imports'] ?? []) as $import) {
        $walkStaticImports((string) $import);
    }
};
if (is_array($manifest) && array_key_exists('resources/js/app.tsx', $manifest)) {
    $walkStaticImports('resources/js/app.tsx');
}
$counts['initial_js'] = count($initialAssets);
if (function_exists('gzencode')) {
    foreach ($initialAssets as $absolute) {
        $encoded = gzencode((string) file_get_contents($absolute), 9);
        if (is_string($encoded)) $totals['initial_js_gzip'] += strlen($encoded);
    }
    $initialBudget = (int) ($budgets['initial_javascript_gzip_bytes'] ?? 0);
    if ($initialBudget <= 0) {
        $errors[] = 'initial JavaScript gzip budget is missing or invalid.';
    } elseif ($totals['initial_js_gzip'] > $initialBudget) {
        $errors[] = "initial gzip JS budget exceeded: {$totals['initial_js_gzip']} > {$initialBudget}";
    }
}

if (! is_dir($reportDir) && ! mkdir($reportDir,0775,true) && ! is_dir($reportDir)) $errors[]='unable to create certification report directory.';
$status=$errors===[]?'pass':'fail';
$report=[
    'schema'=>2,
    'status'=>$status,
    'platform_version'=>(string)($platform['version']??'unknown'),
    'source_tree_sha256'=>$sourceAttestation['tree_sha256'],
    'checked_at'=>gmdate(DATE_ATOM),
    'budgets'=>$budgets,
    'totals'=>$totals,
    'counts'=>$counts,
    'initial_javascript_assets'=>array_keys($initialAssets),
    'files'=>$files,
    'errors'=>$errors,
    'warnings'=>$warnings,
];
if (is_dir($reportDir)) file_put_contents($reportDir.'/build-assets.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL);

if ($errors !== []) {
    fwrite(STDERR,"[Nexora Build Assets] FAILED\n - ".implode("\n - ",$errors)."\n");
    exit(1);
}
fwrite(STDOUT,"[Nexora Build Assets] PASS — build {$totals['build']} bytes; JS {$totals['js']} bytes (gzip {$totals['js_gzip']}; initial gzip {$totals['initial_js_gzip']}); CSS {$totals['css']} bytes; {$counts['js']} JS / {$counts['css']} CSS assets.\n");
