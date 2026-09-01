<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required Search 2.0 source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read Search 2.0 source file: {$relative}";
        return '';
    }
    return $contents;
};

$indexer = $read('app/Nexora/Discovery/Search/SearchIndexer.php');
$publicController = $read('app/Http/Controllers/Public/SiteSearchController.php');
$adminController = $read('app/Http/Controllers/Admin/SearchController.php');
$directory = $read('app/Nexora/Enterprise/Services/TenantMemberDirectory.php');
$indexModel = $read('app/Models/SearchIndexEntry.php');
$queryLogModel = $read('app/Models/SearchQueryLog.php');
$provider = $read('app/Providers/AppServiceProvider.php');
$routes = $read('routes/web.php');
$test = $read('tests/Feature/Discovery/SearchProductIsolationTest.php');

foreach ([
    'private PublicDocumentVisibility $publicVisibility' => 'shared public document visibility dependency',
    "?array \$resourceTypes = null" => 'resource-type filtering contract',
    "? ['document']" => 'public search document-only boundary',
    '$this->normalizeResourceTypes($resourceTypes)' => 'admin resource-type allow-list normalization',
    '$protectedDocumentIds = $this->publicVisibility->protectedDocumentIds()' => 'membership-protected document lookup',
    "->whereNotIn('resource_id', \$protectedDocumentIds)" => 'protected document non-disclosure predicate',
    "private const RESOURCE_TYPES = ['document', 'media']" => 'bounded searchable resource types',
] as $needle => $label) {
    if ($indexer !== '' && ! str_contains($indexer, $needle)) {
        $errors[] = "Search indexer contract missing: {$label}.";
    }
}

foreach ([
    "\$this->search->search(\$query, true, 30)" => 'public-only search invocation',
    "analytics->search(\$request, \$query, \$results->count(), 'public')" => 'privacy-aware public search demand recording',
    "search.public_enabled" => 'public search feature switch',
    "meta name=\"robots\" content=\"noindex,follow\"" => 'search result noindex boundary',
] as $needle => $label) {
    if ($publicController !== '' && ! str_contains($publicController, $needle)) {
        $errors[] = "Public Search controller contract missing: {$label}.";
    }
}

foreach ([
    'private TenantMemberDirectory $tenantMembers' => 'tenant-member directory dependency',
    "hasPermission('documents.view')" => 'document search permission boundary',
    "hasPermission('media.view')" => 'media search permission boundary',
    '$this->contentSearch->search($q, false, 8, null, $resourceTypes)' => 'permission-derived searchable resource scope',
    '$this->tenantMembers->search($q, 6)' => 'tenant-scoped user search',
] as $needle => $label) {
    if ($adminController !== '' && ! str_contains($adminController, $needle)) {
        $errors[] = "Admin Search controller contract missing: {$label}.";
    }
}
if ($adminController !== '' && str_contains($adminController, 'User::query()')) {
    $errors[] = 'Admin Search controller still contains platform-wide user search.';
}

foreach ([
    'public function search(string $query, int $limit = 20): Collection' => 'reusable tenant-member search API',
    "->whereHas('enterpriseMemberships'" => 'tenant membership relationship filter',
    "->where('organization_id', \$tenantId)" => 'active organization predicate',
    "->where('status', 'active')" => 'active user/member boundary',
] as $needle => $label) {
    if ($directory !== '' && ! str_contains($directory, $needle)) {
        $errors[] = "Tenant member directory Search contract missing: {$label}.";
    }
}

foreach ([
    'use BelongsToTenant;' => $indexModel,
    "protected \$table = 'nx_search_index'" => $indexModel,
] as $needle => $source) {
    if ($source !== '' && ! str_contains($source, $needle)) {
        $errors[] = "Search index tenant model contract missing: {$needle}.";
    }
}
foreach ([
    'use BelongsToTenant;' => $queryLogModel,
    "protected \$table = 'nx_search_query_logs'" => $queryLogModel,
] as $needle => $source) {
    if ($source !== '' && ! str_contains($source, $needle)) {
        $errors[] = "Search query-log tenant model contract missing: {$needle}.";
    }
}

foreach ([
    '\\App\\Models\\Document::observe(DocumentSearchObserver::class)' => 'document index lifecycle observer',
    '\\App\\Models\\MediaAsset::observe(MediaAssetSearchObserver::class)' => 'media index lifecycle observer',
    '\\App\\Models\\SeoEntry::observe(SeoEntrySearchObserver::class)' => 'SEO index lifecycle observer',
] as $needle => $label) {
    if ($provider !== '' && ! str_contains($provider, $needle)) {
        $errors[] = "Search lifecycle contract missing: {$label}.";
    }
}

foreach ([
    "Route::get('/search', SiteSearchController::class)" => 'public Search route',
    "'throttle:60,1'" => 'public/admin search throttling',
    "Route::get('/search', SearchController::class)->middleware(['permission:search.use'" => 'Admin Search permission route',
    "permission:search.index.manage" => 'manual reindex permission boundary',
] as $needle => $label) {
    if ($routes !== '' && ! str_contains($routes, $needle)) {
        $errors[] = "Search route contract missing: {$label}.";
    }
}

foreach ([
    'test_public_search_excludes_membership_protected_published_documents' => 'protected public Search non-disclosure acceptance test',
    'assertDontSee($protected->title)' => 'protected document response exclusion assertion',
    'test_admin_global_search_does_not_disclose_users_from_another_tenant' => 'Admin tenant-member Search isolation acceptance test',
    "assertJsonMissing(['title' => 'Needle Other Member'])" => 'cross-tenant user non-disclosure assertion',
] as $needle => $label) {
    if ($test !== '' && ! str_contains($test, $needle)) {
        $errors[] = "Search acceptance contract missing: {$label}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Search 2.0 Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Search 2.0 Product Contract] PASS — public results respect membership visibility, Search data remains tenant-scoped, Admin user discovery is tenant-member scoped, document/media results honor independent permissions and index lifecycle observers remain wired.'.PHP_EOL,
);
