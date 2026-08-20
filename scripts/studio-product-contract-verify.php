<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$errors=[];
$read=static function(string $relative) use($root,&$errors): string {
    $path=$root.'/'.$relative;
    if(!is_file($path)){ $errors[]="Required Studio Product source file missing: {$relative}"; return ''; }
    $contents=file_get_contents($path);
    if($contents===false){ $errors[]="Unable to read Studio Product source file: {$relative}"; return ''; }
    return $contents;
};

$routes=$read('routes/web.php');
$controller=$read('app/Http/Controllers/Admin/Studio/StudioController.php');
$manager=$read('app/Nexora/Studio/Services/StudioManager.php');
$validator=$read('app/Nexora/Studio/Services/StudioCanvasValidator.php');
$renderer=$read('app/Nexora/Studio/Services/StudioCanvasRenderer.php');
$public=$read('app/Http/Controllers/Public/ThemePageController.php');
$index=$read('resources/js/admin/pages/Admin/Studio/Index.tsx');
$editor=$read('resources/js/admin/pages/Admin/Studio/Editor.tsx');
$test=$read('tests/Feature/Studio/StudioFlowTest.php');

foreach([
    "Route::post('/studio', [StudioController::class, 'store'])"=>'canvas create route',
    "Route::get('/studio/{canvas}/edit', [StudioController::class, 'edit'])"=>'canvas editor route',
    "Route::put('/studio/{canvas}', [StudioController::class, 'update'])"=>'canvas save route',
    "Route::post('/studio/{canvas}/publish', [StudioController::class, 'publish'])"=>'canvas publish route',
    "Route::post('/studio/{canvas}/unpublish', [StudioController::class, 'unpublish'])"=>'canvas unpublish route',
    "Route::post('/studio/{canvas}/components', [StudioController::class, 'component'])"=>'reusable component route',
    "Route::delete('/studio/{canvas}', [StudioController::class, 'destroy'])"=>'canvas delete route',
] as $needle=>$label){ if($routes!==''&&!str_contains($routes,$needle)) $errors[]="Studio Product route contract missing: {$label}."; }

foreach([
    '$this->studio->create('=>'validated canvas creation handoff',
    '$this->studio->update('=>'validated save handoff',
    '$this->studio->publish('=>'publish handoff',
    '$this->studio->unpublish('=>'unpublish handoff',
    '$this->studio->saveComponent('=>'reusable component handoff',
    "Rule::in(['standalone', 'document', 'theme-template'])"=>'scope allow-list',
    "new TenantExists('nx_documents')"=>'tenant-safe document binding',
] as $needle=>$label){ if($controller!==''&&!str_contains($controller,$needle)) $errors[]="Studio Product controller contract missing: {$label}."; }

foreach([
    'lockForUpdate()'=>'row-level concurrency locking',
    "This Studio canvas changed in another session. Reload before saving."=>'stale-write rejection',
    "'lock_version' => ((int) \$locked->lock_version) + 1"=>'optimistic lock version advance',
    '$this->revision($locked, $userId);'=>'save revision snapshot',
    "'status' => 'published'"=>'publish state',
    "'status' => 'draft'"=>'unpublish state',
] as $needle=>$label){ if($manager!==''&&!str_contains($manager,$needle)) $errors[]="Studio Product manager contract missing: {$label}."; }

foreach([
    'private const MAX_NODES = 500'=>'canvas node budget',
    'private const MAX_DEPTH = 20'=>'canvas nesting budget',
    "preg_match('/^[a-zA-Z0-9_-]{8,80}$/', \$id)"=>'safe stable node id validation',
    "preg_match('#^(https?://|/|#)#i', \$href)"=>'safe button URL allow-list',
    "in_array(\$value, ['_self', '_blank'], true)"=>'button target normalization',
    'sanitizeStyles'=>'style allow-list sanitization',
    'sanitizeBindings'=>'binding registry validation',
] as $needle=>$label){ if($validator!==''&&!str_contains($validator,$needle)) $errors[]="Studio Product validator contract missing: {$label}."; }

foreach([
    "->where('scope', 'document')"=>'document canvas lookup',
    "->where('status', 'published')"=>'published-only public rendering',
    "'document.title'"=>'document title binding',
    "'seo.title'"=>'SEO title binding',
    "'site.name'"=>'site name binding',
    'htmlspecialchars'=>'HTML output escaping',
    '@media(max-width:1024px)'=>'tablet responsive output',
    '@media(max-width:640px)'=>'mobile responsive output',
] as $needle=>$label){ if($renderer!==''&&!str_contains($renderer,$needle)) $errors[]="Studio Product renderer contract missing: {$label}."; }

if($public!==''&&!str_contains($public,'$this->studio->renderDocument($document) ?? $this->documents->render($document->content)')){
    $errors[]='Studio Product public integration missing: published Studio document canvas must override the document renderer with safe fallback.';
}

foreach([
    'New canvas'=>'Studio create UX',
    'ConfirmDialog'=>'canvas delete confirmation',
    'Open Studio'=>'editor navigation',
] as $needle=>$label){ if($index!==''&&!str_contains($index,$needle)) $errors[]="Studio Product index UI contract missing: {$label}."; }

foreach([
    'const [history'=>'undo/redo history state',
    'const undo ='=>'undo command',
    'const redo ='=>'redo command',
    'Desktop viewport'=>'desktop responsive preview',
    'Tablet viewport'=>'tablet responsive preview',
    'Mobile viewport'=>'mobile responsive preview',
    'Dynamic text binding'=>'binding UX',
    'Save selected as component'=>'reusable component UX',
    'Unsaved changes'=>'dirty-state UX',
] as $needle=>$label){ if($editor!==''&&!str_contains($editor,$needle)) $errors[]="Studio Product editor UI contract missing: {$label}."; }

foreach([
    'test_administrator_can_create_save_publish_and_render_document_canvas'=>'public create/save/publish acceptance test',
    "->get('/content/'.\$document->slug)"=>'public render assertion',
    "->assertSee('nx-studio-page', false)"=>'Studio renderer public shell assertion',
    'test_stale_studio_update_is_rejected_without_overwriting_newer_revision'=>'concurrency acceptance test',
    "->assertSessionHasErrors('canvas')"=>'stale write error assertion',
    'test_studio_rejects_unsafe_button_url_and_normalizes_target'=>'unsafe link acceptance test',
    "self::assertSame('#', \$button['props']['href'])"=>'unsafe URL normalization assertion',
] as $needle=>$label){ if($test!==''&&!str_contains($test,$needle)) $errors[]="Studio Product acceptance-test contract missing: {$label}."; }

if($errors!==[]){ fwrite(STDERR,"[Nexora Studio Product Contract] FAILED\n - ".implode("\n - ",$errors)."\n"); exit(1); }
fwrite(STDOUT,"[Nexora Studio Product Contract] PASS — create/edit/revision/concurrency/publish/public-render/responsive/binding/component and safe-output acceptance-test source are aligned.\n");
