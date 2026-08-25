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
        'Windows-safe child process capture',
        'status=blocked',
        'status=fail',
    ] as $needle) {
        if (! str_contains($docs, $needle)) {
            $errors[] = 'runtime recovery documentation missing required operator contract: '.$needle;
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, "[Nexora Runtime Recovery Contracts] FAIL\n");
    foreach (array_values(array_unique($errors)) as $error) {
        fwrite(STDERR, '- '.$error."\n");
    }
    exit(1);
}

fwrite(STDOUT, "[Nexora Runtime Recovery Contracts] PASS — dry-run/confirmation, exact rc.93 adapter, child-exit binding, Windows-safe child stdout/stderr capture, stale-receipt gate, exact target-to-web one-time challenge proof before /login, TLS verification, single-writer apply serialization, unique receipts and forbidden mutation boundaries are enforced.\n");
