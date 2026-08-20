<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root.'/bootstrap/nexora-runtime-bootstrap.php';
$strict = in_array('--strict-source', $argv, true);
$failures = [];

$forbiddenFiles = [
    '.env',
    'storage/app/nexora/installed.lock',
    'storage/app/nexora/installing.lock',
    'storage/app/nexora/deployment.lock',
    'storage/app/nexora/deployment-access.key',
    'storage/app/nexora/deployment-last-run.json',
    'storage/app/nexora/deployment-last-interrupted.json',
    'storage/app/nexora/environment/.env',
    'storage/app/nexora/environment/active',
    'storage/app/nexora/environment/bootstrap.key',
    'bootstrap/cache/nexora/runtime.php',
];
foreach ($forbiddenFiles as $relative) {
    if (file_exists($root.'/'.$relative)) $failures[] = "Unexpected zero-install state: {$relative}";
}

foreach (['storage/app/nexora/deployment-control', 'storage/app/nexora/installation-control', 'storage/app/nexora/release-stage', 'storage/app/nexora/upgrade'] as $relative) {
    $path = $root.'/'.$relative;
    if (! is_dir($path)) continue;
    $entries = array_values(array_diff(scandir($path) ?: [], ['.', '..', '.gitkeep', '.gitignore']));
    if ($entries !== []) $failures[] = "Zero-install control directory is not empty: {$relative}";
}

if ($strict) {
    foreach (['vendor', 'node_modules', 'public/build', 'storage/app/nexora/tools', 'storage/app/nexora/target-runtime', 'storage/app/nexora/target-bootstrap', 'storage/app/nexora/target-intake', 'storage/app/nexora/dependency-intake', 'storage/app/nexora/target-orchestrator', 'storage/app/nexora/target-remediation', 'storage/app/nexora/n1-c1', 'storage/app/nexora/n1-c2', 'storage/app/nexora/n1-c3', 'storage/app/nexora/n1-c4', 'storage/app/nexora/n1-c5', 'storage/app/nexora/n1-c6', 'storage/app/nexora/n1-target-execution', 'storage/app/nexora/certification',
    'storage/app/nexora/release-signing', 'storage/app/nexora/update-trust', 'storage/app/nexora/runtime', 'dist'] as $relative) {
        if (file_exists($root.'/'.$relative)) $failures[] = "Strict source-zero test requires [{$relative}] to be absent.";
    }
}

foreach (['bootstrap/cache', 'storage/app/nexora', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $relative) {
    $path = $root.'/'.$relative;
    if (! is_dir($path) || ! is_writable($path)) $failures[] = "Runtime directory is unavailable or not writable: {$relative}";
}

foreach (['.env.example', 'public/index.php', 'public/nexora-bootstrap.php', 'bootstrap/nexora-installer-bootstrap.php'] as $relative) {
    if (! is_file($root.'/'.$relative) || filesize($root.'/'.$relative) === 0) $failures[] = "Required zero-install bootstrap artifact is missing: {$relative}";
}

if ($failures !== []) {
    fwrite(STDERR, "[Nexora Zero State] FAILED\n - ".implode("\n - ", $failures)."\n");
    exit(1);
}

fwrite(STDOUT, "[Nexora Zero State] PASS — no persisted environment/install/deployment state is pre-seeding browser installation".($strict ? '; dependency/build/private-tool artifacts are absent.' : '.')."\n");
