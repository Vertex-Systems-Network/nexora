<?php

declare(strict_types=1);

/**
 * Nexora pre-framework runtime bootstrap.
 *
 * This file intentionally has no Composer/Laravel dependency. Both HTTP and CLI
 * entry points execute it before vendor/autoload.php so clean ZIP deployments
 * always have Laravel's writable runtime directories before package discovery,
 * view compilation, sessions, cache, logs, or maintenance-mode checks run.
 */
$root = dirname(__DIR__);

$runtimeDirectories = [
    $root.'/bootstrap/cache',
    $root.'/storage/app',
    $root.'/storage/app/public',
    $root.'/storage/app/nexora',
    $root.'/storage/framework',
    $root.'/storage/framework/cache',
    $root.'/storage/framework/cache/data',
    $root.'/storage/framework/sessions',
    $root.'/storage/framework/views',
    $root.'/storage/logs',
    $root.'/storage/nexora/cache',
    $root.'/storage/nexora/logs',
    $root.'/storage/nexora/packages',
    $root.'/storage/nexora/quarantine',
    $root.'/storage/nexora/sentinel',
];

$failures = [];
foreach ($runtimeDirectories as $directory) {
    if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
        $failures[] = $directory.' (could not create)';
        continue;
    }

    if (! is_writable($directory)) {
        $failures[] = $directory.' (not writable)';
    }
}

if ($failures !== []) {
    $message = "Nexora cannot prepare required runtime directories:\n - ".implode("\n - ", $failures);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "[Nexora Runtime Bootstrap] {$message}\n");
        exit(1);
    }

    if (! defined('NEXORA_RUNTIME_BOOTSTRAP_ERROR')) {
        define('NEXORA_RUNTIME_BOOTSTRAP_ERROR', $message);
    }
}
