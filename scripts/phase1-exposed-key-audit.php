<?php

declare(strict_types=1);

/**
 * Operator-side, read-only historical APP_KEY audit for issue #72.
 * Secret reference is a private file, never an argv value or a source constant.
 * This is a file-inventory aid, NOT historical non-use or key retirement proof.
 */
function nexoraHistoricalKeyBytes(string $input): ?string
{
    // Laravel always interprets a `base64:` prefix as encoded key material;
    // even a 32-byte string carrying that prefix must not be called a raw key.
    $decodePrefixed = static function (string $value): ?string {
        $decoded = base64_decode(substr($value, 7), true);
        return $decoded !== false && strlen($decoded) === 32 ? $decoded : null;
    };
    if (str_starts_with($input, 'base64:')) {
        return $decodePrefixed($input);
    }
    // Do not trim an exact raw 32-byte key: its first/last byte may be
    // whitespace, a quote or a newline (valid as bytes, even if weak).
    if (strlen($input) === 32) {
        return $input;
    }
    $key = rtrim($input, "\r\n");
    if (str_starts_with($key, 'base64:')) {
        return $decodePrefixed($key);
    }
    if (strlen($key) === 32) {
        return $key;
    }
    if (strlen($key) > 1 && ($key[0] === '"' || $key[0] === "'")
        && substr($key, -1) === $key[0]) {
        $key = substr($key, 1, -1);
        if (str_starts_with($key, 'base64:')) {
            return $decodePrefixed($key);
        }
        if (strlen($key) === 32) {
            return $key;
        }
    }
    // Only normalize outside whitespace for an encoded representation.
    $key = trim($key, " \t");
    return str_starts_with($key, 'base64:') ? $decodePrefixed($key) : null;
}

// Bound operator-side file reads: a corrupted or hostile candidate must yield
// INCONCLUSIVE instead of exhausting CLI memory while scanning credentials.
const NEXORA_HISTORICAL_MAX_ENV_BYTES = 1048576;
const NEXORA_HISTORICAL_MAX_KEY_BYTES = 1024;
const NEXORA_HISTORICAL_MAX_MARKER_BYTES = 128;

function nexoraHistoricalBoundedRead(string $path, int $limit): ?string
{
    // Do not trust cached filesize() or read an unbounded file after a size
    // check: the file can change between stat and read. Bound the *read*.
    $content = @file_get_contents($path, false, null, 0, $limit + 1);
    return is_string($content) && strlen($content) <= $limit ? $content : null;
}

/**
 * Every path segment under this checkout must be a real directory or file,
 * not a symlink into a different credential store. Never treat a linked
 * parent path as a trustworthy scoped-negative key inventory.
 */
function nexoraHistoricalPathHasSymlink(string $root, string $relative): bool
{
    $current = $root;
    foreach (explode('/', $relative) as $segment) {
        $current .= '/'.$segment;
        if (is_link($current)) {
            return true;
        }
    }
    return false;
}

