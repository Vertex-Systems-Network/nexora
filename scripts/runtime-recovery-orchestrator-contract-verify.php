<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$orchestratorPath = $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'runtime-recovery-orchestrator.php';
$packagePath = $root.DIRECTORY_SEPARATOR.'package.json';
$docPath = $root.DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'RUNTIME_RECOVERY_ORCHESTRATOR.md';

$errors = [];
foreach ([$orchestratorPath, $packagePath, $docPath] as $path) {
    if (! is_file($path)) {
        $errors[] = 'missing required runtime recovery artifact: '.str_replace($root.DIRECTORY_SEPARATOR, '', $path);
    }
}

if ($errors === []) {
    $source = (string) file_get_contents($orchestratorPath);
    $package = json_decode((string) file_get_contents($packagePath), true, 512, JSON_THROW_ON_ERROR);
    $docs = (string) file_get_contents($docPath);

    $requiredSource = [
        "const NEXORA_RUNTIME_RECOVERY_CONFIRMATION = 'RECOVER-RUNTIME'",
        "const NEXORA_RUNTIME_RECOVERY_RC93_VERSION = '1.0.0-rc.93'",
        "'nexora:runtime:compatibility-status'",
        "'--deep'",
        "'nexora:runtime:post-install-status'",
        "'--assert-ready'",
        "'nexora:runtime:post-install-reconcile'",
        "'--confirm=RECONCILE'",
        "'rc93-post-install-identity-repair.php'",
        "'--confirm=REPAIR-RC93'",
        "return \$result['exit_code'] === 0",
        "'receipt-refresh-required'",
        "'runtime_ready'",
        "'receipt_current'",
        "nexoraRuntimeRecoveryResolveTargetAppUrl(\$target)",
        "config(\"app.url\"",
        "nexoraRuntimeRecoveryWebIdentityProof(\$target, \$targetAppUrl)",
        "'/install/source-status'",
        "'X-Nexora-Activation-Token: '.\$token",
        "'X-Nexora-Source-Ack'",
        "'token-required'",
        "'acknowledged'",
        "'App\\\\Nexora\\\\Installation\\\\SourceActivationIdentity'",
        "'App\\\\Nexora\\\\Installation\\\\SourceActivationHandshake'",
        "->issueCliActivation(\$source)",
        "'nexora:source:status'",
        "'--require-web-ack'",
        "'challenge_issued'",
        "unset(\$token, \$challenge['token'])",
        "Login smoke is not authoritative until exact target-to-web identity proof passes.",
        "'follow_location' => 0",
        "'verify_peer' => true",
        "'verify_peer_name' => true",
        "'fail' => 'fail'",
        "'fail' => 1",
        "default => 'blocked'",
        "nexoraRuntimeRecoveryAppliedFailure",
        "nexoraRuntimeRecoveryAppliedFailureContext()",
        "nexoraRuntimeRecoverySetAppliedFailureContext(static function () use (\$target, &\$steps, &\$mutationPerformed): array",
        "'failure_context' => \$context",
        "'evidence_write_status' => \$receipt === null ? 'fail' : 'pass'",
        "nexoraRuntimeRecoveryDirectory(\$target, true)",
        "realpath(\$candidate)",
        "hash_equals(\$candidatePath, \$resolvedPath)",
        "is_link(\$candidate)",
        "'target_verification_complete' => \$overallStatus === 'pass'",
        "['bypass_shell' => true]",
        "\$stderrHandle = @tmpfile()",
        "2 => \$stderrHandle",
        "@rewind(\$stderrHandle)",
        "fclose(\$stderrHandle)",
        "LOCK_EX | LOCK_NB",
        "'.apply.lock'",
        "'exclusive-nonblocking'",
        "Another apply-mode runtime recovery is already active for this target.",
        "bin2hex(random_bytes(6))",
    ];
    foreach ($requiredSource as $needle) {
        if (! str_contains($source, $needle)) {
            $errors[] = 'orchestrator missing required fail-closed contract: '.$needle;
        }
    }

    if (substr_count($source, "return \$result['exit_code'] === 0") < 2) {
        $errors[] = 'compatibility and readiness PASS must both bind to child exit code 0';
    }
    if (substr_count($source, 'flock(') < 2) {
        $errors[] = 'apply-mode single-writer lock must be acquired and released explicitly';
    }
    if (substr_count($source, "'evidence_write_status' => \$receipt === null ? 'fail' : 'pass'") < 2) {
        $errors[] = 'both generic post-lock apply failures and explicit applied failures must expose evidence-write status';
    }

    $directoryValidation = strpos($source, '$directory = nexoraRuntimeRecoveryDirectory($target, true);');
    $lockOpen = strpos($source, "$handle = @fopen($path, 'c+');");
    if ($directoryValidation === false || $lockOpen === false || $directoryValidation > $lockOpen) {
        $errors[] = 'recovery-storage containment must be validated before the apply lock file is opened';
    }

    $applyLockStep = strpos($source, "\$steps['apply_lock'] = ['status' => 'pass', 'mode' => 'exclusive-nonblocking'];");
    $applyFailureContext = strpos(
        $source,
        'nexoraRuntimeRecoverySetAppliedFailureContext(static function () use ($target, &$steps, &$mutationPerformed): array',
    );
    if ($applyLockStep === false || $applyFailureContext === false || $applyLockStep > $applyFailureContext) {
        $errors[] = 'receipt-aware generic failure context must activate only after the validated target apply lock is owned';
    }

    if (strpos($source, "\$steps['web_identity_proof'] = \$webIdentity;") === false
        || strpos($source, "\$steps['login_smoke'] = \$loginSmoke;") === false
        || strpos($source, "\$steps['web_identity_proof'] = \$webIdentity;") > strpos($source, "\$steps['login_smoke'] = \$loginSmoke;")) {
        $errors[] = 'exact target-to-web identity proof must precede authoritative login smoke';
    }

    if (str_contains($source, "2 => ['pipe', 'w']")) {
        $errors[] = 'stderr must not use a second anonymous child pipe; that can deadlock on Windows when stderr fills before stdout reaches EOF';
    }
    if (strpos($source, '$exitCode = proc_close($process);') === false
        || strpos($source, '@rewind($stderrHandle)') === false
        || strpos($source, '$exitCode = proc_close($process);') > strpos($source, '@rewind($stderrHandle)')) {
        $errors[] = 'transient stderr capture must be read only after the child process has closed';
    }

    $forbiddenSource = [
        "'base-url:'",
        '--base-url=',
        "'verify_peer' => false",
        "'verify_peer_name' => false",
        'composer install',
        'composer update',
        'npm install',
        'npm ci',
        'git pull',
        'git checkout',
        'artisan migrate',
        'migrate --force',
        'shell_exec(',
        'system(',
        'passthru(',
    ];
    foreach ($forbiddenSource as $needle) {
        if (str_contains($source, $needle)) {
            $errors[] = 'orchestrator contains forbidden behavior/escape hatch: '.$needle;
        }
    }

    if (($package['scripts']['runtime:recover'] ?? null) !== 'php scripts/runtime-recovery-orchestrator.php') {
        $errors[] = 'package.json runtime:recover must point only to the canonical orchestrator';
    }

    foreach ([
        'Dry-run is the default',
        '--apply --confirm=RECOVER-RUNTIME',
        "target application's own bootstrapped `config('app.url')`",
        'Arbitrary HTTP target overrides are intentionally unsupported',
        'one-time',
        'exact target',
        'never written into the recovery receipt',
        'single-writer',
        'unique',
        'post-lock apply failures',
        'evidence_write_status',
        'symlink/junction',
        'filesystem redirection',
        'Windows-safe child process capture',
        'status=blocked',
        'status=fail',
    ] as $needle) {
        if (! str_contains($docs, $needle)) {
            $errors[] = 'runtime recovery documentation missing required operator contract: '.$needle;
        }
    }
}

