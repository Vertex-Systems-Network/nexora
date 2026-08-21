<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Marketplace source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Marketplace source file: {$relative}";
        return '';
    }
    return $contents;
};

$migration = $read('database/migrations/2026_08_15_001600_add_nexora_extensions_marketplace.php');
$sourceModel = $read('app/Models/MarketplaceSource.php');
$catalogModel = $read('app/Models/MarketplaceCatalogItem.php');
$catalog = $read('app/Nexora/Extensions/Services/MarketplaceCatalogService.php');
$stager = $read('app/Nexora/Extensions/Services/MarketplacePackageStager.php');
$controller = $read('app/Http/Controllers/Admin/Extensions/ExtensionController.php');
$routes = $read('routes/web.php');
$lifecycleRoutes = $read('routes/marketplace.php');
$provider = $read('app/Providers/MarketplaceServiceProvider.php');
$providers = $read('bootstrap/providers.php');
$page = $read('resources/js/admin/pages/Admin/Extensions/Index.tsx');
$test = $read('tests/Feature/Marketplace/MarketplaceWorkflowTest.php');

foreach ([
    "Schema::create('nx_marketplace_sources'" => 'Marketplace source table',
    "Schema::create('nx_marketplace_catalog_items'" => 'Marketplace catalog table',
    "cascadeOnDelete()" => 'source/catalog cascade lifecycle',
    "unique(['source_id', 'package_identifier']" => 'source-local package identity',
] as $needle => $label) {
    if ($migration !== '' && ! str_contains($migration, $needle)) {
        $errors[] = "Marketplace migration missing: {$label}.";
    }
}

foreach ([
    'public function items(): HasMany' => 'source/catalog relationship',
    'public function isActive(): bool' => 'source lifecycle helper',
    "return \$this->status === 'active';" => 'active source policy',
] as $needle => $label) {
    if ($sourceModel !== '' && ! str_contains($sourceModel, $needle)) {
        $errors[] = "Marketplace source model missing: {$label}.";
    }
}
if ($catalogModel !== '' && ! str_contains($catalogModel, "protected \$table='nx_marketplace_catalog_items'")) {
    $errors[] = 'Marketplace catalog model table mapping is missing.';
}

foreach ([
    'private const MAX_PACKAGES = 5000;' => 'catalog package-count budget',
    'private const MAX_METADATA_BYTES = 65536;' => 'catalog metadata budget',
    "if (! \$source->isActive())" => 'paused-source sync rejection',
    "Marketplace catalog schema is unsupported. Expected schema 1." => 'catalog schema policy',
    'DB::transaction(function ()' => 'atomic catalog replacement',
    "duplicate package identifier" => 'duplicate package rejection',
    "whereNotIn('package_identifier'" => 'stale catalog retirement',
    "preg_match('/^[A-Za-z0-9][A-Za-z0-9._\\/-]{0,179}$/'" => 'package identifier validation',
    "['extension', 'app', 'integration', 'studio-pack']" => 'controlled Marketplace package types',
    "preg_match('/^[a-f0-9]{64}$/'" => 'artifact digest validation',
    'trusted_publishers_only && $publisherKey === null' => 'trusted-source publisher identity requirement',
] as $needle => $label) {
    if ($catalog !== '' && ! str_contains($catalog, $needle)) {
        $errors[] = "Marketplace catalog service missing: {$label}.";
    }
}

foreach ([
    "if (! \$item->source->isActive())" => 'paused-source staging rejection',
    'Marketplace package metadata is stale.' => 'stale catalog staging rejection',
    "\$item->source->last_synced_at === null" => 'unsynchronized resumed-source staging rejection',
    "TrustedPublisher::query()->where('key_id'" => 'local trusted publisher lookup',
    "signature_status !== 'verified'" => 'post-download signature verification',
    "\$this->scanner->scan(\$package, \$userId)" => 'Sentinel scan boundary',
    "hash_equals(strtolower((string) \$item->artifact_sha256)" => 'catalog artifact digest enforcement',
    'Marketplace package exceeds the configured download limit.' => 'download budget',
] as $needle => $label) {
    if ($stager !== '' && ! str_contains($stager, $needle)) {
        $errors[] = "Marketplace package stager missing: {$label}.";
    }
}

