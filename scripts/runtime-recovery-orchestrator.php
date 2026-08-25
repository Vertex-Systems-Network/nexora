<?php

declare(strict_types=1);

/**
 * Nexora Runtime Recovery / Closure Orchestrator.
 *
 * This operator-side tool coordinates only already-authorized runtime recovery
 * primitives against one explicit target. It never copies/upgrades source,
 * installs dependencies, runs migrations/seeders, or changes the target release.
 *
 * Dry-run is the default. Apply mode requires the exact RECOVER-RUNTIME token.
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

    $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
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
function nexoraRuntimeRecoveryMismatches(array $payload): array
{
    $runtime = is_array($payload['runtime'] ?? null) ? $payload['runtime'] : [];
    $raw = $payload['mismatches'] ?? $runtime['mismatches'] ?? [];
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

/** @param array{exit_code:int,payload:array<string,mixed>} $result */
function nexoraRuntimeRecoveryCompatibilityPass(array $result): bool
{
    $payload = $result['payload'];
    $runtime = is_array($payload['runtime'] ?? null) ? $payload['runtime'] : [];

    return $result['exit_code'] === 0
        && ($payload['status'] ?? null) === 'pass'
        && nexoraRuntimeRecoveryMismatches($payload) === []
        && ($runtime['compatible'] ?? false) === true
        && ($runtime['mode'] ?? null) === 'installed-data-plane';
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

/** @param array{exit_code:int,payload:array<string,mixed>} $result */
function nexoraRuntimeRecoveryReady(array $result): bool
{
    $payload = $result['payload'];

    return $result['exit_code'] === 0
        && ($payload['status'] ?? null) === 'pass'
        && ($payload['ready'] ?? false) === true
        && ($payload['runtime_ready'] ?? false) === true
        && ($payload['receipt_current'] ?? false) === true
        && array_values((array) ($payload['errors'] ?? [])) === [];
}

function nexoraRuntimeRecoveryNeedsReceiptRefresh(array $payload): bool
{
    return ($payload['status'] ?? null) === 'receipt-refresh-required'
        && ($payload['ready'] ?? true) === false
        && ($payload['runtime_ready'] ?? false) === true
        && ($payload['receipt_current'] ?? true) === false;
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

function nexoraRuntimeRecoveryResolveTargetAppUrl(string $target): ?string
{
    $targetLiteral = var_export($target, true);
    $code = '$target='.$targetLiteral.';'
        .'require $target.DIRECTORY_SEPARATOR."vendor".DIRECTORY_SEPARATOR."autoload.php";'
        .'$app=require $target.DIRECTORY_SEPARATOR."bootstrap".DIRECTORY_SEPARATOR."app.php";'
        .'$kernel=$app->make("Illuminate\\\\Contracts\\\\Console\\\\Kernel");'
        .'$kernel->bootstrap();'
        .'fwrite(STDOUT,json_encode(["app_url"=>trim((string) config("app.url",""))],JSON_UNESCAPED_SLASHES));';
    $result = nexoraRuntimeRecoveryRun([PHP_BINARY, '-r', $code], $target);
    $payload = nexoraRuntimeRecoveryExtractJson($result['stdout'], $result['stderr']);
    if ($result['exit_code'] !== 0 || ! is_array($payload)) {
        return null;
    }

    $url = trim((string) ($payload['app_url'] ?? ''));

    return $url !== '' ? $url : null;
}

function nexoraRuntimeRecoverySafeBaseUrl(string $targetAppUrl): ?string
{
    $parts = parse_url($targetAppUrl);
    if (! is_array($parts)
        || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
        || trim((string) ($parts['host'] ?? '')) === ''
        || isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['query'])
        || isset($parts['fragment'])) {
        return null;
    }

    return rtrim($targetAppUrl, '/');
}

/** @param list<string> $headers @return array{status_code:?int,body:?string,headers:list<string>} */
function nexoraRuntimeRecoveryHttpGet(string $url, array $headers): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => 12,
            'header' => implode("\r\n", $headers)."\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $http_response_header = [];
    $body = @file_get_contents($url, false, $context);
    $responseHeaders = is_array($http_response_header ?? null)
        ? array_values(array_map(static fn ($header): string => (string) $header, $http_response_header))
        : [];
    $statusCode = null;
    if (isset($responseHeaders[0]) && preg_match('/\s(\d{3})(?:\s|$)/', $responseHeaders[0], $matches) === 1) {
        $statusCode = (int) $matches[1];
    }

    return [
        'status_code' => $statusCode,
        'body' => is_string($body) ? $body : null,
        'headers' => $responseHeaders,
    ];
}

