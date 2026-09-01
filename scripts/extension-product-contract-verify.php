<?php

declare(strict_types=1);

$root=dirname(__DIR__);
$errors=[];
$read=static function(string $relative) use($root,&$errors): string {
    $path=$root.'/'.$relative;
    if(!is_file($path)){ $errors[]="Required Extension Product source file missing: {$relative}"; return ''; }
    $contents=file_get_contents($path);
    if($contents===false){ $errors[]="Unable to read Extension Product source file: {$relative}"; return ''; }
    return $contents;
};

$routes=$read('routes/web.php');
$controller=$read('app/Http/Controllers/Admin/Extensions/ExtensionController.php');
$installer=$read('app/Nexora/Extensions/Services/ExtensionPackageInstaller.php');
$lifecycle=$read('app/Nexora/Extensions/Services/ExtensionLifecycleManager.php');
$index=$read('resources/js/admin/pages/Admin/Extensions/Index.tsx');
$show=$read('resources/js/admin/pages/Admin/Extensions/Show.tsx');
$test=$read('tests/Feature/Extensions/ExtensionsAdminFlowTest.php');

foreach([
    "Route::post('/security/sentinel', [SentinelController::class, 'store'])"=>'Sentinel package upload route',
    "Route::post('/extensions/install/{artifact}', [ExtensionController::class, 'install'])"=>'verified artifact install route',
    "Route::put('/extensions/{extension}/capabilities', [ExtensionController::class, 'capabilities'])"=>'capability grant route',
    "Route::post('/extensions/{extension}/enable', [ExtensionController::class, 'enable'])"=>'extension enable route',
    "Route::post('/extensions/{extension}/disable', [ExtensionController::class, 'disable'])"=>'extension disable route',
    "Route::post('/extensions/{extension}/rollback', [ExtensionController::class, 'rollback'])"=>'extension rollback route',
    "Route::delete('/extensions/{extension}', [ExtensionController::class, 'uninstall'])"=>'extension uninstall route',
] as $needle=>$label){ if($routes!==''&&!str_contains($routes,$needle)) $errors[]="Extension Product route contract missing: {$label}."; }

foreach([
    '$installer->install($artifact'=>'verified artifact installer handoff',
    '$lifecycle->grantCapabilities('=>'capability grant lifecycle handoff',
    '$lifecycle->enable('=>'enable lifecycle handoff',
    '$lifecycle->disable('=>'disable lifecycle handoff',
    '$lifecycle->rollback('=>'rollback lifecycle handoff',
    '$lifecycle->uninstall('=>'uninstall lifecycle handoff',
] as $needle=>$label){ if($controller!==''&&!str_contains($controller,$needle)) $errors[]="Extension Product controller contract missing: {$label}."; }

foreach([
    "\$artifact->scan->decision !== 'allow'"=>'Sentinel ALLOW install gate',
    "\$artifact->content_sha256 === ''"=>'supply-chain content digest requirement',
    'same extension version already exists with different package content'=>'version immutability guard',
    'copyStreamAtomically'=>'bounded atomic extraction',
    '@rename($temp,$destination)'=>'atomic directory publication',
] as $needle=>$label){ if($installer!==''&&!str_contains($installer,$needle)) $errors[]="Extension Product installer contract missing: {$label}."; }

foreach([
    'Grant all requested capabilities before enabling this extension'=>'deny-by-default capability gate',
    'capabilities that are not registered by the current Nexora runtime'=>'unregistered capability block',
    'assertDependencies($version)'=>'dependency gate',
    "\$version->runtime_mode==='trusted-php'"=>'trusted PHP execution policy gate',
    'Enabled extensions depend on this package.'=>'dependent extension uninstall guard',
    'forward-only schema changes without schema-compatible rollback'=>'schema rollback guard',
] as $needle=>$label){ if($lifecycle!==''&&!str_contains($lifecycle,$needle)) $errors[]="Extension Product lifecycle contract missing: {$label}."; }

foreach([
    'Upload extension'=>'extension upload UX',
    'upload.post('=>'upload form submission handoff',
    '/admin/security/sentinel'=>'upload-to-Sentinel route target',
    'error={upload.errors.package}'=>'upload validation UX',
    'Verified packages ready to install'=>'verified artifact install queue',
    'Send to Sentinel'=>'Marketplace quarantine handoff',
    'i: "success"'=>'valid enabled-summary icon',
] as $needle=>$label){ if($index!==''&&!str_contains($index,$needle)) $errors[]="Extension Product index UI contract missing: {$label}."; }

foreach([
    'ConfirmDialog'=>'shared destructive confirmation UI',
    'Uninstall extension'=>'explicit uninstall confirmation',
    'setUninstallOpen(true)'=>'uninstall confirmation gate',
    'Save capability grants'=>'capability review UX',
] as $needle=>$label){ if($show!==''&&!str_contains($show,$needle)) $errors[]="Extension Product lifecycle UI contract missing: {$label}."; }

foreach([
    'test_declarative_extension_moves_from_sentinel_to_install_enable_disable_and_uninstall'=>'end-to-end extension acceptance test',
    '/admin/security/sentinel'=>'acceptance test quarantine/scan request',
    '/admin/extensions/install/'=>'acceptance test install request',
    '/enable'=>'acceptance test enable request',
    '/disable'=>'acceptance test disable request',
    "->delete('/admin/extensions/'"=>'acceptance test uninstall request',
    "'event'=>'uninstalled'"=>'acceptance test lifecycle evidence',
] as $needle=>$label){ if($test!==''&&!str_contains($test,$needle)) $errors[]="Extension Product acceptance-test contract missing: {$label}."; }

if($errors!==[]){ fwrite(STDERR,"[Nexora Extension Product Contract] FAILED\n - ".implode("\n - ",$errors)."\n"); exit(1); }
fwrite(STDOUT,"[Nexora Extension Product Contract] PASS — Sentinel upload, verified install, capability gating, enable/disable/rollback/uninstall UX and acceptance-test source are aligned.\n");
