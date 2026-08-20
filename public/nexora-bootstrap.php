<?php

declare(strict_types=1);

// The deployment bootstrap is rendered internally by public/index.php so the
// customer always sees the canonical domain URL. Direct access to this
// implementation file is intentionally collapsed back to the site root.
if (! defined('NEXORA_BOOTSTRAP_INTERNAL')) {
    header('Location: /', true, 302);
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive');

/**
 * Nexora standalone deployment bootstrap.
 *
 * Runs before Composer/Laravel is available. It contains no framework calls and
 * exposes only a fixed allow-list of dependency/build tasks. There is no shell
 * command input. Production release archives should already contain vendor/ and
 * public/build; server-build mode is an assisted fallback for source packages.
 */
$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-runtime-bootstrap.php';
require_once $root.'/bootstrap/nexora-installer-bootstrap.php';
require_once $root.'/bootstrap/nexora-process-environment.php';

if (defined('NEXORA_RUNTIME_BOOTSTRAP_ERROR')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo NEXORA_RUNTIME_BOOTSTRAP_ERROR;
    exit;
}

if (defined('NEXORA_INSTALL_BOOTSTRAP_ERROR')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Nexora installer bootstrap error: '.NEXORA_INSTALL_BOOTSTRAP_ERROR;
    exit;
}

$installedLock = $root.'/storage/app/nexora/installed.lock';
if (is_file($installedLock)) {
    header('Location: /', true, 302);
    exit;
}

session_save_path($root.'/storage/framework/sessions');
session_name('nexora_bootstrap');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (! empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (! isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

if (nxIsLocalDeploymentRequest()) {
    $_SESSION['bootstrap_authorized'] = true;
}

$nxLocalization = require $root.'/bootstrap/nexora-locales.php';
$nxSupportedLocales = is_array($nxLocalization['supported'] ?? null) ? $nxLocalization['supported'] : ['en' => ['label' => 'English', 'native' => 'English', 'dir' => 'ltr']];
$requestedLocale = strtolower((string) ($_GET['lang'] ?? ''));
if ($requestedLocale !== '' && isset($nxSupportedLocales[$requestedLocale])) {
    $_SESSION['nexora_locale'] = $requestedLocale;
    setcookie('nexora_locale', $requestedLocale, [
        'expires' => time() + 31536000,
        'path' => '/',
        'secure' => (! empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off'),
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    header('Location: /', true, 303);
    exit;
}
$nxLocaleCandidate = strtolower((string) ($_SESSION['nexora_locale'] ?? $_COOKIE['nexora_locale'] ?? 'en'));
$nxLocale = isset($nxSupportedLocales[$nxLocaleCandidate]) ? $nxLocaleCandidate : 'en';
$nxDirection = (string) ($nxSupportedLocales[$nxLocale]['dir'] ?? 'ltr');
$nxMessages = is_array($nxLocalization['messages'] ?? null) ? $nxLocalization['messages'] : [];

function nxT(string $key, array $replace = []): string
{
    global $nxMessages, $nxLocale;
    $value = (string) ($nxMessages[$nxLocale][$key] ?? $nxMessages['en'][$key] ?? $key);
    foreach ($replace as $name => $replacement) {
        $value = str_replace(':'.$name, (string) $replacement, $value);
    }
    return $value;
}

function nxh(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function nxIcon(string $name, int $size = 18): string
{
    $paths = [
        'check-circle' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
        'x-circle' => '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>',
        'alert' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'shield' => '<path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z"/><path d="m9 12 2 2 4-4"/>',
        'server' => '<rect width="20" height="8" x="2" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/><path d="M6 6h.01"/><path d="M6 18h.01"/>',
        'terminal' => '<path d="m4 17 6-6-6-6"/><path d="M12 19h8"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/>',
        'package' => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
        'play' => '<polygon points="6 3 20 12 6 21 6 3"/>',
        'file' => '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5Z"/><polyline points="14 2 14 8 20 8"/>',
        'refresh' => '<path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 4v5h5"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2M20 20v-5h-5"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'cancel' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'arrow-right' => '<path d="m12 5 7 7-7 7"/><path d="M5 12h14"/>',
        'loader' => '<path d="M21 12a9 9 0 1 1-6.22-8.56"/>',
        'cpu' => '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3"/>',
        'folder' => '<path d="M3 5h6l2 2h10v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 0 20"/><path d="M12 2a15.3 15.3 0 0 0 0 20"/>',
        'upload-cloud' => '<path d="M12 13v8"/><path d="m8 17 4-4 4 4"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 4.3 16.3"/>',
    ];
    $body = $paths[$name] ?? $paths['info'];
    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$body.'</svg>';
}

function nxStatusIcon(bool $ok, string $label): string
{
    return '<span class="status-badge '.($ok ? 'good' : 'bad').'" title="'.nxh($label).'" aria-label="'.nxh($label).'">'.nxIcon($ok ? 'check-circle' : 'x-circle', 17).'<span class="sr-only">'.nxh($label).'</span></span>';
}

function nxDisabledFunctions(): array
{
    $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
    return array_fill_keys($disabled, true);
}

function nxCanRunProcesses(): bool
{
    $disabled = nxDisabledFunctions();
    return function_exists('proc_open') && ! isset($disabled['proc_open']);
}

/** @return array<string,string> */
function nxProcessEnvironment(string $root, array $extra = []): array
{
    return NexoraBootstrapProcessEnvironment::build($root, $extra);
}

/** @return array{composer_home:string,composer_home_source:string,appdata:?string,home:string,npm_cache:string,composer_home_writable:bool} */
function nxProcessEnvironmentSummary(string $root): array
{
    return NexoraBootstrapProcessEnvironment::summary($root);
}


/** @return array{ok:bool,output:string,exit_code:int} */
function nxRunFixedCommand(string $command, string $cwd, int $timeoutSeconds = 900, array $extraEnvironment = []): array
{
    @set_time_limit(max(30, $timeoutSeconds + 30));

    if (! nxCanRunProcesses()) {
        return ['ok' => false, 'output' => 'Server process execution is unavailable (proc_open is disabled). Use a prebuilt Nexora release package.', 'exit_code' => 126];
    }

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $environment = nxProcessEnvironment($cwd, $extraEnvironment);
    $process = @proc_open($command, $descriptors, $pipes, $cwd, $environment, ['suppress_errors' => true]);
    if (! is_resource($process)) {
        return ['ok' => false, 'output' => 'Unable to start the fixed build command on this server.', 'exit_code' => 127];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $output = '';
    $started = microtime(true);
    $timedOut = false;

    while (true) {
        $output .= (string) stream_get_contents($pipes[1]);
        $output .= (string) stream_get_contents($pipes[2]);
        $status = proc_get_status($process);

        if (! $status['running']) {
            break;
        }

        if ((microtime(true) - $started) > $timeoutSeconds) {
            $timedOut = true;
            @proc_terminate($process);
            $output .= "\nNexora stopped the task because the server execution window was exceeded.";
            break;
        }

        usleep(100000);
    }

    $output .= (string) stream_get_contents($pipes[1]);
    $output .= (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_get_status($process);
    $exit = proc_close($process);
    if ($exit === -1 && isset($status['exitcode']) && $status['exitcode'] >= 0) {
        $exit = (int) $status['exitcode'];
    }
    if ($timedOut && $exit === 0) {
        $exit = 124;
    }

    $output = trim($output);
    if (strlen($output) > 120000) {
        $output = substr($output, -120000);
        $output = "[Earlier command output truncated]\n".$output;
    }

    return ['ok' => $exit === 0, 'output' => $output !== '' ? $output : ($exit === 0 ? 'Task completed.' : 'Task failed without output.'), 'exit_code' => $exit];
}



/** @return array{ok:bool,message:string} */
function nxInstallUploadedRelease(array $file, string $root): array
{
    if (! class_exists('ZipArchive')) {
        return ['ok' => false, 'message' => 'PHP ext-zip is required to deploy a prebuilt release bundle.'];
    }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ! isset($file['tmp_name']) || ! is_uploaded_file((string) $file['tmp_name'])) {
        return ['ok' => false, 'message' => 'Select a valid Nexora production release ZIP.'];
    }

    $zip = new ZipArchive();
    if ($zip->open((string) $file['tmp_name'], ZipArchive::RDONLY) !== true) {
        return ['ok' => false, 'message' => 'The uploaded file is not a readable ZIP archive.'];
    }

    try {
        if ($zip->numFiles < 1 || $zip->numFiles > 25000) {
            return ['ok' => false, 'message' => 'Release archive entry count is outside the allowed deployment policy.'];
        }

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = is_array($stat) ? (string) ($stat['name'] ?? '') : '';
            $normalized = str_replace('\\', '/', $name);
            $parts = array_values(array_filter(explode('/', $normalized), static fn (string $part): bool => $part !== ''));
            if ($normalized === '' || str_contains($normalized, "\0") || str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:\//', $normalized) === 1 || in_array('..', $parts, true)) {
                return ['ok' => false, 'message' => 'Release archive contains an unsafe path and was rejected.'];
            }
            if (isset($names[strtolower($normalized)])) {
                return ['ok' => false, 'message' => 'Release archive contains duplicate/conflicting paths.'];
            }
            $names[strtolower($normalized)] = true;
            if (in_array(strtolower($normalized), ['.env', '.env.production', 'storage/app/nexora/installed.lock', 'storage/app/nexora/installing.lock'], true)) {
                return ['ok' => false, 'message' => 'Release archive attempts to overwrite environment/installation state.'];
            }

            // Unix symlink entries are not valid Nexora release payloads.
            $opsys = 0; $attr = 0;
            if (method_exists($zip, 'getExternalAttributesIndex') && $zip->getExternalAttributesIndex($i, $opsys, $attr)) {
                $mode = ($attr >> 16) & 0xF000;
                if ($mode === 0xA000) {
                    return ['ok' => false, 'message' => 'Release archive contains a symbolic link and was rejected.'];
                }
            }
        }

        foreach (['nexora-release.json', 'vendor/autoload.php', 'public/build/manifest.json', 'composer.lock', 'package-lock.json'] as $required) {
            if ($zip->locateName($required, ZipArchive::FL_NOCASE) === false) {
                return ['ok' => false, 'message' => "Release archive is missing required file: {$required}"];
            }
        }

        $manifestRaw = $zip->getFromName('nexora-release.json');
        $manifest = is_string($manifestRaw) ? json_decode($manifestRaw, true) : null;
        if (! is_array($manifest) || ($manifest['product'] ?? null) !== 'Nexora' || ($manifest['type'] ?? null) !== 'production-release') {
            return ['ok' => false, 'message' => 'Release manifest is missing or invalid.'];
        }
        $artifacts = (array) ($manifest['artifacts'] ?? []);
        foreach ([
            'composer_lock_sha256' => 'composer.lock',
            'package_lock_sha256' => 'package-lock.json',
            'frontend_manifest_sha256' => 'public/build/manifest.json',
        ] as $hashKey => $entry) {
            $contents = $zip->getFromName($entry);
            $expected = (string) ($artifacts[$hashKey] ?? '');
            if (! is_string($contents) || $expected === '' || ! hash_equals($expected, hash('sha256', $contents))) {
                return ['ok' => false, 'message' => "Release integrity check failed for {$entry}."];
            }
        }

        $stage = $root.'/storage/app/nexora/release-stage/'.bin2hex(random_bytes(10));
        if (! @mkdir($stage, 0775, true) && ! is_dir($stage)) {
            return ['ok' => false, 'message' => 'Unable to create the release staging directory.'];
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = is_array($stat) ? str_replace('\\', '/', (string) ($stat['name'] ?? '')) : '';
                if ($name === '' || str_ends_with($name, '/')) {
                    continue;
                }
                $destination = $stage.'/'.$name;
                if (! is_dir(dirname($destination)) && ! @mkdir(dirname($destination), 0775, true) && ! is_dir(dirname($destination))) {
                    throw new RuntimeException('Unable to create a staged release directory.');
                }
                $stream = $zip->getStream($name);
                $out = @fopen($destination, 'wb');
                if (! is_resource($stream) || ! is_resource($out)) {
                    if (is_resource($stream)) { fclose($stream); }
                    if (is_resource($out)) { fclose($out); }
                    throw new RuntimeException('Unable to stage a release file.');
                }
                stream_copy_to_stream($stream, $out);
                fclose($stream); fclose($out);
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $item) {
                $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($stage) + 1));
                $destination = $root.'/'.$relative;
                if ($item->isDir()) {
                    if (! is_dir($destination) && ! @mkdir($destination, 0775, true) && ! is_dir($destination)) {
                        throw new RuntimeException("Unable to create deployment directory: {$relative}");
                    }
                    continue;
                }
                if (! is_dir(dirname($destination)) && ! @mkdir(dirname($destination), 0775, true) && ! is_dir(dirname($destination))) {
                    throw new RuntimeException("Unable to prepare deployment directory: {$relative}");
                }
                if (! @copy($item->getPathname(), $destination)) {
                    throw new RuntimeException("Unable to deploy release file: {$relative}");
                }
            }
        } finally {
            $cleanup = static function (string $path) use (&$cleanup): void {
                if (! is_dir($path)) { @unlink($path); return; }
                foreach (scandir($path) ?: [] as $entry) {
                    if ($entry === '.' || $entry === '..') { continue; }
                    $cleanup($path.'/'.$entry);
                }
                @rmdir($path);
            };
            $cleanup($stage);
        }

        return ['ok' => true, 'message' => 'Prebuilt Nexora production release verified and deployed.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Release deployment failed: '.$e->getMessage()];
    } finally {
        $zip->close();
    }
}

function nxCommandPath(string $path): string
{
    if (PHP_OS_FAMILY === 'Windows') {
        return '"'.str_replace('"', '""', $path).'"';
    }

    return escapeshellarg($path);
}

/** @return list<string> */
function nxLaragonRoots(string $root): array
{
    return NexoraBootstrapProcessEnvironment::laragonRoots($root);
}


function nxProbePathExecutable(string $name, string $root): ?string
{
    if (! nxCanRunProcesses()) {
        return null;
    }

    if (preg_match('/^[A-Za-z0-9._-]+$/', $name) !== 1) {
        return null;
    }

    $probe = PHP_OS_FAMILY === 'Windows'
        ? 'where.exe '.$name
        : 'command -v '.$name;
    $result = nxRunFixedCommand($probe, $root, 15);
    if (! $result['ok']) {
        return null;
    }

    foreach (preg_split('/\\R/', $result['output']) ?: [] as $line) {
        $path = trim($line, " \t\n\r\0\x0B\"'");
        if ($path !== '' && is_file($path)) {
            return $path;
        }
    }

    return null;
}

/** @return array{ok:bool,version:?string} */
function nxValidateToolCommand(string $command, string $root, string $versionArgument = '--version'): array
{
    $result = nxRunFixedCommand($command.' '.$versionArgument, $root, 30);
    if (! $result['ok']) {
        return ['ok' => false, 'version' => null];
    }

    $firstLine = trim((string) (preg_split('/\\R/', $result['output']) ?: [''])[0]);
    return ['ok' => true, 'version' => $firstLine !== '' ? $firstLine : null];
}

/** @return array{path:string,command:string,source:string}|null */
function nxResolvePhpCli(string $root): ?array
{
    $candidates = [];
    $fromPath = nxProbePathExecutable(PHP_OS_FAMILY === 'Windows' ? 'php.exe' : 'php', $root);
    if ($fromPath !== null) {
        $candidates[] = [$fromPath, 'PATH'];
    }

    if (defined('PHP_BINARY') && is_file(PHP_BINARY) && preg_match('/^php(?:\\.exe)?$/i', basename(PHP_BINARY)) === 1) {
        $candidates[] = [PHP_BINARY, 'web PHP binary'];
    }

    if (defined('PHP_BINDIR')) {
        $candidate = rtrim((string) PHP_BINDIR, "\\/").DIRECTORY_SEPARATOR.(PHP_OS_FAMILY === 'Windows' ? 'php.exe' : 'php');
        if (is_file($candidate)) {
            $candidates[] = [$candidate, 'PHP_BINDIR'];
        }
    }

    foreach (nxLaragonRoots($root) as $laragon) {
        foreach (glob($laragon.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'php'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'php.exe') ?: [] as $candidate) {
            if (is_file($candidate)) {
                $candidates[] = [$candidate, 'Laragon'];
            }
        }
    }

    foreach ($candidates as [$path, $source]) {
        if (! is_file($path)) {
            continue;
        }
        return ['path' => $path, 'command' => nxCommandPath($path), 'source' => $source];
    }

    return null;
}

/** @return array{path:string,command:string,source:string,version:?string}|null */
function nxResolveComposer(string $root, ?array $phpCli = null): ?array
{
    $phpCli ??= nxResolvePhpCli($root);
    $candidates = [];

    // Prefer the Composer that the host already exposes through its environment.
    // This respects local development environments before Nexora-specific fallbacks.
    foreach ([PHP_OS_FAMILY === 'Windows' ? 'composer.bat' : 'composer', 'composer'] as $probe) {
        $path = nxProbePathExecutable($probe, $root);
        if ($path !== null) {
            $candidates[] = [$path, nxCommandPath($path), 'PATH / OS environment'];
        }
    }

    if (PHP_OS_FAMILY === 'Windows') {
        $programData = trim((string) getenv('ProgramData'));
        $appData = trim((string) getenv('APPDATA'));
        $userProfile = trim((string) getenv('USERPROFILE'));
        if ($programData !== '') {
            $candidate = $programData.'\\ComposerSetup\\bin\\composer.bat';
            if (is_file($candidate)) {
                $candidates[] = [$candidate, nxCommandPath($candidate), 'ComposerSetup'];
            }
        }
        if ($appData === '' && $userProfile !== '') {
            $derived = rtrim($userProfile, "\\/").'\\AppData\\Roaming';
            if (is_dir($derived)) {
                $appData = $derived;
            }
        }
        if ($appData !== '') {
            $candidate = $appData.'\\Composer\\vendor\\bin\\composer.bat';
            if (is_file($candidate)) {
                $candidates[] = [$candidate, nxCommandPath($candidate), 'User Composer'];
            }
        }
    }

    if ($phpCli !== null && PHP_OS_FAMILY === 'Windows') {
        foreach (nxLaragonRoots($root) as $laragon) {
            $direct = $laragon.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'composer'.DIRECTORY_SEPARATOR.'composer.phar';
            if (is_file($direct)) {
                $candidates[] = [$direct, $phpCli['command'].' '.nxCommandPath($direct), 'Laragon'];
            }
            $globbed = glob($laragon.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'composer'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'composer.phar') ?: [];
            usort($globbed, static fn (string $a, string $b): int => strnatcasecmp($b, $a));
            foreach ($globbed as $candidate) {
                $candidates[] = [$candidate, $phpCli['command'].' '.nxCommandPath($candidate), 'Laragon'];
            }
        }
    }

    if ($phpCli !== null) {
        foreach ([
            [$root.'/composer.phar', 'project Composer PHAR'],
            [$root.'/storage/app/nexora/tools/composer.phar', 'Nexora private tool'],
        ] as [$candidate, $source]) {
            if (is_file($candidate)) {
                $candidates[] = [$candidate, $phpCli['command'].' '.nxCommandPath($candidate), $source];
            }
        }
    }

    $seen = [];
    foreach ($candidates as [$path, $command, $source]) {
        $key = strtolower(str_replace('\\', '/', $path));
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;

        $health = nxValidateToolCommand($command, $root, '--version --no-ansi');
        if ($health['ok']) {
            return [
                'path' => $path,
                'command' => $command,
                'source' => $source,
                'version' => $health['version'],
            ];
        }
    }

    return null;
}


/** @return array{path:string,command:string,source:string,version:?string}|null */
function nxResolveNode(string $root): ?array
{
    $candidates = [];
    $path = nxProbePathExecutable(PHP_OS_FAMILY === 'Windows' ? 'node.exe' : 'node', $root);
    if ($path !== null) {
        $candidates[] = [$path, 'PATH / OS environment'];
    }

    if (PHP_OS_FAMILY === 'Windows') {
        foreach (nxLaragonRoots($root) as $laragon) {
            foreach (glob($laragon.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'nodejs'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'node.exe') ?: [] as $candidate) {
                $candidates[] = [$candidate, 'Laragon'];
            }
            $candidates[] = [$laragon.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'nodejs'.DIRECTORY_SEPARATOR.'node.exe', 'Laragon'];
        }
        foreach ([(string) getenv('ProgramFiles'), (string) getenv('ProgramFiles(x86)')] as $programFiles) {
            if ($programFiles !== '') {
                $candidates[] = [rtrim($programFiles, "\\/").DIRECTORY_SEPARATOR.'nodejs'.DIRECTORY_SEPARATOR.'node.exe', 'Program Files'];
            }
        }
        $candidates[] = [$root.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'nexora'.DIRECTORY_SEPARATOR.'tools'.DIRECTORY_SEPARATOR.'node'.DIRECTORY_SEPARATOR.'node.exe', 'Nexora private tool'];
    } else {
        $candidates[] = [$root.'/storage/app/nexora/tools/node/bin/node', 'Nexora private tool'];
        foreach (['/usr/local/bin/node', '/usr/bin/node'] as $candidate) {
            $candidates[] = [$candidate, 'system'];
        }
    }

    $seen = [];
    foreach ($candidates as [$candidate, $source]) {
        if (! is_file($candidate)) {
            continue;
        }
        $key = strtolower(str_replace('\\', '/', $candidate));
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $command = nxCommandPath($candidate);
        $health = nxValidateToolCommand($command, $root);
        if ($health['ok']) {
            return ['path' => $candidate, 'command' => $command, 'source' => $source, 'version' => $health['version']];
        }
    }

    return null;
}


/** @return array{path:string,command:string,source:string,version:?string}|null */
function nxResolveNpm(string $root, ?array $node = null): ?array
{
    $node ??= nxResolveNode($root);
    $candidates = [];

    foreach ([PHP_OS_FAMILY === 'Windows' ? 'npm.cmd' : 'npm', 'npm'] as $probe) {
        $path = nxProbePathExecutable($probe, $root);
        if ($path !== null) {
            $candidates[] = [$path, nxCommandPath($path), 'PATH / OS environment'];
        }
    }

    if ($node !== null) {
        $dir = dirname($node['path']);
        $cliCandidates = PHP_OS_FAMILY === 'Windows'
            ? [$dir.DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR.'npm'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'npm-cli.js']
            : [dirname($dir).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR.'npm'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'npm-cli.js'];
        foreach ($cliCandidates as $candidate) {
            if (is_file($candidate)) {
                $candidates[] = [$candidate, $node['command'].' '.nxCommandPath($candidate), $node['source']];
            }
        }

        $candidate = PHP_OS_FAMILY === 'Windows' ? $dir.DIRECTORY_SEPARATOR.'npm.cmd' : $dir.DIRECTORY_SEPARATOR.'npm';
        if (is_file($candidate)) {
            $candidates[] = [$candidate, nxCommandPath($candidate), $node['source']];
        }
    }

    $seen = [];
    foreach ($candidates as [$path, $command, $source]) {
        $key = strtolower(str_replace('\\', '/', $path));
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $health = nxValidateToolCommand($command, $root);
        if ($health['ok']) {
            return ['path' => $path, 'command' => $command, 'source' => $source, 'version' => $health['version']];
        }
    }

    return null;
}


/** @return array{php:?array,composer:?array,node:?array,npm:?array} */
function nxResolveTooling(string $root): array
{
    $php = nxResolvePhpCli($root);
    $node = nxResolveNode($root);
    return [
        'php' => $php,
        'composer' => nxResolveComposer($root, $php),
        'node' => $node,
        'npm' => nxResolveNpm($root, $node),
    ];
}

/** @return array{ok:bool,message:string} */
function nxDownloadHttpsToFile(string $url, string $destination, int $maxBytes, ?callable $progress = null): array
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if (! in_array($host, ['getcomposer.org', 'composer.github.io', 'nodejs.org'], true) || ! str_starts_with($url, 'https://')) {
        return ['ok' => false, 'message' => 'Nexora blocked a download from a non-approved bootstrap host.'];
    }

    if (! is_dir(dirname($destination)) && ! @mkdir(dirname($destination), 0775, true) && ! is_dir(dirname($destination))) {
        return ['ok' => false, 'message' => 'Unable to create the private bootstrap tools directory.'];
    }

    $out = @fopen($destination, 'wb');
    if (! is_resource($out)) {
        return ['ok' => false, 'message' => 'Unable to create the bootstrap download file.'];
    }

    $bytes = 0;
    $ok = false;
    $error = 'Unable to download the required bootstrap tool over HTTPS.';

    try {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'Nexora-Installer/0.10',
                CURLOPT_FILE => $out,
                CURLOPT_NOPROGRESS => false,
                CURLOPT_PROGRESSFUNCTION => static function ($resource, float $downloadSize, float $downloaded, float $uploadSize, float $uploaded) use ($maxBytes, $progress): int {
                    static $lastProgressAt = 0.0;
                    $now = microtime(true);
                    if ($progress !== null && ($now - $lastProgressAt >= 0.5 || ($downloadSize > 0 && $downloaded >= $downloadSize))) {
                        $lastProgressAt = $now;
                        $progress(['downloaded' => (int) $downloaded, 'total' => $downloadSize > 0 ? (int) $downloadSize : null]);
                    }
                    return $downloaded > $maxBytes ? 1 : 0;
                },
            ]);
            $ran = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            $bytes = is_file($destination) ? (int) filesize($destination) : 0;
            $ok = $ran === true && $status >= 200 && $status < 300 && $bytes > 0 && $bytes <= $maxBytes;
            if (! $ok && $curlError !== '') {
                $error = 'HTTPS download failed: '.$curlError;
            }
        } elseif ((bool) ini_get('allow_url_fopen')) {
            $context = stream_context_create(['http' => ['timeout' => 60, 'follow_location' => 0, 'user_agent' => 'Nexora-Installer/0.10'], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
            $in = @fopen($url, 'rb', false, $context);
            if (is_resource($in)) {
                while (! feof($in)) {
                    $chunk = fread($in, 1024 * 1024);
                    if ($chunk === false) {
                        break;
                    }
                    $bytes += strlen($chunk);
                    if ($bytes > $maxBytes) {
                        $error = 'Bootstrap download exceeded the maximum allowed size.';
                        break;
                    }
                    fwrite($out, $chunk);
                    if ($progress !== null) {
                        $progress(['downloaded' => $bytes, 'total' => null]);
                    }
                }
                fclose($in);
                $ok = $bytes > 0 && $bytes <= $maxBytes;
            }
        }
    } finally {
        fclose($out);
    }

    if (! $ok) {
        @unlink($destination);
        return ['ok' => false, 'message' => $error];
    }

    return ['ok' => true, 'message' => 'Downloaded securely.'];
}

/** @return array{ok:bool,message:string} */
function nxInstallPrivateComposer(string $root, ?array $phpCli, ?callable $progress = null): array
{
    if (! nxCanRunProcesses()) {
        return ['ok' => false, 'message' => 'Process execution is unavailable. Use a prebuilt Nexora release instead.'];
    }
    if ($phpCli === null) {
        return ['ok' => false, 'message' => 'A PHP CLI binary could not be resolved. Composer cannot be bootstrapped on this host.'];
    }

    $tools = $root.'/storage/app/nexora/tools';
    $installer = $tools.'/composer-setup.php';
    $sigFile = $tools.'/composer-installer.sig';
    $composer = $tools.'/composer.phar';

    $sigDownload = nxDownloadHttpsToFile('https://composer.github.io/installer.sig', $sigFile, 4096, $progress);
    if (! $sigDownload['ok']) {
        return $sigDownload;
    }
    $installerDownload = nxDownloadHttpsToFile('https://getcomposer.org/installer', $installer, 2 * 1024 * 1024, $progress);
    if (! $installerDownload['ok']) {
        @unlink($sigFile);
        return $installerDownload;
    }

    $expected = trim((string) @file_get_contents($sigFile));
    $actual = hash_file('sha384', $installer);
    @unlink($sigFile);
    if ($expected === '' || $actual === false || ! hash_equals(strtolower($expected), strtolower($actual))) {
        @unlink($installer);
        return ['ok' => false, 'message' => 'Composer installer signature verification failed. Nothing was executed.'];
    }

    $result = nxRunFixedCommand(
        $phpCli['command'].' '.nxCommandPath($installer).' --no-ansi --install-dir='.nxCommandPath($tools).' --filename=composer.phar',
        $root,
        180
    );
    @unlink($installer);
    if (! $result['ok'] || ! is_file($composer)) {
        return ['ok' => false, 'message' => "Composer bootstrap failed.\n".$result['output']];
    }

    return ['ok' => true, 'message' => 'Composer was installed privately for Nexora and verified before execution.'];
}

function nxRemoveTree(string $path): void
{
    if (! file_exists($path)) {
        return;
    }
    if (! is_dir($path)) {
        @unlink($path);
        return;
    }
    foreach (scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        nxRemoveTree($path.DIRECTORY_SEPARATOR.$entry);
    }
    @rmdir($path);
}

function nxCopyTree(string $source, string $destination): bool
{
    if (is_file($source)) {
        if (! is_dir(dirname($destination)) && ! @mkdir(dirname($destination), 0775, true) && ! is_dir(dirname($destination))) {
            return false;
        }
        return @copy($source, $destination);
    }
    if (! is_dir($destination) && ! @mkdir($destination, 0775, true) && ! is_dir($destination)) {
        return false;
    }
    foreach (scandir($source) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (! nxCopyTree($source.DIRECTORY_SEPARATOR.$entry, $destination.DIRECTORY_SEPARATOR.$entry)) {
            return false;
        }
    }
    return true;
}

/** @return array{ok:bool,message:string} */
function nxInstallPrivateNode(string $root, ?callable $progress = null): array
{
    if (! nxCanRunProcesses()) {
        return ['ok' => false, 'message' => 'Process execution is unavailable. Use a prebuilt Nexora release instead.'];
    }

    $os = PHP_OS_FAMILY;
    $machine = strtolower((string) php_uname('m'));
    $arch = in_array($machine, ['arm64', 'aarch64'], true) ? 'arm64' : (in_array($machine, ['x86_64', 'amd64', 'x64'], true) ? 'x64' : null);
    if ($arch === null || ! in_array($os, ['Windows', 'Linux', 'Darwin'], true)) {
        return ['ok' => false, 'message' => 'Private Node.js bootstrap is not supported on this operating-system/CPU combination. Use a prebuilt Nexora release.'];
    }

    $channel = 'https://nodejs.org/download/release/latest-v24.x/';
    $tools = $root.'/storage/app/nexora/tools';
    $stage = $tools.'/node-stage-'.bin2hex(random_bytes(6));
    @mkdir($stage, 0775, true);
    $sums = $stage.'/SHASUMS256.txt';
    $sumDownload = nxDownloadHttpsToFile($channel.'SHASUMS256.txt', $sums, 1024 * 1024, $progress);
    if (! $sumDownload['ok']) {
        nxRemoveTree($stage);
        return $sumDownload;
    }

    $platform = match ($os) {
        'Windows' => 'win-'.$arch.'.zip',
        'Linux' => 'linux-'.$arch.'.tar.gz',
        'Darwin' => 'darwin-'.$arch.'.tar.gz',
    };
    $contents = (string) @file_get_contents($sums);
    if (preg_match('/^([a-f0-9]{64})\\s+(node-v[0-9.]+-'.preg_quote($platform, '/').')$/mi', $contents, $match) !== 1) {
        nxRemoveTree($stage);
        return ['ok' => false, 'message' => 'Unable to resolve a checksum-published Node.js LTS artifact for this server.'];
    }

    $expected = strtolower($match[1]);
    $filename = $match[2];
    $archive = $stage.'/'.$filename;
    $download = nxDownloadHttpsToFile($channel.$filename, $archive, 150 * 1024 * 1024, $progress);
    if (! $download['ok']) {
        nxRemoveTree($stage);
        return $download;
    }
    $actual = hash_file('sha256', $archive);
    if ($actual === false || ! hash_equals($expected, strtolower($actual))) {
        nxRemoveTree($stage);
        return ['ok' => false, 'message' => 'Node.js archive checksum verification failed. Nothing was installed.'];
    }

    $extract = $stage.'/extract';
    @mkdir($extract, 0775, true);
    try {
        if ($os === 'Windows') {
            if (! class_exists('ZipArchive')) {
                throw new RuntimeException('PHP ext-zip is required to unpack the private Node.js runtime.');
            }
            $zip = new ZipArchive();
            if ($zip->open($archive, ZipArchive::RDONLY) !== true) {
                throw new RuntimeException('Unable to open the verified Node.js archive.');
            }
            $zip->extractTo($extract);
            $zip->close();
        } else {
            if (! class_exists('PharData')) {
                throw new RuntimeException('PHP PharData is required to unpack the private Node.js runtime.');
            }
            $phar = new PharData($archive);
            $phar->decompress();
            $tarPath = substr($archive, 0, -3);
            $tar = new PharData($tarPath);
            $tar->extractTo($extract, null, true);
        }

        $dirs = array_values(array_filter(glob($extract.'/node-v*') ?: [], 'is_dir'));
        if (count($dirs) !== 1) {
            throw new RuntimeException('Verified Node.js archive has an unexpected directory layout.');
        }

        $target = $tools.'/node';
        nxRemoveTree($target);
        if (! nxCopyTree($dirs[0], $target)) {
            throw new RuntimeException('Unable to deploy the private Node.js runtime.');
        }
        if ($os !== 'Windows') {
            @chmod($target.'/bin/node', 0755);
            @chmod($target.'/bin/npm', 0755);
            @chmod($target.'/bin/npx', 0755);
        }
    } catch (Throwable $e) {
        nxRemoveTree($stage);
        return ['ok' => false, 'message' => 'Node.js bootstrap failed: '.$e->getMessage()];
    }
    nxRemoveTree($stage);

    $resolved = nxResolveNode($root);
    $npm = nxResolveNpm($root, $resolved);
    if ($resolved === null || $npm === null) {
        return ['ok' => false, 'message' => 'Node.js files were deployed but Nexora could not resolve the private Node/npm runtime.'];
    }

    return ['ok' => true, 'message' => 'A checksum-verified private Node.js LTS runtime with npm was installed for this Nexora source build.'];
}


function nxIsLocalDeploymentRequest(): bool
{
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if (in_array($remote, ['127.0.0.1', '::1'], true)) {
        return true;
    }

    if ($remote !== '' && filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        return ! str_contains($host, '.') || str_ends_with($host, '.test') || str_ends_with($host, '.localhost');
    }

    return false;
}

function nxDeploymentAccessKeyPath(string $root): string
{
    return $root.'/storage/app/nexora/deployment-access.key';
}

function nxAtomicWriteFile(string $path, string $contents, int $directoryMode = 0775, ?int $fileMode = null): bool
{
    $directory = dirname($path);
    if (! is_dir($directory) && ! @mkdir($directory, $directoryMode, true) && ! is_dir($directory)) return false;
    if (! is_writable($directory) || is_link($path)) return false;

    try {
        $temporary = $directory.DIRECTORY_SEPARATOR.'.nexora-atomic-'.bin2hex(random_bytes(8)).'.tmp';
    } catch (Throwable) {
        return false;
    }
    $handle = @fopen($temporary, 'xb');
    if (! is_resource($handle)) return false;
    $ok = true;
    try {
        if (! @flock($handle, LOCK_EX)) $ok = false;
        $length = strlen($contents);
        $offset = 0;
        while ($ok && $offset < $length) {
            $written = @fwrite($handle, substr($contents, $offset));
            if ($written === false || $written === 0) { $ok = false; break; }
            $offset += $written;
        }
        if ($ok && ! @fflush($handle)) $ok = false;
        if ($ok && function_exists('fsync') && ! @fsync($handle)) $ok = false;
    } finally {
        @flock($handle, LOCK_UN);
        fclose($handle);
    }
    if (! $ok) { @unlink($temporary); return false; }
    if ($fileMode !== null) @chmod($temporary, $fileMode);

    if (PHP_OS_FAMILY !== 'Windows') {
        $published = @rename($temporary, $path);
        if (! $published) @unlink($temporary);
        return $published;
    }

    if (! file_exists($path)) {
        $published = @rename($temporary, $path);
        if (! $published) @unlink($temporary);
        return $published;
    }

    try { $backup = $directory.DIRECTORY_SEPARATOR.'.nexora-replace-'.bin2hex(random_bytes(8)).'.bak'; }
    catch (Throwable) { @unlink($temporary); return false; }
    if (! @rename($path, $backup)) { @unlink($temporary); return false; }
    if (! @rename($temporary, $path)) {
        @rename($backup, $path);
        @unlink($temporary);
        return false;
    }
    if (! @unlink($backup) && is_file($backup)) return false;
    return true;
}

function nxEnsureDeploymentAccessKey(string $root): string
{
    $path = nxDeploymentAccessKeyPath($root);
    $directory = dirname($path);
    if (! is_dir($directory)) {
        @mkdir($directory, 0700, true);
    }
    if (is_file($path)) {
        $existing = trim((string) @file_get_contents($path));
        if (preg_match('/^[A-Z0-9-]{20,64}$/', $existing) === 1) {
            return $existing;
        }
    }

    $raw = strtoupper(bin2hex(random_bytes(12)));
    $key = implode('-', str_split($raw, 6));
    if (! nxAtomicWriteFile($path, $key.PHP_EOL, 0700, 0600)) {
        throw new RuntimeException('Unable to create the protected deployment access key.');
    }
    @chmod($path, 0600);

    return $key;
}

/** @return array{ok:bool,message:string} */
function nxAuthorizeDeployment(string $root, array $input): array
{
    if (nxIsLocalDeploymentRequest()) {
        return ['ok' => true, 'message' => 'Local deployment session authorized automatically.'];
    }

    $provided = strtoupper(trim((string) ($input['deployment_key'] ?? '')));
    if ($provided === '') {
        return ['ok' => false, 'message' => 'Enter the deployment access key from storage/app/nexora/deployment-access.key.'];
    }

    try {
        $expected = nxEnsureDeploymentAccessKey($root);
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }

    if (! hash_equals($expected, $provided)) {
        return ['ok' => false, 'message' => 'The deployment access key is invalid.'];
    }

    return ['ok' => true, 'message' => 'This browser is authorized for deployment preparation.'];
}

/** Emit a single deployment event as newline-delimited JSON. */
function nxDeploymentStreamEvent(array $event): void
{
    $event['timestamp'] ??= gmdate(DATE_ATOM);
    echo json_encode($event, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n";
    if (function_exists('ob_flush')) {
        @ob_flush();
    }
    flush();
}

/** Prepare an HTTP response that can expose command progress without a full-page wait. */
function nxBeginDeploymentStream(): void
{
    @ini_set('zlib.output_compression', '0');
    @ini_set('output_buffering', '0');
    @set_time_limit(0);
    ignore_user_abort(false);

    while (ob_get_level() > 0) {
        @ob_end_flush();
    }
    ob_implicit_flush(true);

    header('Content-Type: application/x-ndjson; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Accel-Buffering: no');
    header('X-Content-Type-Options: nosniff');
}

function nxDeploymentControlDirectory(string $root): string
{
    $directory = $root.'/storage/app/nexora/deployment-control';
    if (! is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }

    return $directory;
}

function nxDeploymentStatePath(string $root): string
{
    return nxDeploymentControlDirectory($root).'/state.json';
}

function nxDeploymentCancelPath(string $root, string $runId): string
{
    return nxDeploymentControlDirectory($root).'/cancel-'.$runId.'.flag';
}

/** @param array<string,mixed> $extra */
function nxWriteDeploymentState(string $root, string $runId, bool $active, array $extra = []): void
{
    if ($runId === '') {
        return;
    }

    $state = [
        'run_id' => $runId,
        'active' => $active,
        'owner_pid' => getmypid(),
        'heartbeat_at' => gmdate(DATE_ATOM),
        'heartbeat_epoch' => time(),
        ...$extra,
    ];
    $path = nxDeploymentStatePath($root);
    if (! nxAtomicWriteFile($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 0775, 0600)) {
        throw new RuntimeException('Unable to persist deployment state atomically.');
    }
}

/** @return array<string,mixed> */
function nxReadDeploymentState(string $root): array
{
    $path = nxDeploymentStatePath($root);
    if (! is_file($path)) {
        return [];
    }

    $decoded = json_decode((string) @file_get_contents($path), true);
    return is_array($decoded) ? $decoded : [];
}

function nxDeploymentLockAvailable(string $root): bool
{
    $path = $root.'/storage/app/nexora/deployment.lock';
    if (! is_dir(dirname($path)) && ! @mkdir(dirname($path), 0775, true) && ! is_dir(dirname($path))) {
        return false;
    }
    $handle = @fopen($path, 'c+');
    if (! is_resource($handle)) return false;
    try {
        $locked = @flock($handle, LOCK_EX | LOCK_NB);
        if ($locked) @flock($handle, LOCK_UN);
        return (bool) $locked;
    } finally {
        fclose($handle);
    }
}

/** @param array<string,mixed> $state */
function nxArchiveInterruptedDeploymentState(string $root, array $state): void
{
    if ($state === []) return;
    $state['active'] = false;
    $state['status'] = 'interrupted';
    $state['recovered_at'] = gmdate(DATE_ATOM);
    $state['heartbeat_at'] = gmdate(DATE_ATOM);
    $state['heartbeat_epoch'] = time();
    if (! nxAtomicWriteFile($root.'/storage/app/nexora/deployment-last-interrupted.json', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 0775, 0600)) {
        throw new RuntimeException('Unable to archive interrupted deployment state atomically.');
    }
}

/** @return array<string,mixed> */
function nxRecoverInterruptedDeploymentState(string $root): array
{
    $state = nxReadDeploymentState($root);
    if ($state === [] || empty($state['active'])) return $state;
    if (! nxDeploymentLockAvailable($root)) return $state;

    nxArchiveInterruptedDeploymentState($root, $state);
    $state['active'] = false;
    $state['status'] = 'interrupted';
    $state['recovered_at'] = gmdate(DATE_ATOM);
    $state['heartbeat_at'] = gmdate(DATE_ATOM);
    $state['heartbeat_epoch'] = time();
    if (! nxAtomicWriteFile(nxDeploymentStatePath($root), json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 0775, 0600)) {
        throw new RuntimeException('Unable to persist recovered deployment state atomically.');
    }
    return $state;
}

function nxDeploymentCancellationRequested(string $root, string $runId): bool
{
    return $runId !== '' && is_file(nxDeploymentCancelPath($root, $runId));
}

/** @return array{ok:bool,message:string,run_id:string} */
function nxRequestDeploymentCancellation(string $root, string $runId): array
{
    if (! preg_match('/^[a-f0-9]{24}$/', $runId)) {
        return ['ok' => false, 'message' => 'The deployment run identifier is invalid.', 'run_id' => $runId];
    }

    $state = nxRecoverInterruptedDeploymentState($root);
    if (($state['run_id'] ?? null) !== $runId || empty($state['active'])) {
        return ['ok' => true, 'message' => 'The deployment task is no longer active.', 'run_id' => $runId];
    }

    $path = nxDeploymentCancelPath($root, $runId);
    if (! nxAtomicWriteFile($path, gmdate(DATE_ATOM)."\n", 0775, 0600)) {
        return ['ok' => false, 'message' => 'Nexora could not persist the cancellation request.', 'run_id' => $runId];
    }

    nxWriteDeploymentState($root, $runId, true, [
        'status' => 'cancelling',
        'task' => (string) ($state['task'] ?? ''),
        'step' => (string) ($state['step'] ?? ''),
        'child_pid' => $state['child_pid'] ?? null,
    ]);

    return ['ok' => true, 'message' => 'Cancellation accepted. Nexora is stopping the active process safely.', 'run_id' => $runId];
}

/** @param resource $process @param array<string,mixed> $status */
function nxTerminateDeploymentProcess($process, array $status): void
{
    @proc_terminate($process);

    if (PHP_OS_FAMILY === 'Windows' && isset($status['pid']) && is_numeric($status['pid'])) {
        $pid = (int) $status['pid'];
        if ($pid > 0 && nxCanRunProcesses()) {
            $null = fopen(PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null', 'w');
            $descriptors = [0 => ['pipe', 'r'], 1 => $null, 2 => $null];
            $killer = @proc_open('taskkill /PID '.$pid.' /T /F', $descriptors, $pipes, null, nxProcessEnvironment(dirname(__DIR__)));
            if (is_resource($killer)) {
                if (isset($pipes[0]) && is_resource($pipes[0])) {
                    fclose($pipes[0]);
                }
                @proc_close($killer);
            }
            if (is_resource($null)) {
                fclose($null);
            }
        }
    }
}

function nxCleanDeploymentOutput(string $chunk): string
{
    // Remove terminal ANSI control sequences and NUL bytes before displaying logs in the browser.
    $chunk = str_replace("\0", '', $chunk);
    $chunk = preg_replace('/\x1B(?:[@-_]|\[[0-?]*[ -\/]*[@-~])/', '', $chunk) ?? $chunk;
    if (strlen($chunk) > 24000) {
        $chunk = substr($chunk, -24000);
    }
    return $chunk;
}

/**
 * Execute one fixed deployment command while streaming real stdout/stderr and heartbeats.
 * Progress is stage-based rather than fabricated from elapsed time.
 *
 * @return array{ok:bool,output:string,exit_code:int,cancelled:bool,timed_out:bool}
 */
function nxStreamFixedCommand(
    string $step,
    string $label,
    string $command,
    string $cwd,
    int $progressStart,
    int $progressEnd,
    int $timeoutSeconds = 900,
    array $extraEnvironment = [],
): array {
    if (! nxCanRunProcesses()) {
        nxDeploymentStreamEvent([
            'type' => 'step', 'step' => $step, 'label' => $label, 'status' => 'failed',
            'progress' => $progressStart, 'message' => 'Controlled process execution is unavailable.',
        ]);
        return ['ok' => false, 'output' => 'Controlled process execution is unavailable.', 'exit_code' => 126, 'cancelled' => false, 'timed_out' => false];
    }

    nxDeploymentStreamEvent([
        'type' => 'step', 'step' => $step, 'label' => $label, 'status' => 'running',
        'progress' => $progressStart, 'message' => $label.' started.',
    ]);

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $environment = nxProcessEnvironment($cwd, $extraEnvironment);
    $process = @proc_open($command, $descriptors, $pipes, $cwd, $environment, ['suppress_errors' => true]);
    if (! is_resource($process)) {
        nxDeploymentStreamEvent([
            'type' => 'step', 'step' => $step, 'label' => $label, 'status' => 'failed',
            'progress' => $progressStart, 'message' => 'Unable to start the fixed deployment process.',
        ]);
        return ['ok' => false, 'output' => 'Unable to start the fixed deployment process.', 'exit_code' => 127, 'cancelled' => false, 'timed_out' => false];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $started = microtime(true);
    $lastHeartbeat = 0.0;
    $combined = '';
    $cancelled = false;
    $timedOut = false;
    $runId = (string) ($GLOBALS['NEXORA_DEPLOYMENT_RUN_ID'] ?? '');
    $root = $cwd;

    while (true) {
        foreach ([1 => 'stdout', 2 => 'stderr'] as $index => $streamName) {
            $chunk = (string) stream_get_contents($pipes[$index]);
            if ($chunk !== '') {
                $chunk = nxCleanDeploymentOutput($chunk);
                $combined .= $chunk;
                if (strlen($combined) > 160000) {
                    $combined = substr($combined, -160000);
                }
                nxDeploymentStreamEvent([
                    'type' => 'log', 'step' => $step, 'stream' => $streamName,
                    'message' => $chunk, 'progress' => $progressStart,
                    'elapsed' => (int) floor(microtime(true) - $started),
                ]);
            }
        }

        $status = proc_get_status($process);
        if (! $status['running']) {
            break;
        }

        $elapsed = microtime(true) - $started;
        if (nxDeploymentCancellationRequested($root, $runId) || connection_aborted()) {
            $cancelled = true;
            nxTerminateDeploymentProcess($process, $status);
            break;
        }
        if ($elapsed > $timeoutSeconds) {
            $timedOut = true;
            nxTerminateDeploymentProcess($process, $status);
            break;
        }
        if (($elapsed - $lastHeartbeat) >= 1.0) {
            $lastHeartbeat = $elapsed;
            nxWriteDeploymentState($root, $runId, true, [
                'status' => 'running',
                'step' => $step,
                'label' => $label,
                'child_pid' => isset($status['pid']) ? (int) $status['pid'] : null,
            ]);
            nxDeploymentStreamEvent([
                'type' => 'heartbeat', 'step' => $step, 'label' => $label,
                'progress' => $progressStart, 'elapsed' => (int) floor($elapsed),
                'message' => $label.' is still running.',
            ]);
        }
        usleep(100000);
    }

    foreach ([1 => 'stdout', 2 => 'stderr'] as $index => $streamName) {
        $chunk = (string) stream_get_contents($pipes[$index]);
        if ($chunk !== '') {
            $chunk = nxCleanDeploymentOutput($chunk);
            $combined .= $chunk;
            nxDeploymentStreamEvent([
                'type' => 'log', 'step' => $step, 'stream' => $streamName,
                'message' => $chunk, 'progress' => $progressStart,
                'elapsed' => (int) floor(microtime(true) - $started),
            ]);
        }
        fclose($pipes[$index]);
    }

    $status = proc_get_status($process);
    $exit = proc_close($process);
    if ($exit === -1 && isset($status['exitcode']) && $status['exitcode'] >= 0) {
        $exit = (int) $status['exitcode'];
    }
    if (($cancelled || $timedOut) && $exit === 0) {
        $exit = $cancelled ? 130 : 124;
    }

    $ok = $exit === 0 && ! $cancelled && ! $timedOut;
    $message = $cancelled
        ? $label.' was cancelled from the browser.'
        : ($timedOut ? $label.' exceeded the allowed execution window.' : ($ok ? $label.' completed.' : $label.' failed with exit code '.$exit.'.'));

    nxDeploymentStreamEvent([
        'type' => 'step', 'step' => $step, 'label' => $label,
        'status' => $ok ? 'completed' : ($cancelled ? 'cancelled' : 'failed'),
        'progress' => $ok ? $progressEnd : $progressStart,
        'elapsed' => (int) floor(microtime(true) - $started),
        'message' => $message, 'exit_code' => $exit,
    ]);

    return [
        'ok' => $ok,
        'output' => trim($combined),
        'exit_code' => $exit,
        'cancelled' => $cancelled,
        'timed_out' => $timedOut,
    ];
}

/** @return resource|null */
function nxAcquireDeploymentLock(string $root, string $runId)
{
    $path = $root.'/storage/app/nexora/deployment.lock';
    if (! is_dir(dirname($path))) {
        @mkdir(dirname($path), 0775, true);
    }
    $handle = @fopen($path, 'c+');
    if (! is_resource($handle)) {
        return null;
    }
    if (! @flock($handle, LOCK_EX | LOCK_NB)) {
        fclose($handle);
        return null;
    }

    @ftruncate($handle, 0);
    @rewind($handle);
    @fwrite($handle, json_encode([
        'run_id' => $runId,
        'owner_pid' => getmypid(),
        'started_at' => gmdate(DATE_ATOM),
    ], JSON_UNESCAPED_SLASHES));
    @fflush($handle);

    return $handle;
}

/** Stream one deployment plan and exit only after a verifiable result is available. */
function nxStreamDeploymentTask(string $task, string $root): void
{
    nxBeginDeploymentStream();
    ignore_user_abort(true);
    $runId = bin2hex(random_bytes(12));
    $GLOBALS['NEXORA_DEPLOYMENT_RUN_ID'] = $runId;
    $lock = nxAcquireDeploymentLock($root, $runId);
    if (! is_resource($lock)) {
        $activeState = nxReadDeploymentState($root);
        nxDeploymentStreamEvent([
            'type' => 'complete', 'ok' => false, 'progress' => 0,
            'active_run_id' => is_string($activeState['run_id'] ?? null) ? $activeState['run_id'] : null,
            'active_step' => $activeState['step'] ?? null,
            'heartbeat_at' => $activeState['heartbeat_at'] ?? null,
            'message' => 'Another deployment task is still shutting down or running. You can stop the previous worker safely, then retry without refreshing the whole installer.',
        ]);
        return;
    }

    $previousState = nxReadDeploymentState($root);
    if (! empty($previousState['active']) && ($previousState['run_id'] ?? null) !== $runId) {
        nxArchiveInterruptedDeploymentState($root, $previousState);
    }

    $started = microtime(true);
    $taskLabels = [
        'run_all' => 'Prepare everything automatically',
        'composer_install' => 'Install PHP dependencies',
        'npm_install' => 'Install frontend dependencies',
        'npm_build' => 'Build production assets',
        'bootstrap_composer' => 'Install private Composer',
        'bootstrap_node' => 'Install private Node.js + npm',
    ];

    nxWriteDeploymentState($root, $runId, true, ['status' => 'starting', 'task' => $task, 'step' => 'preflight']);

    try {
        nxDeploymentStreamEvent([
            'type' => 'start', 'task' => $task, 'run_id' => $runId,
            'label' => $taskLabels[$task] ?? 'Deployment task',
            'progress' => 0,
            'steps' => [
                ['id' => 'preflight', 'label' => 'Preflight & tool validation'],
                ['id' => 'composer', 'label' => 'PHP dependencies'],
                ['id' => 'npm', 'label' => 'Frontend dependencies'],
                ['id' => 'build', 'label' => 'Production frontend build'],
                ['id' => 'verify', 'label' => 'Artifact verification'],
            ],
        ]);
        // A valid ignored NDJSON padding event helps common FastCGI/proxy buffers
        // flush the first visible progress event immediately.
        nxDeploymentStreamEvent(['type' => 'padding', 'message' => str_repeat(' ', 4096)]);
        nxWriteDeploymentState($root, $runId, true, ['status' => 'running', 'task' => $task, 'step' => 'preflight']);
        nxDeploymentStreamEvent([
            'type' => 'step', 'step' => 'preflight', 'label' => 'Preflight & tool validation',
            'status' => 'running', 'progress' => 2, 'message' => 'Validating runtime paths and deployment tools.',
        ]);

        if (! nxCanRunProcesses()) {
            nxDeploymentStreamEvent([
                'type' => 'step', 'step' => 'preflight', 'label' => 'Preflight & tool validation',
                'status' => 'failed', 'progress' => 2,
                'message' => 'This host disables controlled process execution. Upload a prebuilt Nexora production release instead.',
            ]);
            nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 2, 'message' => 'Deployment preflight failed.']);
            return;
        }

        $tooling = nxResolveTooling($root);
        nxDeploymentStreamEvent([
            'type' => 'step', 'step' => 'preflight', 'label' => 'Preflight & tool validation',
            'status' => 'completed', 'progress' => 8,
            'message' => 'Preflight passed. Resolved available OS/Laragon/private tools.',
        ]);

        $selected = match ($task) {
            'composer_install', 'bootstrap_composer' => ['composer'],
            'npm_install', 'bootstrap_node' => ['npm'],
            'npm_build' => ['build'],
            default => ['composer', 'npm', 'build'],
        };

        if (in_array('composer', $selected, true)) {
            clearstatcache(true);
            if (is_file($root.'/vendor/autoload.php') && $task !== 'bootstrap_composer') {
                nxDeploymentStreamEvent(['type' => 'step', 'step' => 'composer', 'label' => 'PHP dependencies', 'status' => 'skipped', 'progress' => 45, 'message' => 'Composer dependencies already exist; skipped.']);
            } else {
                $tooling = nxResolveTooling($root);
                $phpCli = $tooling['php'];
                $composer = $tooling['composer'];
                if ($composer === null) {
                    nxDeploymentStreamEvent(['type' => 'step', 'step' => 'composer', 'label' => 'PHP dependencies', 'status' => 'running', 'progress' => 10, 'message' => 'Composer is not available. Installing a verified private Composer runtime.']);
                    $installed = nxInstallPrivateComposer($root, $phpCli, static function (array $download): void {
                        $downloaded = (int) ($download['downloaded'] ?? 0);
                        $total = isset($download['total']) && is_int($download['total']) ? $download['total'] : null;
                        $message = 'Downloading Composer bootstrap assets: '.number_format($downloaded / 1048576, 2).' MB';
                        if ($total !== null && $total > 0) {
                            $message .= ' / '.number_format($total / 1048576, 2).' MB';
                        }
                        nxDeploymentStreamEvent(['type' => 'heartbeat', 'step' => 'composer', 'label' => 'PHP dependencies', 'progress' => 10, 'message' => $message]);
                    });
                    nxDeploymentStreamEvent(['type' => 'log', 'step' => 'composer', 'stream' => 'system', 'progress' => 10, 'message' => $installed['message']]);
                    if (! $installed['ok']) {
                        nxDeploymentStreamEvent(['type' => 'step', 'step' => 'composer', 'label' => 'PHP dependencies', 'status' => 'failed', 'progress' => 10, 'message' => $installed['message']]);
                        nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 10, 'message' => 'Composer bootstrap failed.']);
                        return;
                    }
                    $composer = nxResolveComposer($root, $phpCli);
                }
                if ($composer === null) {
                    nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 10, 'message' => 'Composer remains unresolved after bootstrap.']);
                    return;
                }
                if ($task === 'bootstrap_composer') {
                    nxDeploymentStreamEvent(['type' => 'step', 'step' => 'composer', 'label' => 'PHP dependencies', 'status' => 'completed', 'progress' => 100, 'message' => 'Composer is ready.']);
                } else {
                    $composerLocked = is_file($root.'/composer.lock');
                    // Final certified releases still require: composer.lock is required for deterministic PHP dependency installation.
                    if (! $composerLocked) {
                        nxDeploymentStreamEvent(['type' => 'log', 'step' => 'composer', 'stream' => 'system', 'progress' => 10, 'message' => 'Development source mode: composer.lock is missing. Composer will resolve dependencies and create a candidate lock now. Final certification must review and seal this lock before release.']);
                    }
                    $environment = nxProcessEnvironmentSummary($root);
                    nxDeploymentStreamEvent(['type' => 'log', 'step' => 'composer', 'stream' => 'system', 'progress' => 10, 'message' => 'Using '.$composer['source'].' · '.$composer['version']."\nComposer home: ".$environment['composer_home'].' ('.$environment['composer_home_source'].')']);
                    $result = nxStreamFixedCommand('composer', 'PHP dependencies', $composer['command'].' install --no-interaction --prefer-dist --optimize-autoloader --no-progress', $root, 10, 45, 1200);
                    if (! $result['ok']) {
                        if ($result['cancelled']) {
                            nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'cancelled' => true, 'progress' => 10, 'message' => 'Deployment cancelled. The PHP dependency process was stopped safely.']);
                            return;
                        }
                        nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 10, 'message' => 'PHP dependency installation failed. Review the live output above and retry this step.']);
                        return;
                    }
                    clearstatcache(true);
                    if (! is_file($root.'/vendor/autoload.php')) {
                        nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 45, 'message' => 'Composer exited successfully but vendor/autoload.php is still missing.']);
                        return;
                    }
                }
            }
        } else {
            nxDeploymentStreamEvent(['type' => 'step', 'step' => 'composer', 'label' => 'PHP dependencies', 'status' => 'pending', 'progress' => 8, 'message' => 'Not part of this selected task.']);
        }

        if (in_array('npm', $selected, true)) {
            clearstatcache(true);
            if (is_dir($root.'/node_modules') && $task !== 'bootstrap_node') {
                nxDeploymentStreamEvent(['type' => 'step', 'step' => 'npm', 'label' => 'Frontend dependencies', 'status' => 'skipped', 'progress' => 75, 'message' => 'node_modules already exists; skipped.']);
            } else {
                $tooling = nxResolveTooling($root);
                $node = $tooling['node'];
                $npm = $tooling['npm'];
                if ($node === null || $npm === null) {
                    nxDeploymentStreamEvent(['type' => 'step', 'step' => 'npm', 'label' => 'Frontend dependencies', 'status' => 'running', 'progress' => 48, 'message' => 'Node.js/npm is not available. Installing a checksum-verified private Node.js runtime.']);
                    $installed = nxInstallPrivateNode($root, static function (array $download): void {
                        $downloaded = (int) ($download['downloaded'] ?? 0);
                        $total = isset($download['total']) && is_int($download['total']) ? $download['total'] : null;
                        $message = 'Downloading Node.js LTS: '.number_format($downloaded / 1048576, 2).' MB';
                        if ($total !== null && $total > 0) {
                            $message .= ' / '.number_format($total / 1048576, 2).' MB';
                        }
                        nxDeploymentStreamEvent(['type' => 'heartbeat', 'step' => 'npm', 'label' => 'Frontend dependencies', 'progress' => 48, 'message' => $message]);
                    });
                    nxDeploymentStreamEvent(['type' => 'log', 'step' => 'npm', 'stream' => 'system', 'progress' => 48, 'message' => $installed['message']]);
                    if (! $installed['ok']) {
                        nxDeploymentStreamEvent(['type' => 'step', 'step' => 'npm', 'label' => 'Frontend dependencies', 'status' => 'failed', 'progress' => 48, 'message' => $installed['message']]);
                        nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 48, 'message' => 'Node.js/npm bootstrap failed.']);
                        return;
                    }
                    $tooling = nxResolveTooling($root);
                    $node = $tooling['node'];
                    $npm = $tooling['npm'];
                }
                if ($node === null || $npm === null) {
                    nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 48, 'message' => 'Node.js/npm remains unresolved after bootstrap.']);
                    return;
                }
                if ($task === 'bootstrap_node') {
                    nxDeploymentStreamEvent(['type' => 'step', 'step' => 'npm', 'label' => 'Frontend dependencies', 'status' => 'completed', 'progress' => 100, 'message' => 'Node.js and npm are ready.']);
                } else {
                    $npmLocked = is_file($root.'/package-lock.json');
                    // Final certified releases still require: package-lock.json is required for deterministic frontend dependency installation.
                    // $npmArgs = 'ci --no-audit --no-fund' remains the certified lock-present path.
                    $npmArgs = $npmLocked ? 'ci --no-audit --no-fund' : 'install --no-audit --no-fund';
                    if (! $npmLocked) {
                        nxDeploymentStreamEvent(['type' => 'log', 'step' => 'npm', 'stream' => 'system', 'progress' => 48, 'message' => 'Development source mode: package-lock.json is missing. npm install will resolve dependencies and create a candidate lock now. Final certification must review and seal this lock before release.']);
                    }
                    nxDeploymentStreamEvent(['type' => 'log', 'step' => 'npm', 'stream' => 'system', 'progress' => 48, 'message' => 'Using '.$node['source'].' · '.$node['version'].' · npm '.$npm['version']]);
                    $result = nxStreamFixedCommand('npm', 'Frontend dependencies', $npm['command'].' '.$npmArgs, $root, 48, 75, 1200);
                    if (! $result['ok']) {
                        if ($result['cancelled']) {
                            nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'cancelled' => true, 'progress' => 48, 'message' => 'Deployment cancelled. The frontend dependency process was stopped safely.']);
                            return;
                        }
                        nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 48, 'message' => 'Frontend dependency installation failed. Review the live output above and retry this step.']);
                        return;
                    }
                    clearstatcache(true);
                    if (! is_dir($root.'/node_modules')) {
                        nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 75, 'message' => 'npm exited successfully but node_modules is still missing.']);
                        return;
                    }
                }
            }
        } else {
            nxDeploymentStreamEvent(['type' => 'step', 'step' => 'npm', 'label' => 'Frontend dependencies', 'status' => 'pending', 'progress' => 45, 'message' => 'Not part of this selected task.']);
        }

        if (in_array('build', $selected, true)) {
            clearstatcache(true);
            if (is_file($root.'/public/build/manifest.json')) {
                nxDeploymentStreamEvent(['type' => 'step', 'step' => 'build', 'label' => 'Production frontend build', 'status' => 'skipped', 'progress' => 95, 'message' => 'Production build already exists; skipped.']);
            } else {
                $tooling = nxResolveTooling($root);
                $node = $tooling['node'];
                $npm = $tooling['npm'];
                if ($node === null || $npm === null) {
                    nxDeploymentStreamEvent(['type' => 'step', 'step' => 'build', 'label' => 'Production frontend build', 'status' => 'failed', 'progress' => 78, 'message' => 'Node.js/npm is unavailable. Install frontend dependencies first.']);
                    nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 78, 'message' => 'Production build could not start.']);
                    return;
                }
                if (! is_dir($root.'/node_modules')) {
                    nxDeploymentStreamEvent(['type' => 'step', 'step' => 'build', 'label' => 'Production frontend build', 'status' => 'failed', 'progress' => 78, 'message' => 'node_modules is missing. Install frontend dependencies first.']);
                    nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 78, 'message' => 'Production build could not start.']);
                    return;
                }
                $result = nxStreamFixedCommand('build', 'Production frontend build', $npm['command'].' run build', $root, 78, 95, 1200);
                if (! $result['ok']) {
                    if ($result['cancelled']) {
                        nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'cancelled' => true, 'progress' => 78, 'message' => 'Deployment cancelled. The production build process was stopped safely.']);
                        return;
                    }
                    nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 78, 'message' => 'Production frontend build failed. Review the live output above and retry this step.']);
                    return;
                }
            }
        } else {
            nxDeploymentStreamEvent(['type' => 'step', 'step' => 'build', 'label' => 'Production frontend build', 'status' => 'pending', 'progress' => 75, 'message' => 'Not part of this selected task.']);
        }

        nxDeploymentStreamEvent(['type' => 'step', 'step' => 'verify', 'label' => 'Artifact verification', 'status' => 'running', 'progress' => 96, 'message' => 'Verifying deployable artifacts.']);
        clearstatcache(true);
        $vendorReady = is_file($root.'/vendor/autoload.php');
        $buildReady = is_file($root.'/public/build/manifest.json');
        $taskOk = match ($task) {
            'composer_install' => $vendorReady,
            'npm_install' => is_dir($root.'/node_modules'),
            'npm_build' => $buildReady,
            'bootstrap_composer' => nxResolveComposer($root, nxResolvePhpCli($root)) !== null,
            'bootstrap_node' => ($resolved = nxResolveTooling($root)) && $resolved['node'] !== null && $resolved['npm'] !== null,
            default => $vendorReady && $buildReady,
        };
        nxDeploymentStreamEvent([
            'type' => 'step', 'step' => 'verify', 'label' => 'Artifact verification',
            'status' => $taskOk ? 'completed' : 'failed',
            'progress' => $taskOk ? 100 : 96,
            'message' => $taskOk ? 'Selected deployment artifacts verified.' : 'The selected task finished but its expected artifact is still missing.',
        ]);

        $fullyReady = $vendorReady && $buildReady;
        $final = [
            'task' => $task,
            'ok' => $taskOk,
            'fully_ready' => $fullyReady,
            'completed_at' => gmdate(DATE_ATOM),
            'elapsed_seconds' => (int) floor(microtime(true) - $started),
        ];
        if (! nxAtomicWriteFile($root.'/storage/app/nexora/deployment-last-run.json', json_encode($final, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 0775, 0600)) {
            throw new RuntimeException('Unable to persist final deployment run evidence atomically.');
        }

        nxDeploymentStreamEvent([
            'type' => 'complete', 'ok' => $taskOk, 'ready' => $fullyReady,
            'progress' => $taskOk ? 100 : 96,
            'elapsed' => $final['elapsed_seconds'],
            'message' => $taskOk
                ? ($fullyReady ? 'Deployment preparation is complete. Nexora is ready for the installation wizard.' : 'Selected task completed. Remaining deployment steps are still required.')
                : 'Deployment task did not produce the expected artifact.',
        ]);
    } catch (Throwable $exception) {
        nxDeploymentStreamEvent([
            'type' => 'complete', 'ok' => false, 'progress' => 0,
            'message' => 'Deployment failed: '.$exception->getMessage(),
        ]);
    } finally {
        $wasCancelled = nxDeploymentCancellationRequested($root, $runId);
        @unlink(nxDeploymentCancelPath($root, $runId));
        nxWriteDeploymentState($root, $runId, false, [
            'status' => $wasCancelled ? 'cancelled' : 'finished',
            'task' => $task,
            'step' => null,
            'child_pid' => null,
        ]);
        @flock($lock, LOCK_UN);
        fclose($lock);
        unset($GLOBALS['NEXORA_DEPLOYMENT_RUN_ID']);
    }
}