/** @param list<string> $headers */
function nexoraRuntimeRecoveryHeaderValue(array $headers, string $name): ?string
{
    $prefix = strtolower($name).':';
    foreach ($headers as $header) {
        if (str_starts_with(strtolower($header), $prefix)) {
            return trim(substr($header, strlen($prefix)));
        }
    }

    return null;
}

/** @return array{status:string,token:?string,nonce:?string,source_set_fingerprint:?string,runtime_class_fingerprint:?string,error:?string} */
function nexoraRuntimeRecoveryIssueWebIdentityChallenge(string $target): array
{
    $targetLiteral = var_export($target, true);
    $kernelClass = var_export('Illuminate\\Contracts\\Console\\Kernel', true);
    $identityClass = var_export('App\\Nexora\\Installation\\SourceActivationIdentity', true);
    $handshakeClass = var_export('App\\Nexora\\Installation\\SourceActivationHandshake', true);
    $code = '$target='.$targetLiteral.';'
        .'require $target.DIRECTORY_SEPARATOR."vendor".DIRECTORY_SEPARATOR."autoload.php";'
        .'$app=require $target.DIRECTORY_SEPARATOR."bootstrap".DIRECTORY_SEPARATOR."app.php";'
        .'$kernel=$app->make('.$kernelClass.');'
        .'$kernel->bootstrap();'
        .'$identity=$app->make('.$identityClass.');'
        .'$handshake=$app->make('.$handshakeClass.');'
        .'$source=$identity->inspect();'
        .'if (($source["status"]??"fail")!=="pass") {'
        .'fwrite(STDERR,json_encode(["status"=>"fail","error"=>"critical source identity is not PASS"],JSON_UNESCAPED_SLASHES));exit(3);}'
        .'$receipt=$handshake->issueCliActivation($source);'
        .'$token=$handshake->webAckToken($source);'
        .'if (!is_string($token)||$token==="") {'
        .'fwrite(STDERR,json_encode(["status"=>"fail","error"=>"one-time web acknowledgement token was not issued"],JSON_UNESCAPED_SLASHES));exit(4);}'
        .'fwrite(STDOUT,json_encode(['
        .'"status"=>"pass","token"=>$token,"nonce"=>$receipt["nonce"]??null,'
        .'"source_set_fingerprint"=>$source["source_set_fingerprint"]??null,'
        .'"runtime_class_fingerprint"=>$source["runtime_class_fingerprint"]??null'
        .'],JSON_UNESCAPED_SLASHES));';

    $result = nexoraRuntimeRecoveryRun([PHP_BINARY, '-r', $code], $target);
    $payload = nexoraRuntimeRecoveryExtractJson($result['stdout'], $result['stderr']);
    if ($result['exit_code'] !== 0 || ! is_array($payload) || ($payload['status'] ?? null) !== 'pass') {
        return [
            'status' => 'fail',
            'token' => null,
            'nonce' => null,
            'source_set_fingerprint' => null,
            'runtime_class_fingerprint' => null,
            'error' => is_array($payload)
                ? (string) ($payload['error'] ?? 'Unable to issue exact-target web identity challenge.')
                : 'Unable to issue exact-target web identity challenge.',
        ];
    }

    $token = trim((string) ($payload['token'] ?? ''));
    $nonce = trim((string) ($payload['nonce'] ?? ''));
    if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1 || preg_match('/^[a-f0-9]{48}$/', $nonce) !== 1) {
        return [
            'status' => 'fail',
            'token' => null,
            'nonce' => null,
            'source_set_fingerprint' => null,
            'runtime_class_fingerprint' => null,
            'error' => 'Exact-target web identity challenge returned invalid secret/nonce material.',
        ];
    }

    return [
        'status' => 'pass',
        'token' => $token,
        'nonce' => $nonce,
        'source_set_fingerprint' => trim((string) ($payload['source_set_fingerprint'] ?? '')) ?: null,
        'runtime_class_fingerprint' => trim((string) ($payload['runtime_class_fingerprint'] ?? '')) ?: null,
        'error' => null,
    ];
}

