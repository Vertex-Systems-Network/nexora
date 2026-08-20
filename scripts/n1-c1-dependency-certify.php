<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/target-composer.php';
require_once $root.'/scripts/lib/n1-frontend-build-diagnostics.php';
require_once $root.'/scripts/lib/n1-historical-typescript-remediation.php';
require_once $root.'/scripts/lib/dependency-lock-intake.php';
require_once $root.'/scripts/lib/dependency-toolchain.php';

$platform = require $root.'/config/nexora.php';
$version = (string) ($platform['version'] ?? 'unknown');
$environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);

$installDependencies = in_array('--install-deps', $argv, true);
$applyExtensions = in_array('--apply-extensions', $argv, true);
$statusOnly = in_array('--status-only', $argv, true);

if ($statusOnly && ($installDependencies || $applyExtensions)) {
    fwrite(
        STDERR,
        "[N1.0-C1] --status-only cannot be combined with mutation/execution options.\n",
    );
    exit(2);
}

$baseDirectory = $root.'/storage/app/nexora/n1-c1';
if ($statusOnly) {
    $latest = $baseDirectory.'/latest.json';
    if (is_file($latest)) {
        fwrite(STDOUT, (string) file_get_contents($latest));
        exit(0);
    }

    fwrite(STDOUT, "[N1.0-C1] No prior C1 evidence.\n");
    exit(2);
}

$runId = gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));
$runDirectory = $baseDirectory.'/'.$runId;
$logsDirectory = $runDirectory.'/steps';
foreach ([$baseDirectory, $runDirectory, $logsDirectory] as $directory) {
    if (! is_dir($directory)
        && ! mkdir($directory, 0775, true)
        && ! is_dir($directory)) {
        throw new RuntimeException("Unable to create C1 evidence directory [{$directory}].");
    }
}

$source = nexoraComputeSourceAttestation($root);
$historical = nexoraAnalyzeHistoricalTypeScriptRemediation($root);
$steps = [];
$firstBlocker = null;

$redact = static function (string $text): string {
    $patterns = [
        '/((?:password|passwd|secret|token|authorization|cookie|api[_-]?key)\s*[:=]\s*)([^\s\r\n]+)/i',
        '/(Bearer\s+)[A-Za-z0-9._~+\/-]+/i',
    ];

    foreach ($patterns as $pattern) {
        $text = (string) preg_replace($pattern, '$1[REDACTED]', $text);
    }

    return $text;
};

$runStep = static function (
    string $id,
    string $label,
    array $commandParts,
    bool $required = true,
) use (
    $root,
    $environment,
    $logsDirectory,
    $redact,
    &$steps,
    &$firstBlocker,
): bool {
    $command = implode(' ', array_map(
        static fn (mixed $part): string => escapeshellarg((string) $part),
        $commandParts,
    ));

    fwrite(STDOUT, "\n[N1.0-C1] {$label}\n> {$command}\n");
    $started = microtime(true);
    $process = @proc_open(
        $command,
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $root,
        $environment,
        ['bypass_shell' => false],
    );

    if (! is_resource($process)) {
        $stdout = '';
        $stderr = 'Unable to start process.';
        $exitCode = 127;
    } else {
        fclose($pipes[0]);
        $stdout = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
    }

    $stdout = $redact($stdout);
    $stderr = $redact($stderr);
    file_put_contents("{$logsDirectory}/{$id}.stdout.log", $stdout, LOCK_EX);
    file_put_contents("{$logsDirectory}/{$id}.stderr.log", $stderr, LOCK_EX);

    if ($stdout !== '') {
        fwrite(STDOUT, $stdout.(str_ends_with($stdout, "\n") ? '' : "\n"));
    }
    if ($stderr !== '') {
        fwrite(STDERR, $stderr.(str_ends_with($stderr, "\n") ? '' : "\n"));
    }

    $passed = $exitCode === 0;
    $steps[] = [
        'id' => $id,
        'label' => $label,
        'status' => $passed ? 'pass' : ($required ? 'fail' : 'blocked'),
        'required' => $required,
        'exit_code' => $exitCode,
        'duration_seconds' => round(microtime(true) - $started, 3),
        'stdout_log' => "steps/{$id}.stdout.log",
        'stderr_log' => "steps/{$id}.stderr.log",
    ];

    if (! $passed && $required && $firstBlocker === null) {
        $firstBlocker = [
            'id' => $id,
            'label' => $label,
            'exit_code' => $exitCode,
        ];
    }

    return $passed;
};