function nxSourcePlatformVersion(string $root): string
{
    $config = @file_get_contents($root.'/config/nexora.php');
    if (! is_string($config)) return 'unknown';
    return preg_match("/'version'\s*=>\s*'([^']+)'/", $config, $match) === 1 ? (string) $match[1] : 'unknown';
}

/** @return array<string,mixed> */
function nxDeploymentDiagnostics(string $root, array $tooling, array $environment, array $runtimePaths, bool $vendorReady, bool $buildReady, bool $releaseIntegrity, string $releaseIntegrityDetail): array
{
    $tool = static function (?array $resolved): ?array {
        if ($resolved === null) {
            return null;
        }
        return [
            'source' => $resolved['source'] ?? null,
            'path' => $resolved['path'] ?? null,
            'version' => $resolved['version'] ?? null,
        ];
    };

    return [
        'generated_at' => gmdate(DATE_ATOM),
        'nexora' => ['version' => nxSourcePlatformVersion($root), 'stage' => 'deployment-bootstrap'],
        'platform' => [
            'os_family' => PHP_OS_FAMILY,
            'os' => PHP_OS,
            'architecture' => php_uname('m'),
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'process_execution' => nxCanRunProcesses(),
        ],
        'tooling' => [
            'php' => $tool($tooling['php'] ?? null),
            'composer' => $tool($tooling['composer'] ?? null),
            'node' => $tool($tooling['node'] ?? null),
            'npm' => $tool($tooling['npm'] ?? null),
        ],
        'process_environment' => [
            'composer_home_source' => $environment['composer_home_source'] ?? null,
            'composer_home' => $environment['composer_home'] ?? null,
            'composer_home_writable' => $environment['composer_home_writable'] ?? false,
            'appdata_available' => ! empty($environment['appdata']),
            'home' => $environment['home'] ?? null,
            'npm_cache' => $environment['npm_cache'] ?? null,
        ],
        'readiness' => [
            'vendor_autoload' => $vendorReady,
            'frontend_manifest' => $buildReady,
            'release_integrity' => $releaseIntegrity,
            'release_integrity_detail' => $releaseIntegrityDetail,
            'runtime_paths' => $runtimePaths,
        ],
    ];
}

