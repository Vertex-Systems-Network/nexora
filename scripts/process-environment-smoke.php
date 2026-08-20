<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';

$environment = NexoraBootstrapProcessEnvironment::build($root);
$required = ['COMPOSER_CACHE_DIR', 'NPM_CONFIG_CACHE', 'HOME'];
$errors = [];

foreach ($required as $key) {
    $value = trim((string) ($environment[$key] ?? ''));
    if ($value === '') {
        $errors[] = "{$key} is empty";
        continue;
    }
    if (in_array($key, ['COMPOSER_HOME', 'COMPOSER_CACHE_DIR', 'NPM_CONFIG_CACHE'], true) && ! is_dir($value)) {
        $errors[] = "{$key} directory does not exist: {$value}";
    }
}

if (PHP_OS_FAMILY === 'Windows'
    && trim((string) ($environment['APPDATA'] ?? '')) === ''
    && trim((string) ($environment['COMPOSER_HOME'] ?? '')) === '') {
    $errors[] = 'Windows Composer requires APPDATA or COMPOSER_HOME, but neither is available.';
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Process Environment] FAILED\n - ".implode("\n - ", $errors)."\n");
    exit(1);
}

$summary = NexoraBootstrapProcessEnvironment::summary($root);
if (! $summary['composer_home_writable']) {
    fwrite(STDERR, "[Nexora Process Environment] FAILED\n - Composer process home is not writable: {$summary['composer_home']}\n");
    exit(1);
}
fwrite(STDOUT, "[Nexora Process Environment] PASS\n");
fwrite(STDOUT, 'Composer home source: '.$summary['composer_home_source'].PHP_EOL);
fwrite(STDOUT, 'Composer home: '.$summary['composer_home'].PHP_EOL);