$attachFrontendDiagnostics = static function (
    string $stepId,
    string $command,
) use (
    $root,
    $logsDirectory,
    $version,
    $source,
    &$steps,
    &$firstBlocker,
): ?array {
    $stdoutPath = "{$logsDirectory}/{$stepId}.stdout.log";
    $stderrPath = "{$logsDirectory}/{$stepId}.stderr.log";
    if (! is_file($stdoutPath) && ! is_file($stderrPath)) {
        return null;
    }

    $output = (is_file($stdoutPath) ? (string) file_get_contents($stdoutPath) : '')
        ."\n"
        .(is_file($stderrPath) ? (string) file_get_contents($stderrPath) : '');

    $exitCode = null;
    foreach ($steps as &$step) {
        if (($step['id'] ?? null) !== $stepId) {
            continue;
        }
        $exitCode = (int) ($step['exit_code'] ?? 1);
        break;
    }
    unset($step);

    $diagnostics = nexoraAnalyzeFrontendBuildOutput(
        $output,
        $root,
        $exitCode,
        $command,
    );
    $diagnostics['platform_version'] = $version;
    $diagnostics['source_tree_sha256'] = $source['tree_sha256'];
    $diagnostics['step_id'] = $stepId;
    $diagnostics['generated_at'] = gmdate(DATE_ATOM);
    $diagnosticPath = "{$logsDirectory}/{$stepId}.diagnostics.json";
    nexoraWriteFrontendBuildDiagnostics($diagnosticPath, $diagnostics);

    foreach ($steps as &$step) {
        if (($step['id'] ?? null) !== $stepId) {
            continue;
        }
        $step['frontend_diagnostics'] = [
            'path' => "steps/{$stepId}.diagnostics.json",
            'sha256' => hash_file('sha256', $diagnosticPath) ?: null,
            'diagnostic_count' => $diagnostics['diagnostic_count'],
            'diagnostic_file_count' => $diagnostics['diagnostic_file_count'],
            'historical_target_diagnostics' => $diagnostics['historical_target_diagnostics'],
            'historical_target_files_with_diagnostics' => $diagnostics['historical_target_files_with_diagnostics'],
            'dependency_graph_missing' => $diagnostics['dependency_graph_missing'],
        ];
        break;
    }
    unset($step);

    if (($firstBlocker['id'] ?? null) === $stepId) {
        $firstBlocker['frontend_diagnostics'] = [
            'diagnostic_count' => $diagnostics['diagnostic_count'],
            'diagnostic_file_count' => $diagnostics['diagnostic_file_count'],
            'historical_target_diagnostics' => $diagnostics['historical_target_diagnostics'],
            'historical_target_files_with_diagnostics' => $diagnostics['historical_target_files_with_diagnostics'],
            'dependency_graph_missing' => $diagnostics['dependency_graph_missing'],
            'first_diagnostic' => $diagnostics['first_diagnostic'],
            'report' => "steps/{$stepId}.diagnostics.json",
        ];
    }

    return $diagnostics;
};

$hash = static fn (string $file): ?string => is_file($file)
    ? (hash_file('sha256', $file) ?: null)
    : null;

$status = 'pass';
$typecheckDiagnostics = null;
$viteBuildDiagnostics = null;

