<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/source-attestation.php';

$platform = require $root.'/config/nexora.php';
$version = (string) ($platform['version'] ?? 'unknown');
$env = NexoraBootstrapProcessEnvironment::build($root, $_ENV);

$installDeps = in_array('--install-deps', $argv, true);
$full = in_array('--full', $argv, true);
$final = in_array('--final', $argv, true);
$resumeLatest = in_array('--resume-latest', $argv, true);
$statusOnly = in_array('--status-only', $argv, true);
$sealEvidence = in_array('--seal-evidence', $argv, true);
$evidenceInput = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--evidence=')) {
        $evidenceInput = trim(substr($arg, 11));
    }
}

if ($final && ! $full) {
    fwrite(STDERR, "[Nexora Target Orchestrator] --final requires --full.\n");
    exit(2);
}
if ($sealEvidence && ($evidenceInput === null || $evidenceInput === '')) {
    fwrite(STDERR, "[Nexora Target Orchestrator] --seal-evidence requires --evidence=<directory-or-zip>.\n");
    exit(2);
}
if ($statusOnly && ($installDeps || $full || $final || $resumeLatest || $sealEvidence || $evidenceInput !== null)) {
    fwrite(STDERR, "[Nexora Target Orchestrator] --status-only cannot be combined with execution/evidence options.\n");
    exit(2);
}

$redact = static function (string $text): string {
    foreach ([
        '/((?:password|passwd|secret|token|authorization|cookie|api[_-]?key)\s*[:=]\s*)([^\s\r\n]+)/i',
        '/(Bearer\s+)[A-Za-z0-9._~+\/-]+/i',
        '/([?&](?:token|secret|key|password)=)[^&\s]+/i',
    ] as $pattern) {
        $text = (string) preg_replace($pattern, '$1[REDACTED]', $text);
    }
    return $text;
};

$runId = gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));
$base = $root.'/storage/app/nexora/target-orchestrator';
$runDir = $base.'/'.$runId;
$stepDir = $runDir.'/steps';
foreach ([$base, $runDir, $stepDir] as $dir) {
    if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
        throw new RuntimeException("Unable to create [{$dir}].");
    }
}

$source = nexoraComputeSourceAttestation($root);
$context = [
    'schema' => 1,
    'run_id' => $runId,
    'platform_version' => $version,
    'source_tree_sha256' => $source['tree_sha256'],
    'started_at' => gmdate(DATE_ATOM),
    'php_binary' => PHP_BINARY,
    'php_version' => PHP_VERSION,
    'os_family' => PHP_OS_FAMILY,
    'options' => [
        'install_dependencies' => $installDeps,
        'full' => $full,
        'final' => $final,
        'resume_latest' => $resumeLatest,
        'status_only' => $statusOnly,
        'evidence_input' => $evidenceInput !== null ? basename(str_replace('\\', '/', $evidenceInput)) : null,
        'seal_evidence' => $sealEvidence,
    ],
];
file_put_contents($runDir.'/context.json', json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);

$steps = [];
$firstBlocker = null;
$run = static function (string $id, string $label, array $parts, bool $required = true) use ($root, $env, $redact, $stepDir, &$steps, &$firstBlocker): bool {
    $command = implode(' ', array_map(static fn ($part): string => escapeshellarg((string) $part), $parts));
    fwrite(STDOUT, "\n[Nexora Target Orchestrator] {$label}\n> {$command}\n");
    $started = microtime(true);
    $proc = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $env, ['bypass_shell' => false]);
    if (! is_resource($proc)) {
        $stdout = '';
        $stderr = 'Unable to start process.';
        $exit = 127;
    } else {
        fclose($pipes[0]);
        $stdout = (string) stream_get_contents($pipes[1]); fclose($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]); fclose($pipes[2]);
        $exit = proc_close($proc);
    }
    $stdout = $redact($stdout);
    $stderr = $redact($stderr);
    file_put_contents($stepDir.'/'.$id.'.stdout.log', $stdout);
    file_put_contents($stepDir.'/'.$id.'.stderr.log', $stderr);
    if ($stdout !== '') fwrite(STDOUT, $stdout.(str_ends_with($stdout, "\n") ? '' : "\n"));
    if ($stderr !== '') fwrite(STDERR, $stderr.(str_ends_with($stderr, "\n") ? '' : "\n"));
    $step = [
        'id' => $id,
        'label' => $label,
        'required' => $required,
        'status' => $exit === 0 ? 'pass' : ($required ? 'fail' : 'blocked'),
        'exit_code' => $exit,
        'duration_seconds' => round(microtime(true) - $started, 3),
        'command' => array_values(array_map('strval', $parts)),
        'stdout_log' => 'steps/'.$id.'.stdout.log',
        'stderr_log' => 'steps/'.$id.'.stderr.log',
    ];
    $steps[] = $step;
    if ($required && $exit !== 0 && $firstBlocker === null) {
        $firstBlocker = ['id' => $id, 'label' => $label, 'exit_code' => $exit];
    }
    return $exit === 0;
};

