<?php

declare(strict_types=1);

/**
 * Nexora Runtime Recovery / Closure Orchestrator.
 *
 * Operator-side coordinator for the currently-authorized runtime closure path.
 * It never copies source into the target, installs/updates dependencies, runs
 * migrations/seeders, or changes the target release.
 *
 * Dry-run is the default. Apply mode requires RECOVER-RUNTIME. Any version-
 * specific mutation is delegated to an explicitly approved adapter and then
 * independently verified by the target's own compatibility/readiness commands.
 */

const NEXORA_RUNTIME_RECOVERY_CONFIRMATION = 'RECOVER-RUNTIME';
const NEXORA_RUNTIME_RECOVERY_RC93_VERSION = '1.0.0-rc.93';
const NEXORA_RUNTIME_RECOVERY_RC93_ALLOWED_MISMATCHES = [
    'activation',
    'environment',
    'process',
    'service',
];

/** @param array<string,mixed> $payload */
function nexoraRuntimeRecoveryEmit(array $payload, int $exitCode = 0): never
{
    $payload['schema'] = 1;
    $payload['tool'] = 'runtime-recovery-orchestrator';
    fwrite(
        $exitCode === 0 ? STDOUT : STDERR,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
    );
    exit($exitCode);
}

/** @param array<string,mixed> $context */
function nexoraRuntimeRecoveryFail(string $message, array $context = []): never
{
    nexoraRuntimeRecoveryEmit([
        'status' => 'fail',
        'message' => $message,
        ...$context,
    ], 1);
}

/** @param list<string> $command @return array{exit_code:int,stdout:string,stderr:string} */
function nexoraRuntimeRecoveryRun(array $command, string $cwd): array
{
    if (! function_exists('proc_open')) {
        nexoraRuntimeRecoveryFail('Runtime recovery requires proc_open for shell-bypassed child execution.');
    }

    $descriptors = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $pipes = [];
    $process = @proc_open($command, $descriptors, $pipes, $cwd, null, ['bypass_shell' => true]);
    if (! is_resource($process)) {
        nexoraRuntimeRecoveryFail('Unable to start a required recovery child process.', [
            'command' => $command,
            'cwd' => $cwd,
        ]);
    }

    $stdout = is_resource($pipes[1] ?? null) ? (string) stream_get_contents($pipes[1]) : '';
    $stderr = is_resource($pipes[2] ?? null) ? (string) stream_get_contents($pipes[2]) : '';
    foreach ($pipes as $pipe) {
        if (is_resource($pipe)) {
            fclose($pipe);
        }
    }
    $exitCode = proc_close($process);

    return [
        'exit_code' => is_int($exitCode) ? $exitCode : 1,
        'stdout' => trim($stdout),
        'stderr' => trim($stderr),
    ];
}

