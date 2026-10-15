<?php

declare(strict_types=1);

/**
 * Operator-side, read-only historical APP_KEY audit for issue #72.
 * Secret reference is a private file, never an argv value or a source constant.
 * This is a file-inventory aid, NOT historical non-use or key retirement proof.
 */
function nexoraHistoricalKeyBytes(string $input): ?string
{
    $key = trim($input, " \t\r\n\"'");
    if (! str_starts_with($key, 'base64:')) {
        return null;
    }
    $decoded = base64_decode(substr($key, 7), true);
    return $decoded !== false && strlen($decoded) === 32 ? $decoded : null;
}

/** @return array{values:list<string>,readable:bool} */
function nexoraHistoricalReadEnv(string $path): array
{
    if (! file_exists($path) && ! is_link($path)) {
        return ['values' => [], 'readable' => true];
    }
    if (is_link($path) || ! is_file($path) || ! is_readable($path)) {
        return ['values' => [], 'readable' => false];
    }
    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if (! is_array($lines)) {
        return ['values' => [], 'readable' => false];
    }
    $values = [];
    foreach ($lines as $line) {
        if (preg_match('/^\s*(APP_KEY|APP_PREVIOUS_KEYS)\s*=\s*(.*?)\s*$/D', $line, $parts) !== 1) {
            continue;
        }
        $setting = trim($parts[2]);
        if (strlen($setting) > 1 && ($setting[0] === '"' || $setting[0] === "'") && substr($setting, -1) === $setting[0]) {
            $setting = substr($setting, 1, -1);
        }
        foreach (explode(',', $setting) as $candidate) {
            if (($candidate = trim($candidate)) !== '') {
                $values[] = $candidate;
            }
        }
    }
    return ['values' => $values, 'readable' => true];
}

/**
 * @return array{status:string,scanned:int,key_slots_scanned:int,unreadable:int,cached_config_present:bool,installed_lock_present:bool,historical_non_use_proved:false,target_verified:false,retired:false}
 */
function nexoraHistoricalAuditTarget(string $referenceBytes, string $target): array
{
    $root = realpath($target);
    if ($root === false || ! is_dir($root) || ! is_file($root.'/artisan') || ! is_file($root.'/bootstrap/app.php')) {
        throw new RuntimeException('Target must be a readable Nexora project directory with artisan and bootstrap/app.php.');
    }
    $relative = [
        '.env',
        'storage/app/nexora/environment/.env',
        'storage/app/nexora/environment/bootstrap.key',
    ];
    $scanned = 0;
    $keysParsed = 0;
    $unreadable = 0;
    $foundMatch = false;
    foreach ($relative as $file) {
        $path = $root.'/'.$file;
        if (! file_exists($path) && ! is_link($path)) {
            continue;
        }
        $scanned++;
        if (str_ends_with($file, 'bootstrap.key')) {
            if (is_link($path) || ! is_file($path) || ! is_readable($path)) {
                $unreadable++;
                continue;
            }
            $candidate = nexoraHistoricalKeyBytes((string) @file_get_contents($path));
            if ($candidate === null) {
                $unreadable++;
            } else {
                $keysParsed++;
            }
            if ($candidate !== null && hash_equals($referenceBytes, $candidate)) {
                $foundMatch = true;
            }
            continue;
        }
        $result = nexoraHistoricalReadEnv($path);
        if (! $result['readable']) {
            $unreadable++;
            continue;
        }
        foreach ($result['values'] as $value) {
            $candidate = nexoraHistoricalKeyBytes($value);
            if ($candidate === null) {
                $unreadable++;
            } else {
                $keysParsed++;
            }
            if ($candidate !== null && hash_equals($referenceBytes, $candidate)) {
                $foundMatch = true;
            }
        }
    }
    $marker = $root.'/storage/app/nexora/environment/active';
    if (file_exists($marker) || is_link($marker)) {
        $selection = is_link($marker) || ! is_file($marker) || ! is_readable($marker)
            ? null : strtolower(trim((string) @file_get_contents($marker)));
        if (! in_array($selection, ['root', 'fallback'], true)) {
            $unreadable++;
        } elseif (! is_file($root.'/'.($selection === 'root' ? '.env' : 'storage/app/nexora/environment/.env'))) {
            $unreadable++;
        }
    }
    $cached = file_exists($root.'/bootstrap/cache/config.php') || is_link($root.'/bootstrap/cache/config.php');
    return [
        'status' => $foundMatch ? 'EXPOSED_KEY_OBSERVED' : ($unreadable > 0 || $keysParsed === 0 || $cached ? 'INCONCLUSIVE' : 'NO_MATCH_IN_SCANNED_FILES'),
        'scanned' => $scanned,
        'key_slots_scanned' => $keysParsed,
        'unreadable' => $unreadable,
        'cached_config_present' => $cached,
        'installed_lock_present' => is_file($root.'/storage/app/nexora/installed.lock'),
        'historical_non_use_proved' => false,
        'target_verified' => false,
        'retired' => false,
    ];
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $args = ['reference-file' => null, 'target' => null];
    foreach (array_slice($argv, 1) as $argument) {
        foreach (array_keys($args) as $name) {
            if (str_starts_with($argument, '--'.$name.'=')) {
                $args[$name] = substr($argument, strlen($name) + 3);
            }
        }
    }
    $reference = $args['reference-file'];
    if (! is_string($reference) || ! is_string($args['target']) || $reference === '' || $args['target'] === '') {
        fwrite(STDERR, "Usage: php scripts/phase1-exposed-key-audit.php --reference-file=/private/exposed-key --target=/absolute/nexora-target\n");
        exit(2);
    }
    if (is_link($reference) || ! is_file($reference) || ! is_readable($reference)
        || (PHP_OS_FAMILY !== 'Windows' && (fileperms($reference) & 0077) !== 0)) {
        fwrite(STDERR, "Reference key file must exist, be readable, non-symlink and owner-only (mode 0600).\n");
        exit(2);
    }
    $bytes = nexoraHistoricalKeyBytes((string) file_get_contents($reference));
    if ($bytes === null) {
        fwrite(STDERR, "Reference key format is not a Laravel 32-byte base64 key.\n");
        exit(2);
    }
    try {
        $result = nexoraHistoricalAuditTarget($bytes, $args['target']);
    } catch (Throwable) {
        fwrite(STDERR, "Target audit could not complete. No secret material has been printed.\n");
        exit(2);
    }
    fwrite(STDOUT, json_encode(['schema' => 1, 'scope' => 'local_file_inventory_only', 'issue' => 72] + $result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL);
    exit($result['status'] === 'EXPOSED_KEY_OBSERVED' ? 3 : ($result['status'] === 'INCONCLUSIVE' ? 2 : 0));
}
