<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required content migration source file missing: {$relative}";
        return '';
    }
    $value = file_get_contents($path);
    if ($value === false) {
        $errors[] = "Unable to read content migration source file: {$relative}";
        return '';
    }
    return $value;
};
$require = static function (string $source, array $needles, string $scope) use (&$errors): void {
    foreach ($needles as $needle => $label) {
        if ($source !== '' && ! str_contains($source, (string) $needle)) {
            $errors[] = "{$scope} missing: {$label}.";
        }
    }
};
$forbid = static function (string $source, array $needles, string $scope) use (&$errors): void {
    foreach ($needles as $needle => $label) {
        if ($source !== '' && str_contains($source, (string) $needle)) {
            $errors[] = "{$scope} contains forbidden {$label}.";
        }
    }
};

$migration = $read('database/migrations/2026_08_22_000100_add_content_migration_engine.php');
$runModel = $read('app/Models/ContentMigrationRun.php');
$itemModel = $read('app/Models/ContentMigrationItem.php');
$reader = $read('app/Nexora/Migrations/WordPress/WordPressWxrReader.php');
$manager = $read('app/Nexora/Migrations/Services/ContentMigrationManager.php');
$importer = $read('app/Nexora/Migrations/Services/WordPressContentImporter.php');
$job = $read('app/Jobs/ProcessContentMigrationJob.php');
$export = $read('app/Nexora/Migrations/Services/ContentExportService.php');
$controller = $read('app/Http/Controllers/Admin/Migrations/ContentMigrationController.php');
$routes = $read('routes/content-migrations.php');
$provider = $read('app/Providers/ContentMigrationServiceProvider.php');
$providers = $read('bootstrap/providers.php');
$ui = $read('resources/js/admin/pages/Admin/Migrations/Index.tsx');
$test = $read('tests/Feature/Migrations/ContentMigrationProductTest.php');
$progress = $read('NEXORA_PROGRESS.md');

$require($migration, [
    "Schema::create('nx_content_migration_runs'" => 'tenant migration run table',
    "\$table->unique(['tenant_id', 'source_type', 'source_hash']" => 'source replay uniqueness',
    "Schema::create('nx_content_migration_items'" => 'per-item replay table',
    "\$table->unique(['migration_run_id', 'source_key']" => 'per-run item replay uniqueness',
], 'migration schema');
$require($runModel, ['use BelongsToTenant;' => 'tenant-scoped run model'], 'run model');
$require($itemModel, ['use BelongsToTenant;' => 'tenant-scoped item model'], 'item model');

$require($reader, [
    '52_428_800' => '50 MB WXR limit',
    'LIBXML_NONET' => 'network-disabled XML parser',
    'XMLReader::LOADDTD, false' => 'DTD loading disabled',
    'XMLReader::SUBST_ENTITIES, false' => 'entity substitution disabled',
    "['post', 'page']" => 'bounded WordPress post-type allow-list',
], 'WXR reader');
$forbid($reader, [
    'LIBXML_NOENT' => 'external/general entity expansion',
    'LIBXML_DTDLOAD' => 'DTD loading flag',
], 'WXR reader');

$require($manager, [
    "in_array(\$extension, ['xml', 'wxr'], true)" => 'source extension allow-list',
    "stripos(\$header, 'wordpress.org/export')" => 'WXR signature check',
    "hash_file('sha256', \$temporaryPath)" => 'source SHA-256 fingerprint',
    "'nexora/migrations/'.\$organization->id" => 'server-controlled tenant staging path',
    "in_array(\$existing->status, ['failed', 'completed_with_errors'], true)" => 'partial run resumability',
    "ProcessContentMigrationJob::dispatch(\$run->id)->onQueue('migrations')" => 'dedicated migration queue',
], 'migration manager');

$require($job, [
    "withoutGlobalScope('nexora_tenant')" => 'unscoped tenant identity lookup',
    '$tenantScope->runRequired(' => 'required queue tenant restoration',
    'lockForUpdate()->findOrFail($this->runId)' => 'atomic run claim',
    '$seen > 20_000' => '20k item safety limit',
    "'completed_with_errors'" => 'partial completion state',
    "Storage::disk('local')->delete(\$run->source_path)" => 'successful source cleanup',
], 'migration job');

$require($importer, [
    'DocumentRepositoryContract $documents' => 'canonical document repository use',
    "if (\$item->status === 'imported'" => 'already-imported replay skip',
    'strlen($rawContent) > 2_097_152' => 'per-item content bound',
    "'remote_media_fetch' => false" => 'remote media disabled',
    "in_array(\$scheme, ['http', 'https'], true)" => 'metadata URL scheme allow-list',
    "return 'failed';" => 'committed sanitized item failure state',
], 'WordPress importer');
$forbid($importer, [
    'Http::' => 'network fetch during import',
    'file_get_contents($url' => 'direct remote URL fetch',
], 'WordPress importer');

$require($export, [
    "nexora.documents.export.v1" => 'versioned export schema',
    '->chunkById(100' => 'bounded streaming query chunks',
    "'Cache-Control' => 'private, no-store, max-age=0'" => 'private no-store export',
], 'document export');
$require($controller, [
    'ContentMigrationRun::query()' => 'tenant-scoped run listing/re-resolution',
    'public function resume(string $run' => 'scalar run route argument',
    "['required', 'file', 'max:51200']" => 'web upload size bound',
], 'migration controller');
$require($routes, [
    "EnsureTenantRouteBinding::class" => 'Admin tenant binding',
    "permission:documents.create" => 'import owning-engine permission',
    "permission:documents.view" => 'view/export owning-engine permission',
    "->whereUuid('run')" => 'bounded resume route ID',
], 'migration routes');
$require($provider, [
    "base_path('routes/content-migrations.php')" => 'route provider boot',
    "'href' => '/admin/migrations'" => 'Admin navigation registration',
], 'migration provider');
$require($providers, ['ContentMigrationServiceProvider::class' => 'provider bootstrap'], 'provider bootstrap');
$require($ui, [
    'WordPress WXR import' => 'WXR upload UX',
    'Remote media is not fetched by Core' => 'SSRF-safe UX disclosure',
    '/admin/migrations/export/documents' => 'export UX',
    '/resume' => 'resume UX',
], 'migration UI');
$require($test, [
    'test_wordpress_wxr_is_staged_on_server_path_deduplicated_and_imported_through_tenant_document_engine' => 'WXR replay/tenant acceptance',
    'test_failed_item_state_is_persisted_without_creating_a_destination_document' => 'failed-item persistence acceptance',
    'test_streaming_export_contains_only_the_active_tenant_documents' => 'tenant export isolation acceptance',
], 'migration acceptance');

if ($progress !== '' && ! str_contains($progress, 'GitHub Actions: **DEFERRED BY USER')) {
    $errors[] = 'progress governance missing Actions quota deferral state.';
}
if ($progress !== '' && ! str_contains($progress, 'TARGET POWER    50.0%')) {
    $errors[] = 'progress governance missing unchanged Target Power evidence boundary.';
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Content Migration Product Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(STDOUT, '[Nexora Content Migration Product Contract] PASS — tenant-owned replay-safe migration state, local-only bounded WXR parsing, canonical Document repository imports, resumable queue tenant restoration, no Core remote-media fetch, private streaming export and acceptance source are present.'.PHP_EOL);
