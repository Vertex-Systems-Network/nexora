<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static fn (string $path): string => is_file($root.'/'.$path) ? (string) file_get_contents($root.'/'.$path) : '';

$launcher = $read('scripts/pkg1-run.php');
$batch = $read('scripts/pkg1-run.bat');
$finalizer = $read('scripts/pkg1-finalize-login-smoke.ps1');
$finalizerBatch = $read('scripts/pkg1-finalize-login-smoke.bat');

foreach (['scripts/pkg1-run.php', 'scripts/pkg1-run.bat', 'scripts/pkg1-finalize-login-smoke.ps1', 'scripts/pkg1-finalize-login-smoke.bat'] as $path) {
    if (! is_file($root.'/'.$path) || filesize($root.'/'.$path) === 0) $errors[] = "missing launcher artifact [{$path}]";
}
if (is_file($root.'/scripts/pkg1-run.ps1')) {
    $errors[] = 'legacy primary PowerShell launcher scripts/pkg1-run.ps1 must not ship';
}

foreach ([
    'nexoraPkg1LauncherStatus',
    'nexoraPkg1LauncherStopOnBlock',
    'WAITING_REVIEW',
    'PROMOTE-REVIEWED',
    'WAITING_RECOVERY',
    'ROLLBACK',
    'WAITING_SOURCE_RESTART',
    'WAITING_INSTALL',
    'rundll32.exe',
    'WAITING_AUTH_SMOKE',
    'pkg1-finalize-login-smoke.ps1',
    'pkg1-closure-evidence-verify.php',
    'PKG-1 COMPLETE',
    'Fix the blocker above, then run the same scripts\\\\pkg1-run.bat command again.',
] as $marker) {
    if (! str_contains($launcher, $marker)) $errors[] = "PHP PKG-1 launcher missing [{$marker}]";
}
if (preg_match('/[\x80-\xFF]/', $launcher) === 1) $errors[] = 'PHP PKG-1 launcher must remain ASCII-only for Windows console portability';
if (! str_contains($batch, 'php "%~dp0pkg1-run.php"')) $errors[] = 'pkg1-run.bat must invoke the PHP launcher directly';
if (stripos($batch, 'powershell') !== false || stripos($batch, 'pkg1-run.ps1') !== false) $errors[] = 'primary pkg1-run.bat must not depend on PowerShell';

if (preg_match('/[\x80-\xFF]/', $finalizer) === 1) $errors[] = 'hidden-password PowerShell finalizer must remain ASCII-only';
foreach (['System.Management.Automation.Language.Parser', 'ParseFile', 'NEXORA_PKG1_FINALIZER'] as $marker) {
    if (! str_contains($finalizerBatch, $marker)) $errors[] = "finalizer batch parser guard missing [{$marker}]";
}
$parsePosition = strpos($finalizerBatch, 'ParseFile');
$filePosition = strpos($finalizerBatch, ' -File ');
if ($parsePosition === false || $filePosition === false || $parsePosition >= $filePosition) {
    $errors[] = 'finalizer PowerShell ParseFile guard must run before -File execution';
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora PKG-1 Launcher Contract] FAIL\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora PKG-1 Launcher Contract] PASS - primary Laragon launcher is PHP-only, blocker-aware, human gates remain explicit, and the hidden-password PowerShell boundary is parser-guarded.\n");
