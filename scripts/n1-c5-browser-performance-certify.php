<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/source-attestation.php';

$version = (string) ((require $root.'/config/nexora.php')['version'] ?? 'unknown');
$baseUrl = '';
$evidenceDir = '';
$auditor = '';
$waveAlertsReviewed = false;
$waveNoKey = false;
$w3cValidatorUrl = '';
$w3cCssValidatorUrl = '';
$waveApiUrl = '';
$waveKeyEnv = '';
$statusOnly = in_array('--status-only', $argv, true);

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--base-url=')) $baseUrl = trim(substr($arg, 11));
    elseif (str_starts_with($arg, '--evidence=')) $evidenceDir = trim(substr($arg, 11));
    elseif (str_starts_with($arg, '--auditor=')) $auditor = trim(substr($arg, 10));
    elseif ($arg === '--wave-alerts-reviewed') $waveAlertsReviewed = true;
    elseif ($arg === '--wave-no-key') $waveNoKey = true;
    elseif (str_starts_with($arg, '--w3c-validator-url=')) $w3cValidatorUrl = trim(substr($arg, 20));
    elseif (str_starts_with($arg, '--w3c-css-validator-url=')) $w3cCssValidatorUrl = trim(substr($arg, 24));
    elseif (str_starts_with($arg, '--wave-api-url=')) $waveApiUrl = trim(substr($arg, 15));
    elseif (str_starts_with($arg, '--wave-key-env=')) $waveKeyEnv = trim(substr($arg, 15));
}

$base = $root.'/storage/app/nexora/n1-c5';
if ($statusOnly) {
    $p = $base.'/latest.json';
    if (is_file($p)) {
        fwrite(STDOUT, (string) file_get_contents($p));
        exit(0);
    }
    fwrite(STDOUT, "[N1.0-C5] No prior C5 run evidence.\n");
    exit(2);
}

$runId = gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));
$dir = $base.'/'.$runId;
$logs = $dir.'/steps';
foreach ([$base, $dir, $logs] as $d) {
    if (! is_dir($d) && ! mkdir($d, 0775, true) && ! is_dir($d)) throw new RuntimeException("Unable to create {$d}");
}

$source = nexoraComputeSourceAttestation($root);
$env = NexoraBootstrapProcessEnvironment::build($root, [
    'APP_ENV' => 'testing',
    'APP_DEBUG' => 'false',
    'NEXORA_INSTALLER_BYPASS' => 'true',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
]);
$steps = [];
$status = 'pass';
$first = null;
$redact = static function (string $text): string {
    foreach ([
        '/((?:password|passwd|secret|token|authorization|cookie|api[_-]?key)\s*[:=]\s*)([^\s\r\n]+)/i',
        '/(Bearer\s+)[A-Za-z0-9._~+\/-]+/i',
    ] as $p) $text = (string) preg_replace($p, '$1[REDACTED]', $text);
    return $text;
};
$run = static function (string $id, string $label, array $cmd) use ($root, $env, $logs, $redact, &$steps, &$status, &$first): bool {
    $line = implode(' ', array_map(static fn ($v) => escapeshellarg((string) $v), $cmd));
    fwrite(STDOUT, "\n[N1.0-C5] {$label}\n> {$line}\n");
    $started = microtime(true);
    $p = @proc_open($line, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $env, ['bypass_shell' => false]);
    if (! is_resource($p)) {
        $out = '';
        $err = 'Unable to start process.';
        $code = 127;
    } else {
        fclose($pipes[0]);
        $out = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $code = proc_close($p);
    }
    $out = $redact($out);
    $err = $redact($err);
    file_put_contents("{$logs}/{$id}.stdout.log", $out);
    file_put_contents("{$logs}/{$id}.stderr.log", $err);
    if ($out !== '') fwrite(STDOUT, $out.(str_ends_with($out, "\n") ? '' : "\n"));
    if ($err !== '') fwrite(STDERR, $err.(str_ends_with($err, "\n") ? '' : "\n"));
    $ok = $code === 0;
    $steps[] = [
        'id' => $id,
        'label' => $label,
        'status' => $ok ? 'pass' : 'fail',
        'required' => true,
        'exit_code' => $code,
        'duration_seconds' => round(microtime(true) - $started, 3),
        'stdout_log' => "steps/{$id}.stdout.log",
        'stderr_log' => "steps/{$id}.stderr.log",
    ];
    if (! $ok) {
        $status = 'blocked';
        if ($first === null) $first = ['id' => $id, 'label' => $label, 'exit_code' => $code];
    }
    return $ok;
};

$ordered = [
    ['c2-evidence', 'Exact-source C2 PASS evidence', [PHP_BINARY, 'scripts/n1-c2-evidence-verify.php']],
    ['browser-source', 'Browser / RTL / accessibility source contracts', [PHP_BINARY, 'scripts/browser-ux-contract-verify.php']],
    ['performance-source', 'Performance / packaging source contracts', [PHP_BINARY, 'scripts/performance-contract-verify.php']],
    ['security-source', 'HTTP security source contracts', [PHP_BINARY, 'scripts/security-contract-verify.php']],
    ['build-assets', 'Production Vite asset budgets and provenance', [PHP_BINARY, 'scripts/performance-build-verify.php']],
];
foreach ($ordered as [$id, $label, $cmd]) {
    if ($status !== 'pass') break;
    if (! $run($id, $label, $cmd)) break;
}

