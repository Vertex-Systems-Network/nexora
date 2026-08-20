<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/pkg1-closure.php';

$operator = '';
$reviewer = '';
$baseUrl = '';
$promoteReviewed = false;
$statusOnly = false;
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--operator=')) {
        $operator = trim(substr($argument, 11));
    } elseif (str_starts_with($argument, '--reviewer=')) {
        $reviewer = trim(substr($argument, 11));
    } elseif (str_starts_with($argument, '--base-url=')) {
        $baseUrl = rtrim(trim(substr($argument, 11)), '/');
    } elseif ($argument === '--promote-reviewed') {
        $promoteReviewed = true;
    } elseif ($argument === '--status-only') {
        $statusOnly = true;
    }
}

$evidencePath = $root.'/storage/app/nexora/pkg1/latest.json';
if ($statusOnly) {
    $command = [PHP_BINARY, 'scripts/pkg1-status.php'];
    if ($baseUrl !== '') {
        $command[] = '--base-url='.$baseUrl;
    }
    $result = nexoraPkg1Run($command, $root, NexoraBootstrapProcessEnvironment::build($root, $_ENV));
    if ($result['stdout'] !== '') fwrite(STDOUT, $result['stdout'].(str_ends_with($result['stdout'], "\n") ? '' : "\n"));
    if ($result['stderr'] !== '') fwrite(STDERR, $result['stderr'].(str_ends_with($result['stderr'], "\n") ? '' : "\n"));
    exit($result['exit_code']);
}

if ($operator === '' || strlen($operator) > 120) {
    fwrite(STDERR, "[PKG-1] --operator=<name> is required and must be <=120 characters.\n");
    exit(2);
}
if ($baseUrl === '') {
    $baseUrl = rtrim((string) (getenv('APP_URL') ?: 'http://nexora'), '/');
}

$environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);
$environment['NEXORA_PKG1_BASE_URL'] = $baseUrl;
$state = nexoraPkg1BaseState($root);
$state['operator'] = $operator;
$state['base_url'] = $baseUrl;
$state['acceptance'] = [
    'c1' => '14/14',
    'installer' => '100%',
    'installation_lock' => 'valid',
    'post_install_handoff' => 'pass',
    'live_login_admin_smoke' => 'pass',
];

$record = static function (
    string $id,
    string $label,
    array $command,
    int $progress,
    bool $required = true,
) use ($root, $environment, &$state): array {
    fwrite(STDOUT, "\n[PKG-1] {$label}\n");
    $result = nexoraPkg1Run($command, $root, $environment);
    $stdout = nexoraPkg1Redact($result['stdout']);
    $stderr = nexoraPkg1Redact($result['stderr']);
    if ($stdout !== '') {
        fwrite(STDOUT, $stdout.(str_ends_with($stdout, "\n") ? '' : "\n"));
    }
    if ($stderr !== '') {
        fwrite(STDERR, $stderr.(str_ends_with($stderr, "\n") ? '' : "\n"));
    }
    $passed = $result['exit_code'] === 0;
    $state['steps'][$id] = [
        'label' => $label,
        'status' => $passed ? 'pass' : ($required ? 'fail' : 'waiting'),
        'exit_code' => $result['exit_code'],
        'duration_seconds' => $result['duration_seconds'],
        'stdout_excerpt' => substr(trim($stdout), 0, 1400),
        'stderr_excerpt' => substr(trim($stderr), 0, 1400),
    ];
    if ($passed) {
        $state['progress_percent'] = max((int) $state['progress_percent'], $progress);
    } elseif ($required && $state['first_blocker'] === null) {
        $state['first_blocker'] = [
            'id' => $id,
            'label' => $label,
            'exit_code' => $result['exit_code'],
            'detail' => substr(trim($stderr !== '' ? $stderr : $stdout), 0, 1200),
        ];
    }
    nexoraPkg1Persist($root, $state);

    return $result;
};

$finish = static function (string $status, string $phase, int $progress, string $message) use ($root, &$state): never {
    $state['status'] = $status;
    $state['phase'] = $phase;
    $state['progress_percent'] = $progress;
    $state['message'] = $message;
    nexoraPkg1Persist($root, $state);
    fwrite(STDOUT, "\n".nexoraPkg1Render($state)."\n{$message}\n");
    exit($status === 'pass' ? 0 : 2);
};

