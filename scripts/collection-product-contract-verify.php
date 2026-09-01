<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$errors=[];
$read=static function(string $relative) use($root,&$errors): string {
    $path=$root.'/'.$relative;
    if(!is_file($path)){ $errors[]="Required Collection Product source file missing: {$relative}"; return ''; }
    $contents=file_get_contents($path);
    if($contents===false){ $errors[]="Unable to read Collection Product source file: {$relative}"; return ''; }
    return $contents;
};

$migration=$read('database/migrations/2026_08_21_000100_add_nexora_content_collections.php');
$model=$read('app/Models/ContentCollection.php');
$document=$read('app/Models/Document.php');
$schema=$read('app/Nexora/Documents/Collections/ContentCollectionSchema.php');
$controller=$read('app/Http/Controllers/Admin/Content/ContentCollectionController.php');
$routes=$read('routes/content-collections.php');
$module=$read('app/Nexora/Modules/Core/DocumentEngineModule.php');
$index=$read('resources/js/admin/pages/Admin/Collections/Index.tsx');
$show=$read('resources/js/admin/pages/Admin/Collections/Show.tsx');
$test=$read('tests/Feature/ContentCollectionFlowTest.php');
$dbContracts=$read('scripts/lib/database-contracts.php');

foreach([
    "Schema::create('nx_content_collections'"=>'collection table',
    "Schema::create('nx_content_collection_documents'"=>'collection/document entry table',
    "uuid('tenant_id')"=>'tenant-native column',
    "foreign('tenant_id', 'nx_col_tenant_fk')"=>'tenant foreign key',
    "on('nx_enterprise_organizations')"=>'enterprise tenant target',
    "json('schema')"=>'typed schema storage',
    "json('data')"=>'entry field storage',
    "unique(['tenant_id', 'slug'], 'nx_col_tenant_slug_uq')"=>'tenant slug uniqueness',
    "'collections.view'"=>'collection view permission provisioning',
    "'collections.manage'"=>'collection manage permission provisioning',
    "Schema::dropIfExists('nx_content_collection_documents')"=>'entry rollback coverage',
    "Schema::dropIfExists('nx_content_collections')"=>'collection rollback coverage',
] as $needle=>$label){ if($migration!==''&&!str_contains($migration,$needle)) $errors[]="Collection Product migration contract missing: {$label}."; }

foreach([
    'use BelongsToTenant;'=>'tenant-aware model boundary',
    "protected \$table = 'nx_content_collections'"=>'explicit collection table',
    "'schema' => 'array'"=>'schema JSON cast',
    "'metadata' => 'array'"=>'metadata JSON cast',
    "belongsToMany(Document::class, 'nx_content_collection_documents'"=>'document membership relation',
    "withPivot(['position', 'data'])"=>'ordered typed entry pivot',
] as $needle=>$label){ if($model!==''&&!str_contains($model,$needle)) $errors[]="Collection Product model contract missing: {$label}."; }

if($document!==''&&!str_contains($document,"belongsToMany(ContentCollection::class, 'nx_content_collection_documents'")) $errors[]='Collection Product Document model relationship missing.';

foreach([
    'private const MAX_FIELDS = 50'=>'schema field budget',
    "private const TYPES = ['text', 'long-text', 'number', 'boolean', 'date', 'url']"=>'field type allow-list',
    "preg_match('/^[a-z][a-z0-9_]{0,62}$/', \$key)"=>'safe stable field keys',
    'Collection field key ['=>'duplicate field rejection',
    'normalizeEntry'=>'entry normalization',
    "['http', 'https']"=>'HTTP/HTTPS URL allow-list',
] as $needle=>$label){ if($schema!==''&&!str_contains($schema,$needle)) $errors[]="Collection Product schema contract missing: {$label}."; }