if ($status === 'pass') {
    if ($baseUrl === '') {
        $status = 'blocked';
        $first = ['id' => 'http-performance', 'label' => 'Target HTTP/security/latency evidence', 'exit_code' => 2];
        $steps[] = ['id' => 'http-performance', 'label' => 'Target HTTP/security/latency evidence', 'status' => 'fail', 'required' => true, 'exit_code' => 2, 'duration_seconds' => 0, 'stdout_log' => null, 'stderr_log' => null];
        fwrite(STDERR, "[N1.0-C5] Target base URL required: --base-url=https://target\n");
    } else {
        $run('http-performance', 'Target HTTP/security/latency evidence', [PHP_BINARY, 'scripts/http-smoke.php', '--require-base-url', '--base-url='.$baseUrl]);
    }
}

if ($status === 'pass') {
    $standards = [
        PHP_BINARY,
        'scripts/n1-c5-web-standards-certify.php',
        '--base-url='.$baseUrl,
        '--auditor='.$auditor,
    ];
    if ($waveAlertsReviewed) $standards[] = '--wave-alerts-reviewed';
    if ($waveNoKey) $standards[] = '--wave-no-key';
    if ($w3cValidatorUrl !== '') $standards[] = '--w3c-validator-url='.$w3cValidatorUrl;
    if ($w3cCssValidatorUrl !== '') $standards[] = '--w3c-css-validator-url='.$w3cCssValidatorUrl;
    if ($waveApiUrl !== '') $standards[] = '--wave-api-url='.$waveApiUrl;
    if ($waveKeyEnv !== '') $standards[] = '--wave-key-env='.$waveKeyEnv;
    $run('web-standards', 'W3C Nu + W3C CSS + WAVE target accessibility evidence', $standards);
}

if ($status === 'pass' && $evidenceDir !== '') {
    $run('operator-evidence', 'Validate and import browser + Web Vitals evidence', [PHP_BINARY, 'scripts/n1-c5-evidence-import.php', '--input='.$evidenceDir]);
} elseif ($status === 'pass') {
    $run('browser-evidence', 'Validate sealed browser/A11y/RTL evidence', [PHP_BINARY, 'scripts/n1-c5-browser-evidence-verify.php']);
    if ($status === 'pass') $run('web-vitals', 'Validate sealed Web Vitals evidence', [PHP_BINARY, 'scripts/n1-c5-web-vitals-evidence-verify.php']);
}
if ($status === 'pass') $run('web-standards-evidence', 'Validate sealed W3C/WAVE evidence', [PHP_BINARY, 'scripts/n1-c5-web-standards-evidence-verify.php']);
if ($status === 'pass') $run('c5-evidence', 'Seal exact-source C5 evidence manifest', [PHP_BINARY, 'scripts/n1-c5-evidence-verify.php']);

$hash = static fn (string $f): ?string => is_file($f) ? (hash_file('sha256', $f) ?: null) : null;
$summary = [
    'schema' => 1,
    'chunk' => 'N1.0-C5',
    'scope' => 'Browser / Accessibility / W3C HTML+CSS / WAVE / RTL / Performance',
    'platform_version' => $version,
    'source_tree_sha256' => $source['tree_sha256'],
    'run_id' => $runId,
    'status' => $status,
    'first_blocker' => $first,
    'base_url' => $baseUrl !== '' ? $baseUrl : null,
    'artifacts' => [
        'c2_evidence_sha256' => $hash($root.'/storage/app/nexora/n1-c2/latest.json'),
        'composer_lock_sha256' => $hash($root.'/composer.lock'),
        'package_lock_sha256' => $hash($root.'/package-lock.json'),
        'reviewed_locks_sha256' => $hash($root.'/storage/app/nexora/dependency-intake/reviewed-locks.json'),
        'browser_evidence_sha256' => $hash($root.'/storage/app/nexora/certification/browser-evidence.json'),
        'web_vitals_evidence_sha256' => $hash($root.'/storage/app/nexora/certification/web-vitals-evidence.json'),
        'web_standards_evidence_sha256' => $hash($root.'/storage/app/nexora/certification/web-standards-evidence.json'),
        'http_performance_sha256' => $hash($root.'/storage/app/nexora/certification/http-performance.json'),
        'build_assets_sha256' => $hash($root.'/storage/app/nexora/certification/build-assets.json'),
        'c5_evidence_sha256' => $hash($root.'/storage/app/nexora/n1-c5/c5-evidence.json'),
    ],
    'steps' => $steps,
    'finished_at' => gmdate(DATE_ATOM),
];
$json = json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
file_put_contents($dir.'/summary.json', $json);
file_put_contents($base.'/latest.json', $json);
$md = "# Nexora N1.0-C5 Browser / Accessibility / W3C HTML+CSS / WAVE / RTL / Performance\n\nStatus: **".strtoupper($status)."**  \nPlatform: `{$version}`  \nSource: `{$source['tree_sha256']}`\n";
if ($first) $md .= "First blocker: `{$first['id']}` — {$first['label']}\n";
$md .= "\n| Gate | Status | Exit |\n|---|---:|---:|\n";
foreach ($steps as $s) $md .= '| '.$s['label'].' | '.strtoupper($s['status']).' | '.$s['exit_code']." |\n";
file_put_contents($dir.'/summary.md', $md);
file_put_contents($base.'/latest.md', $md);
fwrite(STDOUT, "\n{$md}\nEvidence: storage/app/nexora/n1-c5/{$runId}/\n");
exit($status === 'pass' ? 0 : 1);