/** @return array{values:list<string>,readable:bool,app_key_present:bool,duplicate_assignments:int} */
function nexoraHistoricalReadEnv(string $path): array
{
    if (! file_exists($path) && ! is_link($path)) {
        return ['values' => [], 'readable' => true, 'app_key_present' => false, 'duplicate_assignments' => 0];
    }
    if (is_link($path) || ! is_file($path) || ! is_readable($path)) {
        return ['values' => [], 'readable' => false, 'app_key_present' => false, 'duplicate_assignments' => 0];
    }
    $content = nexoraHistoricalBoundedRead($path, NEXORA_HISTORICAL_MAX_ENV_BYTES);
    if ($content === null) {
        return ['values' => [], 'readable' => false, 'app_key_present' => false, 'duplicate_assignments' => 0];
    }
    $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
    $values = [];
    $appKeyPresent = false;
    $seenAssignments = [];
    $duplicateAssignments = 0;
    foreach ($lines as $line) {
        if (preg_match('/^\s*(?:export\s+)?(APP_KEY|APP_PREVIOUS_KEYS)\s*=\s*(.*?)\s*$/D', $line, $parts) !== 1) {
            continue;
        }
        // Nexora's pre-Laravel parser is last-assignment-wins. A repeated
        // APP_KEY/APP_PREVIOUS_KEYS makes scoped absence evidence ambiguous,
        // especially when the final APP_KEY is empty. Scan EVERY candidate
        // for an observed exposed key, but fail closed for a no-match.
        if (isset($seenAssignments[$parts[1]])) {
            $duplicateAssignments++;
        }
        $seenAssignments[$parts[1]] = true;
        $setting = trim($parts[2]);
        // Nexora's pre-Laravel bootstrap does NOT strip inline comments
        // from unquoted values. Laravel dotenv consumers might interpret them
        // differently. Inspect both candidate interpretations, so a raw
        // 32-byte APP_KEY containing " #" is never silently truncated.
        // Ambiguous unmatched interpretations must remain INCONCLUSIVE.
        // Never print any source line or key candidate.
        $interpretations = [$setting];
        if (strlen($setting) > 1 && ($setting[0] === '"' || $setting[0] === "'")) {
            $quote = $setting[0];
            $closing = null;
            // A quoted Laravel/Nexora dotenv value can contain an escaped
            // double quote or an unescaped quote byte *inside* the key. The
            // closing quote is the one followed only by whitespace/comment.
            for ($i = 1, $length = strlen($setting); $i < $length; $i++) {
                if ($quote === '"' && $setting[$i] === '\\' && $i + 1 < $length) {
                    $i++; // escaped byte cannot terminate the quoted value
                    continue;
                }
                if ($setting[$i] !== $quote) {
                    continue;
                }
                $remainder = trim(substr($setting, $i + 1));
                if ($remainder === '' || str_starts_with($remainder, '#')) {
                    $closing = $i;
                    break;
                }
            }
            if ($closing !== null) {
                $setting = substr($setting, 1, $closing - 1);
                // Match the pre-Laravel bootstrap's quoted dotenv decoding.
                // Decode before splitting APP_PREVIOUS_KEYS or comparing keys;
                // otherwise escaped raw 32-byte keys can be missed.
                $setting = str_replace(['\\n', '\\"', '\\\\'], ["\n", '"', '\\'], $setting);
                $interpretations = [$setting];
            }
        } else {
            $stripped = (string) preg_replace('/\s+#.*$/D', '', $setting);
            if ($stripped !== $setting) {
                // The bootstrap reads the raw value; other dotenv parsers may
                // drop the comment. Do not pretend either interpretation is
                // authoritative across CLI and web workers. Scanning both
                // makes a raw match visible; an invalid alternate candidate
                // forces an unresolved inventory to be inconclusive.
                $interpretations[] = $stripped;
            }
        }
        // APP_KEY may itself be a raw 32-byte string containing commas. Only
        // Laravel's APP_PREVIOUS_KEYS is a CSV list.
        if ($parts[1] === 'APP_KEY') {
            $appKeyPresent = count(array_filter($interpretations, static fn (string $v): bool => $v !== '')) > 0;
        }
        foreach ($interpretations as $possibleValue) {
            foreach ($parts[1] === 'APP_PREVIOUS_KEYS' ? explode(',', $possibleValue) : [$possibleValue] as $candidate) {
                if ($candidate !== '') {
                    // Preserve raw key bytes, including leading/trailing spaces.
                    $values[] = $candidate;
                }
            }
        }
    }
    return ['values' => $values, 'readable' => true, 'app_key_present' => $appKeyPresent, 'duplicate_assignments' => $duplicateAssignments];
}

/**
 * Read only APP_KEY and APP_PREVIOUS_KEYS from this invoking PHP CLI process.
 * The three PHP environment APIs can disagree under CLI/SAPI bootstrap;
 * inventory ALL distinct values instead of silently preferring getenv().
 * Nothing here proves which values an unrelated web worker accepts.
 * @return array<string,string|list<mixed>>
 */
function nexoraHistoricalReadProcessSettings(): array
{
    $values = [];
    foreach (['APP_KEY', 'APP_PREVIOUS_KEYS'] as $name) {
        $candidates = [];
        // Distinguish absent values from explicit empty/null values. In
        // particular, an empty APP_KEY in one PHP environment API must not
        // disappear when another API exposes a plausible non-matching key.
        $sources = [];
        $fromProcess = getenv($name);
        if ($fromProcess !== false) {
            $sources[] = $fromProcess;
        }
        if (array_key_exists($name, $_ENV)) {
            $sources[] = $_ENV[$name];
        }
        if (array_key_exists($name, $_SERVER)) {
            $sources[] = $_SERVER[$name];
        }
        foreach ($sources as $value) {
            // Null, arrays and objects cannot represent usable key material;
            // retain a sentinel so the audit fails closed without emitting it.
            $candidate = is_string($value) ? $value : null;
            if (! in_array($candidate, $candidates, true)) {
                $candidates[] = $candidate;
            }
        }
        if ($candidates !== []) {
            $values[$name] = count($candidates) === 1 && is_string($candidates[0])
                ? $candidates[0] : $candidates;
        }
    }
    return $values;
}