// A sealed exact-target closure is terminal. Verify it before any dependency
// or network work so an already-usable installation remains resumable offline.
if (is_file($root.'/storage/app/nexora/pkg1/closure.json') && is_file($root.'/vendor/autoload.php')) {
    $closureFast = $record(
        'closure-fast-resume',
        'Verify existing sealed PKG-1 closure before dependency/network work',
        [PHP_BINARY, 'scripts/pkg1-closure-evidence-verify.php'],
        100,
        false,
    );
    if ($closureFast['exit_code'] === 0) {
        $finish(
            'pass',
            'complete',
            100,
            'PKG-1 COMPLETE — existing sealed closure remains valid; dependency/network stages were skipped.',
        );
    }
}

$journalPath = $root.'/storage/app/nexora/dependency-intake/lock-promotion-journal.json';
if (is_file($journalPath)) {
    try {
        $journal = json_decode((string) file_get_contents($journalPath), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        $journal = ['status' => 'invalid'];
    }
    if (! in_array((string) ($journal['status'] ?? ''), ['complete', 'rolled-back'], true)) {
        $state['first_blocker'] = [
            'id' => 'dependency-promotion-journal',
            'label' => 'Incomplete dependency lock promotion journal',
            'detail' => (string) ($journal['status'] ?? 'invalid'),
        ];
        $finish(
            'waiting-recovery',
            'dependency-locks',
            10,
            'Run `scripts\\recover-dependency-lock-promotion.bat --confirm=ROLLBACK`, then rerun PKG-1.',
        );
    }
}

$c1Reusable = false;
if (is_file($root.'/storage/app/nexora/n1-c1/latest.json')
    && is_file($root.'/vendor/autoload.php')
    && is_dir($root.'/node_modules')) {
    $state['phase'] = 'c1';
    nexoraPkg1Persist($root, $state);
    $c1ResumeEarly = $record(
        'c1-fast-resume',
        'Verify reusable exact-source C1 PASS before dependency/network work',
        [PHP_BINARY, 'scripts/n1-c1-evidence-verify.php'],
        65,
        false,
    );
    $c1Reusable = $c1ResumeEarly['exit_code'] === 0;
}

if (! $c1Reusable) {
    $state['phase'] = 'dependency-locks';
    nexoraPkg1Persist($root, $state);

    $reviewedAttestationPath = $root.'/storage/app/nexora/dependency-intake/reviewed-locks.json';
    $candidateState = nexoraPkg1DependencyCandidateState($root);
    if ($candidateState['present'] && ! $candidateState['valid']) {
        try {
            $quarantine = nexoraPkg1QuarantineStaleCandidate($root);
            $state['steps']['stale-candidate-quarantine'] = [
                'label' => 'Quarantine stale dependency candidate before network/toolchain work',
                'status' => 'pass',
                'exit_code' => 0,
                'duration_seconds' => 0.0,
                'stdout_excerpt' => $quarantine !== null ? 'quarantined='.$quarantine : 'nothing-to-quarantine',
                'stderr_excerpt' => '',
            ];
            nexoraPkg1Persist($root, $state);
        } catch (Throwable $exception) {
            $state['first_blocker'] ??= [
                'id' => 'stale-candidate-quarantine',
                'label' => 'Quarantine stale dependency candidate before network/toolchain work',
                'exit_code' => 2,
                'detail' => substr($exception->getMessage(), 0, 1200),
            ];
            $finish('blocked', 'dependency-locks', 12, 'Stale dependency candidate could not be quarantined safely; root locks remain untouched.');
        }
        $candidateState = nexoraPkg1DependencyCandidateState($root);
    }

    // Human review itself must not require Composer or registry access. If a
    // valid unpromoted candidate already exists, stop here before networking.
    if ($candidateState['valid'] && ! is_file($reviewedAttestationPath) && ! $promoteReviewed) {
        $finish(
            'waiting-review',
            'dependency-locks',
            20,
            'Review `storage/app/nexora/dependency-intake/lock-refresh.md` and both candidate lockfiles. Then rerun this same PKG-1 command with `--promote-reviewed --reviewer="REAL NAME"`.',
        );
    }
    if ($candidateState['valid'] && ! is_file($reviewedAttestationPath) && $promoteReviewed
        && ($reviewer === '' || strlen($reviewer) > 120)) {
        $finish(
            'waiting-review',
            'dependency-locks',
            20,
            '`--reviewer="REAL NAME"` is required with `--promote-reviewed` after human review.',
        );
    }

    // From here onward generation, promotion, reviewed-attestation validation
    // or C1 will need the exact Composer toolchain.
    $composerBootstrap = $record(
        'composer-bootstrap',
        'Verified Composer availability/bootstrap',
        [PHP_BINARY, 'scripts/composer-bootstrap.php'],
        14,
        false,
    );
    if ($composerBootstrap['exit_code'] !== 0) {
        $state['first_blocker'] ??= [
            'id' => 'composer-bootstrap',
            'label' => 'Verified Composer availability/bootstrap',
            'exit_code' => $composerBootstrap['exit_code'],
            'detail' => substr(trim($composerBootstrap['stderr'] ?: $composerBootstrap['stdout']), 0, 1200),
        ];
        $finish(
            'blocked',
            'dependency-locks',
            12,
            'Composer bootstrap is blocked. Resolve the exact DNS/TLS/internet blocker shown above; root locks were not mutated.',
        );
    }

    $reviewed = $record(
        'reviewed-locks',
        'Reviewed dependency-lock attestation',
        [PHP_BINARY, 'scripts/dependency-lock-review.php', '--verify-attestation', '--require-refresh-handoff'],
        25,
        false,
    );

    if ($reviewed['exit_code'] !== 0) {
        $candidateState = nexoraPkg1DependencyCandidateState($root);
        if (! $candidateState['valid']) {
            $refresh = $record(
                'lock-refresh',
                'Double-run reproducible dependency lock candidate generation',
                [PHP_BINARY, 'scripts/dependency-lock-refresh.php', '--confirm=REFRESH'],
                18,
                false,
            );
            $candidateState = nexoraPkg1DependencyCandidateState($root);
            if ($refresh['exit_code'] !== 2 || ! $candidateState['valid']) {
                $state['first_blocker'] ??= [
                    'id' => 'lock-refresh',
                    'label' => 'Dependency lock candidate generation',
                    'exit_code' => $refresh['exit_code'],
                    'detail' => substr(trim($refresh['stderr'] ?: $refresh['stdout']), 0, 1200),
                ];
                $finish(
                    'blocked',
                    'dependency-locks',
                    12,
                    'Dependency candidate generation is blocked. Resolve the exact toolchain/registry blocker shown above; root locks were not mutated.',
                );
            }
        }

        if (! $promoteReviewed) {
            $finish(
                'waiting-review',
                'dependency-locks',
                20,
                'Review `storage/app/nexora/dependency-intake/lock-refresh.md` and both candidate lockfiles. Then rerun this same PKG-1 command with `--promote-reviewed --reviewer="REAL NAME"`.',
            );
        }
        if ($reviewer === '' || strlen($reviewer) > 120) {
            $finish(
                'waiting-review',
                'dependency-locks',
                20,
                '`--reviewer="REAL NAME"` is required with `--promote-reviewed` after human review.',
            );
        }

        $promotion = $record(
            'lock-promotion',
            'Transactional reviewed dependency-lock promotion',
            [
                PHP_BINARY,
                'scripts/dependency-lock-promote.php',
                '--reviewer='.$reviewer,
                '--confirm=PROMOTE-REVIEWED',
            ],
            30,
        );
        if ($promotion['exit_code'] !== 0) {
            $finish(
                'blocked',
                'dependency-locks',
                22,
                'Reviewed-lock promotion failed or rolled back. Resolve the reported blocker and rerun PKG-1.',
            );
        }
    }
}

$state['phase'] = 'c1';
nexoraPkg1Persist($root, $state);
if (! $c1Reusable) {
    $c1Verify = $record(
        'c1-evidence-resume',
        'Check for reusable exact-source C1 PASS evidence',
        [PHP_BINARY, 'scripts/n1-c1-evidence-verify.php'],
        65,
        false,
    );
} else {
    $c1Verify = ['exit_code' => 0, 'stdout' => '', 'stderr' => '', 'duration_seconds' => 0.0];
}
if ($c1Verify['exit_code'] !== 0) {
    $c1 = $record(
        'c1-certification',
        'N1.0 C1 dependency + TypeScript + Vitest + Vite closure',
        [PHP_BINARY, 'scripts/n1-c1-dependency-certify.php', '--install-deps'],
        60,
    );
    if ($c1['exit_code'] !== 0) {
        $finish(
            'blocked',
            'c1',
            35,
            'C1 is not 14/14 yet. Fix the first blocker in `storage/app/nexora/n1-c1/latest.json`, then rerun PKG-1; already-valid dependency review state is preserved.',
        );
    }

    $c1Verify = $record(
        'c1-evidence',
        'C1 evidence integrity verification',
        [PHP_BINARY, 'scripts/n1-c1-evidence-verify.php'],
        65,
    );
    if ($c1Verify['exit_code'] !== 0) {
        $finish('blocked', 'c1', 60, 'C1 execution passed but C1 evidence integrity did not verify.');
    }
}

// Clean release ZIPs intentionally do not carry .env. Once C1 has installed
// vendor dependencies, create the standard local bootstrap only when missing.
$envPath = $root.'/.env';
$envCreated = false;
if (! is_file($envPath)) {
    if (! is_file($root.'/.env.example') || ! copy($root.'/.env.example', $envPath)) {
        $finish('blocked', 'environment-bootstrap', 65, 'Unable to create .env from .env.example after C1.');
    }
    $envCreated = true;
}
$envContents = (string) file_get_contents($envPath);
$keyMissing = preg_match('/^APP_KEY=\s*$/m', $envContents) === 1
    || preg_match('/^APP_KEY=/m', $envContents) !== 1;
if ($envCreated || $keyMissing) {
    $key = $record(
        'app-key-bootstrap',
        'Generate local application key without overwriting an existing key',
        [PHP_BINARY, 'artisan', 'key:generate', '--force'],
        67,
    );
    if ($key['exit_code'] !== 0) {
        $finish('blocked', 'environment-bootstrap', 65, 'APP_KEY bootstrap failed after C1.');
    }
}

$state['phase'] = 'source-identity';
nexoraPkg1Persist($root, $state);
$source = $record(
    'source-status',
    'Exact source + web-process acknowledgement',
    [PHP_BINARY, 'artisan', 'nexora:source:status', '--require-web-ack'],
    70,
    false,
);
if ($source['exit_code'] !== 0) {
    $activation = $record(
        'source-activate',
        'Issue exact CLI source activation receipt',
        [PHP_BINARY, 'artisan', 'nexora:source:activate', '--assert-current'],
        68,
        false,
    );
    if ($activation['exit_code'] !== 0) {
        $finish(
            'blocked',
            'source-identity',
            65,
            'Source activation could not be issued on the exact C1 dependency graph. Resolve the command blocker and rerun PKG-1.',
        );
    }

    // A rerun after the web/PHP process reload can consume the one-time token
    // automatically. This request never logs or persists the bearer token.
    $tokenResult = nexoraPkg1Run(
        [PHP_BINARY, 'artisan', 'nexora:source:status', '--web-token'],
        $root,
        $environment,
    );
    $token = trim($tokenResult['stdout']);
    if ($token !== '' && preg_match('/^[a-f0-9]{64}$/', $token) === 1) {
        $headers = "Accept: application/json\r\n"
            ."Cache-Control: no-cache\r\n"
            ."X-Nexora-Activation-Token: {$token}\r\n";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'ignore_errors' => true,
                'timeout' => 15,
                'header' => $headers,
            ],
        ]);
        @file_get_contents($baseUrl.'/install/source-status', false, $context);
    }

    $sourceAfterAck = $record(
        'source-status-after-ack',
        'Recheck exact CLI/web source convergence',
        [PHP_BINARY, 'artisan', 'nexora:source:status', '--require-web-ack'],
        72,
        false,
    );
    if ($sourceAfterAck['exit_code'] !== 0) {
        $finish(
            'waiting-source-restart',
            'source-identity',
            68,
            'C1 is PASS. Restart/reload Laragon Apache/Nginx/PHP, then rerun this same PKG-1 command; it will consume the existing one-time web-ack token automatically.',
        );
    }
}