$vendorReady = is_file($root.'/vendor/autoload.php');
$buildReady = is_file($root.'/public/build/manifest.json');
$releaseManifestPath = $root.'/nexora-release.json';
$releaseManifestPresent = is_file($releaseManifestPath);
$releaseIntegrity = true;
$releaseIntegrityDetail = $releaseManifestPresent ? 'Release manifest present.' : 'Source/server-build mode.';
if ($releaseManifestPresent) {
    $release = json_decode((string) @file_get_contents($releaseManifestPath), true);
    $artifacts = is_array($release) ? (array) ($release['artifacts'] ?? []) : [];
    $checks = [
        'composer_lock_sha256' => $root.'/composer.lock',
        'package_lock_sha256' => $root.'/package-lock.json',
        'frontend_manifest_sha256' => $root.'/public/build/manifest.json',
    ];
    foreach ($checks as $key => $path) {
        $expected = (string) ($artifacts[$key] ?? '');
        if ($expected === '' || ! is_file($path) || ! hash_equals($expected, (string) hash_file('sha256', $path))) {
            $releaseIntegrity = false;
            $releaseIntegrityDetail = 'Prebuilt release artifact integrity mismatch.';
            break;
        }
    }
    if ($releaseIntegrity) {
        $releaseIntegrityDetail = 'Prebuilt production release artifacts verified.';
    }
}
$tooling = nxResolveTooling($root);
$phpCli = $tooling['php'];
$composerTool = $tooling['composer'];
$nodeTool = $tooling['node'];
$npmTool = $tooling['npm'];
$composerAvailable = $composerTool !== null;
$nodeAvailable = $nodeTool !== null;
$npmAvailable = $npmTool !== null;
$processAvailable = nxCanRunProcesses();
$processEnvironment = nxProcessEnvironmentSummary($root);
$message = null;
$messageType = 'info';
$commandOutput = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['_token'] ?? '');
    if (! hash_equals((string) $_SESSION['csrf'], $csrf)) {
        http_response_code(419);
        $message = 'The deployment session expired. Refresh and retry.';
        $messageType = 'bad';
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'authorize_deployment') {
            $verified = nxAuthorizeDeployment($root, $_POST);
            $_SESSION['bootstrap_authorized'] = $verified['ok'];
            if ($verified['ok']) {
                session_regenerate_id(true);
            }
            $message = $verified['message'];
            $messageType = $verified['ok'] ? 'good' : 'bad';
        } elseif ($action === 'download_diagnostics') {
            if (empty($_SESSION['bootstrap_authorized'])) {
                $message = 'Authorize this browser first to unlock deployment diagnostics.';
                $messageType = 'bad';
            } else {
                $runtimePathsForDiagnostics = [
                    'storage/framework/views' => is_dir($root.'/storage/framework/views') && is_writable($root.'/storage/framework/views'),
                    'storage/framework/sessions' => is_dir($root.'/storage/framework/sessions') && is_writable($root.'/storage/framework/sessions'),
                    'storage/framework/cache/data' => is_dir($root.'/storage/framework/cache/data') && is_writable($root.'/storage/framework/cache/data'),
                    'bootstrap/cache' => is_dir($root.'/bootstrap/cache') && is_writable($root.'/bootstrap/cache'),
                ];
                $diagnostics = nxDeploymentDiagnostics(
                    $root,
                    nxResolveTooling($root),
                    nxProcessEnvironmentSummary($root),
                    $runtimePathsForDiagnostics,
                    is_file($root.'/vendor/autoload.php'),
                    is_file($root.'/public/build/manifest.json'),
                    $releaseIntegrity,
                    $releaseIntegrityDetail,
                );
                header('Content-Type: application/json; charset=UTF-8');
                header('Content-Disposition: attachment; filename="nexora-deployment-diagnostics.json"');
                echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                exit;
            }
        } elseif ($action === 'cancel_stream') {
            if (empty($_SESSION['bootstrap_authorized'])) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(403);
                echo json_encode(['ok' => false, 'message' => 'Authorize this browser before controlling deployment tasks.']);
                exit;
            }

            $runId = (string) ($_POST['run_id'] ?? '');
            session_write_close();
            $cancelResult = nxRequestDeploymentCancellation($root, $runId);
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code($cancelResult['ok'] ? 202 : 422);
            echo json_encode($cancelResult, JSON_UNESCAPED_SLASHES);
            exit;
        } elseif ($action === 'deployment_status') {
            if (empty($_SESSION['bootstrap_authorized'])) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(403);
                echo json_encode(['ok' => false, 'message' => 'Deployment status is locked until this browser is authorized.']);
                exit;
            }
            $state = nxRecoverInterruptedDeploymentState($root);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => true, 'state' => $state], JSON_UNESCAPED_SLASHES);
            exit;
        } elseif ($action === 'stream_task') {
            if (empty($_SESSION['bootstrap_authorized'])) {
                nxBeginDeploymentStream();
                nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 0, 'message' => 'Authorize this browser before starting deployment tasks.']);
                exit;
            }

            $task = (string) ($_POST['task'] ?? '');
            $allowedTasks = ['run_all', 'composer_install', 'npm_install', 'npm_build', 'bootstrap_composer', 'bootstrap_node'];
            if (! in_array($task, $allowedTasks, true)) {
                nxBeginDeploymentStream();
                nxDeploymentStreamEvent(['type' => 'complete', 'ok' => false, 'progress' => 0, 'message' => 'Unknown deployment task.']);
                exit;
            }

            // Release the PHP session file lock before a long-running build so
            // diagnostics/other browser requests are not needlessly blocked.
            session_write_close();
            nxStreamDeploymentTask($task, $root);
            exit;
        } elseif ($action === 'upload_release') {
            if (empty($_SESSION['bootstrap_authorized'])) {
                $message = 'Authorize this browser before deploying a release bundle.';
                $messageType = 'bad';
            } else {
                $deployed = nxInstallUploadedRelease((array) ($_FILES['release_zip'] ?? []), $root);
                $message = $deployed['message'];
                $messageType = $deployed['ok'] ? 'good' : 'bad';
                clearstatcache(true);
                $vendorReady = is_file($root.'/vendor/autoload.php');
                $buildReady = is_file($root.'/public/build/manifest.json');
                if ($deployed['ok']) {
                    $releaseManifestPresent = is_file($root.'/nexora-release.json');
                    $releaseIntegrity = true;
                    $releaseIntegrityDetail = 'Prebuilt production release artifacts verified.';
                }
            }
        } elseif (in_array($action, ['bootstrap_composer', 'bootstrap_node', 'composer_install', 'npm_install', 'npm_build', 'run_all'], true)) {
            if (empty($_SESSION['bootstrap_authorized'])) {
                $message = 'Authorize this browser first. This prevents anonymous visitors from starting deployment bootstrap tasks.';
                $messageType = 'bad';
            } elseif (! $processAvailable) {
                $message = 'This host does not allow controlled process execution. Upload a prebuilt Nexora production release instead; it requires neither Composer nor Node on the customer server.';
                $messageType = 'bad';
            } else {
                $outputs = [];
                $ok = true;

                if ($action === 'bootstrap_composer') {
                    $installed = nxInstallPrivateComposer($root, $phpCli);
                    $outputs[] = '[Composer bootstrap] '.$installed['message'];
                    $ok = $installed['ok'];
                } elseif ($action === 'bootstrap_node') {
                    $installed = nxInstallPrivateNode($root);
                    $outputs[] = '[Node.js bootstrap] '.$installed['message'];
                    $ok = $installed['ok'];
                } else {
                    $tasks = match ($action) {
                        'composer_install' => ['composer'],
                        'npm_install' => ['npm'],
                        'npm_build' => ['build'],
                        default => ['composer', 'npm', 'build'],
                    };

                    foreach ($tasks as $task) {
                        clearstatcache(true);
                        $vendorReady = is_file($root.'/vendor/autoload.php');
                        $buildReady = is_file($root.'/public/build/manifest.json');
                        $tooling = nxResolveTooling($root);
                        $phpCli = $tooling['php'];
                        $composerTool = $tooling['composer'];
                        $nodeTool = $tooling['node'];
                        $npmTool = $tooling['npm'];

                        if ($task === 'composer' && $vendorReady) {
                            $outputs[] = '[Composer] Dependencies already present; skipped.';
                            continue;
                        }
                        if ($task === 'npm' && is_dir($root.'/node_modules')) {
                            $outputs[] = '[NPM] node_modules already present; skipped.';
                            continue;
                        }
                        if ($task === 'build' && $buildReady) {
                            $outputs[] = '[Build] Production assets already present; skipped.';
                            continue;
                        }

                        if ($task === 'composer') {
                            if ($composerTool === null) {
                                $bootstrapped = nxInstallPrivateComposer($root, $phpCli);
                                $outputs[] = '[Composer bootstrap] '.$bootstrapped['message'];
                                if (! $bootstrapped['ok']) {
                                    $ok = false;
                                    break;
                                }
                                $tooling = nxResolveTooling($root);
                                $composerTool = $tooling['composer'];
                            }
                            if ($composerTool === null) {
                                $outputs[] = '[Composer] Composer remains unresolved after bootstrap.';
                                $ok = false;
                                break;
                            }
                            $composerLocked = is_file($root.'/composer.lock');
                            // Final certified releases still require: composer.lock is required for deterministic dependency installation.
                            if (! $composerLocked) {
                                $outputs[] = '[Composer] Development source mode: composer.lock is missing; Composer will resolve dependencies and create a candidate lock. Final certification must review it.';
                            }
                            $result = nxRunFixedCommand($composerTool['command'].' install --no-interaction --prefer-dist --optimize-autoloader --no-progress', $root);
                            $environmentSummary = nxProcessEnvironmentSummary($root);
                            $outputs[] = '[Composer '.($composerTool['source'] ?? 'resolved').']'
                                ."\nExecutable: ".($composerTool['path'] ?? 'resolved command')
                                ."\nComposer home: ".$environmentSummary['composer_home'].' ('.$environmentSummary['composer_home_source'].')'
                                .($environmentSummary['appdata'] ? "\nAPPDATA: ".$environmentSummary['appdata'] : '')
                                ."\n".$result['output'];
                        } elseif ($task === 'npm') {
                            if ($nodeTool === null || $npmTool === null) {
                                $bootstrapped = nxInstallPrivateNode($root);
                                $outputs[] = '[Node.js bootstrap] '.$bootstrapped['message'];
                                if (! $bootstrapped['ok']) {
                                    $ok = false;
                                    break;
                                }
                                $tooling = nxResolveTooling($root);
                                $nodeTool = $tooling['node'];
                                $npmTool = $tooling['npm'];
                            }
                            if ($nodeTool === null || $npmTool === null) {
                                $outputs[] = '[NPM] Node.js/npm remains unresolved after bootstrap.';
                                $ok = false;
                                break;
                            }
                            $npmLocked = is_file($root.'/package-lock.json');
                            // Final certified releases still require: package-lock.json is required for deterministic dependency installation.
                            // $npmArgs = 'ci --no-audit --no-fund' remains the certified lock-present path.
                            $npmArgs = $npmLocked ? 'ci --no-audit --no-fund' : 'install --no-audit --no-fund';
                            if (! $npmLocked) {
                                $outputs[] = '[NPM] Development source mode: package-lock.json is missing; npm install will resolve dependencies and create a candidate lock. Final certification must review it.';
                            }
                            $result = nxRunFixedCommand($npmTool['command'].' '.$npmArgs, $root);
                            $outputs[] = '[NPM '.($npmTool['source'] ?? 'resolved')."]\n".$result['output'];
                        } else {
                            if ($nodeTool === null || $npmTool === null) {
                                $outputs[] = '[Build] Node.js/npm is unavailable even after bootstrap.';
                                $ok = false;
                                break;
                            }
                            $result = nxRunFixedCommand($npmTool['command'].' run build', $root);
                            $outputs[] = '[Build '.($npmTool['source'] ?? 'resolved')."]\n".$result['output'];
                        }

                        if (! $result['ok']) {
                            $ok = false;
                            break;
                        }
                    }
                }

                $commandOutput = implode("\n\n", $outputs);
                clearstatcache(true);
                $vendorReady = is_file($root.'/vendor/autoload.php');
                $buildReady = is_file($root.'/public/build/manifest.json');
                $tooling = nxResolveTooling($root);
                $phpCli = $tooling['php'];
                $composerTool = $tooling['composer'];
                $nodeTool = $tooling['node'];
                $npmTool = $tooling['npm'];
                $composerAvailable = $composerTool !== null;
                $nodeAvailable = $nodeTool !== null;
                $npmAvailable = $npmTool !== null;

                $message = $ok && $vendorReady && $buildReady
                    ? 'Deployment dependencies and production assets are ready. Continue to the Nexora installer.'
                    : ($ok ? 'Selected deployment task completed. Finish the remaining readiness items.' : 'A deployment task failed. Review the output below.');
                $messageType = $ok ? 'good' : 'bad';
            }

        }
    }
}