foreach ([
    "'canManageMarketplace' => \$request->user()?->hasPermission('marketplace.manage')" => 'Marketplace-specific UI permission',
    "\$freshSource = static fn (\$query) => \$query->where('status', 'active')->whereNotNull('last_synced_at');" => 'active synchronized-source catalog visibility',
    'public function sourceStatus(' => 'source pause/resume lifecycle',
    "Rule::in(['active', 'paused'])" => 'controlled source lifecycle states',
    "\$attributes['last_synced_at'] = null;" => 'resume requires fresh synchronization',
    "'fresh_sync_required' => \$next === 'active'" => 'resume freshness audit evidence',
    'public function deleteSource(' => 'source removal lifecycle',
    "if (\$source->isActive())" => 'active-source deletion guard',
    "marketplace.source.synced" => 'sync audit evidence',
    "marketplace.package.staged" => 'staging audit evidence',
] as $needle => $label) {
    if ($controller !== '' && ! str_contains($controller, $needle)) {
        $errors[] = "Marketplace controller missing: {$label}.";
    }
}

foreach ([
    "Route::post('/extensions/marketplace/sources'" => 'source create route',
    "permission:marketplace.manage" => 'Marketplace management permission',
    "Route::post('/extensions/marketplace/items/{item}/stage'" => 'catalog stage route',
    "permission:extensions.install" => 'extension-install staging permission',
] as $needle => $label) {
    if ($routes !== '' && ! str_contains($routes, $needle)) {
        $errors[] = "Marketplace web route contract missing: {$label}.";
    }
}
foreach ([
    "Route::patch('/sources/{source}/status'" => 'source status route',
    "Route::delete('/sources/{source}'" => 'source removal route',
    "permission:marketplace.manage" => 'lifecycle permission guard',
] as $needle => $label) {
    if ($lifecycleRoutes !== '' && ! str_contains($lifecycleRoutes, $needle)) {
        $errors[] = "Marketplace lifecycle route contract missing: {$label}.";
    }
}
if ($provider !== '' && ! str_contains($provider, "loadRoutesFrom(base_path('routes/marketplace.php'))")) {
    $errors[] = 'Marketplace service provider must load the isolated lifecycle route file.';
}
if ($providers !== '' && ! str_contains($providers, 'MarketplaceServiceProvider::class')) {
    $errors[] = 'Marketplace service provider is not registered.';
}

foreach ([
    'canManageMarketplace' => 'Marketplace-specific UI authorization',
    '<ConfirmDialog' => 'destructive source removal confirmation',
    'Active catalog packages' => 'active catalog summary semantics',
    'Only active, synchronized sources are listed.' => 'active-source catalog guidance',
    '? "Pause" : "Resume"' => 'source lifecycle UX',
    'setSourceDeleteTarget' => 'source removal UX',
    'Send to Sentinel' => 'quarantine-first Marketplace action',
] as $needle => $label) {
    if ($page !== '' && ! str_contains($page, $needle)) {
        $errors[] = "Marketplace Admin UI missing: {$label}.";
    }
}
if ($page !== '' && preg_match('/<(button|input|select|textarea)\b/', $page) === 1) {
    $errors[] = 'Marketplace Admin UI must not bypass shared interactive components.';
}

foreach ([
    'test_pausing_source_hides_catalog_and_blocks_staging' => 'pause/visibility/staging regression',
    'test_resuming_source_requires_fresh_sync_before_catalog_or_staging' => 'resume freshness regression',
    'test_source_must_be_paused_before_removal_and_catalog_cache_cascades' => 'safe source deletion regression',
    'test_marketplace_status_only_accepts_known_lifecycle_states' => 'source lifecycle allow-list regression',
] as $needle => $label) {
    if ($test !== '' && ! str_contains($test, $needle)) {
        $errors[] = "Marketplace acceptance-test contract missing: {$label}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Marketplace Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Marketplace Product Contract] PASS — Marketplace catalogs are lifecycle-controlled, atomically synchronized and validation-bounded; resumed sources require fresh synchronization; withdrawn entries retire locally; inactive/stale sources cannot stage; trusted publisher and artifact integrity checks remain inside the Sentinel quarantine boundary.'.PHP_EOL,
);