if ($applyExtensions) {
    $passed = $runStep(
        'extension-remediation',
        'Explicit Laragon PHP extension remediation',
        [PHP_BINARY, 'scripts/target-prerequisite-remediate.php', '--apply-extensions'],
    );
    $status = $passed ? 'restart-required' : 'blocked';
} else {
    $prerequisites = [
        ['prerequisite-intake', 'Target PHP/Composer prerequisite intake', [PHP_BINARY, 'scripts/target-prerequisite-intake.php']],
        ['reviewed-locks', 'Reviewed lockfile attestation', [PHP_BINARY, 'scripts/dependency-lock-review.php', '--verify-attestation']],
        ['strict-locks', 'Strict Composer/npm lock contracts', [PHP_BINARY, 'scripts/dependency-contract-verify.php', '--strict-locks']],
        ['runtime-policy', 'PHP/Composer/Node/npm certified runtime policy', [PHP_BINARY, 'scripts/dependency-runtime-verify.php']],
    ];

    foreach ($prerequisites as [$id, $label, $command]) {
        if ($status !== 'pass') {
            break;
        }
        if (! $runStep($id, $label, $command)) {
            $status = 'blocked';
        }
    }

    if ($status === 'pass' && $installDependencies) {
        $lockHashesBeforeInstall = nexoraDependencyRootLockHashes($root);
        $toolchainBeforeInstall = nexoraCollectDependencyToolchain($root);
        $composer = nexoraLocateTargetComposer($root);
        $composerCommand = (array) ($composer['command'] ?? []);
        if ($composerCommand === [] || ! $runStep(
            'composer-install',
            'Locked Composer install without application scripts',
            array_merge($composerCommand, [
                'install',
                '--no-interaction',
                '--prefer-dist',
                '--optimize-autoloader',
                '--no-progress',
                '--no-scripts',
            ]),
        )) {
            $status = 'blocked';
        }

        if ($status === 'pass' && ! $runStep(
            'npm-ci',
            'Locked npm dependency graph',
            ['npm', 'ci', '--no-audit', '--no-fund'],
        )) {
            $status = 'blocked';
        }

        if ($status === 'pass') {
            $lockHashesAfterInstall = nexoraDependencyRootLockHashes($root);
            $toolchainAfterInstall = nexoraCollectDependencyToolchain($root);
            $lockImmutable = $lockHashesBeforeInstall === $lockHashesAfterInstall;
            $toolchainStable = ($toolchainBeforeInstall['fingerprint_sha256'] ?? null)
                === ($toolchainAfterInstall['fingerprint_sha256'] ?? null);
            $steps[] = [
                'id' => 'locked-install-immutability',
                'label' => 'Locked install preserves reviewed lock/toolchain identity',
                'command' => ['internal-lock-and-toolchain-comparison'],
                'required' => true,
                'exit_code' => $lockImmutable && $toolchainStable ? 0 : 1,
                'status' => $lockImmutable && $toolchainStable ? 'pass' : 'fail',
                'stdout_excerpt' => '',
                'stderr_excerpt' => $lockImmutable && $toolchainStable
                    ? ''
                    : 'Reviewed lock hashes or dependency toolchain fingerprint changed during locked installation.',
                'started_at' => gmdate(DATE_ATOM),
                'finished_at' => gmdate(DATE_ATOM),
                'lock_hashes_before' => $lockHashesBeforeInstall,
                'lock_hashes_after' => $lockHashesAfterInstall,
                'toolchain_fingerprint_before' => $toolchainBeforeInstall['fingerprint_sha256'] ?? null,
                'toolchain_fingerprint_after' => $toolchainAfterInstall['fingerprint_sha256'] ?? null,
            ];
            if (! $lockImmutable || ! $toolchainStable) {
                $firstBlocker ??= [
                    'id' => 'locked-install-immutability',
                    'label' => 'Locked install preserves reviewed lock/toolchain identity',
                    'exit_code' => 1,
                    'stderr_excerpt' => 'Reviewed lock hashes or dependency toolchain fingerprint changed during locked installation.',
                ];
                $status = 'blocked';
            }
        }
    }

    if ($status === 'pass' && ! $runStep(
        'installed-state',
        'Installed dependency graph matches reviewed locks',
        [PHP_BINARY, 'scripts/n1-c1-installed-dependency-verify.php'],
    )) {
        $status = 'blocked';
    }

    if ($status === 'pass' && ! $runStep(
        'inertia-contract',
        'Frontend/Inertia source contract',
        [PHP_BINARY, 'scripts/inertia-frontend-contract-verify.php'],
    )) {
        $status = 'blocked';
    }

    if ($status === 'pass') {
        $passed = $runStep(
            'typecheck',
            'TypeScript strict typecheck',
            ['npm', 'run', 'typecheck'],
        );
        $typecheckDiagnostics = $attachFrontendDiagnostics('typecheck', 'npm run typecheck');
        if (! $passed) {
            $status = 'blocked';
        }
    }

    if ($status === 'pass' && ! $runStep(
        'frontend-tests',
        'Vitest unit/component suite',
        ['npm', 'run', 'test'],
    )) {
        $status = 'blocked';
    }

    if ($status === 'pass') {
        $passed = $runStep(
            'vite-build',
            'Vite production build',
            ['npm', 'run', 'build'],
        );
        $viteBuildDiagnostics = $attachFrontendDiagnostics('vite-build', 'npm run build');
        if (! $passed) {
            $status = 'blocked';
        }
    }

    $remainingGates = [
        ['dependency-provenance', 'Locked dependency provenance', [PHP_BINARY, 'scripts/dependency-provenance.php']],
        ['dependency-audit', 'Composer/npm vulnerability audit', [PHP_BINARY, 'scripts/dependency-audit.php']],
        ['dependency-sbom', 'Deterministic locked dependency SBOM', [PHP_BINARY, 'scripts/dependency-sbom.php', '--write']],
        ['asset-budgets', 'Production build asset budgets', [PHP_BINARY, 'scripts/performance-build-verify.php']],
        ['toolchain-freeze', 'Freeze certified PHP/Composer/Node/npm toolchain fingerprint', [PHP_BINARY, 'scripts/n1-certified-toolchain.php', '--write']],
    ];

    foreach ($remainingGates as [$id, $label, $command]) {
        if ($status !== 'pass') {
            break;
        }
        if (! $runStep($id, $label, $command)) {
            $status = 'blocked';
        }
    }
}

