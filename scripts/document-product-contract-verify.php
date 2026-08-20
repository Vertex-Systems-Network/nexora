<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$errors=[];
$read=static function(string $relative) use($root,&$errors): string {
    $path=$root.'/'.$relative;
    if(!is_file($path)){ $errors[]="Required Document Product source file missing: {$relative}"; return ''; }
    $contents=file_get_contents($path);
    if($contents===false){ $errors[]="Unable to read Document Product source file: {$relative}"; return ''; }
    return $contents;
};

$routes=$read('routes/web.php');
$controller=$read('app/Http/Controllers/Admin/Content/DocumentController.php');
$repository=$read('app/Nexora/Documents/Repositories/DatabaseDocumentRepository.php');
$validator=$read('app/Nexora/Documents/Services/DocumentContentValidator.php');
$renderer=$read('app/Nexora/Themes/Services/DocumentHtmlRenderer.php');
$form=$read('resources/js/admin/pages/Admin/Documents/Form.tsx');
$editor=$read('resources/js/admin/components/writer/BlockEditor.tsx');
$picker=$read('resources/js/admin/components/MediaPicker.tsx');
$engineTest=$read('tests/Feature/DocumentEngineFlowTest.php');
$editorialTest=$read('tests/Feature/DocumentEditorialFlowTest.php');

foreach([
    "Route::get('/documents', [DocumentController::class, 'index'])"=>'document index route',
    "Route::post('/documents', [DocumentController::class, 'store'])"=>'document create route',
    "Route::put('/documents/{document}', [DocumentController::class, 'update'])"=>'document update route',
    "Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])"=>'document delete route',
    "Route::put('/documents/{document}/autosave', DocumentAutosaveController::class)"=>'autosave route',
    "Route::get('/documents/{document}/revisions', [DocumentRevisionController::class, 'index'])"=>'revision history route',
    "Route::post('/documents/{document}/revisions/{revision}/restore', [DocumentRevisionController::class, 'restore'])"=>'revision restore route',
] as $needle=>$label){ if($routes!==''&&!str_contains($routes,$needle)) $errors[]="Document Product route contract missing: {$label}."; }

foreach([
    '$this->documents->create('=>'repository-backed create handoff',
    '$this->documents->update('=>'repository-backed update handoff',
    '$this->workflow->assertTransition('=>'editorial transition guard',
    "'lock_version' => [\$document ? 'required' : 'nullable'"=>'optimistic lock request validation',
    "'mediaAssets' => \$this->mediaAssets()"=>'existing media-reference hydration',
] as $needle=>$label){ if($controller!==''&&!str_contains($controller,$needle)) $errors[]="Document Product controller contract missing: {$label}."; }

foreach([
    'lockForUpdate()'=>'row-level concurrency locking',
    "This document was updated in another session. Reload before saving to avoid overwriting newer work."=>'stale-write rejection',
    "\$normalized['lock_version'] = ((int) \$locked->lock_version) + 1;"=>'optimistic lock version advance',
    '$this->revisions->record('=>'immutable revision snapshot handoff',
    '$this->contentValidator->normalize('=>'canonical content normalization',
] as $needle=>$label){ if($repository!==''&&!str_contains($repository,$needle)) $errors[]="Document Product repository contract missing: {$label}."; }

foreach([
    '$this->blocks->has($type)'=>'registered block allow-list',
    "preg_match('/^[A-Za-z0-9_-]{8,80}$/', \$id)"=>'stable block id validation',
    "return ['version' => \$version, 'blocks' => \$normalized]"=>'canonical document tree output',
] as $needle=>$label){ if($validator!==''&&!str_contains($validator,$needle)) $errors[]="Document Product validator contract missing: {$label}."; }

foreach([
    "'image' => \$this->image(\$data)"=>'image block renderer',
    "MediaAsset::query()->find(\$assetId)"=>'canonical media asset lookup',
    '$asset->trashed()'=>'trashed-media render guard',
    'htmlspecialchars'=>'public output escaping',
    'srcset'=>'responsive media variants',
] as $needle=>$label){ if($renderer!==''&&!str_contains($renderer,$needle)) $errors[]="Document Product renderer contract missing: {$label}."; }

foreach([
    'Recover autosaved work'=>'autosave recovery UX',
    'Autosave stopped to protect newer work'=>'autosave conflict UX',
    'Revision history'=>'revision navigation UX',
    '<BlockEditor'=>'Writer block editor integration',
] as $needle=>$label){ if($form!==''&&!str_contains($form,$needle)) $errors[]="Document Product form UI contract missing: {$label}."; }

foreach([
    'MediaPicker'=>'reusable Media Library picker integration',
    'media_asset_id: asset.id'=>'canonical media asset ID persistence',
    'Public rendering resolves the current asset URL'=>'no stale media URL persistence guidance',
    'Move block up'=>'block ordering UX',
    'Delete block'=>'block deletion UX',
] as $needle=>$label){ if($editor!==''&&!str_contains($editor,$needle)) $errors[]="Document Product Writer UI contract missing: {$label}."; }

foreach([
    'picker: "1"'=>'remote Media Library picker query',
    'Search Media Library'=>'picker search UX',
    'showSelection'=>'reusable selected-media preview',
] as $needle=>$label){ if($picker!==''&&!str_contains($picker,$needle)) $errors[]="Document Product MediaPicker contract missing: {$label}."; }

foreach([
    'test_administrator_can_create_and_revision_a_structured_document'=>'document create/revision acceptance test',
    "self::assertSame(2, \$document->fresh()->revisions()->count())"=>'revision count assertion',
    'self::assertNotNull($document->fresh()->published_at)'=>'publish timestamp assertion',
] as $needle=>$label){ if($engineTest!==''&&!str_contains($engineTest,$needle)) $errors[]="Document Product engine acceptance-test contract missing: {$label}."; }

foreach([
    'test_editorial_autosave_detects_stale_server_versions_and_revision_can_be_restored'=>'autosave/revision restoration acceptance test',
    "->assertStatus(409)->assertJsonPath('status', 'conflict')"=>'autosave concurrency assertion',
    "self::assertSame(3, \$document->fresh()->revisions()->count())"=>'restored revision assertion',
] as $needle=>$label){ if($editorialTest!==''&&!str_contains($editorialTest,$needle)) $errors[]="Document Product editorial acceptance-test contract missing: {$label}."; }

if($errors!==[]){ fwrite(STDERR,"[Nexora Document Product Contract] FAILED\n - ".implode("\n - ",$errors)."\n"); exit(1); }
fwrite(STDOUT,"[Nexora Document Product Contract] PASS — Writer CRUD/revisions/autosave/concurrency, reusable Media Library selection, canonical media rendering and editorial acceptance-test source are aligned.\n");
