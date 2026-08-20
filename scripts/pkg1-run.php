<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/pkg1-closure.php';

$options = [
    'operator' => '',
    'base_url' => 'http://nexora',
    'reviewer' => '',
    'max_steps' => 24,
];
foreach ($argv as $index => $argument) {
    if ($index === 0) continue;
    if (str_starts_with($argument, '--operator=')) $options['operator'] = trim(substr($argument, 11));
    elseif (str_starts_with($argument, '--base-url=')) $options['base_url'] = rtrim(trim(substr($argument, 11)), '/');
    elseif (str_starts_with($argument, '--reviewer=')) $options['reviewer'] = trim(substr($argument, 11));
    elseif (str_starts_with($argument, '--max-steps=')) $options['max_steps'] = max(1, min(100, (int) substr($argument, 12)));
}

if ($options['operator'] === '' || strlen($options['operator']) > 120) {
    fwrite(STDERR, "Usage: php scripts/pkg1-run.php --operator=\"REAL NAME\" [--base-url=http://nexora]\n");
    exit(2);
}
if (! filter_var($options['base_url'], FILTER_VALIDATE_URL)
    || ! in_array(strtolower((string) parse_url($options['base_url'], PHP_URL_SCHEME)), ['http', 'https'], true)) {
    fwrite(STDERR, "[PKG-1 Launcher] Invalid --base-url. Only http/https URLs are accepted.\n");
    exit(2);
}

$environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);

/** @return array{exit_code:int,stdout:string,stderr:string} */
function nexoraPkg1LauncherRun(array $command, string $root, array $environment): array
{
    $result = nexoraPkg1Run($command, $root, $environment);
    if ($result['stdout'] !== '') fwrite(STDOUT, $result['stdout'].(str_ends_with($result['stdout'], "\n") ? '' : "\n"));
    if ($result['stderr'] !== '') fwrite(STDERR, $result['stderr'].(str_ends_with($result['stderr'], "\n") ? '' : "\n"));
    return $result;
}

/** @return array<string,mixed> */
function nexoraPkg1LauncherStatus(string $root, string $baseUrl, array $environment): array
{
    $result = nexoraPkg1Run(
        [PHP_BINARY, 'scripts/pkg1-status.php', '--json', '--base-url='.$baseUrl],
        $root,
        $environment,
    );
    $raw = trim((string) $result['stdout']);
    try {
        $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $exception) {
        throw new RuntimeException('PKG-1 status doctor returned invalid JSON: '.$exception->getMessage());
    }
    if (! is_array($payload) || ! is_string($payload['status'] ?? null)) {
        throw new RuntimeException('PKG-1 status doctor returned an invalid payload.');
    }
    return $payload;
}

function nexoraPkg1LauncherPrompt(string $prompt): string
{
    fwrite(STDOUT, $prompt);
    $value = fgets(STDIN);
    return $value === false ? '' : trim($value);
}

function nexoraPkg1LauncherShowStatus(array $status): void
{
    $percent = max(0, min(100, (int) ($status['progress_percent'] ?? 0)));
    $filled = (int) floor($percent / 5);
    $bar = str_repeat('#', $filled).str_repeat('-', 20 - $filled);
    fwrite(STDOUT, "\nPKG-1 {$percent}% - ".($status['status'] ?? 'UNKNOWN').' - phase='.($status['phase'] ?? 'unknown')."\n");
    fwrite(STDOUT, $bar."\n");
    fwrite(STDOUT, (string) ($status['message'] ?? '')."\n");
    fwrite(STDOUT, 'NEXT_ACTION='.(string) ($status['next_action'] ?? 'UNKNOWN')."\n");
    if (is_string($status['next_command'] ?? null) && $status['next_command'] !== '') {
        fwrite(STDOUT, 'NEXT_COMMAND='.$status['next_command']."\n");
    }
}

/** @return array<string,mixed>|null */
function nexoraPkg1LauncherLatest(string $root): ?array
{
    $path = $root.'/storage/app/nexora/pkg1/latest.json';
    if (! is_file($path)) return null;
    try {
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }
    return is_array($decoded) ? $decoded : null;
}

function nexoraPkg1LauncherStopOnBlock(string $root): bool
{
    $latest = nexoraPkg1LauncherLatest($root);
    if (! is_array($latest) || ($latest['status'] ?? null) !== 'blocked') return false;
    fwrite(STDERR, "\nPKG-1 BLOCKED - phase=".(string) ($latest['phase'] ?? 'unknown')."\n");
    $blocker = $latest['first_blocker'] ?? null;
    if (is_array($blocker)) {
        fwrite(STDERR, 'BLOCKER_ID='.(string) ($blocker['id'] ?? 'unknown')."\n");
        fwrite(STDERR, 'BLOCKER='.(string) ($blocker['detail'] ?? $blocker['label'] ?? 'unknown')."\n");
    }
    fwrite(STDERR, "Fix the blocker above, then run the same scripts\\pkg1-run.bat command again.\n");
    return true;
}