$state['phase'] = 'installer';
nexoraPkg1Persist($root, $state);
$lock = $record(
    'installation-lock',
    'Permanent installation lock validity',
    [PHP_BINARY, 'artisan', 'nexora:install:lock-status', '--assert-valid'],
    82,
    false,
);
if ($lock['exit_code'] !== 0) {
    $finish(
        'waiting-install',
        'installer',
        72,
        'C1 + exact source activation are PASS. Open `/install`; the browser installer will evaluate readiness against the selected database before mutation. Complete/retry it to 100%, then rerun this same PKG-1 command.',
    );
}

$state['phase'] = 'installer-readiness';
nexoraPkg1Persist($root, $state);
$readiness = $record(
    'install-readiness',
    'Reverify installer-safe runtime readiness on committed database configuration',
    [PHP_BINARY, 'artisan', 'nexora:runtime:install-readiness', '--json', '--assert-ready'],
    85,
);
if ($readiness['exit_code'] !== 0) {
    $finish(
        'blocked',
        'installer-readiness',
        82,
        'Installation lock is valid but committed runtime readiness no longer passes. Resolve the exact component before usability closure.',
    );
}

$state['phase'] = 'post-install';
nexoraPkg1Persist($root, $state);
$post = $record(
    'post-install-handoff',
    'Post-install runtime handoff',
    [PHP_BINARY, 'artisan', 'nexora:runtime:post-install-status', '--assert-ready'],
    88,
);
if ($post['exit_code'] !== 0) {
    $finish(
        'blocked',
        'post-install',
        82,
        'Installation lock is valid but runtime handoff is not ready. Reload/restart PHP as instructed by the command and rerun PKG-1.',
    );
}

