<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/dependency-lock-intake.php';

$confirm = '';
$jsonOnly = in_array('--json', $argv, true);
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--confirm=')) {
        $confirm = trim(substr($argument, 10));
    }
}

if ($confirm !== 'ROLLBACK') {
    fwrite(STDERR, "[Nexora Dependency Lock Promotion Recovery] Explicit --confirm=ROLLBACK is required.\n");
    exit(2);
}

$baseDirectory = $root.'/storage/app/nexora/dependency-intake';
$journalPath = $baseDirectory.'/lock-promotion-journal.json';
if (! is_file($journalPath)) {
    fwrite(STDOUT, "[Nexora Dependency Lock Promotion Recovery] No promotion journal exists; nothing to recover.\n");
    exit(0);
}

try {
    $journal = json_decode((string) file_get_contents($journalPath), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    fwrite(STDERR, "[Nexora Dependency Lock Promotion Recovery] Promotion journal is unreadable: {$exception->getMessage()}\n");
    exit(1);
}
if (! is_array($journal)) {
    fwrite(STDERR, "[Nexora Dependency Lock Promotion Recovery] Promotion journal is invalid.\n");
    exit(1);
}

$status = (string) ($journal['status'] ?? 'invalid');
if (in_array($status, ['complete', 'rolled-back'], true)) {
    fwrite(STDOUT, "[Nexora Dependency Lock Promotion Recovery] Journal status is {$status}; no recovery required.\n");
    exit(0);
}

$relativeDirectory = trim((string) ($journal['promotion_directory'] ?? ''));
if ($relativeDirectory === '' || str_contains(str_replace('\\', '/', $relativeDirectory), '..')) {
    fwrite(STDERR, "[Nexora Dependency Lock Promotion Recovery] Promotion directory in journal is invalid.\n");
    exit(1);
}
$promotionDirectory = $root.'/'.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeDirectory);
$backupDirectory = $promotionDirectory.'/before';
if (! is_dir($backupDirectory)) {
    fwrite(STDERR, "[Nexora Dependency Lock Promotion Recovery] Durable promotion backup directory is missing.\n");
    exit(1);
}

$targets = [
    'composer' => ['target' => $root.'/composer.lock', 'backup' => $backupDirectory.'/composer.lock'],
    'npm' => ['target' => $root.'/package-lock.json', 'backup' => $backupDirectory.'/package-lock.json'],
    'refresh' => ['target' => $baseDirectory.'/lock-refresh.json', 'backup' => $backupDirectory.'/lock-refresh.json'],
    'review' => ['target' => $baseDirectory.'/reviewed-locks.json', 'backup' => $backupDirectory.'/reviewed-locks.json'],
];
$errors = [];

foreach ($targets as $key => $paths) {
    $before = (array) ($journal['before'][$key] ?? []);
    $existed = ($before['exists'] ?? false) === true;

    try {
        if ($existed) {
            if (! is_file($paths['backup'])) {
                throw new RuntimeException("Durable backup missing [{$key}].");
            }
            $contents = file_get_contents($paths['backup']);
            if (! is_string($contents)) {
                throw new RuntimeException("Durable backup unreadable [{$key}].");
            }
            $expected = (string) ($before['sha256'] ?? '');
            if ($expected === '' || ! hash_equals($expected, hash('sha256', $contents))) {
                throw new RuntimeException("Durable backup hash mismatch [{$key}].");
            }
            nexoraWriteFileReplace($paths['target'], $contents);
        } elseif (is_file($paths['target']) && ! @unlink($paths['target'])) {
            throw new RuntimeException("Unable to remove promoted file during durable rollback [{$key}].");
        }
    } catch (Throwable $exception) {
        $errors[] = $exception->getMessage();
    }
}

$verified = [];
foreach ($targets as $key => $paths) {
    $before = (array) ($journal['before'][$key] ?? []);
    $expected = ($before['exists'] ?? false) === true ? ($before['sha256'] ?? null) : null;
    $actual = nexoraHashOptionalFile($paths['target']);
    $verified[$key] = $actual === $expected;
    if (! $verified[$key]) {
        $errors[] = "Recovered file fingerprint mismatch [{$key}].";
    }
}

$journal['status'] = $errors === [] ? 'rolled-back' : 'rollback-failed';
$journal['recovered_at'] = gmdate(DATE_ATOM);
$journal['recovery_verified'] = $verified;
$journal['recovery_errors'] = array_values(array_unique($errors));
nexoraWriteFileReplace(
    $journalPath,
    json_encode($journal, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
);

$result = [
    'schema' => 1,
    'status' => $journal['status'],
    'promotion_run_id' => $journal['promotion_run_id'] ?? null,
    'restored' => $verified,
    'errors' => $journal['recovery_errors'],
    'finished_at' => gmdate(DATE_ATOM),
];

if ($jsonOnly) {
    fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
} else {
    fwrite(STDOUT, '[Nexora Dependency Lock Promotion Recovery] '.strtoupper((string) $result['status'])."\n");
    foreach ($result['errors'] as $error) {
        fwrite(STDERR, " - {$error}\n");
    }
}

exit($errors === [] ? 0 : 1);
