<?php

declare(strict_types=1);

function nexoraMutationEvidenceFail(string $message): never
{
    fwrite(STDERR, '[Nexora Runtime Recovery Mutation Evidence Contract] FAIL — '.$message.PHP_EOL);
    exit(1);
}

function nexoraMutationEvidenceRemoveTree(string $path): void
{
    if (! file_exists($path) && ! is_link($path)) {
        return;
    }
    if (is_file($path) || is_link($path)) {
        @unlink($path);

        return;
    }

    $items = scandir($path);
    if (is_array($items)) {
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            nexoraMutationEvidenceRemoveTree($path.DIRECTORY_SEPARATOR.$item);
        }
    }
    @rmdir($path);
}

/** @param list<string> $command @return array{exit_code:int,stdout:string,stderr:string} */
function nexoraMutationEvidenceRun(array $command, string $cwd): array
{
    $descriptors = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $pipes = [];
    $process = proc_open($command, $descriptors, $pipes, $cwd, null, ['bypass_shell' => true]);
    if (! is_resource($process)) {
        nexoraMutationEvidenceFail('Unable to start the disposable orchestrator probe.');
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

/** @return array<string,mixed> */
function nexoraMutationEvidenceDecode(string $json, string $label): array
{
    try {
        $decoded = json_decode($json, true, 256, JSON_THROW_ON_ERROR);
    } catch (Throwable $exception) {
        nexoraMutationEvidenceFail($label.' is not valid JSON: '.$exception->getMessage());
    }
    if (! is_array($decoded)) {
        nexoraMutationEvidenceFail($label.' did not decode to an object.');
    }

    return $decoded;
}

$orchestrator = __DIR__.DIRECTORY_SEPARATOR.'runtime-recovery-orchestrator.php';
if (! is_file($orchestrator)) {
    nexoraMutationEvidenceFail('runtime-recovery-orchestrator.php is missing.');
}
if (! function_exists('proc_open')) {
    nexoraMutationEvidenceFail('proc_open is required for the behavioral mutation-evidence probe.');
}

$root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-mutation-evidence-'.bin2hex(random_bytes(8));
$target = $root.DIRECTORY_SEPARATOR.'target';
$vendor = $target.DIRECTORY_SEPARATOR.'vendor';
$bootstrap = $target.DIRECTORY_SEPARATOR.'bootstrap';
$marker = $target.DIRECTORY_SEPARATOR.'synthetic-reconcile-mutation.txt';

try {
    foreach ([$target, $vendor, $bootstrap] as $directory) {
        if (! @mkdir($directory, 0700, true) && ! is_dir($directory)) {
            nexoraMutationEvidenceFail('Unable to create disposable probe directory '.$directory.'.');
        }
    }

    if (@file_put_contents($vendor.DIRECTORY_SEPARATOR.'autoload.php', "<?php\n") === false
        || @file_put_contents($bootstrap.DIRECTORY_SEPARATOR.'app.php', "<?php\nreturn null;\n") === false) {
        nexoraMutationEvidenceFail('Unable to create disposable target runtime placeholders.');
    }

    $markerLiteral = var_export($marker, true);
    $artisan = <<<'PHP'
<?php

declare(strict_types=1);

$command = (string) ($argv[1] ?? '');
if ($command === 'nexora:runtime:compatibility-status') {
    echo json_encode([
        'status' => 'pass',
        'mismatches' => [],
        'runtime' => [
            'compatible' => true,
            'mode' => 'installed-data-plane',
            'current_version' => '1.0.0-rc.94',
            'installed_version' => '1.0.0-rc.94',
            'mismatches' => [],
        ],
    ], JSON_UNESCAPED_SLASHES);
    exit(0);
}

if ($command === 'nexora:runtime:post-install-status') {
    echo json_encode([
        'status' => 'receipt-refresh-required',
        'ready' => false,
        'runtime_ready' => true,
        'receipt_current' => false,
        'errors' => ['synthetic stale receipt'],
    ], JSON_UNESCAPED_SLASHES);
    exit(1);
}

if ($command === 'nexora:runtime:post-install-reconcile') {
    file_put_contents(__MARKER__, "mutation happened before synthetic failure\n");
    echo json_encode([
        'status' => 'fail',
        'message' => 'synthetic failure after mutation',
    ], JSON_UNESCAPED_SLASHES);
    exit(9);
}

fwrite(STDERR, json_encode(['status' => 'fail', 'unexpected_command' => $command], JSON_UNESCAPED_SLASHES));
exit(19);
PHP;
    $artisan = str_replace('__MARKER__', $markerLiteral, $artisan);
    if (@file_put_contents($target.DIRECTORY_SEPARATOR.'artisan', $artisan) === false) {
        nexoraMutationEvidenceFail('Unable to create disposable target artisan probe.');
    }

    $result = nexoraMutationEvidenceRun([
        PHP_BINARY,
        $orchestrator,
        '--target='.$target,
        '--apply',
        '--confirm=RECOVER-RUNTIME',
    ], __DIR__);

    if ($result['exit_code'] !== 1) {
        nexoraMutationEvidenceFail('Expected orchestrator exit 1 after the synthetic post-mutation reconcile failure; got '.$result['exit_code'].'.');
    }
    if ($result['stdout'] !== '') {
        nexoraMutationEvidenceFail('Failure probe unexpectedly emitted success-channel stdout.');
    }
    if (! is_file($marker)) {
        nexoraMutationEvidenceFail('Synthetic reconcile mutation marker was not created; the probe did not exercise partial mutation.');
    }

    $failure = nexoraMutationEvidenceDecode($result['stderr'], 'orchestrator failure output');
    if (($failure['status'] ?? null) !== 'fail'
        || ($failure['mode'] ?? null) !== 'applied'
        || ($failure['mutation_attempted'] ?? false) !== true
        || ($failure['mutation_performed'] ?? true) !== false
        || ($failure['mutation_may_have_occurred'] ?? false) !== true
        || ($failure['evidence_write_status'] ?? null) !== 'pass'
        || ($failure['target_verification_complete'] ?? true) !== false) {
        nexoraMutationEvidenceFail('Failure output did not conservatively represent the uncertain mutation state.');
    }
    if (($failure['steps']['receipt_reconcile']['status'] ?? null) !== 'attempted'
        || ($failure['steps']['receipt_reconcile']['mutation_performed'] ?? true) !== false) {
        nexoraMutationEvidenceFail('Failure output did not preserve the attempted reconcile step.');
    }
    if (($failure['failure_context']['exit_code'] ?? null) !== 9) {
        nexoraMutationEvidenceFail('Failure output did not preserve the synthetic reconcile child exit code.');
    }

    $receiptPath = (string) ($failure['evidence_receipt'] ?? '');
    $expectedDirectory = $target.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'nexora'
        .DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'recovery-orchestrator';
    if ($receiptPath === '' || ! is_file($receiptPath) || dirname($receiptPath) !== $expectedDirectory) {
        nexoraMutationEvidenceFail('Protected failure receipt is missing or outside the exact target-owned recovery directory.');
    }

    $receiptBytes = file_get_contents($receiptPath);
    if (! is_string($receiptBytes)) {
        nexoraMutationEvidenceFail('Unable to read the protected failure receipt.');
    }
    $receipt = nexoraMutationEvidenceDecode($receiptBytes, 'protected failure receipt');
    if (($receipt['status'] ?? null) !== 'fail'
        || ($receipt['mutation_attempted'] ?? false) !== true
        || ($receipt['mutation_performed'] ?? true) !== false
        || ($receipt['mutation_may_have_occurred'] ?? false) !== true
        || ($receipt['target_verification_complete'] ?? true) !== false
        || ($receipt['steps']['receipt_reconcile']['status'] ?? null) !== 'attempted') {
        nexoraMutationEvidenceFail('Protected failure receipt did not preserve conservative mutation evidence.');
    }

    $expectedSeal = strtolower(trim((string) ($receipt['receipt_sha256'] ?? '')));
    if (preg_match('/^[a-f0-9]{64}$/', $expectedSeal) !== 1) {
        nexoraMutationEvidenceFail('Protected failure receipt does not contain a valid SHA-256 seal.');
    }
    $copy = $receipt;
    unset($copy['receipt_sha256']);
    ksort($copy, SORT_STRING);
    $actualSeal = hash('sha256', json_encode($copy, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    if (! hash_equals($expectedSeal, $actualSeal)) {
        nexoraMutationEvidenceFail('Protected failure receipt SHA-256 seal does not verify.');
    }

    fwrite(STDOUT, '[Nexora Runtime Recovery Mutation Evidence Contract] PASS — a mutating reconcile child that changes target state then exits non-zero is recorded as attempted with mutation_may_have_occurred=true; failure evidence never claims a clean no-mutation outcome.'.PHP_EOL);
} finally {
    nexoraMutationEvidenceRemoveTree($root);
}