$state['phase'] = 'usable-smoke';
nexoraPkg1Persist($root, $state);
$smoke = $record(
    'usable-smoke',
    'Non-destructive live login/admin/core smoke',
    [PHP_BINARY, 'scripts/pkg1-usable-smoke.php'],
    100,
    false,
);
if ($smoke['exit_code'] !== 0) {
    $smokeReport = $root.'/storage/app/nexora/pkg1/usable-smoke.json';
    $reason = 'PKG-1 smoke did not fully pass.';
    if (is_file($smokeReport)) {
        try {
            $payload = json_decode((string) file_get_contents($smokeReport), true, 512, JSON_THROW_ON_ERROR);
            if (($payload['status'] ?? null) === 'waiting-auth-smoke') {
                $finish(
                    'waiting-auth-smoke',
                    'usable-smoke',
                    92,
                    'Set `NEXORA_PKG1_SMOKE_PASSWORD` to the installer Super Admin password for this shell/session, then rerun PKG-1. The password is never written to evidence.',
                );
            }
            $reason = implode(' ', (array) ($payload['errors'] ?? [])) ?: $reason;
        } catch (Throwable) {
            // Keep generic reason.
        }
    }
    $finish('blocked', 'usable-smoke', 90, $reason);
}

$hashArtifact = static fn (string $path): ?string => is_file($path)
    ? (hash_file('sha256', $path) ?: null)
    : null;