$ready = $vendorReady && $buildReady && $releaseIntegrity;
if (! nxIsLocalDeploymentRequest() && empty($_SESSION['bootstrap_authorized'])) {
    try { nxEnsureDeploymentAccessKey($root); } catch (Throwable $e) { $message = $e->getMessage(); $messageType = 'bad'; }
}
$authorized = ! empty($_SESSION['bootstrap_authorized']);
$envRootWritable = defined('NEXORA_ENV_ROOT_WRITABLE') ? (bool) NEXORA_ENV_ROOT_WRITABLE : false;
$envFallbackPath = defined('NEXORA_ENV_FALLBACK_PATH') ? (string) NEXORA_ENV_FALLBACK_PATH : $root.'/storage/app/nexora/environment/.env';
$envFallbackDirectory = dirname($envFallbackPath);
$envFallbackWritable = (is_file($envFallbackPath) && is_writable($envFallbackPath)) || ((is_dir($envFallbackDirectory) || @mkdir($envFallbackDirectory, 0775, true)) && is_writable($envFallbackDirectory));
$envPersistenceReady = $envRootWritable || $envFallbackWritable;
$envPersistenceDetail = $envRootWritable ? 'Root .env is writable.' : ($envFallbackWritable ? 'Protected storage fallback will be used; project root write access is not required.' : 'No writable environment persistence path is available.');