/**
 * @param array<string,string|list<mixed>> $processSettings Explicit caller/CLI environment snapshot; never printed.
 * @return array{status:string,scanned:int,key_slots_scanned:int,unreadable:int,duplicate_assignments:int,process_variables_present:int,process_key_slots_scanned:int,cached_config_present:bool,selected_environment_incomplete:bool,installed_lock_present:bool,historical_non_use_proved:false,target_verified:false,retired:false}
 */
function nexoraHistoricalAuditTarget(string $referenceBytes, string $target, array $processSettings = []): array
{
    // The reusable API must enforce the same decoded-key contract as the CLI.
    // Otherwise an accidental base64 text or short reference can manufacture
    // a misleading NO_MATCH_IN_SCANNED_FILES result for a genuinely exposed key.
    if (strlen($referenceBytes) !== 32) {
        throw new InvalidArgumentException('Historical-key reference must contain exactly 32 decoded bytes.');
    }
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
    $duplicateAssignments = 0;
    $foundMatch = false;
    $bootstrapKeyObserved = false;
    $fileReadings = [];
    foreach ($relative as $file) {
        $path = $root.'/'.$file;
        if (nexoraHistoricalPathHasSymlink($root, $file)) {
            $scanned++;
            $unreadable++;
            continue;
        }
        if (! file_exists($path) && ! is_link($path)) {
            continue;
        }
        $scanned++;
        if (str_ends_with($file, 'bootstrap.key')) {
            if (is_link($path) || ! is_file($path) || ! is_readable($path)) {
                $unreadable++;
                continue;
            }
            $candidateSource = nexoraHistoricalBoundedRead($path, NEXORA_HISTORICAL_MAX_KEY_BYTES);
            $candidate = $candidateSource === null ? null : nexoraHistoricalKeyBytes($candidateSource);
            if ($candidate === null) {
                $unreadable++;
            } else {
                $keysParsed++;
                $bootstrapKeyObserved = true;
            }
            if ($candidate !== null && hash_equals($referenceBytes, $candidate)) {
                $foundMatch = true;
            }
            continue;
        }
        $result = nexoraHistoricalReadEnv($path);
        $fileReadings[$file] = $result;
        // Duplicates are ambiguous even if both values are parseable. Track
        // them separately from unreadable files, but scan every value so any
        // observed historical-key match still has highest priority.
        $duplicateAssignments += $result['duplicate_assignments'];
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

    // Environment-only hosting may supply keys from a service manager, not .env.
    // The invoking CLI environment is compared as an extra observation, never
    // conflated with the active web worker or historical key retirement proof.
    $processVariables = 0;
    $processKeysParsed = 0;
    $processCurrentKeyObserved = false;
    foreach (['APP_KEY', 'APP_PREVIOUS_KEYS'] as $name) {
        if (! array_key_exists($name, $processSettings)) {
            continue;
        }
        $settings = is_array($processSettings[$name])
            ? $processSettings[$name] : [$processSettings[$name]];
        $variablePresent = false;
        foreach ($settings as $setting) {
            if (! is_string($setting)) {
                $unreadable++;
                $variablePresent = true;
                continue;
            }
            if ($setting === '') {
                // A deliberately empty current APP_KEY is not proof that
                // some other file/API contains the effective runtime key.
                // An empty APP_PREVIOUS_KEYS list is harmless and optional.
                if ($name === 'APP_KEY') {
                    $unreadable++;
                    $variablePresent = true;
                }
                continue;
            }
            $variablePresent = true;
            if (strlen($setting) > NEXORA_HISTORICAL_MAX_ENV_BYTES) {
                $unreadable++;
                continue;
            }
            // An exact 32-byte raw environment value is authoritative as-is;
            // only unwrap quotes when the complete value is not a raw key.
            if (strlen($setting) !== 32 && strlen($setting) > 1
                && ($setting[0] === '"' || $setting[0] === "'")
                && substr($setting, -1) === $setting[0]) {
                $setting = substr($setting, 1, -1);
            }
            // APP_KEY is a single key even when the 32 bytes include commas.
            $candidates = $name === 'APP_PREVIOUS_KEYS' ? explode(',', $setting) : [$setting];
            foreach ($candidates as $candidateText) {
                if ($candidateText === '') {
                    continue;
                }
                $candidate = nexoraHistoricalKeyBytes($candidateText);
                if ($candidate === null) {
                    $unreadable++;
                    continue;
                }
                $keysParsed++;
                $processKeysParsed++;
                if ($name === 'APP_KEY') {
                    $processCurrentKeyObserved = true;
                }
                if (hash_equals($referenceBytes, $candidate)) {
                    $foundMatch = true;
                }
            }
        }
        if ($variablePresent) {
            $processVariables++;
        }
    }

    $selectedEnvironmentIncomplete = false;
    $marker = $root.'/storage/app/nexora/environment/active';
    if (file_exists($marker) || is_link($marker)) {
        $markerContents = nexoraHistoricalPathHasSymlink($root, 'storage/app/nexora/environment/active')
            || ! is_file($marker) || ! is_readable($marker)
            ? null : nexoraHistoricalBoundedRead($marker, NEXORA_HISTORICAL_MAX_MARKER_BYTES);
        $selection = $markerContents === null ? null : strtolower(trim($markerContents));
        if (! in_array($selection, ['root', 'fallback'], true)) {
            $unreadable++;
        } else {
            $selectedFile = $selection === 'root' ? '.env' : 'storage/app/nexora/environment/.env';
            if (! isset($fileReadings[$selectedFile])
                || ! $fileReadings[$selectedFile]['readable']
                || ! $fileReadings[$selectedFile]['app_key_present']) {
                // A safe key found in another (inactive) file cannot prove
                // a complete inventory of the explicitly selected runtime.
                // The invoking CLI process alone cannot attest the web key.
                $selectedEnvironmentIncomplete = true;
            }
        }
    } else {
        // Without an explicit marker, Nexora's installation bootstrap chooses
        // the readable root .env first, falling back to protected .env. A
        // previous-key-only file does not establish the current application key.
        // A known protected bootstrap key or this CLI's process APP_KEY is
        // an additional *scanned* source, not proof of the running web key.
        $autoFile = isset($fileReadings['.env'])
            ? '.env'
            : (isset($fileReadings['storage/app/nexora/environment/.env'])
                ? 'storage/app/nexora/environment/.env' : null);
        $autoKeyObserved = $autoFile !== null && $fileReadings[$autoFile]['readable']
            && $fileReadings[$autoFile]['app_key_present'];
        if (! $autoKeyObserved && ! $bootstrapKeyObserved && ! $processCurrentKeyObserved) {
            $selectedEnvironmentIncomplete = true;
        }
    }
    $cached = file_exists($root.'/bootstrap/cache/config.php') || is_link($root.'/bootstrap/cache/config.php');
    return [
        'status' => $foundMatch ? 'EXPOSED_KEY_OBSERVED' : ($unreadable > 0 || $duplicateAssignments > 0 || $keysParsed === 0 || $cached || $selectedEnvironmentIncomplete ? 'INCONCLUSIVE' : 'NO_MATCH_IN_SCANNED_FILES'),
        'scanned' => $scanned,
        'key_slots_scanned' => $keysParsed,
        'unreadable' => $unreadable,
        'duplicate_assignments' => $duplicateAssignments,
        'process_variables_present' => $processVariables,
        'process_key_slots_scanned' => $processKeysParsed,
        'cached_config_present' => $cached,
        'selected_environment_incomplete' => $selectedEnvironmentIncomplete,
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
    $referenceContents = nexoraHistoricalBoundedRead($reference, NEXORA_HISTORICAL_MAX_KEY_BYTES);
    $bytes = $referenceContents === null ? null : nexoraHistoricalKeyBytes($referenceContents);
    if ($bytes === null) {
        fwrite(STDERR, "Reference key is not a valid 32-byte Laravel encryption key.\n");
        exit(2);
    }
    try {
        $result = nexoraHistoricalAuditTarget($bytes, $args['target'], nexoraHistoricalReadProcessSettings());
    } catch (Throwable) {
        fwrite(STDERR, "Target audit could not complete. No secret material has been printed.\n");
        exit(2);
    }
    fwrite(STDOUT, json_encode(['schema' => 1, 'scope' => 'local_file_and_invoking_process_inventory_only', 'issue' => 72] + $result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL);
    exit($result['status'] === 'EXPOSED_KEY_OBSERVED' ? 3 : ($result['status'] === 'INCONCLUSIVE' ? 2 : 0));
}