/** @return array<string,mixed>|null */
function nexoraRuntimeRecoveryExtractJson(string ...$streams): ?array
{
    foreach ($streams as $stream) {
        $text = trim($stream);
        if ($text === '') {
            continue;
        }

        $offset = 0;
        while (($position = strpos($text, '{', $offset)) !== false) {
            $candidate = trim(substr($text, $position));
            try {
                $decoded = json_decode($candidate, true, 256, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                $offset = $position + 1;
                continue;
            }
            if (is_array($decoded)) {
                return $decoded;
            }
            $offset = $position + 1;
        }
    }

    return null;
}

/** @return list<string> */
function nexoraRuntimeRecoveryMismatches(array $compatibility): array
{
    $runtime = is_array($compatibility['runtime'] ?? null) ? $compatibility['runtime'] : [];
    $raw = $compatibility['mismatches'] ?? $runtime['mismatches'] ?? [];
    $rows = [];
    foreach ((array) $raw as $mismatch) {
        $name = strtolower(trim((string) $mismatch));
        if ($name !== '') {
            $rows[] = $name;
        }
    }
    $rows = array_values(array_unique($rows));
    sort($rows, SORT_STRING);

    return $rows;
}

function nexoraRuntimeRecoveryCompatibilityPayloadPass(array $payload): bool
{
    $runtime = is_array($payload['runtime'] ?? null) ? $payload['runtime'] : [];

    return ($payload['status'] ?? null) === 'pass'
        && nexoraRuntimeRecoveryMismatches($payload) === []
        && ($runtime['compatible'] ?? false) === true
        && ($runtime['mode'] ?? null) === 'installed-data-plane';
}

/** @param array{exit_code:int,payload:array<string,mixed>} $result */
function nexoraRuntimeRecoveryCompatibilityPass(array $result): bool
{
    return $result['exit_code'] === 0
        && nexoraRuntimeRecoveryCompatibilityPayloadPass($result['payload']);
}

function nexoraRuntimeRecoveryReadyPayload(array $payload): bool
{
    return ($payload['status'] ?? null) === 'pass'
        && ($payload['ready'] ?? false) === true
        && ($payload['runtime_ready'] ?? false) === true
        && ($payload['receipt_current'] ?? false) === true
        && array_values((array) ($payload['errors'] ?? [])) === [];
}

/** @param array{exit_code:int,payload:array<string,mixed>} $result */
function nexoraRuntimeRecoveryReady(array $result): bool
{
    return $result['exit_code'] === 0
        && nexoraRuntimeRecoveryReadyPayload($result['payload']);
}

function nexoraRuntimeRecoveryNeedsReceiptRefresh(array $payload): bool
{
    return ($payload['status'] ?? null) === 'receipt-refresh-required'
        && ($payload['ready'] ?? true) === false
        && ($payload['runtime_ready'] ?? false) === true
        && ($payload['receipt_current'] ?? true) === false;
}

/** @return array{exit_code:int,payload:array<string,mixed>} */
function nexoraRuntimeRecoveryCompatibility(string $target): array
{
    $result = nexoraRuntimeRecoveryRun([
        PHP_BINARY,
        $target.DIRECTORY_SEPARATOR.'artisan',
        'nexora:runtime:compatibility-status',
        '--deep',
    ], $target);
    $payload = nexoraRuntimeRecoveryExtractJson($result['stdout'], $result['stderr']);
    if (! is_array($payload)) {
        nexoraRuntimeRecoveryFail('Runtime compatibility command did not return a parseable JSON payload.', [
            'exit_code' => $result['exit_code'],
            'stdout' => $result['stdout'],
            'stderr' => $result['stderr'],
        ]);
    }

    return ['exit_code' => $result['exit_code'], 'payload' => $payload];
}

/** @return array{exit_code:int,payload:array<string,mixed>} */
function nexoraRuntimeRecoveryPostInstallStatus(string $target, bool $assertReady): array
{
    $command = [
        PHP_BINARY,
        $target.DIRECTORY_SEPARATOR.'artisan',
        'nexora:runtime:post-install-status',
    ];
    if ($assertReady) {
        $command[] = '--assert-ready';
    }

    $result = nexoraRuntimeRecoveryRun($command, $target);
    $payload = nexoraRuntimeRecoveryExtractJson($result['stdout'], $result['stderr']);
    if (! is_array($payload)) {
        nexoraRuntimeRecoveryFail('Post-install readiness command did not return a parseable JSON payload.', [
            'exit_code' => $result['exit_code'],
            'stdout' => $result['stdout'],
            'stderr' => $result['stderr'],
        ]);
    }

    return ['exit_code' => $result['exit_code'], 'payload' => $payload];
}

/** @return array<string,mixed> */
function nexoraRuntimeRecoveryReconcileReceipt(string $target): array
{
    $result = nexoraRuntimeRecoveryRun([
        PHP_BINARY,
        $target.DIRECTORY_SEPARATOR.'artisan',
        'nexora:runtime:post-install-reconcile',
        '--confirm=RECONCILE',
    ], $target);
    $payload = nexoraRuntimeRecoveryExtractJson($result['stdout'], $result['stderr']);
    if ($result['exit_code'] !== 0 || ! is_array($payload) || ($payload['status'] ?? null) !== 'pass') {
        nexoraRuntimeRecoveryFail('Post-install receipt reconciliation failed.', [
            'exit_code' => $result['exit_code'],
            'payload' => $payload,
            'stderr' => $result['stderr'],
        ]);
    }

    return $payload;
}

/** @return array<string,mixed> */
function nexoraRuntimeRecoveryRc93Repair(string $target, bool $apply): array
{
    $repairScript = __DIR__.DIRECTORY_SEPARATOR.'rc93-post-install-identity-repair.php';
    if (! is_file($repairScript)) {
        nexoraRuntimeRecoveryFail('The required rc.93 recovery adapter is missing from the operator checkout.', [
            'path' => $repairScript,
        ]);
    }

    $command = [PHP_BINARY, $repairScript, '--target='.$target];
    if ($apply) {
        $command[] = '--apply';
        $command[] = '--confirm=REPAIR-RC93';
    }

    $result = nexoraRuntimeRecoveryRun($command, __DIR__);
    $payload = nexoraRuntimeRecoveryExtractJson($result['stdout'], $result['stderr']);
    if ($result['exit_code'] !== 0 || ! is_array($payload) || ($payload['status'] ?? null) !== 'pass') {
        nexoraRuntimeRecoveryFail('The rc.93 bounded recovery adapter failed.', [
            'exit_code' => $result['exit_code'],
            'payload' => $payload,
            'stderr' => $result['stderr'],
        ]);
    }

    return $payload;
}

function nexoraRuntimeRecoveryResolveAppUrl(string $target): ?string
{
    $targetLiteral = var_export($target, true);
    $code = '$target='.$targetLiteral.';'
        .'require $target.DIRECTORY_SEPARATOR."vendor".DIRECTORY_SEPARATOR."autoload.php";'
        .'$app=require $target.DIRECTORY_SEPARATOR."bootstrap".DIRECTORY_SEPARATOR."app.php";'
        .'$kernel=$app->make("Illuminate\\\\Contracts\\\\Console\\\\Kernel");'
        .'$kernel->bootstrap();'
        .'fwrite(STDOUT,trim((string) config("app.url","")));';
    $result = nexoraRuntimeRecoveryRun([PHP_BINARY, '-r', $code], $target);
    if ($result['exit_code'] !== 0) {
        return null;
    }

    $url = trim($result['stdout']);

    return $url !== '' ? $url : null;
}

/** @return array{status:string,url:string,http_status:?int,error:?string} */
function nexoraRuntimeRecoveryLoginSmoke(string $baseUrl): array
{
    $parts = parse_url($baseUrl);
    if (! is_array($parts)
        || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
        || trim((string) ($parts['host'] ?? '')) === ''
        || isset($parts['user'])
        || isset($parts['pass'])) {
        return [
            'status' => 'fail',
            'url' => $baseUrl,
            'http_status' => null,
            'error' => 'Configured application URL is not a safe absolute HTTP(S) URL for the bounded /login smoke.',
        ];
    }

    $url = rtrim($baseUrl, '/').'/login';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => 12,
            'header' => "Accept: text/html\r\nUser-Agent: NexoraRuntimeRecovery/1\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $http_response_header = [];
    $body = @file_get_contents($url, false, $context);
    $headers = is_array($http_response_header ?? null) ? $http_response_header : [];
    $statusCode = null;
    if (isset($headers[0]) && preg_match('/\s(\d{3})(?:\s|$)/', (string) $headers[0], $matches) === 1) {
        $statusCode = (int) $matches[1];
    }

    if ($statusCode === 200 && is_string($body)) {
        return ['status' => 'pass', 'url' => $url, 'http_status' => 200, 'error' => null];
    }

    if ($statusCode !== null) {
        return [
            'status' => 'fail',
            'url' => $url,
            'http_status' => $statusCode,
            'error' => 'Expected HTTP 200 from /login without following redirects.',
        ];
    }

    return [
        'status' => 'blocked',
        'url' => $url,
        'http_status' => null,
        'error' => 'Unable to complete verified HTTP(S) /login smoke. Check web-server reachability and PHP CA trust; TLS verification is not disabled.',
    ];
}

/** @param array<string,mixed> $payload */
function nexoraRuntimeRecoveryWriteReceipt(string $target, array $payload): ?string
{
    $directory = $target.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'nexora'
        .DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'recovery-orchestrator';
    if (! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
        return null;
    }

    $payload['recorded_at'] = gmdate('c');
    $copy = $payload;
    ksort($copy, SORT_STRING);
    $payload['receipt_sha256'] = hash(
        'sha256',
        json_encode($copy, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
    );
    $path = $directory.DIRECTORY_SEPARATOR.'runtime-recovery-'.gmdate('Ymd\THis\Z').'.json';
    $temporary = $path.'.tmp-'.bin2hex(random_bytes(4));
    $bytes = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    if (@file_put_contents($temporary, $bytes, LOCK_EX) === false) {
        return null;
    }
    @chmod($temporary, 0600);
    if (! @rename($temporary, $path)) {
        @unlink($temporary);
        return null;
    }
    @chmod($path, 0600);

    return $path;
}

function nexoraRuntimeRecoveryUsage(): string
{
    return <<<'TEXT'
Nexora Runtime Recovery / Closure Orchestrator

Read-only dry-run:
  php scripts/runtime-recovery-orchestrator.php --target="D:\laragon\www\nexora"

Authorized recovery + automatic verification/reconcile/login smoke:
  php scripts/runtime-recovery-orchestrator.php --target="D:\laragon\www\nexora" --apply --confirm=RECOVER-RUNTIME

Optional explicit app URL for the final HTTP smoke:
  --base-url=https://nexora

The orchestrator never upgrades/copies source, installs dependencies, runs
migrations, or weakens TLS. Apply mode uses only an approved version-specific
adapter, independently re-verifies compatibility/readiness, reconciles only the
exact stale-receipt state, then requires /login HTTP 200 for target closure.
TEXT;
}

$options = getopt('', ['target:', 'apply', 'confirm:', 'base-url:', 'help']);
if ($options === false) {
    nexoraRuntimeRecoveryFail('Unable to parse command-line options.');
}
if (array_key_exists('help', $options)) {
    fwrite(STDOUT, nexoraRuntimeRecoveryUsage().PHP_EOL);
    exit(0);
}

$apply = array_key_exists('apply', $options);
$confirmation = trim((string) ($options['confirm'] ?? ''));
if ($apply && ! hash_equals(NEXORA_RUNTIME_RECOVERY_CONFIRMATION, $confirmation)) {
    nexoraRuntimeRecoveryFail('Apply mode requires the exact recovery confirmation token.', [
        'required_confirmation' => NEXORA_RUNTIME_RECOVERY_CONFIRMATION,
    ]);
}
if (! $apply && $confirmation !== '') {
    nexoraRuntimeRecoveryFail('A confirmation token is only valid together with --apply.');
}

$targetInput = trim((string) ($options['target'] ?? ''));
if ($targetInput === '' || is_link($targetInput)) {
    nexoraRuntimeRecoveryFail('An explicit non-symlink --target directory is required.');
}
$target = realpath($targetInput);
if (! is_string($target) || ! is_dir($target)) {
    nexoraRuntimeRecoveryFail('Target path does not resolve to an existing directory.', ['target' => $targetInput]);
}
foreach (['artisan', 'vendor/autoload.php', 'bootstrap/app.php'] as $relative) {
    $path = $target.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (! is_file($path) || is_link($path)) {
        nexoraRuntimeRecoveryFail('Target is missing a required regular runtime file.', [
            'target' => $target,
            'missing_or_unsafe' => $relative,
        ]);
    }
}

$steps = [];
$mutationPerformed = false;
$compatibility = nexoraRuntimeRecoveryCompatibility($target);
$compatibilityPayload = $compatibility['payload'];
$runtime = is_array($compatibilityPayload['runtime'] ?? null) ? $compatibilityPayload['runtime'] : [];
$currentVersion = trim((string) ($runtime['current_version'] ?? ''));
$installedVersion = trim((string) ($runtime['installed_version'] ?? ''));
$mismatches = nexoraRuntimeRecoveryMismatches($compatibilityPayload);
$steps['compatibility_before'] = [
    'exit_code' => $compatibility['exit_code'],
    'status' => nexoraRuntimeRecoveryCompatibilityPass($compatibility) ? 'pass' : 'fail',
    'current_version' => $currentVersion,
    'installed_version' => $installedVersion,
    'mode' => $runtime['mode'] ?? null,
    'mismatches' => $mismatches,
];

if (! nexoraRuntimeRecoveryCompatibilityPass($compatibility)) {
    $unexpected = array_values(array_diff($mismatches, NEXORA_RUNTIME_RECOVERY_RC93_ALLOWED_MISMATCHES));
    $eligibleRc93Failure = hash_equals(NEXORA_RUNTIME_RECOVERY_RC93_VERSION, $currentVersion)
        && hash_equals(NEXORA_RUNTIME_RECOVERY_RC93_VERSION, $installedVersion)
        && $mismatches !== []
        && $unexpected === []
        && ($compatibilityPayload['status'] ?? null) === 'fail';

    if (! $eligibleRc93Failure) {
        nexoraRuntimeRecoveryFail('No approved bounded recovery adapter matches the observed target state.', [
            'target' => $target,
            'compatibility_exit_code' => $compatibility['exit_code'],
            'current_version' => $currentVersion,
            'installed_version' => $installedVersion,
            'mismatches' => $mismatches,
            'unexpected_mismatches' => $unexpected,
        ]);
    }

    $repair = nexoraRuntimeRecoveryRc93Repair($target, $apply);
    $steps['repair_adapter'] = [
        'status' => 'pass',
        'mode' => $repair['mode'] ?? null,
        'mismatches' => $repair['repaired_mismatches'] ?? $repair['mismatches_before'] ?? $mismatches,
        'mutation_performed' => $repair['mutation_performed'] ?? false,
        'backup_path' => $repair['backup_path'] ?? null,
        'receipt_path' => $repair['receipt_path'] ?? null,
    ];

    if (! $apply) {
        nexoraRuntimeRecoveryEmit([
            'status' => 'pass',
            'mode' => 'dry-run',
            'message' => 'A bounded recovery path is available. No mutation was performed.',
            'target' => $target,
            'steps' => $steps,
            'mutation_performed' => false,
            'next_action' => 'Re-run with --apply --confirm=RECOVER-RUNTIME to execute the approved recovery and deterministic closure checks.',
        ]);
    }

    $mutationPerformed = ($repair['mutation_performed'] ?? false) === true;
    $compatibility = nexoraRuntimeRecoveryCompatibility($target);
    if (! nexoraRuntimeRecoveryCompatibilityPass($compatibility)) {
        nexoraRuntimeRecoveryFail('Recovery adapter returned, but independent deep compatibility did not converge.', [
            'target' => $target,
            'exit_code' => $compatibility['exit_code'],
            'compatibility' => $compatibility['payload'],
        ]);
    }
    $compatibilityPayload = $compatibility['payload'];
    $steps['compatibility_after'] = [
        'exit_code' => 0,
        'status' => 'pass',
        'mode' => $compatibilityPayload['runtime']['mode'] ?? null,
        'mismatches' => nexoraRuntimeRecoveryMismatches($compatibilityPayload),
    ];
} elseif (! $apply) {
    $readinessObserved = nexoraRuntimeRecoveryPostInstallStatus($target, false);
    $steps['readiness_observed'] = [
        'exit_code' => $readinessObserved['exit_code'],
        'status' => $readinessObserved['payload']['status'] ?? null,
        'ready' => $readinessObserved['payload']['ready'] ?? false,
        'runtime_ready' => $readinessObserved['payload']['runtime_ready'] ?? false,
        'receipt_current' => $readinessObserved['payload']['receipt_current'] ?? false,
    ];

    nexoraRuntimeRecoveryEmit([
        'status' => 'pass',
        'mode' => 'dry-run',
        'message' => 'Runtime compatibility already passes. No mutation was performed.',
        'target' => $target,
        'steps' => $steps,
        'mutation_performed' => false,
        'next_action' => nexoraRuntimeRecoveryNeedsReceiptRefresh($readinessObserved['payload'])
            ? 'Apply mode can reconcile the exact stale receipt, re-assert readiness and run /login smoke.'
            : 'Apply mode will assert readiness and run /login smoke; no identity repair is planned.',
    ]);
}

$readiness = nexoraRuntimeRecoveryPostInstallStatus($target, true);
$readinessPayload = $readiness['payload'];
$steps['readiness_before_reconcile'] = [
    'exit_code' => $readiness['exit_code'],
    'status' => $readinessPayload['status'] ?? null,
    'ready' => $readinessPayload['ready'] ?? false,
    'runtime_ready' => $readinessPayload['runtime_ready'] ?? false,
    'receipt_current' => $readinessPayload['receipt_current'] ?? false,
];

if (! nexoraRuntimeRecoveryReady($readiness)) {
    if (! nexoraRuntimeRecoveryNeedsReceiptRefresh($readinessPayload)) {
        nexoraRuntimeRecoveryFail('Runtime recovery did not reach an approved readiness state.', [
            'target' => $target,
            'readiness_exit_code' => $readiness['exit_code'],
            'readiness' => $readinessPayload,
        ]);
    }

    $reconcile = nexoraRuntimeRecoveryReconcileReceipt($target);
    $mutationPerformed = true;
    $steps['receipt_reconcile'] = [
        'status' => 'pass',
        'receipt_sha256' => $reconcile['receipt_sha256'] ?? null,
        'installation_lock_sha256' => $reconcile['installation_lock_sha256'] ?? null,
    ];

    $readiness = nexoraRuntimeRecoveryPostInstallStatus($target, true);
    $readinessPayload = $readiness['payload'];
    if (! nexoraRuntimeRecoveryReady($readiness)) {
        nexoraRuntimeRecoveryFail('Post-install receipt reconciliation returned, but final readiness did not converge.', [
            'target' => $target,
            'readiness_exit_code' => $readiness['exit_code'],
            'readiness' => $readinessPayload,
        ]);
    }
}

$steps['readiness_final'] = [
    'exit_code' => 0,
    'status' => 'pass',
    'ready' => true,
    'runtime_ready' => true,
    'receipt_current' => true,
];

$baseUrl = trim((string) ($options['base-url'] ?? ''));
if ($baseUrl === '') {
    $baseUrl = trim((string) (nexoraRuntimeRecoveryResolveAppUrl($target) ?? ''));
}
$loginSmoke = $baseUrl === ''
    ? [
        'status' => 'blocked',
        'url' => '',
        'http_status' => null,
        'error' => 'Unable to resolve app.url from the target. Supply --base-url explicitly.',
    ]
    : nexoraRuntimeRecoveryLoginSmoke($baseUrl);
$steps['login_smoke'] = $loginSmoke;

$overallStatus = match ($loginSmoke['status']) {
    'pass' => 'pass',
    'fail' => 'fail',
    default => 'blocked',
};
$overallExitCode = match ($overallStatus) {
    'pass' => 0,
    'fail' => 1,
    default => 2,
};
$message = match ($overallStatus) {
    'pass' => 'Runtime recovery closure passed: compatibility, readiness, current handoff receipt and /login HTTP smoke are all PASS.',
    'fail' => 'Runtime compatibility/readiness passed, but /login returned an explicit failing HTTP/configuration result.',
    default => 'Runtime compatibility/readiness passed, but /login transport/TLS reachability could not be certified automatically.',
};

$receipt = nexoraRuntimeRecoveryWriteReceipt($target, [
    'status' => $overallStatus,
    'mode' => 'applied',
    'target' => $target,
    'runtime_version' => $compatibilityPayload['runtime']['current_version'] ?? null,
    'steps' => $steps,
    'mutation_performed' => $mutationPerformed,
    'target_verification_complete' => $overallStatus === 'pass',
]);
if ($receipt === null) {
    nexoraRuntimeRecoveryFail('Runtime closure checks completed, but the required machine-readable recovery receipt could not be written.', [
        'target' => $target,
        'result_status_before_evidence_failure' => $overallStatus,
        'steps' => $steps,
    ]);
}

nexoraRuntimeRecoveryEmit([
    'status' => $overallStatus,
    'mode' => 'applied',
    'message' => $message,
    'target' => $target,
    'steps' => $steps,
    'evidence_receipt' => $receipt,
    'mutation_performed' => $mutationPerformed,
    'target_verification_complete' => $overallStatus === 'pass',
], $overallExitCode);