$runtimePaths = [
    'storage/framework/views' => is_dir($root.'/storage/framework/views') && is_writable($root.'/storage/framework/views'),
    'storage/framework/sessions' => is_dir($root.'/storage/framework/sessions') && is_writable($root.'/storage/framework/sessions'),
    'storage/framework/cache/data' => is_dir($root.'/storage/framework/cache/data') && is_writable($root.'/storage/framework/cache/data'),
    'bootstrap/cache' => is_dir($root.'/bootstrap/cache') && is_writable($root.'/bootstrap/cache'),
];
?><!doctype html>
<html lang="<?= nxh($nxLocale) ?>" dir="<?= nxh($nxDirection) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= nxh(nxT('deployment.title')) ?></title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml"><link rel="alternate icon" href="/favicon.ico"><link rel="apple-touch-icon" href="/apple-touch-icon.png"><link rel="manifest" href="/site.webmanifest"><meta name="theme-color" content="#111318">
<style>
:root{color-scheme:light;--bg:#f5f6fa;--card:#fff;--text:#18181b;--text2:#41414a;--muted:#73737f;--line:#e7e7ee;--line2:#d9d9e3;--brand:#7f56d9;--brand2:#6941c6;--brand-soft:#f4f0ff;--good:#079455;--good-soft:#ecfdf3;--bad:#d92d20;--bad-soft:#fef3f2;--shadow:0 24px 70px rgba(18,15,35,.08),0 2px 10px rgba(18,15,35,.04)}*{box-sizing:border-box}.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}html{background:var(--bg)}body{margin:0;background:radial-gradient(circle at 80% 0%,rgba(127,86,217,.11),transparent 28%),linear-gradient(180deg,#fbfbfe 0,#f5f6fa 58%,#f3f4f8 100%);color:var(--text);font:14px/1.5 Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.topbar{height:72px;border-bottom:1px solid rgba(231,231,238,.9);background:rgba(255,255,255,.8);backdrop-filter:blur(18px);display:flex;align-items:center;justify-content:space-between;padding:0 max(24px,calc((100vw - 1240px)/2));position:sticky;top:0;z-index:20}.brand{display:flex;align-items:center;gap:11px;color:var(--text);text-decoration:none}.brand img{width:38px;height:38px}.brand-name{font-size:15px;font-weight:780;letter-spacing:-.02em}.brand-sub{display:block;font-size:11px;color:var(--muted);margin-top:1px}.secure-badge{display:inline-flex;align-items:center;gap:7px;border:1px solid #e3dcf8;background:#faf8ff;color:#6941c6;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:720}.shell{max-width:1240px;margin:0 auto;padding:38px 24px 76px}.hero{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:24px;align-items:end;padding:6px 2px 26px}.eyebrow{display:flex;align-items:center;gap:7px;font-weight:800;font-size:11px;letter-spacing:.09em;text-transform:uppercase;color:var(--brand)}.hero h1{font-size:40px;line-height:1.06;margin:9px 0 12px;letter-spacing:-.048em}.hero p{max-width:760px;color:var(--muted);font-size:15px;line-height:1.65;margin:0}.hero-side{display:grid;grid-template-columns:repeat(2,auto);gap:8px}.mini-stat{border:1px solid var(--line);background:rgba(255,255,255,.75);border-radius:14px;padding:10px 12px;min-width:120px}.mini-stat span{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);font-weight:700}.mini-stat strong{display:block;margin-top:3px;font-size:13px}.grid{display:grid;grid-template-columns:1.08fr .92fr;gap:20px}.card{background:var(--card);border:1px solid var(--line);border-radius:22px;box-shadow:var(--shadow);overflow:hidden}.head{padding:21px 22px;border-bottom:1px solid var(--line);display:flex;align-items:flex-start;gap:12px}.head-icon{display:grid;place-items:center;width:36px;height:36px;border-radius:12px;background:var(--brand-soft);color:var(--brand);flex:0 0 auto}.head strong{font-size:15px;letter-spacing:-.012em}.body{padding:20px 22px 22px}.row{display:grid;grid-template-columns:36px minmax(0,1fr) 34px;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #f0f0f4}.row:last-child{border:0}.row-icon{display:grid;place-items:center;width:32px;height:32px;border-radius:10px;background:#f5f5f8;color:#666672}.label{font-weight:680;font-size:13px}.detail{font-size:11.5px;color:var(--muted);margin-top:2px;line-height:1.5;overflow-wrap:anywhere}.status-badge{width:30px;height:30px;border-radius:10px;display:grid;place-items:center}.status-badge.good{background:var(--good-soft);color:var(--good)}.status-badge.bad{background:var(--bad-soft);color:var(--bad)}.notice{display:flex;align-items:flex-start;gap:9px;padding:13px 14px;border-radius:13px;margin:0 0 16px;background:#f5f5f7;color:var(--text2);font-size:12.5px;line-height:1.55}.notice.good{background:var(--good-soft);color:#05603a}.notice.bad{background:var(--bad-soft);color:#912018}.fields{display:grid;grid-template-columns:1fr 112px;gap:12px}.field{margin-bottom:13px}.field.full{grid-column:1/-1}.field label{display:block;font-size:12px;font-weight:720;color:var(--text2);margin-bottom:6px}.field input{width:100%;height:44px;border:1px solid var(--line2);border-radius:12px;padding:0 12px;background:white;outline:none;transition:.16s}.field input:hover{border-color:#c9c9d4}.field input:focus{border-color:#9e77ed;box-shadow:0 0 0 4px rgba(127,86,217,.1)}.btns{display:flex;flex-wrap:wrap;gap:9px;margin-top:10px}.btn{appearance:none;border:1px solid var(--line2);background:#fff;color:var(--text2);border-radius:12px;min-height:43px;padding:0 14px;font-weight:720;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 1px 2px rgba(16,24,40,.03);transition:.16s;text-decoration:none}.btn:hover:not(:disabled){background:#fafafa;border-color:#bebec8;transform:translateY(-1px)}.btn.primary{background:linear-gradient(180deg,#875bf7,#7f56d9);color:#fff;border-color:#7f56d9;box-shadow:0 7px 16px rgba(127,86,217,.2),inset 0 1px rgba(255,255,255,.18)}.btn.primary:hover:not(:disabled){background:linear-gradient(180deg,#7f56d9,#6941c6);border-color:#6941c6}.btn:disabled{opacity:.42;cursor:not-allowed;transform:none}.btn.danger{color:#b42318;border-color:#fecdca;background:#fff}.security{display:flex;align-items:flex-start;gap:9px;margin-top:18px;padding:14px;border:1px solid #e9d7fe;background:#f9f5ff;border-radius:13px;color:#53389e;font-size:12.5px;line-height:1.55}.upload-picker{position:relative}.upload-input{position:absolute!important;width:1px!important;height:1px!important;opacity:0!important;pointer-events:none!important}.upload-surface{display:flex;align-items:center;gap:13px;min-height:86px;padding:14px;border:1px dashed #cfcfe0;border-radius:16px;background:linear-gradient(180deg,#fff,#fbfbfe);cursor:pointer;transition:.18s}.upload-surface:hover,.upload-surface.drag{border-color:#9e77ed;background:#faf8ff;box-shadow:0 0 0 4px rgba(127,86,217,.08)}.upload-icon{width:42px;height:42px;border-radius:13px;background:var(--brand-soft);color:var(--brand);display:grid;place-items:center;flex:0 0 auto}.upload-copy{min-width:0;flex:1}.upload-title{display:block;font-size:12.5px;font-weight:760;color:var(--text2)}.upload-file{display:block;margin-top:3px;font-size:11.5px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.upload-action{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line2);background:#fff;border-radius:10px;padding:8px 10px;font-size:11.5px;font-weight:720;color:var(--text2);box-shadow:0 1px 2px rgba(16,24,40,.03)}.language-switcher{display:flex;align-items:center;gap:8px}.bootstrap-language-picker{position:relative}.bootstrap-language-trigger{min-width:180px;height:42px;border:1px solid var(--line2);border-radius:12px;background:#fff;color:var(--text2);padding:5px 9px;display:grid;grid-template-columns:26px minmax(0,1fr) 18px;align-items:center;gap:8px;text-align:left;cursor:pointer;box-shadow:0 1px 2px rgba(16,24,40,.03)}.bootstrap-language-trigger:hover{border-color:#c3c3cf}.bootstrap-language-trigger:focus-visible{outline:none;border-color:#9e77ed;box-shadow:0 0 0 4px rgba(127,86,217,.09)}.language-flag{width:24px;height:16px;border-radius:3px;object-fit:cover;box-shadow:0 0 0 1px rgba(16,24,40,.09)}.bootstrap-language-copy{min-width:0}.bootstrap-language-copy strong,.bootstrap-language-option strong{display:block;font-size:11.5px;line-height:1.15;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.bootstrap-language-copy small,.bootstrap-language-option small{display:block;color:var(--muted);font-size:9.5px;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.bootstrap-language-menu{display:none;position:absolute;right:0;top:calc(100% + 7px);z-index:60;width:235px;max-height:300px;overflow:auto;border:1px solid var(--line);border-radius:14px;background:#fff;padding:6px;box-shadow:0 20px 48px rgba(17,19,24,.16)}.bootstrap-language-picker.open .bootstrap-language-menu{display:block}.bootstrap-language-option{display:grid;grid-template-columns:26px minmax(0,1fr) 20px;align-items:center;gap:9px;padding:9px 10px;border-radius:10px;color:var(--text2);text-decoration:none}.bootstrap-language-option:hover{background:#f7f5fc}.bootstrap-language-option.selected{background:var(--brand-soft);color:#53389e}.bootstrap-language-check{display:grid;place-items:center;color:var(--brand)}html[dir=rtl] .bootstrap-language-menu{right:auto;left:0}html[dir=rtl] body{direction:rtl}.output,.live-output{direction:ltr;text-align:left}html[dir=rtl] .topbar,html[dir=rtl] .head,html[dir=rtl] .row,html[dir=rtl] .progress-head{direction:rtl}html[dir=rtl] .field input{text-align:right}.output{margin-top:16px;background:#111318;color:#e7e7e9;border:1px solid #24262c;border-radius:13px;padding:14px;max-height:360px;overflow:auto;white-space:pre-wrap;font:11.5px/1.58 ui-monospace,SFMono-Regular,Menlo,monospace}.deployment-progress{margin-top:18px;border:1px solid var(--line);border-radius:16px;background:#fcfcfe;overflow:hidden}.progress-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:16px 17px;border-bottom:1px solid var(--line)}.progress-title{font-weight:760}.progress-meta{font-size:11px;color:var(--muted);margin-top:3px}.progress-percent{font-size:21px;font-weight:820;letter-spacing:-.04em}.progress-track{height:7px;background:#ebe9f0;overflow:hidden}.progress-bar{height:100%;width:0;background:linear-gradient(90deg,#875bf7,#7f56d9);transition:width .28s ease}.progress-body{padding:13px 17px 16px}.progress-steps{display:grid;gap:3px}.progress-step{display:grid;grid-template-columns:32px 1fr 28px;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #efeff3}.progress-step:last-child{border-bottom:0}.step-dot{width:28px;height:28px;border-radius:9px;background:#f2f2f6;color:#a3a3ad;display:grid;place-items:center}.step-dot svg,.step-state svg{width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}.progress-step[data-status=running] .step-dot{background:var(--brand-soft);color:var(--brand)}.progress-step[data-status=completed] .step-dot,.progress-step[data-status=skipped] .step-dot{background:var(--good-soft);color:var(--good)}.progress-step[data-status=failed] .step-dot,.progress-step[data-status=cancelled] .step-dot{background:var(--bad-soft);color:var(--bad)}.step-copy strong{display:block;font-size:12px}.step-copy small{display:block;color:var(--muted);font-size:11px;margin-top:1px}.step-state{display:grid;place-items:center;color:#a3a3ad}.progress-step[data-status=running] .step-state{color:var(--brand)}.progress-step[data-status=completed] .step-state{color:var(--good)}.progress-step[data-status=failed] .step-state,.progress-step[data-status=cancelled] .step-state{color:var(--bad)}.spin-icon{animation:spin .8s linear infinite}.live-output{margin-top:12px;background:#101216;color:#e5e7eb;border:1px solid #24262c;border-radius:12px;padding:13px;min-height:125px;max-height:300px;overflow:auto;white-space:pre-wrap;word-break:break-word;font:11.5px/1.58 ui-monospace,SFMono-Regular,Menlo,monospace}.progress-actions{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:12px}.stream-status{font-size:11.5px;color:var(--muted)}[hidden]{display:none!important}@keyframes spin{to{transform:rotate(360deg)}}@media(max-width:900px){.grid{grid-template-columns:1fr}.hero{grid-template-columns:1fr}.hero-side{display:none}.shell{padding:28px 16px 56px}.hero h1{font-size:34px}}@media(max-width:560px){.topbar{padding:0 14px}.brand-sub,.secure-badge span{display:none}.hero h1{font-size:30px}.fields{grid-template-columns:1fr}.head,.body{padding-left:18px;padding-right:18px}.progress-actions{align-items:stretch;flex-direction:column}.btn{width:100%}}
</style></head>
<body><header class="topbar"><a class="brand" href="/" aria-label="Nexora home"><img src="/brand/nexora-mark.svg" alt=""><span><span class="brand-name">Nexora</span><span class="brand-sub"><?= nxh(nxT('deployment.brand_sub')) ?></span></span></a><div class="language-switcher"><div class="bootstrap-language-picker" id="bootstrap-language-picker"><button class="bootstrap-language-trigger" id="bootstrap-language-trigger" type="button" aria-haspopup="listbox" aria-expanded="false"><img src="<?= nxh((string)($nxSupportedLocales[$nxLocale]['flag_asset']??'')) ?>" alt="" class="language-flag"><span class="bootstrap-language-copy"><strong><?= nxh((string)($nxSupportedLocales[$nxLocale]['native']??$nxSupportedLocales[$nxLocale]['label']??$nxLocale)) ?></strong><small><?= nxh((string)($nxSupportedLocales[$nxLocale]['country']??'')) ?></small></span><?= nxIcon('chevron-down',15) ?></button><div class="bootstrap-language-menu" id="bootstrap-language-menu" role="listbox" aria-label="<?= nxh(nxT('deployment.language')) ?>"><?php foreach($nxSupportedLocales as $localeCode=>$localeMeta): ?><a class="bootstrap-language-option <?= $localeCode===$nxLocale?'selected':'' ?>" href="/?lang=<?= rawurlencode((string)$localeCode) ?>" role="option" aria-selected="<?= $localeCode===$nxLocale?'true':'false' ?>"><img src="<?= nxh((string)($localeMeta['flag_asset']??'')) ?>" alt="" class="language-flag"><span><strong><?= nxh((string)($localeMeta['native']??$localeMeta['label']??$localeCode)) ?></strong><small><?= nxh((string)($localeMeta['country']??'')) ?></small></span><span class="bootstrap-language-check"><?= $localeCode===$nxLocale?nxIcon('check-circle',15):'' ?></span></a><?php endforeach; ?></div></div><span class="secure-badge"><?= nxIcon('shield',15) ?><span><?= nxh(nxT('deployment.secure')) ?></span></span></div></header><main class="shell">
<div class="hero"><div><div class="eyebrow"><?= nxIcon('terminal',14) ?><?= nxh(nxT('deployment.eyebrow')) ?></div><h1><?= nxh(nxT('deployment.heading')) ?></h1><p><?= nxh(nxT('deployment.lead')) ?></p></div><div class="hero-side"><div class="mini-stat"><span>Mode</span><strong><?= $releaseManifestPresent ? 'Prebuilt release' : 'Source build' ?></strong></div><div class="mini-stat"><span>Runtime</span><strong><?= PHP_VERSION ?></strong></div></div></div>
<?php if ($message): ?><div class="notice <?= nxh($messageType) ?>" style="margin-top:22px"><?= nxh($message) ?></div><?php endif; ?>
<div class="grid">
<section class="card"><div class="head"><span class="head-icon"><?= nxIcon('server',18) ?></span><div><strong>Deployment readiness</strong><div class="detail">No Laravel schema or application services are booted in this stage.</div></div></div><div class="body">
<div class="row"><span class="row-icon"><?= nxIcon('package') ?></span><div><div class="label">Composer dependencies</div><div class="detail">vendor/autoload.php</div></div><?= nxStatusIcon($vendorReady, $vendorReady ? 'Ready' : 'Missing') ?></div>
<div class="row"><span class="row-icon"><?= nxIcon('cpu') ?></span><div><div class="label">Production frontend</div><div class="detail">public/build/manifest.json</div></div><?= nxStatusIcon($buildReady, $buildReady ? 'Ready' : 'Missing') ?></div>
<div class="row"><span class="row-icon"><?= nxIcon('shield') ?></span><div><div class="label">Release integrity</div><div class="detail"><?= nxh($releaseIntegrityDetail) ?></div></div><?= nxStatusIcon($releaseIntegrity, $releaseManifestPresent ? ($releaseIntegrity ? 'Verified' : 'Failed') : 'Source package') ?></div>
<div class="row"><span class="row-icon"><?= nxIcon('terminal') ?></span><div><div class="label">Controlled process execution</div><div class="detail">Required only for source-package server build mode.</div></div><?= nxStatusIcon($processAvailable, $processAvailable ? 'Available' : 'Unavailable') ?></div>
<div class="row"><span class="row-icon"><?= nxIcon('terminal') ?></span><div><div class="label">PHP CLI</div><div class="detail"><?= $phpCli ? nxh($phpCli['source']) : 'Required for private Composer bootstrap' ?></div></div><?= nxStatusIcon($phpCli !== null, $phpCli ? 'Detected' : 'Not detected') ?></div>
<div class="row"><span class="row-icon"><?= nxIcon('package') ?></span><div><div class="label">Composer</div><div class="detail"><?= $composerTool ? nxh(($composerTool['source'] ?? 'resolved').(($composerTool['version'] ?? null) ? ' · '.$composerTool['version'] : '')) : 'No healthy Composer command was found. Nexora can install a verified private copy when PHP CLI + process execution are available.' ?></div></div><?= nxStatusIcon($composerAvailable, $composerAvailable ? 'Ready' : 'Not ready') ?></div>
<div class="row"><span class="row-icon"><?= nxIcon('folder') ?></span><div><div class="label">Composer process home</div><div class="detail"><?= nxh($processEnvironment['composer_home_source'].' · '.$processEnvironment['composer_home']) ?><?= $processEnvironment['appdata'] ? ' · APPDATA: '.nxh($processEnvironment['appdata']) : '' ?></div></div><?= nxStatusIcon($processEnvironment['composer_home_writable'], $processEnvironment['composer_home_writable'] ? 'Writable' : 'Blocked') ?></div>
<div class="row"><span class="row-icon"><?= nxIcon('cpu') ?></span><div><div class="label">Node.js + npm</div><div class="detail"><?= ($nodeTool&&$npmTool) ? nxh(($nodeTool['source'] ?? 'resolved').(($nodeTool['version'] ?? null) ? ' · '.$nodeTool['version'] : '').(($npmTool['version'] ?? null) ? ' · npm '.$npmTool['version'] : '')) : 'No healthy Node.js/npm pair was found. Nexora can install a checksum-verified private Node.js LTS runtime on supported hosts.' ?></div></div><?= nxStatusIcon($nodeAvailable && $npmAvailable, ($nodeAvailable && $npmAvailable) ? 'Ready' : 'Not ready') ?></div>
<div class="row"><span class="row-icon"><?= nxIcon('file') ?></span><div><div class="label">Environment persistence</div><div class="detail"><?= nxh($envPersistenceDetail) ?></div></div><?= nxStatusIcon($envPersistenceReady, $envPersistenceReady ? 'Ready' : 'Blocked') ?></div>
<?php foreach($runtimePaths as $path=>$ok): ?><div class="row"><span class="row-icon"><?= nxIcon('folder') ?></span><div><div class="label"><?= nxh($path) ?></div><div class="detail">Laravel runtime path</div></div><?= nxStatusIcon($ok, $ok ? 'Writable' : 'Blocked') ?></div><?php endforeach; ?>
<?php if ($ready): ?><div class="notice good" style="margin-top:18px"><?= nxIcon('check-circle') ?><span>Deployment layer is ready. Composer and NPM do not need to run again.</span></div><a class="btn primary continue" href="/install"><span><?= nxh(nxT('deployment.continue_installer')) ?></span><?= nxIcon('arrow-right') ?></a><?php else: ?><div class="security"><?= nxIcon('shield') ?><span><strong>Best production mode:</strong> use a Nexora prebuilt release containing <code>vendor/</code> and <code>public/build/</code>. That mode requires no Composer, Node.js or shell access on the customer server. Server-build mode below is an assisted fallback for source distributions.</span></div><?php endif; ?>
</div></section>
<section class="card"><div class="head"><span class="head-icon"><?= nxIcon('terminal',18) ?></span><div><strong><?= nxh(nxT('deployment.assisted')) ?></strong><div class="detail"><?= nxh(nxT('deployment.assisted_help')) ?></div></div></div><div class="body">
<?php if (!$ready): ?>
<?php if (!$authorized): ?>
<div class="security" style="margin-bottom:16px"><?= nxIcon('shield') ?><span><strong><?= nxh(nxT('deployment.access')) ?>.</strong> <?= nxh(nxT('deployment.access_help')) ?></span></div>
<form method="post"><input type="hidden" name="_token" value="<?= nxh((string)$_SESSION['csrf']) ?>"><input type="hidden" name="action" value="authorize_deployment"><div class="field full"><label><?= nxh(nxT('deployment.access_key')) ?></label><input name="deployment_key" autocomplete="off" spellcheck="false" required placeholder="XXXXXX-XXXXXX-XXXXXX-XXXXXX"><div class="detail">Remote source builds: open <code>storage/app/nexora/deployment-access.key</code> using your hosting file manager. Local Laragon/localhost requests are authorized automatically.</div></div><button class="btn" type="submit"><?= nxIcon('shield') ?><?= nxh(nxT('deployment.authorize')) ?></button></form>
<?php endif; ?>
<?php if ($authorized): ?><div class="notice good" style="margin-top:16px"><?= nxIcon('check-circle') ?><span><?= nxh(nxT('deployment.authorized')) ?></span></div>
<form method="post" style="margin-bottom:14px"><input type="hidden" name="_token" value="<?= nxh((string)$_SESSION['csrf']) ?>"><button class="btn" name="action" value="download_diagnostics"><?= nxIcon('download') ?><?= nxh(nxT('deployment.diagnostics')) ?></button><div class="detail" style="margin-top:6px"><?= nxh(nxT('deployment.diagnostics_help')) ?></div></form>
<form method="post" enctype="multipart/form-data" style="margin-bottom:18px" data-release-upload><input type="hidden" name="_token" value="<?= nxh((string)$_SESSION['csrf']) ?>"><input type="hidden" name="action" value="upload_release"><div class="field full"><label for="release-zip-input"><?= nxh(nxT('deployment.release_zip')) ?></label><div class="upload-picker"><input class="upload-input" id="release-zip-input" name="release_zip" type="file" accept=".zip,application/zip" required><label class="upload-surface" for="release-zip-input" id="release-dropzone"><span class="upload-icon"><?= nxIcon('upload-cloud',20) ?></span><span class="upload-copy"><span class="upload-title"><?= nxh(nxT('deployment.drop_release')) ?></span><span class="upload-file" id="release-file-name"><?= nxh(nxT('deployment.no_release')) ?></span></span><span class="upload-action"><?= nxIcon('file',15) ?><?= nxh(nxT('deployment.browse')) ?></span></label></div><div class="detail"><?= nxh(nxT('deployment.release_help')) ?></div></div><button class="btn" type="submit"><?= nxIcon('shield') ?><?= nxh(nxT('deployment.verify_release')) ?></button></form>
<div class="detail" style="margin:8px 0"><?= nxh(nxT('deployment.or_prepare')) ?></div>
<form method="post" data-deployment-form>
    <input type="hidden" name="_token" value="<?= nxh((string)$_SESSION['csrf']) ?>">
    <div class="btns">
        <?php if(!$composerAvailable): ?><button class="btn" name="action" value="bootstrap_composer" data-task="bootstrap_composer" <?= (!$processAvailable||!$phpCli)?'disabled':'' ?>><?= nxIcon('package') ?><?= nxh(nxT('deployment.install_composer')) ?></button><?php endif; ?>
        <?php if(!$nodeAvailable||!$npmAvailable): ?><button class="btn" name="action" value="bootstrap_node" data-task="bootstrap_node" <?= !$processAvailable?'disabled':'' ?>><?= nxIcon('cpu') ?><?= nxh(nxT('deployment.install_node')) ?></button><?php endif; ?>
        <button class="btn" name="action" value="composer_install" data-task="composer_install" <?= ($vendorReady||!$processAvailable)?'disabled':'' ?>><?= nxIcon('package') ?><?= nxh(nxT('deployment.install_php')) ?></button>
        <button class="btn" name="action" value="npm_install" data-task="npm_install" <?= (is_dir($root.'/node_modules')||!$processAvailable)?'disabled':'' ?>><?= nxIcon('download') ?><?= nxh(nxT('deployment.install_frontend')) ?></button>
        <button class="btn" name="action" value="npm_build" data-task="npm_build" <?= ($buildReady||!$processAvailable)?'disabled':'' ?>><?= nxIcon('cpu') ?><?= nxh(nxT('deployment.build_assets')) ?></button>
        <button class="btn primary" name="action" value="run_all" data-task="run_all" <?= !$processAvailable?'disabled':'' ?>><?= nxIcon('play') ?><?= nxh(nxT('deployment.prepare_all')) ?></button>
    </div>
    <div class="detail" style="margin-top:10px"><?= nxh(nxT('deployment.live_help')) ?></div>
</form>
<div class="deployment-progress" id="deployment-progress" hidden aria-live="polite">
    <div class="progress-head">
        <div><div class="progress-title" id="deployment-current"><?= nxh(nxT('deployment.preparing')) ?></div><div class="progress-meta"><span id="deployment-step-count"><?= nxh(nxT('deployment.starting')) ?></span> · <span id="deployment-elapsed">00:00</span></div></div>
        <div class="progress-percent" id="deployment-percent">0%</div>
    </div>
    <div class="progress-track" aria-hidden="true"><div class="progress-bar" id="deployment-bar"></div></div>
    <div class="progress-body">
        <div class="progress-steps" id="deployment-steps"></div>
        <div class="live-output" id="deployment-log"><?= nxh(nxT('deployment.waiting_stream')) ?></div>
        <div class="progress-actions"><span class="stream-status" id="deployment-status"><?= nxh(nxT('deployment.page_open')) ?></span><button class="btn danger" type="button" id="deployment-cancel"><?= nxIcon('cancel') ?><span id="deployment-cancel-label"><?= nxh(nxT('deployment.cancel')) ?></span></button></div>
    </div>
</div><?php endif; ?>
<?php if (!$processAvailable): ?><div class="notice bad" style="margin-top:16px"><?= nxIcon('alert') ?><span>This host disables process execution. A browser cannot create Node.js or Composer capabilities that the server does not provide. Use the prebuilt Nexora release; the main installer will still handle DB, environment, migrations, admin and runtime entirely through UI.</span></div><?php endif; ?>
<?php else: ?><div class="notice good"><?= nxIcon('check-circle') ?><span>No dependency installation is required on this server.</span></div><a class="btn primary continue" href="/install"><span><?= nxh(nxT('deployment.continue')) ?></span><?= nxIcon('arrow-right') ?></a><?php endif; ?>
<?php if ($commandOutput): ?><div class="output"><?= nxh($commandOutput) ?></div><?php endif; ?>
</div></section>
</div></main>
<script>
(() => {
    const languagePicker = document.getElementById('bootstrap-language-picker');
    const languageTrigger = document.getElementById('bootstrap-language-trigger');
    if (languagePicker && languageTrigger) {
        languageTrigger.addEventListener('click', () => { const open = languagePicker.classList.toggle('open'); languageTrigger.setAttribute('aria-expanded', open ? 'true' : 'false'); });
        document.addEventListener('click', (event) => { if (!languagePicker.contains(event.target)) { languagePicker.classList.remove('open'); languageTrigger.setAttribute('aria-expanded','false'); } });
        languageTrigger.addEventListener('keydown', (event) => { if (event.key === 'Escape') { languagePicker.classList.remove('open'); languageTrigger.setAttribute('aria-expanded','false'); } });
    }
    const i18n = {
        noRelease: <?= json_encode(nxT('deployment.no_release'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        cancel: <?= json_encode(nxT('deployment.cancel'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
        stopPrevious: <?= json_encode(nxT('deployment.stop_previous'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    };
    const deploymentForm = document.querySelector('[data-deployment-form]');
    const panel = document.getElementById('deployment-progress');
    const percent = document.getElementById('deployment-percent');
    const bar = document.getElementById('deployment-bar');
    const current = document.getElementById('deployment-current');
    const stepCount = document.getElementById('deployment-step-count');
    const elapsed = document.getElementById('deployment-elapsed');
    const stepsBox = document.getElementById('deployment-steps');
    const log = document.getElementById('deployment-log');
    const status = document.getElementById('deployment-status');
    const cancel = document.getElementById('deployment-cancel');
    const cancelLabel = document.getElementById('deployment-cancel-label');
    let controller = null;
    let timer = null;
    let startedAt = 0;
    let lastTask = null;
    let currentRunId = null;
    let blockedRunId = null;
    let currentSubmitter = null;
    const knownSteps = new Map();

    const statusIcon = (state) => {
        const icons = {
            pending: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/></svg>',
            running: '<svg class="spin-icon" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-6.22-8.56"/></svg>',
            completed: '<svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
            skipped: '<svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
            failed: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>',
            cancelled: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>',
        };
        return icons[state] || icons.pending;
    };

    const formatElapsed = (seconds) => {
        const value = Math.max(0, Number(seconds) || 0);
        const minutes = Math.floor(value / 60).toString().padStart(2, '0');
        const secs = Math.floor(value % 60).toString().padStart(2, '0');
        return `${minutes}:${secs}`;
    };

    const setProgress = (value) => {
        const safe = Math.max(0, Math.min(100, Number(value) || 0));
        if (percent) percent.textContent = `${safe}%`;
        if (bar) bar.style.width = `${safe}%`;
    };

    const appendLog = (message) => {
        if (!log || !message) return;
        if (log.textContent === 'Waiting for the deployment stream…') log.textContent = '';
        log.textContent += String(message);
        if (!String(message).endsWith('\n')) log.textContent += '\n';
        if (log.textContent.length > 90000) log.textContent = '[Earlier live output truncated]\n' + log.textContent.slice(-80000);
        log.scrollTop = log.scrollHeight;
    };

    const renderSteps = (steps) => {
        if (!stepsBox) return;
        stepsBox.textContent = '';
        knownSteps.clear();
        (steps || []).forEach((step, index) => {
            const row = document.createElement('div');
            row.className = 'progress-step';
            row.dataset.step = step.id;
            row.dataset.status = 'pending';
            row.innerHTML = `<span class="step-dot">${statusIcon('pending')}</span><span class="step-copy"><strong>${step.label}</strong><small>Waiting to start</small></span><span class="step-state" aria-label="Pending">${statusIcon('pending')}</span>`;
            stepsBox.appendChild(row);
            knownSteps.set(step.id, { row, index, label: step.label });
        });
    };

    const updateStep = (event) => {
        const item = knownSteps.get(event.step);
        if (!item) return;
        item.row.dataset.status = event.status || 'pending';
        const state = item.row.querySelector('.step-state');
        if (state) { state.innerHTML = statusIcon(event.status || 'pending'); state.setAttribute('aria-label', event.status || 'pending'); } const dot = item.row.querySelector('.step-dot'); if (dot) dot.innerHTML = statusIcon(event.status || 'pending'); const copy = item.row.querySelector('.step-copy small'); if (copy) copy.textContent = event.message || ((event.status || 'pending') === 'running' ? 'In progress' : (event.status || 'pending') === 'completed' ? 'Completed' : 'Waiting');
        if (current && event.label) current.textContent = event.label;
        const finished = [...knownSteps.values()].filter(({ row }) => ['completed', 'skipped'].includes(row.dataset.status || '')).length;
        if (stepCount) stepCount.textContent = `Step ${Math.min(item.index + 1, knownSteps.size)} of ${knownSteps.size} · ${finished} completed`;
        if (event.message) appendLog(`[${event.label || event.step}] ${event.message}`);
    };

    const finishTimer = () => {
        if (timer) window.clearInterval(timer);
        timer = null;
    };

    const setBusy = (busy, submitter = null) => {
        if (!deploymentForm) return;
        deploymentForm.querySelectorAll('button').forEach((button) => {
            if (!busy && button.dataset.wasDisabled !== '1') button.disabled = false;
            if (busy) {
                button.dataset.wasDisabled = button.disabled ? '1' : '0';
                button.disabled = true;
            } else {
                delete button.dataset.wasDisabled;
            }
        });
        if (submitter instanceof HTMLButtonElement) {
            if (busy) {
                submitter.dataset.originalText = submitter.textContent || '';
                submitter.textContent = 'Running…';
            } else if (submitter.dataset.originalText) {
                submitter.textContent = submitter.dataset.originalText;
                delete submitter.dataset.originalText;
            }
        }
        if (cancel) cancel.disabled = !(busy || blockedRunId);
    };

    const handleEvent = (event) => {
        if (!event || typeof event !== 'object') return;
        if (typeof event.progress !== 'undefined') setProgress(event.progress);
        if (typeof event.elapsed !== 'undefined' && elapsed) elapsed.textContent = formatElapsed(event.elapsed);

        if (event.type === 'start') {
            currentRunId = event.run_id || currentRunId;
            if (panel) panel.hidden = false;
            if (log) log.textContent = '';
            if (current) current.textContent = event.label || 'Deployment started';
            if (status) status.textContent = 'Live server output is connected.';
            renderSteps(event.steps || []);
            appendLog(`Nexora started: ${event.label || event.task}`);
        } else if (event.type === 'step') {
            updateStep(event);
        } else if (event.type === 'log') {
            appendLog(event.message || '');
        } else if (event.type === 'heartbeat') {
            if (current && event.label) current.textContent = event.label;
            if (status) status.textContent = `${event.message || 'Task is still running.'} Server heartbeat received.`;
        } else if (event.type === 'complete') {
            finishTimer();
            if (event.active_run_id) {
                blockedRunId = event.active_run_id;
                currentRunId = event.active_run_id;
                if (cancel) cancel.disabled = false;
                if (cancelLabel) cancelLabel.textContent = i18n.stopPrevious;
                if (current) current.textContent = 'Previous deployment worker is still active';
                if (status) status.textContent = event.message || 'A previous worker is still releasing its process lock.';
                appendLog(event.message || 'A previous deployment worker is still active.');
                return;
            }
            blockedRunId = null;
            if (cancelLabel) cancelLabel.textContent = i18n.cancel;
            if (current) current.textContent = event.cancelled ? 'Deployment cancelled' : (event.ok ? 'Deployment task completed' : 'Deployment task stopped');
            if (status) status.textContent = event.message || (event.ok ? 'Completed.' : 'Failed.');
            appendLog(event.message || 'Deployment task finished.');
            if (event.ok && event.ready) {
                setProgress(100);
                if (status) status.textContent = 'Dependencies and production assets are ready. Opening the installation wizard…';
                window.setTimeout(() => { window.location.href = '/install'; }, 900);
            } else if (event.ok) {
                window.setTimeout(() => window.location.reload(), 900);
            }
        }
    };

    const runTask = async (task, submitter) => {
        if (!deploymentForm || controller) return;
        lastTask = task;
        blockedRunId = null;
        currentRunId = null;
        if (cancelLabel) cancelLabel.textContent = i18n.cancel;
        currentSubmitter = submitter;
        controller = new AbortController();
        startedAt = Date.now();
        if (panel) panel.hidden = false;
        if (current) current.textContent = 'Connecting to Nexora deployment stream…';
        if (status) status.textContent = 'Waiting for the first server heartbeat.';
        if (log) log.textContent = 'Opening secure deployment stream…\n';
        setProgress(0);
        setBusy(true, submitter);
        timer = window.setInterval(() => {
            if (elapsed) elapsed.textContent = formatElapsed((Date.now() - startedAt) / 1000);
        }, 500);

        const body = new FormData();
        body.append('_token', deploymentForm.querySelector('input[name="_token"]').value);
        body.append('action', 'stream_task');
        body.append('task', task);

        try {
            const response = await fetch(window.location.pathname || '/', {
                method: 'POST',
                body,
                headers: { 'Accept': 'application/x-ndjson' },
                signal: controller.signal,
                cache: 'no-store',
            });
            if (!response.ok || !response.body) {
                throw new Error(`Deployment stream could not start (HTTP ${response.status}).`);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let completedEventSeen = false;
            while (true) {
                const { value, done } = await reader.read();
                buffer += decoder.decode(value || new Uint8Array(), { stream: !done });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';
                for (const line of lines) {
                    const trimmed = line.trim();
                    if (!trimmed) continue;
                    try {
                        const event = JSON.parse(trimmed);
                        if (event.type === 'complete') completedEventSeen = true;
                        handleEvent(event);
                    } catch (_) {
                        appendLog(trimmed);
                    }
                }
                if (done) break;
            }
            if (buffer.trim()) {
                try {
                    const event = JSON.parse(buffer.trim());
                    if (event.type === 'complete') completedEventSeen = true;
                    handleEvent(event);
                } catch (_) { appendLog(buffer.trim()); }
            }
            if (!completedEventSeen) {
                throw new Error('The server connection ended before Nexora reported a final deployment result.');
            }
        } catch (error) {
            finishTimer();
            if (error && error.name === 'AbortError') {
                if (current) current.textContent = 'Cancelling deployment…';
                if (status) status.textContent = 'The browser connection was cancelled. The server process is being terminated.';
                appendLog('Cancellation requested from the browser.');
            } else {
                if (current) current.textContent = 'Deployment connection failed';
                if (status) status.textContent = error?.message || 'The deployment stream ended unexpectedly.';
                appendLog(`ERROR: ${error?.message || error}`);
            }
        } finally {
            controller = null;
            if (!blockedRunId) currentRunId = null;
            currentSubmitter = null;
            setBusy(false, submitter);
            if (blockedRunId && cancel) cancel.disabled = false;
        }
    };

    if (deploymentForm) {
        deploymentForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const submitter = event.submitter;
            const task = submitter instanceof HTMLButtonElement ? submitter.dataset.task : null;
            if (task) runTask(task, submitter);
        });
    }
    if (cancel) {
        cancel.addEventListener('click', async () => {
            const runId = currentRunId || blockedRunId;
            if (!runId) return;
            cancel.disabled = true;
            if (current) current.textContent = 'Cancelling deployment safely…';
            if (status) status.textContent = 'Sending a cancellation request to the active Nexora worker. Keep this page open until cancellation is confirmed.';
            appendLog('Cancellation requested. Waiting for the server worker to release the deployment lock…');
            const body = new FormData();
            body.append('_token', deploymentForm.querySelector('input[name="_token"]').value);
            body.append('action', 'cancel_stream');
            body.append('run_id', runId);
            try {
                const response = await fetch(window.location.pathname || '/', { method:'POST', body, headers:{'Accept':'application/json'}, cache:'no-store' });
                const payload = await response.json().catch(()=>({ok:false,message:'Cancellation endpoint returned an invalid response.'}));
                if (!response.ok || !payload.ok) throw new Error(payload.message || `Cancellation failed (HTTP ${response.status}).`);
                if (status) status.textContent = payload.message || 'Cancellation accepted. Waiting for the running process to stop.';
                appendLog(payload.message || 'Cancellation accepted by the server.');

                if (!controller || blockedRunId) {
                    const waitForIdle = async () => {
                        for (let attempt = 0; attempt < 30; attempt += 1) {
                            await new Promise(resolve => window.setTimeout(resolve, 400));
                            const check = new FormData();
                            check.append('_token', deploymentForm.querySelector('input[name="_token"]').value);
                            check.append('action', 'deployment_status');
                            try {
                                const statusResponse = await fetch(window.location.pathname || '/', { method:'POST', body:check, headers:{'Accept':'application/json'}, cache:'no-store' });
                                const statusPayload = await statusResponse.json();
                                const active = !!statusPayload?.state?.active && statusPayload?.state?.run_id === runId;
                                if (!active) {
                                    blockedRunId = null;
                                    currentRunId = null;
                                    cancel.disabled = true;
                                    if (cancelLabel) cancelLabel.textContent = i18n.cancel;
                                    if (current) current.textContent = 'Previous deployment stopped';
                                    if (status) status.textContent = 'The deployment lock has been released. You can start the task again now.';
                                    appendLog('Previous worker stopped and released the deployment lock.');
                                    return;
                                }
                            } catch (_) {}
                        }
                        if (status) status.textContent = 'Cancellation was requested, but the previous worker has not released its lock yet. Wait a few seconds and retry Stop previous task.';
                        cancel.disabled = false;
                    };
                    await waitForIdle();
                } else {
                    window.setTimeout(() => {
                        if (controller && status) status.textContent = 'Cancellation is taking longer than expected. Nexora is still waiting for the worker to release its process lock.';
                    }, 5000);
                }
            } catch (error) {
                appendLog(`Cancellation control error: ${error?.message || error}`);
                if (status) status.textContent = 'The cancellation control request failed. Closing the stream as a fallback.';
                if (controller) controller.abort(); else cancel.disabled = false;
            }
        });
        cancel.disabled = true;
    }

    const releaseInput = document.getElementById('release-zip-input');
    const releaseDropzone = document.getElementById('release-dropzone');
    const releaseFileName = document.getElementById('release-file-name');
    const syncReleaseName = () => {
        if (!releaseFileName || !releaseInput) return;
        const file = releaseInput.files?.[0];
        releaseFileName.textContent = file ? `${file.name} · ${(file.size / 1048576).toFixed(2)} MB` : i18n.noRelease;
    };
    if (releaseInput) releaseInput.addEventListener('change', syncReleaseName);
    if (releaseDropzone && releaseInput) {
        ['dragenter','dragover'].forEach(type => releaseDropzone.addEventListener(type, event => { event.preventDefault(); releaseDropzone.classList.add('drag'); }));
        ['dragleave','drop'].forEach(type => releaseDropzone.addEventListener(type, event => { event.preventDefault(); releaseDropzone.classList.remove('drag'); }));
        releaseDropzone.addEventListener('drop', event => {
            const file = event.dataTransfer?.files?.[0];
            if (!file) return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            releaseInput.files = transfer.files;
            syncReleaseName();
        });
    }

    // Normal short forms (DB verification, diagnostics, release upload) keep a
    // conventional submit state. Long build forms are handled above by streaming fetch.
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form === deploymentForm) return;
        const submitter = event.submitter;
        if (submitter && submitter.name === 'action') {
            const hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = 'action'; hidden.value = submitter.value;
            form.appendChild(hidden);
        }
        form.querySelectorAll('button').forEach(function (button) { button.disabled = true; });
        if (submitter instanceof HTMLButtonElement) {
            submitter.dataset.originalText = submitter.textContent || '';
            submitter.textContent = 'Working…';
        }
    });
})();
</script>
</body></html>
