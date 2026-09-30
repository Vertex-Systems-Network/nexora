<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$read = static function (string $relative) use ($root, &$errors): string {
    $path = $root.'/'.$relative;
    if (! is_file($path)) {
        $errors[] = "Required installer database UX source file missing: {$relative}";
        return '';
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        $errors[] = "Unable to read installer database UX source file: {$relative}";
        return '';
    }
    return $contents;
};

$registry = $read('app/Nexora/Installation/Database/DatabaseDriverRegistry.php');
$select = $read('resources/views/components/ui/select.blade.php');
$ui = $read('public/installer/nexora-ui.js');
$view = $read('resources/views/install/index.blade.php');

foreach ([
    "'default_host'" => 'driver default host metadata',
    "'default_port'" => 'driver default port metadata',
    "'default_database'" => 'driver default database metadata',
    "'default_username'" => 'driver default username metadata',
    "'supports_create'" => 'driver create capability metadata',
    "'network'" => 'network/local field policy metadata',
    "'managed'" => 'managed database metadata',
] as $needle => $label) {
    if ($registry !== '' && ! str_contains($registry, $needle)) {
        $errors[] = "Database driver registry missing installer UX metadata: {$label}.";
    }
}

foreach ([
    "\$kind === 'database'" => 'database-kind specialization',
    'DatabaseDriverRegistry::class' => 'registry-backed option metadata',
    'data-default-host=' => 'default-host data attribute',
    'data-default-port=' => 'default-port data attribute',
    'data-default-database=' => 'default-database data attribute',
    'data-default-username=' => 'default-username data attribute',
    'data-network=' => 'network/local data attribute',
    'data-supports-create=' => 'create-capability data attribute',
    'data-managed=' => 'managed-provider data attribute',
    "basename(str_replace('\\\\', '/', \$databaseDefault))" => 'SQLite server-path non-disclosure',
] as $needle => $label) {
    if ($select !== '' && ! str_contains($select, $needle)) {
        $errors[] = "Shared select database metadata contract missing: {$label}.";
    }
}

foreach ([
    'enhanceDatabaseFields' => 'database field enhancer',
    'optionMeta' => 'selected option metadata reader',
    'replaceIfDefault' => 'non-destructive default switching',
    "fields.password.value = ''" => 'credential clearing on driver switch',
    'field.disabled = !meta.network' => 'SQLite network-field disabling',
    'fields.create.disabled = !meta.supportsCreate' => 'managed/local create capability enforcement',
    "form.dataset.nxDatabaseTestCurrent = '0'" => 'dirty database-test state',
    "form.dataset.nxDatabaseTestCurrent = '1'" => 'successful test state',
    'Database settings changed. Test the connection again before continuing.' => 'stale-test user feedback',
    'blockStaleContinuation' => 'Continue/navigation stale-test guard',
    "form.addEventListener('submit'" => 'install-submit stale-test guard',
    'Database settings changed after the last successful test.' => 'install-submit stale-test message',
] as $needle => $label) {
    if ($ui !== '' && ! str_contains($ui, $needle)) {
        $errors[] = "Installer database UI runtime contract missing: {$label}.";
    }
}

if ($ui !== '' && preg_match('/const\s+(?:databaseDefaults|driverDefaults)\s*=\s*\{/', $ui) === 1) {
    $errors[] = 'Installer database UI must consume registry-backed option metadata instead of duplicating a hardcoded driver-default matrix in JavaScript.';
}

foreach ([
    'kind="database"' => 'database select enhancement hook',
    'id="test-database"' => 'explicit connection test control',
    'id="db-result"' => 'database test result surface',
    'name="db_create"' => 'create database control',
] as $needle => $label) {
    if ($view !== '' && ! str_contains($view, $needle)) {
        $errors[] = "Installer database view missing: {$label}.";
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Installer Database UX Contract] FAILED\n - ".implode("\n - ", array_values(array_unique($errors)))."\n");
    exit(1);
}

fwrite(
    STDOUT,
    '[Nexora Installer Database UX Contract] PASS — database driver switching is registry-driven, SQLite/managed field capabilities are enforced client-side, credentials are not carried across drivers, and any connectivity change invalidates the prior connection test before Continue/Install.'.PHP_EOL,
);