/** @return array{status:string,url:string,http_status:?int,challenge_issued:bool,nonce:?string,source_set_fingerprint:?string,runtime_class_fingerprint:?string,error:?string} */
function nexoraRuntimeRecoveryWebIdentityProof(string $target, string $targetAppUrl): array
{
    $baseUrl = nexoraRuntimeRecoverySafeBaseUrl($targetAppUrl);
    $url = $baseUrl === null ? $targetAppUrl : $baseUrl.'/install/source-status';
    if ($baseUrl === null) {
        return [
            'status' => 'fail',
            'url' => $url,
            'http_status' => null,
            'challenge_issued' => false,
            'nonce' => null,
            'source_set_fingerprint' => null,
            'runtime_class_fingerprint' => null,
            'error' => 'Target-owned app.url is not a safe absolute HTTP(S) base URL for exact web identity proof.',
        ];
    }

    $preflight = nexoraRuntimeRecoveryHttpGet($url, [
        'Accept: application/json',
        'User-Agent: NexoraRuntimeRecovery/1',
        'Cache-Control: no-cache',
    ]);
    if ($preflight['status_code'] === null) {
        return [
            'status' => 'blocked',
            'url' => $url,
            'http_status' => null,
            'challenge_issued' => false,
            'nonce' => null,
            'source_set_fingerprint' => null,
            'runtime_class_fingerprint' => null,
            'error' => 'Unable to reach target-owned /install/source-status with verified transport before issuing a challenge.',
        ];
    }
    if ($preflight['status_code'] !== 200
        || strtolower((string) nexoraRuntimeRecoveryHeaderValue($preflight['headers'], 'X-Nexora-Source-Ack')) !== 'token-required') {
        return [
            'status' => 'fail',
            'url' => $url,
            'http_status' => $preflight['status_code'],
            'challenge_issued' => false,
            'nonce' => null,
            'source_set_fingerprint' => null,
            'runtime_class_fingerprint' => null,
            'error' => 'Target-owned source-status endpoint did not expose the expected fail-closed acknowledgement contract.',
        ];
    }

    $challenge = nexoraRuntimeRecoveryIssueWebIdentityChallenge($target);
    if (($challenge['status'] ?? 'fail') !== 'pass') {
        return [
            'status' => 'fail',
            'url' => $url,
            'http_status' => $preflight['status_code'],
            'challenge_issued' => true,
            'nonce' => null,
            'source_set_fingerprint' => null,
            'runtime_class_fingerprint' => null,
            'error' => (string) ($challenge['error'] ?? 'Unable to issue exact-target web identity challenge.'),
        ];
    }

    $token = (string) $challenge['token'];
    $nonce = (string) $challenge['nonce'];
    $ack = nexoraRuntimeRecoveryHttpGet($url, [
        'Accept: application/json',
        'User-Agent: NexoraRuntimeRecovery/1',
        'Cache-Control: no-cache',
        'X-Nexora-Activation-Token: '.$token,
    ]);
    unset($token, $challenge['token']);

    if ($ack['status_code'] === null) {
        return [
            'status' => 'blocked',
            'url' => $url,
            'http_status' => null,
            'challenge_issued' => true,
            'nonce' => $nonce,
            'source_set_fingerprint' => $challenge['source_set_fingerprint'],
            'runtime_class_fingerprint' => $challenge['runtime_class_fingerprint'],
            'error' => 'Exact-target web identity challenge was issued, but the acknowledgement transport/TLS request could not be completed.',
        ];
    }

    $ackPayload = is_string($ack['body'])
        ? nexoraRuntimeRecoveryExtractJson($ack['body'])
        : null;
    $ackHandshake = is_array($ackPayload['activation_handshake'] ?? null)
        ? $ackPayload['activation_handshake']
        : [];
    $httpAckPass = $ack['status_code'] === 200
        && strtolower((string) nexoraRuntimeRecoveryHeaderValue($ack['headers'], 'X-Nexora-Source-Ack')) === 'acknowledged'
        && is_array($ackPayload)
        && ($ackPayload['status'] ?? null) === 'pass'
        && ($ackPayload['diagnostic_detail'] ?? null) === 'authorized'
        && ($ackHandshake['acknowledgement_authorized'] ?? false) === true
        && hash_equals($nonce, (string) ($ackHandshake['nonce'] ?? ''));
    if (! $httpAckPass) {
        return [
            'status' => 'fail',
            'url' => $url,
            'http_status' => $ack['status_code'],
            'challenge_issued' => true,
            'nonce' => $nonce,
            'source_set_fingerprint' => $challenge['source_set_fingerprint'],
            'runtime_class_fingerprint' => $challenge['runtime_class_fingerprint'],
            'error' => 'The app.url web process did not acknowledge the exact target-local one-time source/runtime challenge.',
        ];
    }

    $local = nexoraRuntimeRecoveryRun([
        PHP_BINARY,
        $target.DIRECTORY_SEPARATOR.'artisan',
        'nexora:source:status',
        '--require-web-ack',
    ], $target);
    $localPayload = nexoraRuntimeRecoveryExtractJson($local['stdout'], $local['stderr']);
    $localHandshake = is_array($localPayload['activation_handshake'] ?? null)
        ? $localPayload['activation_handshake']
        : [];
    $localPass = $local['exit_code'] === 0
        && is_array($localPayload)
        && ($localPayload['status'] ?? null) === 'pass'
        && ($localHandshake['status'] ?? null) === 'pass'
        && ($localHandshake['web_ack_valid'] ?? false) === true
        && hash_equals($nonce, (string) ($localHandshake['nonce'] ?? ''))
        && hash_equals(
            (string) ($challenge['source_set_fingerprint'] ?? ''),
            (string) ($localPayload['source_set_fingerprint'] ?? ''),
        )
        && hash_equals(
            (string) ($challenge['runtime_class_fingerprint'] ?? ''),
            (string) ($localPayload['runtime_class_fingerprint'] ?? ''),
        );
    if (! $localPass) {
        return [
            'status' => 'fail',
            'url' => $url,
            'http_status' => 200,
            'challenge_issued' => true,
            'nonce' => $nonce,
            'source_set_fingerprint' => $challenge['source_set_fingerprint'],
            'runtime_class_fingerprint' => $challenge['runtime_class_fingerprint'],
            'error' => 'Web acknowledgement returned, but local CLI verification did not bind it to the exact target source/runtime generation.',
        ];
    }

    return [
        'status' => 'pass',
        'url' => $url,
        'http_status' => 200,
        'challenge_issued' => true,
        'nonce' => $nonce,
        'source_set_fingerprint' => $challenge['source_set_fingerprint'],
        'runtime_class_fingerprint' => $challenge['runtime_class_fingerprint'],
        'error' => null,
    ];
}