$artifacts = [
    'composer_lock_sha256' => $hash($root.'/composer.lock'),
    'package_lock_sha256' => $hash($root.'/package-lock.json'),
    'reviewed_locks_sha256' => $hash($root.'/storage/app/nexora/dependency-intake/reviewed-locks.json'),
    'build_assets_sha256' => $hash($root.'/storage/app/nexora/certification/build-assets.json'),
    'pkg1_build_input_sha256' => $hash($root.'/storage/app/nexora/certification/pkg1-build-input.json'),
    'dependency_audit_sha256' => $hash($root.'/storage/app/nexora/certification/dependency-audit.json'),
    'dependency_provenance_sha256' => $hash($root.'/storage/app/nexora/certification/dependency-provenance.json'),
    'dependency_sbom_sha256' => $hash($root.'/storage/app/nexora/certification/dependency-sbom.json'),
    'certified_toolchain_sha256' => $hash($root.'/storage/app/nexora/certification/toolchain.json'),
    'frontend_typecheck_diagnostics_sha256' => $hash($logsDirectory.'/typecheck.diagnostics.json'),
    'frontend_vite_build_diagnostics_sha256' => $hash($logsDirectory.'/vite-build.diagnostics.json'),
];

$summary = [
    'schema' => 2,
    'chunk' => 'N1.0-C1',
    'scope' => 'Target Environment + Dependencies',
    'platform_version' => $version,
    'source_tree_sha256' => $source['tree_sha256'],
    'run_id' => $runId,
    'status' => $status,
    'first_blocker' => $firstBlocker,
    'options' => [
        'install_dependencies' => $installDependencies,
        'apply_extensions' => $applyExtensions,
    ],
    'frontend_baseline' => [
        'historical_errors' => $historical['historical_error_total'],
        'historical_files' => $historical['historical_file_total'],
        'source_remediated_errors' => $historical['source_remediated_errors'],
        'source_remediated_files' => $historical['source_remediated_files'],
        'target_verified' => $status === 'pass'
            && ($typecheckDiagnostics['compiler_clean'] ?? false) === true
            && ($viteBuildDiagnostics['compiler_clean'] ?? false) === true,
    ],
    'artifacts' => $artifacts,
    'steps' => $steps,
    'finished_at' => gmdate(DATE_ATOM),
];

$json = json_encode(
    $summary,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
).PHP_EOL;
file_put_contents($runDirectory.'/summary.json', $json, LOCK_EX);
file_put_contents($baseDirectory.'/latest.json', $json, LOCK_EX);

$markdown = "# Nexora N1.0-C1 Target Environment + Dependencies\n\n";
$markdown .= 'Status: **'.strtoupper($status)."**  \n";
$markdown .= "Platform: `{$version}`  \n";
$markdown .= "Source: `{$source['tree_sha256']}`\n";
$markdown .= sprintf(
    "Historical TypeScript source remediation: **%d/%d diagnostics across %d/%d files**  \n",
    $historical['source_remediated_errors'],
    $historical['historical_error_total'],
    $historical['source_remediated_files'],
    $historical['historical_file_total'],
);
if ($firstBlocker !== null) {
    $markdown .= "First blocker: `{$firstBlocker['id']}` — {$firstBlocker['label']}\n";
    $frontend = (array) ($firstBlocker['frontend_diagnostics'] ?? []);
    if ($frontend !== []) {
        $markdown .= sprintf(
            "Frontend diagnostics: **%d** errors / **%d** files; historical targets **%d** errors / **%d** files.\n",
            (int) ($frontend['diagnostic_count'] ?? 0),
            (int) ($frontend['diagnostic_file_count'] ?? 0),
            (int) ($frontend['historical_target_diagnostics'] ?? 0),
            (int) ($frontend['historical_target_files_with_diagnostics'] ?? 0),
        );
    }
}
$markdown .= "\n| Gate | Status | Exit |\n|---|---:|---:|\n";
foreach ($steps as $step) {
    $markdown .= '| '.$step['label'].' | '.strtoupper((string) $step['status']).' | '.$step['exit_code']." |\n";
}

file_put_contents($runDirectory.'/summary.md', $markdown, LOCK_EX);
file_put_contents($baseDirectory.'/latest.md', $markdown, LOCK_EX);

fwrite(STDOUT, "\n{$markdown}\n");
fwrite(STDOUT, "Evidence: storage/app/nexora/n1-c1/{$runId}/\n");

exit($status === 'pass' ? 0 : ($status === 'restart-required' ? 2 : 1));