foreach([
    "middleware(['web', 'auth', 'verified', 'admin', EnsureTenantRouteBinding::class])"=>'authenticated tenant-aware route boundary',
    "Route::get('/collections'"=>'collection index route',
    "Route::post('/collections'"=>'collection create route',
    "Route::get('/collections/{collection}'"=>'collection detail route',
    "Route::put('/collections/{collection}'"=>'collection update route',
    "Route::delete('/collections/{collection}'"=>'collection delete route',
    "Route::post('/collections/{collection}/documents'"=>'attach document route',
    "Route::put('/collections/{collection}/documents/{document}'"=>'entry update route',
    "Route::delete('/collections/{collection}/documents/{document}'"=>'detach document route',
    'permission:collections.view'=>'collection view permission boundary',
    'permission:collections.manage'=>'collection manage permission boundary',
] as $needle=>$label){ if($routes!==''&&!str_contains($routes,$needle)) $errors[]="Collection Product route contract missing: {$label}."; }

foreach([
    '$this->schemas->normalize('=>'schema normalization handoff',
    '$this->schemas->normalizeEntry('=>'entry normalization handoff',
    'The selected document type is incompatible with one or more existing collection entries.'=>'safe type-restriction change',
    'This document is already part of the collection.'=>'duplicate membership rejection',
    '$collection->documents()->attach('=>'explicit entry attach',
    '$collection->documents()->detach('=>'membership-only detach',
    "'content.collection.created'"=>'collection creation audit',
    "'content.collection.document.attached'"=>'entry attach audit',
] as $needle=>$label){ if($controller!==''&&!str_contains($controller,$needle)) $errors[]="Collection Product controller contract missing: {$label}."; }

foreach([
    "'id' => 'collections'"=>'collection admin navigation',
    "'href' => '/admin/collections'"=>'collection admin destination',
    "'permission' => 'collections.view'"=>'navigation permission boundary',
    "require base_path('routes/content-collections.php')"=>'module-owned route loading',
] as $needle=>$label){ if($module!==''&&!str_contains($module,$needle)) $errors[]="Collection Product module contract missing: {$label}."; }

foreach([
    'Content Collections'=>'collection workspace UX',
    'New collection'=>'collection creation UX',
    'Custom fields'=>'field-builder UX',
    '@nexora/admin-ui'=>'shared UI library boundary',
] as $needle=>$label){ if($index!==''&&!str_contains($index,$needle)) $errors[]="Collection Product index UI contract missing: {$label}."; }
foreach([
    'Add document'=>'entry attach UX',
    'Edit fields'=>'entry field editing UX',
    'Collection settings'=>'schema/settings UX',
    'ConfirmDialog'=>'destructive confirmation UX',
    'The document itself will not be deleted.'=>'non-destructive detach communication',
] as $needle=>$label){ if($show!==''&&!str_contains($show,$needle)) $errors[]="Collection Product detail UI contract missing: {$label}."; }

foreach([
    'test_administrator_can_create_collection_attach_typed_entry_update_and_detach_without_deleting_document'=>'collection lifecycle acceptance test',
    "self::assertTrue(Document::query()->whereKey(\$article->id)->exists())"=>'document survival assertion',
    "->assertSessionHasErrors('document_id')"=>'document type restriction assertion',
    'test_collection_schema_rejects_duplicate_keys_and_entry_urls_are_http_only'=>'schema/link safety acceptance test',
    "'javascript:alert(1)'"=>'unsafe URL regression fixture',
    "->assertSessionHasErrors('data.website')"=>'unsafe URL rejection assertion',
] as $needle=>$label){ if($test!==''&&!str_contains($test,$needle)) $errors[]="Collection Product acceptance-test contract missing: {$label}."; }

foreach([
    'function nexoraMigrationForwardTenantizesTable('=>'forward tenant migration semantics',
    '$isPostEnterprise'=>'post-enterprise tenant-native ordering',
    "foreign('tenant_id'"=>'tenant-native foreign key guard',
] as $needle=>$label){ if($dbContracts!==''&&!str_contains($dbContracts,$needle)) $errors[]="Collection Product database contract missing: {$label}."; }

if($errors!==[]){ fwrite(STDERR,"[Nexora Collection Product Contract] FAILED\n - ".implode("\n - ",$errors)."\n"); exit(1); }
fwrite(STDOUT,"[Nexora Collection Product Contract] PASS — tenant-native collection schema, typed fields, document membership, permission/audit boundaries and non-destructive lifecycle source are aligned.\n");