$removeTree = static function (string $path): void {
    if (is_link($path)) {
        @unlink($path);

        return;
    }
    if (! is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $item) {
        $itemPath = $item->getPathname();
        if ($item->isLink() || $item->isFile()) {
            @unlink($itemPath);
        } else {
            @rmdir($itemPath);
        }
    }
    @rmdir($path);
};

/** @return array{exit_code:int,stdout:string,stderr:string} */
$runApplyFailureProbe = static function (string $target) use ($orchestratorPath, $root): array {
    $stderrHandle = @tmpfile();
    if (! is_resource($stderrHandle)) {
        return ['exit_code' => 127, 'stdout' => '', 'stderr' => 'unable to create probe stderr capture'];
    }

    $pipes = [];
    $process = @proc_open([
        PHP_BINARY,
        $orchestratorPath,
        '--target='.$target,
        '--apply',
        '--confirm=RECOVER-RUNTIME',
    ], [
        1 => ['pipe', 'w'],
        2 => $stderrHandle,
    ], $pipes, $root, null, ['bypass_shell' => true]);
    if (! is_resource($process)) {
        fclose($stderrHandle);

        return ['exit_code' => 127, 'stdout' => '', 'stderr' => 'unable to start orchestrator probe'];
    }

    $stdout = is_resource($pipes[1] ?? null) ? trim((string) stream_get_contents($pipes[1])) : '';
    if (is_resource($pipes[1] ?? null)) {
        fclose($pipes[1]);
    }
    $exitCode = proc_close($process);

    $stderr = '';
    if (@rewind($stderrHandle)) {
        $stderr = trim((string) stream_get_contents($stderrHandle));
    }
    fclose($stderrHandle);

    return [
        'exit_code' => is_int($exitCode) ? $exitCode : 1,
        'stdout' => $stdout,
        'stderr' => $stderr,
    ];
};