/** @return array{status:string,url:string,http_status:?int,error:?string} */
function nexoraRuntimeRecoveryLoginSmoke(string $targetAppUrl): array
{
    $baseUrl = nexoraRuntimeRecoverySafeBaseUrl($targetAppUrl);
    if ($baseUrl === null) {
        return [
            'status' => 'fail',
            'url' => $targetAppUrl,
            'http_status' => null,
            'error' => 'Target-owned app.url is not a safe absolute HTTP(S) base URL for the bounded /login smoke.',
        ];
    }

    $url = $baseUrl.'/login';
    $response = nexoraRuntimeRecoveryHttpGet($url, [
        'Accept: text/html',
        'User-Agent: NexoraRuntimeRecovery/1',
        'Cache-Control: no-cache',
    ]);

    if ($response['status_code'] === 200 && is_string($response['body'])) {
        return ['status' => 'pass', 'url' => $url, 'http_status' => 200, 'error' => null];
    }
    if ($response['status_code'] !== null) {
        return [
            'status' => 'fail',
            'url' => $url,
            'http_status' => $response['status_code'],
            'error' => 'Expected HTTP 200 from exact-target /login without following redirects.',
        ];
    }

    return [
        'status' => 'blocked',
        'url' => $url,
        'http_status' => null,
        'error' => 'Unable to complete verified exact-target HTTP(S) /login smoke. Check web-server reachability and PHP CA trust; TLS verification is not disabled.',
    ];
}

