<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];

$gitignorePath = $root.DIRECTORY_SEPARATOR.'.gitignore';
$gitignore = is_file($gitignorePath) ? (string) file_get_contents($gitignorePath) : '';

if ($gitignore === '') {
    $errors[] = 'Missing or empty .gitignore.';
} else {
    if (str_contains($gitignore, '```')) {
        $errors[] = '.gitignore must not contain Markdown code fences.';
    }

    $requiredIgnoreLines = [
        '.env',
        '.env.backup',
        '.env.production',
        '/storage/*.key',
        '/storage/logs/*',
        '!/storage/logs/.gitkeep',
        '/storage/app/nexora/*.lock',
        '/storage/app/nexora/environment/',
        '/storage/app/nexora/deployment-access.key',
        '/vendor',
        '/node_modules',
    ];

    $ignoreLines = array_values(array_filter(array_map('trim', preg_split('/\R/', $gitignore) ?: []), static fn (string $line): bool => $line !== ''));
    foreach ($requiredIgnoreLines as $requiredLine) {
        if (! in_array($requiredLine, $ignoreLines, true)) {
            $errors[] = ".gitignore lost required protection: {$requiredLine}";
        }
    }

    foreach (['composer.lock', 'package-lock.json'] as $requiredTrackedLock) {
        if (in_array($requiredTrackedLock, $ignoreLines, true) || in_array('/'.$requiredTrackedLock, $ignoreLines, true)) {
            $errors[] = ".gitignore must not ignore deterministic root lockfile: {$requiredTrackedLock}";
        }
    }
}

foreach (['composer.lock', 'package-lock.json'] as $requiredLock) {
    $path = $root.DIRECTORY_SEPARATOR.$requiredLock;
    if (! is_file($path) || filesize($path) === 0) {
        $errors[] = "Missing deterministic root lockfile: {$requiredLock}";
    }
}

$tracked = [];
$exitCode = 0;
exec('git -C '.escapeshellarg($root).' ls-files', $tracked, $exitCode);
if ($exitCode !== 0) {
    $errors[] = 'Unable to enumerate tracked files with git ls-files.';
} else {
    foreach ($tracked as $trackedPath) {
        $normalized = str_replace('\\', '/', trim((string) $trackedPath));
        if ($normalized === '') {
            continue;
        }

        if (in_array($normalized, ['.env', '.env.backup', '.env.production', 'auth.json', 'storage/app/nexora/deployment-access.key'], true)) {
            $errors[] = "Forbidden local secret/runtime artifact is tracked: {$normalized}";
            continue;
        }

        if (str_starts_with($normalized, 'storage/app/nexora/environment/')) {
            $errors[] = "Forbidden target-local environment artifact is tracked: {$normalized}";
            continue;
        }

        if (str_starts_with($normalized, 'storage/logs/') && $normalized !== 'storage/logs/.gitkeep') {
            $errors[] = "Forbidden runtime log is tracked: {$normalized}";
            continue;
        }

        if (preg_match('#^storage/[^/]+\.key$#', $normalized) === 1) {
            $errors[] = "Forbidden runtime key is tracked: {$normalized}";
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Repository Hygiene] FAIL\n");
    foreach (array_values(array_unique($errors)) as $error) {
        fwrite(STDERR, ' - '.$error."\n");
    }
    exit(1);
}

echo "[Nexora Repository Hygiene] PASS — critical ignore protections retained; deterministic root locks present; no tracked target-local keys/logs/environment artifacts.\n";