if ($errors === []) {
    if (! function_exists('proc_open')) {
        $errors[] = 'behavioral apply-failure evidence probe requires proc_open';
    } else {
        $probeTarget = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-runtime-recovery-contract-'.bin2hex(random_bytes(6));
        try {
            $vendorDirectory = $probeTarget.DIRECTORY_SEPARATOR.'vendor';
            $bootstrapDirectory = $probeTarget.DIRECTORY_SEPARATOR.'bootstrap';
            if ((! @mkdir($vendorDirectory, 0700, true) && ! is_dir($vendorDirectory))
                || (! @mkdir($bootstrapDirectory, 0700, true) && ! is_dir($bootstrapDirectory))) {
                $errors[] = 'unable to create behavioral runtime-recovery probe target';
            } else {
                $artisan = $probeTarget.DIRECTORY_SEPARATOR.'artisan';
                $autoload = $vendorDirectory.DIRECTORY_SEPARATOR.'autoload.php';
                $app = $bootstrapDirectory.DIRECTORY_SEPARATOR.'app.php';
                if (@file_put_contents($artisan, "<?php\nfwrite(STDOUT, \"not-json\\n\");\nexit(9);\n") === false
                    || @file_put_contents($autoload, "<?php\n") === false
                    || @file_put_contents($app, "<?php\n") === false) {
                    $errors[] = 'unable to populate behavioral runtime-recovery probe target';
                } else {
                    $receipts = [];
                    foreach ([$runApplyFailureProbe($probeTarget), $runApplyFailureProbe($probeTarget)] as $index => $probe) {
                        if ($probe['exit_code'] !== 1 || $probe['stdout'] !== '') {
                            $errors[] = 'post-lock apply-failure probe #'.($index + 1).' did not fail closed with exit 1 and empty stdout';
                            continue;
                        }

                        try {
                            $payload = json_decode($probe['stderr'], true, 512, JSON_THROW_ON_ERROR);
                        } catch (Throwable) {
                            $payload = null;
                        }
                        if (! is_array($payload)) {
                            $errors[] = 'post-lock apply-failure probe #'.($index + 1).' did not emit parseable JSON failure evidence';
                            continue;
                        }

                        if (($payload['status'] ?? null) !== 'fail'
                            || ($payload['mode'] ?? null) !== 'applied'
                            || ($payload['target_verification_complete'] ?? true) !== false
                            || ($payload['mutation_performed'] ?? true) !== false
                            || ($payload['steps']['apply_lock']['status'] ?? null) !== 'pass'
                            || ($payload['evidence_write_status'] ?? null) !== 'pass'
                            || ($payload['failure_context']['exit_code'] ?? null) !== 9
                            || ($payload['failure_context']['stdout'] ?? null) !== 'not-json') {
                            $errors[] = 'post-lock apply-failure probe #'.($index + 1).' did not preserve the required failure/lock/evidence context';
                            continue;
                        }

                        $receiptPath = $payload['evidence_receipt'] ?? null;
                        $expectedPrefix = realpath($probeTarget).DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'
                            .DIRECTORY_SEPARATOR.'nexora'.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'recovery-orchestrator'
                            .DIRECTORY_SEPARATOR;
                        if (! is_string($receiptPath)
                            || ! str_starts_with($receiptPath, $expectedPrefix)
                            || ! is_file($receiptPath)) {
                            $errors[] = 'post-lock apply-failure probe #'.($index + 1).' did not create a protected target-owned receipt';
                            continue;
                        }

                        try {
                            $receipt = json_decode((string) file_get_contents($receiptPath), true, 512, JSON_THROW_ON_ERROR);
                        } catch (Throwable) {
                            $receipt = null;
                        }
                        if (! is_array($receipt)
                            || ($receipt['status'] ?? null) !== 'fail'
                            || ($receipt['mode'] ?? null) !== 'applied'
                            || ($receipt['target_verification_complete'] ?? true) !== false
                            || ($receipt['steps']['apply_lock']['status'] ?? null) !== 'pass'
                            || ($receipt['failure_context']['exit_code'] ?? null) !== 9) {
                            $errors[] = 'post-lock apply-failure probe #'.($index + 1).' receipt payload is incomplete or not fail-closed';
                            continue;
                        }

                        $storedSeal = $receipt['receipt_sha256'] ?? null;
                        unset($receipt['receipt_sha256']);
                        ksort($receipt, SORT_STRING);
                        $calculatedSeal = hash(
                            'sha256',
                            json_encode($receipt, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                        );
                        if (! is_string($storedSeal) || ! hash_equals($storedSeal, $calculatedSeal)) {
                            $errors[] = 'post-lock apply-failure probe #'.($index + 1).' receipt integrity seal is invalid';
                            continue;
                        }

                        $receipts[] = $receiptPath;
                    }

                    if (count($receipts) === 2 && hash_equals($receipts[0], $receipts[1])) {
                        $errors[] = 'sequential post-lock apply failures overwrote/reused the same receipt path';
                    }
                    $diskReceipts = glob(
                        $probeTarget.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'nexora'
                            .DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'recovery-orchestrator'.DIRECTORY_SEPARATOR
                            .'runtime-recovery-*.json',
                    );
                    if (count(array_values(array_filter(is_array($diskReceipts) ? $diskReceipts : [], 'is_file'))) !== 2) {
                        $errors[] = 'behavioral post-lock apply-failure probe did not preserve exactly two unique receipts';
                    }
                }
            }
        } finally {
            $removeTree($probeTarget);
        }
    }
}

if ($errors === [] && PHP_OS_FAMILY !== 'Windows' && function_exists('symlink') && function_exists('proc_open')) {
    $redirectTarget = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-runtime-recovery-redirect-target-'.bin2hex(random_bytes(6));
    $outsideDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nexora-runtime-recovery-redirect-outside-'.bin2hex(random_bytes(6));
    try {
        $vendorDirectory = $redirectTarget.DIRECTORY_SEPARATOR.'vendor';
        $bootstrapDirectory = $redirectTarget.DIRECTORY_SEPARATOR.'bootstrap';
        $runtimeDirectory = $redirectTarget.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'nexora'
            .DIRECTORY_SEPARATOR.'runtime';
        if ((! @mkdir($vendorDirectory, 0700, true) && ! is_dir($vendorDirectory))
            || (! @mkdir($bootstrapDirectory, 0700, true) && ! is_dir($bootstrapDirectory))
            || (! @mkdir($runtimeDirectory, 0700, true) && ! is_dir($runtimeDirectory))
            || (! @mkdir($outsideDirectory, 0700, true) && ! is_dir($outsideDirectory))) {
            $errors[] = 'unable to create filesystem-redirection behavioral probe directories';
        } else {
            $artisan = $redirectTarget.DIRECTORY_SEPARATOR.'artisan';
            $autoload = $vendorDirectory.DIRECTORY_SEPARATOR.'autoload.php';
            $app = $bootstrapDirectory.DIRECTORY_SEPARATOR.'app.php';
            $redirectPath = $runtimeDirectory.DIRECTORY_SEPARATOR.'recovery-orchestrator';
            if (@file_put_contents($artisan, "<?php\nfwrite(STDOUT, \"should-not-run\\n\");\nexit(9);\n") === false
                || @file_put_contents($autoload, "<?php\n") === false
                || @file_put_contents($app, "<?php\n") === false
                || ! @symlink($outsideDirectory, $redirectPath)) {
                $errors[] = 'unable to populate filesystem-redirection behavioral probe';
            } else {
                $probe = $runApplyFailureProbe($redirectTarget);
                try {
                    $payload = json_decode($probe['stderr'], true, 512, JSON_THROW_ON_ERROR);
                } catch (Throwable) {
                    $payload = null;
                }
                $outsideEntries = array_values(array_diff((array) @scandir($outsideDirectory), ['.', '..']));
                if ($probe['exit_code'] !== 1
                    || $probe['stdout'] !== ''
                    || ! is_array($payload)
                    || ($payload['status'] ?? null) !== 'fail'
                    || ! str_contains((string) ($payload['message'] ?? ''), 'protected target-owned runtime-recovery directory')
                    || array_key_exists('mode', $payload)
                    || array_key_exists('evidence_receipt', $payload)
                    || $outsideEntries !== []) {
                    $errors[] = 'filesystem-redirection probe did not fail before lock ownership without writing through the redirected path';
                }
            }
        }
    } finally {
        $removeTree($redirectTarget);
        $removeTree($outsideDirectory);
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Runtime Recovery Contracts] FAIL\n");
    foreach (array_values(array_unique($errors)) as $error) {
        fwrite(STDERR, '- '.$error."\n");
    }
    exit(1);
}

fwrite(STDOUT, "[Nexora Runtime Recovery Contracts] PASS — dry-run/confirmation, exact rc.93 adapter, child-exit binding, Windows-safe child stdout/stderr capture, stale-receipt gate, behaviorally verified post-lock apply-failure evidence receipts, fail-closed recovery-storage symlink/junction redirection controls, exact target-to-web one-time challenge proof before /login, TLS verification, single-writer apply serialization, unique receipts and forbidden mutation boundaries are enforced.\n");