/** @return resource */
function nexoraRuntimeRecoveryAcquireApplyLock(string $target)
{
    if (! function_exists('flock')) {
        nexoraRuntimeRecoveryFail('Apply-mode runtime recovery requires flock for single-writer serialization.');
    }

    $directory = $target.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'nexora'
        .DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'recovery-orchestrator';
    if (! is_dir($directory) && ! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
        nexoraRuntimeRecoveryFail('Unable to create the protected runtime-recovery lock directory.', [
            'target' => $target,
            'directory' => $directory,
        ]);
    }

    $path = $directory.DIRECTORY_SEPARATOR.'.apply.lock';
    $handle = @fopen($path, 'c+');
    if (! is_resource($handle)) {
        nexoraRuntimeRecoveryFail('Unable to open the apply-mode recovery lock.', [
            'target' => $target,
            'lock_path' => $path,
        ]);
    }

    if (! @flock($handle, LOCK_EX | LOCK_NB)) {
        fclose($handle);
        nexoraRuntimeRecoveryFail('Another apply-mode runtime recovery is already active for this target.', [
            'target' => $target,
            'lock_path' => $path,
        ]);
    }

    $metadata = json_encode([
        'pid' => getmypid(),
        'started_at' => gmdate('c'),
        'target' => $target,
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    if (! @ftruncate($handle, 0) || ! @rewind($handle) || @fwrite($handle, $metadata) === false || ! @fflush($handle)) {
        @flock($handle, LOCK_UN);
        fclose($handle);
        nexoraRuntimeRecoveryFail('Unable to persist apply-mode recovery lock metadata.', [
            'target' => $target,
            'lock_path' => $path,
        ]);
    }

    register_shutdown_function(static function () use ($handle): void {
        if (is_resource($handle)) {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    });

    return $handle;
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
    $receiptId = bin2hex(random_bytes(6));
    $path = $directory.DIRECTORY_SEPARATOR.'runtime-recovery-'.gmdate('Ymd\\THis\\Z').'-'.$receiptId.'.json';
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

/** @param array<string,mixed> $steps */
function nexoraRuntimeRecoveryAppliedFailure(
    string $target,
    string $message,
    array $steps,
    bool $mutationPerformed,
    array $context = [],
): never {
    $receipt = nexoraRuntimeRecoveryWriteReceipt($target, [
        'status' => 'fail',
        'mode' => 'applied',
        'target' => $target,
        'steps' => $steps,
        'mutation_performed' => $mutationPerformed,
        'target_verification_complete' => false,
        'failure_context' => $context,
    ]);

    nexoraRuntimeRecoveryEmit([
        'status' => 'fail',
        'mode' => 'applied',
        'message' => $message,
        'target' => $target,
        'steps' => $steps,
        'mutation_performed' => $mutationPerformed,
        'target_verification_complete' => false,
        'evidence_receipt' => $receipt,
        ...$context,
    ], 1);
}

function nexoraRuntimeRecoveryUsage(): string
{
    return <<<'TEXT'
Nexora Runtime Recovery / Closure Orchestrator

Read-only dry-run:
  php scripts/runtime-recovery-orchestrator.php --target="D:\\laragon\\www\\nexora"

Authorized recovery + automatic verification/reconcile/web identity/login smoke:
  php scripts/runtime-recovery-orchestrator.php --target="D:\\laragon\\www\\nexora" --apply --confirm=RECOVER-RUNTIME

The final HTTP smoke is bound to the target application's own bootstrapped
config('app.url'). Before /login can PASS, the app.url web process must consume
a fresh one-time challenge issued from the exact target and the local CLI must
verify the same acknowledgement nonce/source/runtime generation.

Arbitrary URL overrides are intentionally unsupported. The one-time challenge
token is kept in-process only and is never written into the orchestrator receipt.

Apply mode is single-writer serialized for the explicit target. Recovery receipts
use unique identifiers so repeated runs cannot silently overwrite prior evidence.

The orchestrator never upgrades/copies source, installs dependencies, runs
migrations, or weakens TLS. Apply mode uses only existing runtime identity and
recovery primitives, independently re-verifies compatibility/readiness, reconciles
only the exact stale-receipt state, then requires exact-target /login HTTP 200.
TEXT;
}

$options = getopt('', ['target:', 'apply', 'confirm:', 'help']);
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
if ($apply) {
    nexoraRuntimeRecoveryAcquireApplyLock($target);
    $steps['apply_lock'] = ['status' => 'pass', 'mode' => 'exclusive-nonblocking'];
}

$compatibility = nexoraRuntimeRecoveryCompatibility($target);
$payload = $compatibility['payload'];
$runtime = is_array($payload['runtime'] ?? null) ? $payload['runtime'] : [];
$currentVersion = trim((string) ($runtime['current_version'] ?? ''));
$installedVersion = trim((string) ($runtime['installed_version'] ?? ''));
$mismatches = nexoraRuntimeRecoveryMismatches($payload);
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
        && ($payload['status'] ?? null) === 'fail';

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
        nexoraRuntimeRecoveryAppliedFailure(
            $target,
            'Recovery adapter returned, but independent deep compatibility did not converge.',
            $steps,
            $mutationPerformed,
            ['compatibility_exit_code' => $compatibility['exit_code'], 'compatibility' => $compatibility['payload']],
        );
    }
    $payload = $compatibility['payload'];
    $steps['compatibility_after'] = [
        'exit_code' => 0,
        'status' => 'pass',
        'mode' => $payload['runtime']['mode'] ?? null,
        'mismatches' => nexoraRuntimeRecoveryMismatches($payload),
    ];
} elseif (! $apply) {
    $readinessObserved = nexoraRuntimeRecoveryPostInstallStatus($target, false);
    $readyObserved = nexoraRuntimeRecoveryReady($readinessObserved);
    $refreshObserved = nexoraRuntimeRecoveryNeedsReceiptRefresh($readinessObserved['payload']);
    $steps['readiness_observed'] = [
        'exit_code' => $readinessObserved['exit_code'],
        'status' => $readinessObserved['payload']['status'] ?? null,
        'ready' => $readinessObserved['payload']['ready'] ?? false,
        'runtime_ready' => $readinessObserved['payload']['runtime_ready'] ?? false,
        'receipt_current' => $readinessObserved['payload']['receipt_current'] ?? false,
    ];

    if (! $readyObserved && ! $refreshObserved) {
        nexoraRuntimeRecoveryFail('Compatibility passes, but readiness is neither PASS nor the exact approved stale-receipt state.', [
            'target' => $target,
            'readiness_exit_code' => $readinessObserved['exit_code'],
            'readiness' => $readinessObserved['payload'],
        ]);
    }

    nexoraRuntimeRecoveryEmit([
        'status' => 'pass',
        'mode' => 'dry-run',
        'message' => 'Runtime compatibility/readiness planning passed. No mutation was performed.',
        'target' => $target,
        'steps' => $steps,
        'mutation_performed' => false,
        'next_action' => $refreshObserved
            ? 'Apply mode can reconcile the exact stale receipt, re-assert readiness, prove the app.url web process belongs to this target, and run /login smoke.'
            : 'Apply mode will re-assert readiness, prove the app.url web process belongs to this target, and run /login smoke; no identity repair/reconcile is planned.',
    ]);
}

$readiness = nexoraRuntimeRecoveryPostInstallStatus($target, true);
$steps['readiness_before_reconcile'] = [
    'exit_code' => $readiness['exit_code'],
    'status' => $readiness['payload']['status'] ?? null,
    'ready' => $readiness['payload']['ready'] ?? false,
    'runtime_ready' => $readiness['payload']['runtime_ready'] ?? false,
    'receipt_current' => $readiness['payload']['receipt_current'] ?? false,
];

if (! nexoraRuntimeRecoveryReady($readiness)) {
    if (! nexoraRuntimeRecoveryNeedsReceiptRefresh($readiness['payload'])) {
        nexoraRuntimeRecoveryAppliedFailure(
            $target,
            'Runtime recovery did not reach an approved readiness state.',
            $steps,
            $mutationPerformed,
            ['readiness_exit_code' => $readiness['exit_code'], 'readiness' => $readiness['payload']],
        );
    }

    $reconcile = nexoraRuntimeRecoveryReconcileReceipt($target);
    $mutationPerformed = true;
    $steps['receipt_reconcile'] = [
        'status' => 'pass',
        'receipt_sha256' => $reconcile['receipt_sha256'] ?? null,
        'installation_lock_sha256' => $reconcile['installation_lock_sha256'] ?? null,
    ];

    $readiness = nexoraRuntimeRecoveryPostInstallStatus($target, true);
    if (! nexoraRuntimeRecoveryReady($readiness)) {
        nexoraRuntimeRecoveryAppliedFailure(
            $target,
            'Receipt reconciliation returned, but final readiness did not converge.',
            $steps,
            $mutationPerformed,
            ['readiness_exit_code' => $readiness['exit_code'], 'readiness' => $readiness['payload']],
        );
    }
}

$steps['readiness_final'] = [
    'exit_code' => 0,
    'status' => 'pass',
    'ready' => true,
    'runtime_ready' => true,
    'receipt_current' => true,
];

$targetAppUrl = nexoraRuntimeRecoveryResolveTargetAppUrl($target);
$webIdentity = $targetAppUrl === null
    ? [
        'status' => 'blocked',
        'url' => '',
        'http_status' => null,
        'challenge_issued' => false,
        'nonce' => null,
        'source_set_fingerprint' => null,
        'runtime_class_fingerprint' => null,
        'error' => 'Unable to resolve target-owned config(app.url) for exact web identity proof.',
    ]
    : nexoraRuntimeRecoveryWebIdentityProof($target, $targetAppUrl);
if (($webIdentity['challenge_issued'] ?? false) === true) {
    $mutationPerformed = true;
}
$steps['web_identity_proof'] = $webIdentity;

$loginSmoke = ($webIdentity['status'] ?? 'blocked') === 'pass' && is_string($targetAppUrl)
    ? nexoraRuntimeRecoveryLoginSmoke($targetAppUrl)
    : [
        'status' => 'skipped',
        'url' => is_string($targetAppUrl) ? rtrim($targetAppUrl, '/').'/login' : '',
        'http_status' => null,
        'error' => 'Login smoke is not authoritative until exact target-to-web identity proof passes.',
    ];
$steps['login_smoke'] = $loginSmoke;

$overallStatus = match ($webIdentity['status'] ?? 'blocked') {
    'fail' => 'fail',
    'blocked' => 'blocked',
    'pass' => match ($loginSmoke['status']) {
        'pass' => 'pass',
        'fail' => 'fail',
        default => 'blocked',
    },
    default => 'blocked',
};
$overallExitCode = match ($overallStatus) {
    'pass' => 0,
    'fail' => 1,
    default => 2,
};
$message = match ($overallStatus) {
    'pass' => 'Runtime recovery closure passed: compatibility, readiness/current receipt, exact target-to-web identity proof and /login HTTP 200 are all PASS.',
    'fail' => 'Runtime compatibility/readiness passed, but exact target-to-web identity or /login returned an explicit failing result.',
    default => 'Runtime compatibility/readiness passed, but exact target-to-web identity and/or /login transport/TLS evidence could not be certified automatically.',
};

$receipt = nexoraRuntimeRecoveryWriteReceipt($target, [
    'status' => $overallStatus,
    'mode' => 'applied',
    'target' => $target,
    'target_app_url' => $targetAppUrl,
    'runtime_version' => $payload['runtime']['current_version'] ?? null,
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
    'target_app_url' => $targetAppUrl,
    'steps' => $steps,
    'evidence_receipt' => $receipt,
    'mutation_performed' => $mutationPerformed,
    'target_verification_complete' => $overallStatus === 'pass',
], $overallExitCode);