$canonicalize = static function (mixed $value) use (&$canonicalize): mixed {
    if (! is_array($value)) { return $value; }
    if (array_is_list($value)) { return array_map($canonicalize, $value); }
    ksort($value, SORT_STRING);
    foreach ($value as $key => $item) { $value[$key] = $canonicalize($item); }
    return $value;
};
$closureReceipt = [
    'schema' => 1,
    'package' => 'PKG-1',
    'status' => 'pass',
    'scope' => 'Usable Release + C1 Closure',
    'platform_version' => $state['platform_version'],
    'source_tree_sha256' => $state['source_tree_sha256'],
    'operator' => $operator,
    'base_url' => $baseUrl,
    'acceptance' => [
        'c1' => '14/14',
        'installer' => '100%',
        'installation_lock' => 'pass',
        'post_install_handoff' => 'pass',
        'login_admin_smoke' => 'pass',
        'usable' => true,
    ],
    'artifacts' => [
        'c1_evidence_sha256' => $hashArtifact($root.'/storage/app/nexora/n1-c1/latest.json'),
        'composer_lock_sha256' => $hashArtifact($root.'/composer.lock'),
        'package_lock_sha256' => $hashArtifact($root.'/package-lock.json'),
        'reviewed_locks_sha256' => $hashArtifact($root.'/storage/app/nexora/dependency-intake/reviewed-locks.json'),
        'build_assets_sha256' => $hashArtifact($root.'/storage/app/nexora/certification/build-assets.json'),
        'pkg1_build_input_sha256' => $hashArtifact($root.'/storage/app/nexora/certification/pkg1-build-input.json'),
        'installation_lock_sha256' => $hashArtifact($root.'/storage/app/nexora/installed.lock'),
        'post_install_handoff_sha256' => $hashArtifact($root.'/storage/app/nexora/runtime/post-install-handoff.json'),
        'usable_smoke_sha256' => $hashArtifact($root.'/storage/app/nexora/pkg1/usable-smoke.json'),
    ],
    'closed_at' => gmdate(DATE_ATOM),
];
$closureReceipt['receipt_sha256'] = hash(
    'sha256',
    json_encode($canonicalize($closureReceipt), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
);
nexoraPkg1WriteJson($root.'/storage/app/nexora/pkg1/closure.json', $closureReceipt);
$closureVerify = $record(
    'closure-evidence',
    'Sealed PKG-1 closure evidence integrity',
    [PHP_BINARY, 'scripts/pkg1-closure-evidence-verify.php'],
    100,
);
if ($closureVerify['exit_code'] !== 0) {
    $finish('blocked', 'closure-evidence', 95, 'PKG-1 acceptance checks passed but the sealed closure receipt did not verify.');
}
$state['closure'] = $closureReceipt;
$finish(
    'pass',
    'complete',
    100,
    'PKG-1 COMPLETE — Nexora is usable and C1 is closed on this exact target source/dependency/runtime identity.',
);