function nexoraPkg1LauncherOpenUrl(string $url): void
{
    if (PHP_OS_FAMILY === 'Windows') {
        @proc_close(@proc_open(
            ['rundll32.exe', 'url.dll,FileProtocolHandler', $url],
            [0 => ['file', 'NUL', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']],
            $pipes,
        ));
        return;
    }
    if (PHP_OS_FAMILY === 'Darwin') {
        @proc_close(@proc_open(['open', $url], [], $pipes));
        return;
    }
    @proc_close(@proc_open(['xdg-open', $url], [], $pipes));
}

function nexoraPkg1LauncherInteractiveProcess(array $command, string $cwd): int
{
    $spec = [
        0 => ['file', 'php://stdin', 'r'],
        1 => ['file', 'php://stdout', 'w'],
        2 => ['file', 'php://stderr', 'w'],
    ];
    $process = @proc_open($command, $spec, $pipes, $cwd, null, ['bypass_shell' => true]);
    if (! is_resource($process)) return 127;
    return proc_close($process);
}

function nexoraPkg1LauncherAuthSmoke(string $root, string $operator, string $baseUrl): int
{
    if (PHP_OS_FAMILY !== 'Windows') {
        fwrite(STDERR, "[PKG-1 Launcher] Hidden-password auth smoke requires the authoritative Windows/Laragon target.\n");
        return 2;
    }
    $finalizer = $root.'/scripts/pkg1-finalize-login-smoke.ps1';
    $powershell = (string) (getenv('SystemRoot') ?: 'C:\\Windows').'\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
    if (! is_file($powershell)) $powershell = 'powershell.exe';

    $parseScript = <<<'PS'
$tokens=$null; $errors=$null; $null=[System.Management.Automation.Language.Parser]::ParseFile($args[0],[ref]$tokens,[ref]$errors); if($errors.Count -gt 0){foreach($e in $errors){[Console]::Error.WriteLine($e.Message)}; exit 3}; exit 0
PS;
    $parse = nexoraPkg1LauncherInteractiveProcess(
        [$powershell, '-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', $parseScript, $finalizer],
        $root,
    );
    if ($parse !== 0) {
        fwrite(STDERR, "[PKG-1 Launcher] Hidden-password finalizer PowerShell parser check failed.\n");
        return $parse;
    }

    return nexoraPkg1LauncherInteractiveProcess(
        [$powershell, '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $finalizer, $operator, $baseUrl],
        $root,
    );
}

$runClosure = static function (array $extra = []) use ($root, $environment, $options): int {
    $command = array_merge(
        [PHP_BINARY, 'scripts/pkg1-usable-closure.php', '--operator='.$options['operator'], '--base-url='.$options['base_url']],
        $extra,
    );
    $result = nexoraPkg1LauncherRun($command, $root, $environment);
    return $result['exit_code'];
};

fwrite(STDOUT, "Nexora PKG-1 PHP launcher - operator={$options['operator']} - target={$options['base_url']}\n");
fwrite(STDOUT, "Human lock review, recovery confirmation, browser installation and real login smoke are never bypassed.\n");

for ($step = 1; $step <= $options['max_steps']; $step++) {
    try {
        $status = nexoraPkg1LauncherStatus($root, $options['base_url'], $environment);
    } catch (Throwable $exception) {
        fwrite(STDERR, '[PKG-1 Launcher] '.$exception->getMessage()."\n");
        exit(3);
    }
    nexoraPkg1LauncherShowStatus($status);
    $state = (string) $status['status'];

    if ($state === 'COMPLETE') {
        $verify = nexoraPkg1LauncherRun([PHP_BINARY, 'scripts/pkg1-closure-evidence-verify.php'], $root, $environment);
        if ($verify['exit_code'] !== 0) exit($verify['exit_code']);
        fwrite(STDOUT, "PKG-1 COMPLETE - sealed evidence verified on the current exact target.\n");
        exit(0);
    }

    if ($state === 'WAITING_RECOVERY') {
        $confirm = nexoraPkg1LauncherPrompt('Type ROLLBACK to run fail-safe promotion recovery: ');
        if ($confirm !== 'ROLLBACK') {
            fwrite(STDOUT, "Recovery not confirmed; stopping without mutation.\n");
            exit(2);
        }
        $recovery = nexoraPkg1LauncherRun(
            [PHP_BINARY, 'scripts/dependency-lock-promotion-recover.php', '--confirm=ROLLBACK'],
            $root,
            $environment,
        );
        if ($recovery['exit_code'] !== 0) exit($recovery['exit_code']);
        continue;
    }

    if ($state === 'WAITING_REVIEW') {
        fwrite(STDOUT, "\nHuman dependency-lock review is mandatory.\n");
        fwrite(STDOUT, "Review these exact files:\n");
        fwrite(STDOUT, "  storage/app/nexora/dependency-intake/lock-refresh.md\n");
        fwrite(STDOUT, "  storage/app/nexora/dependency-intake/candidates/composer.lock\n");
        fwrite(STDOUT, "  storage/app/nexora/dependency-intake/candidates/package-lock.json\n\n");
        if ($options['reviewer'] === '') {
            $options['reviewer'] = nexoraPkg1LauncherPrompt('Reviewer real name: ');
        }
        if ($options['reviewer'] === '' || strlen($options['reviewer']) > 120) {
            fwrite(STDERR, "Reviewer real name is required.\n");
            exit(2);
        }
        $confirm = nexoraPkg1LauncherPrompt('After review, type PROMOTE-REVIEWED: ');
        if ($confirm !== 'PROMOTE-REVIEWED') {
            fwrite(STDOUT, "Promotion not confirmed. PKG-1 remains at WAITING_REVIEW.\n");
            exit(2);
        }
        $code = $runClosure(['--reviewer='.$options['reviewer'], '--promote-reviewed']);
        if ($code !== 0 && $code !== 2) exit($code);
        if ($code === 2 && nexoraPkg1LauncherStopOnBlock($root)) exit(2);
        continue;
    }

    if ($state === 'WAITING_SOURCE_RESTART') {
        fwrite(STDOUT, "Reload or restart the Laragon web stack (Apache/Nginx/PHP) now.\n");
        nexoraPkg1LauncherPrompt('After reload, press Enter to continue: ');
        $code = $runClosure();
        if ($code !== 0 && $code !== 2) exit($code);
        if ($code === 2 && nexoraPkg1LauncherStopOnBlock($root)) exit(2);
        continue;
    }

    if ($state === 'WAITING_INSTALL') {
        $installerUrl = $options['base_url'].'/install';
        fwrite(STDOUT, "Opening installer: {$installerUrl}\n");
        nexoraPkg1LauncherOpenUrl($installerUrl);
        nexoraPkg1LauncherPrompt('Complete the browser installer to 100%, then press Enter to continue: ');
        $code = $runClosure();
        if ($code !== 0 && $code !== 2) exit($code);
        if ($code === 2 && nexoraPkg1LauncherStopOnBlock($root)) exit(2);
        continue;
    }

    if ($state === 'WAITING_AUTH_SMOKE') {
        fwrite(STDOUT, "Starting hidden-password Super Admin login to /admin smoke...\n");
        $code = nexoraPkg1LauncherAuthSmoke($root, $options['operator'], $options['base_url']);
        if ($code !== 0 && $code !== 2) exit($code);
        if ($code === 2 && nexoraPkg1LauncherStopOnBlock($root)) exit(2);
        continue;
    }

    if ($state === 'BLOCKED_TOOLCHAIN') {
        fwrite(STDERR, "PKG-1 BLOCKED - dependency toolchain is not inside the certified ranges or could not be executed.\n");
        $errors = (array) ($status['checks']['dependency_toolchain_errors'] ?? []);
        foreach ($errors as $error) fwrite(STDERR, ' - '.(string) $error."\n");
        fwrite(STDERR, "Fix the toolchain blocker above, then run the same scripts\pkg1-run.bat command again.\n");
        exit(2);
    }

    if (in_array($state, [
        'READY',
        'READY_COMPOSER_BOOTSTRAP',
        'READY_CANDIDATE_GENERATION',
        'STALE_CANDIDATE',
        'READY_C1',
        'READY_SOURCE_RESUME',
        'WAITING_POST_INSTALL',
    ], true)) {
        $code = $runClosure();
        if ($code !== 0 && $code !== 2) exit($code);
        if ($code === 2 && nexoraPkg1LauncherStopOnBlock($root)) exit(2);
        continue;
    }

    fwrite(STDERR, "Unhandled PKG-1 state: {$state}\n");
    fwrite(STDERR, "Run scripts\\pkg1-status.bat for diagnostic details.\n");
    exit(3);
}

fwrite(STDERR, "PKG-1 launcher reached max steps without terminal closure.\n");
exit(2);