if ($statusOnly) {
    $ok = $run('closure-dashboard', 'N1.0 closure dashboard', [PHP_BINARY, 'scripts/closure-dashboard.php'], false);
    $status = $ok ? 'pass' : 'blocked';
} else {
    $status = 'pass';
    if (! $run('prerequisite-intake', 'Laragon/target prerequisite intake', [PHP_BINARY, 'scripts/target-prerequisite-intake.php'])) {
        $status = 'blocked';
        fwrite(STDOUT, "
Remediation helper: scripts\\target-prerequisite-remediate.bat
Review-only by default; --apply-extensions is explicit and Windows/Laragon-only.
");
    }
    if ($status === 'pass' && ! $run('reviewed-locks', 'Reviewed dependency lock attestation', [PHP_BINARY, 'scripts/dependency-lock-review.php', '--verify-attestation'])) $status = 'blocked';

    if ($status === 'pass') {
        $runtime = [PHP_BINARY, 'scripts/target-runtime-run.php'];
        if ($installDeps) $runtime[] = '--install-deps';
        if ($full) $runtime[] = '--full';
        if ($resumeLatest) $runtime[] = '--resume-latest';
        if (! $run('target-runtime', $full ? 'Full isolated target runtime certification' : 'Target runtime readiness certification', $runtime)) $status = 'blocked';
    }

    if ($status === 'pass' && $evidenceInput !== null && $evidenceInput !== '') {
        $evidence = [PHP_BINARY, 'scripts/target-evidence-intake.php', '--input='.$evidenceInput];
        if ($sealEvidence) {
            $evidence[] = '--seal';
            $evidence[] = '--require-complete';
        }
        if (! $run('operator-evidence', $sealEvidence ? 'Seal complete operator evidence' : 'Validate operator evidence intake', $evidence)) $status = 'blocked';
    }

    // Dashboard is always produced after a successful runtime phase. A non-zero dashboard
    // means closure evidence is still pending; it is informational unless --final was asked.
    if ($status === 'pass') {
        $dashboardOk = $run('closure-dashboard', 'N1.0 closure dashboard', [PHP_BINARY, 'scripts/closure-dashboard.php'], $final);
        if ($final && ! $dashboardOk) $status = 'blocked';
    }

    if ($status === 'pass' && $final) {
        if (! $run('final-release', 'Final target evidence + production release sealing', [PHP_BINARY, 'scripts/final-target-run.php', '--final'])) $status = 'blocked';
    }
}

$summary = array_merge($context, [
    'finished_at' => gmdate(DATE_ATOM),
    'status' => $status,
    'first_blocker' => $firstBlocker,
    'steps' => $steps,
]);
file_put_contents($runDir.'/summary.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
file_put_contents($base.'/latest.json', json_encode(['run_id' => $runId, 'status' => $status, 'summary' => 'storage/app/nexora/target-orchestrator/'.$runId.'/summary.json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);

$md = "# Nexora target certification orchestrator\n\n";
$md .= "- Platform: `{$version}`\n- Source SHA-256: `{$source['tree_sha256']}`\n- Run: `{$runId}`\n- Status: **".strtoupper($status)."**\n";
if ($firstBlocker !== null) $md .= "- First blocker: `{$firstBlocker['id']}` ({$firstBlocker['label']})\n";
$md .= "\n| Step | Status | Exit |\n|---|---:|---:|\n";
foreach ($steps as $step) $md .= '| '.$step['label'].' | '.strtoupper((string) $step['status']).' | '.(string) $step['exit_code']." |\n";
file_put_contents($runDir.'/summary.md', $md);
fwrite(STDOUT, "\n{$md}\nEvidence: storage/app/nexora/target-orchestrator/{$runId}/\n");
exit($status === 'pass' ? 0 : 1);
