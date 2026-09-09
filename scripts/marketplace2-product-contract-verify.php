<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$paths = [
    'migration' => $root.'/database/migrations/2026_08_22_000400_harden_marketplace_catalog_generation.php',
    'catalog' => $root.'/app/Nexora/Extensions/Services/MarketplaceCatalogService.php',
    'stager' => $root.'/app/Nexora/Extensions/Services/MarketplacePackageStager.php',
    'controller' => $root.'/app/Http/Controllers/Admin/Extensions/ExtensionController.php',
    'config' => $root.'/config/nexora-transfers.php',
    'routes' => $root.'/routes/marketplace.php',
    'test' => $root.'/tests/Feature/Marketplace/Marketplace2HardeningTest.php',
];

$files = [];
$failures = [];
foreach ($paths as $key => $path) {
    if (! is_file($path)) {
        $failures[] = "Missing Marketplace 2.0 source file [{$key}].";
        $files[$key] = '';
        continue;
    }
    $content = file_get_contents($path);
    $files[$key] = is_string($content) ? $content : '';
}

$require = static function (string $key, string $needle, string $message) use (&$files, &$failures): void {
    if (! str_contains($files[$key] ?? '', $needle)) $failures[] = $message;
};
$forbid = static function (string $key, string $needle, string $message) use (&$files, &$failures): void {
    if (str_contains($files[$key] ?? '', $needle)) $failures[] = $message;
};

// Forward-compatible generation identity. Historical rows are intentionally fail-closed until fresh sync.
$require('migration', "uuid('catalog_generation')->nullable()", 'Marketplace sources need nullable catalog generation identity.');
$require('migration', "uuid('sync_generation')->nullable()", 'Marketplace items need nullable sync generation identity.');
$require('migration', 'Historical catalog rows intentionally remain generation-null', 'Marketplace generation migration must document fail-closed historical behavior.');
$forbid('migration', 'DB::statement', 'Marketplace generation migration must remain portable.');
$forbid('migration', '->after(', 'Marketplace generation migration must not depend on non-portable column placement.');

// Remote catalog bytes are bounded before JSON decoding/normalization.
$require('config', "'max_catalog_bytes' => 8_388_608", 'Marketplace catalog transfer budget must be explicit.');
$require('catalog', "config('nexora-transfers.marketplace.max_catalog_bytes'", 'Marketplace sync must consume the configured catalog byte budget.');
$require('catalog', "'sink' => \$temp", 'Marketplace catalog must stream into bounded temporary storage.');
$require('catalog', "'progress' => static function", 'Marketplace catalog download must abort when streamed bytes exceed budget.');
$require('catalog', "assertSourceFile(\$temp, \$maximum, 'Marketplace catalog')", 'Marketplace catalog bytes must be verified before decode.');
$require('catalog', 'json_decode($raw, true, 512, JSON_THROW_ON_ERROR)', 'Marketplace catalog JSON must be decoded only after bounded download.');
$forbid('catalog', '$response->json()', 'Marketplace sync must not decode an unbounded HTTP response directly.');
$require('catalog', "'package_identifier' => \$identifier", 'Normalized Marketplace entries must carry their package identifier.');

// Every successful synchronization seals one generation across source and all retained catalog items.
$require('catalog', '$generation = (string) Str::uuid();', 'Marketplace sync must mint a new generation identity.');
$require('catalog', "'sync_generation' => \$generation", 'Marketplace items must inherit the successful sync generation.');
$require('catalog', "'catalog_generation' => \$generation", 'Marketplace source must publish the successful catalog generation atomically.');
$require('catalog', 'DB::transaction(function ()', 'Marketplace generation publication must remain atomic with catalog replacement.');

// Dynamic package permissions must honor both global RBAC and the current organization role.
$require('stager', 'TenantAuthorizationService $tenantAuthorization', 'Marketplace stager must depend on current-tenant authorization.');
$require('stager', '! $this->tenantAuthorization->allows($user, $requiredPermission)', 'Marketplace dynamic staging must enforce tenant-role authority.');
$require('stager', "\$requiredPermission = \$item->type === 'theme' ? 'themes.install' : 'extensions.install';", 'Marketplace staging permission must follow the owning package engine.');
$require('stager', '$sourceGeneration = trim((string) $item->source->catalog_generation);', 'Marketplace staging must resolve the source generation.');
$require('stager', '$itemGeneration = trim((string) $item->sync_generation);', 'Marketplace staging must resolve the item generation.');
$require('stager', '! hash_equals($sourceGeneration, $itemGeneration)', 'Marketplace staging must require exact source/item generation equality.');
$forbid('stager', "copy()->subSecond()", 'Timestamp-tolerance freshness checks are forbidden in Marketplace 2.0.');

// Admin visibility/resume behavior mirrors the same fail-closed generation semantics.
$require('controller', "whereNotNull('catalog_generation')", 'Marketplace catalog visibility must require a synchronized source generation.');
$require('controller', "whereNotNull('sync_generation')", 'Marketplace catalog visibility must require synchronized item generation.');
$require('controller', "\$attributes['catalog_generation'] = null;", 'Resuming a Marketplace source must invalidate its previous generation.');
$require('controller', "TenantAuthorizationService \$tenantAuthorization", 'Marketplace Admin capabilities must be tenant-aware.');
$require('controller', "\$tenantAuthorization->allows(\$user, 'marketplace.manage')", 'Marketplace management UI capability must mirror tenant authorization.');

// Canonical dynamic route remains protected by the shared tenant-binding Admin group and service-level type policy.
$require('routes', "Route::post('/catalog/{item}/stage'", 'Marketplace 2.0 canonical catalog staging route is missing.');
$require('routes', "EnsureTenantRouteBinding::class", 'Marketplace 2.0 route group must resolve current tenant context.');
$require('routes', "throttle:8,1", 'Marketplace staging must remain throttled.');

foreach ([
    'test_generation_null_catalog_is_hidden_until_fresh_sync',
    'test_matching_catalog_generation_is_visible',
    'test_generation_mismatch_blocks_staging_before_download',
    'test_current_tenant_role_can_deny_globally_granted_marketplace_stage_permission',
] as $method) {
    $require('test', $method, 'Missing Marketplace 2.0 acceptance regression: '.$method);
}

if ($failures !== []) {
    fwrite(STDERR, "Nexora Marketplace 2.0 Product Contract: FAIL\n");
    foreach (array_values(array_unique($failures)) as $failure) fwrite(STDERR, ' - '.$failure."\n");
    exit(1);
}

fwrite(STDOUT, "Nexora Marketplace 2.0 Product Contract: PASS\n");
fwrite(STDOUT, " - bounded catalog transfer before decode\n");
fwrite(STDOUT, " - atomic source/item sync-generation identity\n");
fwrite(STDOUT, " - tenant-aware owning-engine staging authorization\n");
fwrite(STDOUT, " - fail-closed historical/resumed/stale catalog visibility\n");
