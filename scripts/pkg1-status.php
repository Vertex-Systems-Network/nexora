<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root.'/bootstrap/nexora-process-environment.php';
require_once $root.'/scripts/lib/pkg1-closure.php';
require_once $root.'/scripts/lib/target-composer.php';
require_once $root.'/scripts/lib/dependency-toolchain.php';

$json = in_array('--json', $argv, true);
$baseUrl = '';
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--base-url=')) {
        $baseUrl = rtrim(trim(substr($argument, 11)), '/');
    }
}
if ($baseUrl === '') {
    $baseUrl = rtrim((string) (getenv('APP_URL') ?: 'http://nexora'), '/');
}

$source = nexoraComputeSourceAttestation($root);
$platform = require $root.'/config/nexora.php';
$environment = NexoraBootstrapProcessEnvironment::build($root, $_ENV);
$hasVendor = is_file($root.'/vendor/autoload.php');
$hasNodeModules = is_dir($root.'/node_modules');

$probe = static function (array $command) use ($root, $environment): array {
    $result = nexoraPkg1Run($command, $root, $environment);
    return [
        'pass' => $result['exit_code'] === 0,
        'exit_code' => $result['exit_code'],
        'detail' => substr(trim(nexoraPkg1Redact($result['stderr'] !== '' ? $result['stderr'] : $result['stdout'])), 0, 900),
    ];
};

$status = 'READY';
$phase = 'dependency-locks';
$progress = 5;
$nextAction = 'RUN_PKG1';
$nextCommand = 'scripts\\pkg1-usable-closure.bat --operator="REAL NAME" --base-url='.$baseUrl;
$message = 'Start or resume PKG-1.';
$resumeCommand = 'scripts\\pkg1-run.bat "REAL NAME" '.$baseUrl;
$checks = [
    'source_tree_sha256' => $source['tree_sha256'],
    'vendor_present' => $hasVendor,
    'node_modules_present' => $hasNodeModules,
    'candidate_present' => is_file($root.'/storage/app/nexora/dependency-intake/candidates/candidate.json'),
    'reviewed_attestation_present' => is_file($root.'/storage/app/nexora/dependency-intake/reviewed-locks.json'),
    'c1_evidence_present' => is_file($root.'/storage/app/nexora/n1-c1/latest.json'),
    'installation_lock_present' => is_file($root.'/storage/app/nexora/installed.lock'),
    'closure_receipt_present' => is_file($root.'/storage/app/nexora/pkg1/closure.json'),
];

// A verified closure is terminal. Do this before all dependency/network checks so
// an already-usable installation remains statusable and resumable while offline.
if ($checks['closure_receipt_present'] && $hasVendor) {
    $closure = $probe([PHP_BINARY, 'scripts/pkg1-closure-evidence-verify.php']);
    $checks['closure_receipt_valid'] = $closure['pass'];
    if ($closure['pass']) {
        $status = 'COMPLETE';
        $phase = 'complete';
        $progress = 100;
        $nextAction = 'NONE';
        $nextCommand = null;
        $message = 'PKG-1 is already closed and the sealed closure receipt still verifies on this exact target.';
        goto render;
    }
    $checks['closure_receipt_detail'] = $closure['detail'];
}

$journalPath = $root.'/storage/app/nexora/dependency-intake/lock-promotion-journal.json';
if (is_file($journalPath)) {
    try {
        $journal = json_decode((string) file_get_contents($journalPath), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        $journal = ['status' => 'invalid'];
    }
    $journalStatus = (string) ($journal['status'] ?? 'invalid');
    $checks['promotion_journal_status'] = $journalStatus;
    if (! in_array($journalStatus, ['complete', 'rolled-back'], true)) {
        $status = 'WAITING_RECOVERY';
        $phase = 'dependency-locks';
        $progress = 10;
        $nextAction = 'RECOVER_LOCK_PROMOTION';
        $nextCommand = 'scripts\\recover-dependency-lock-promotion.bat --confirm=ROLLBACK';
        $message = 'An incomplete dependency-lock promotion journal must be rolled back before PKG-1 can continue.';
        goto render;
    }
}

// C1 PASS is the dependency/network boundary. Once exact-source C1 evidence is
// reusable, status and closure resume must not require Composer or registry access.
if ($checks['c1_evidence_present'] && $hasVendor && $hasNodeModules) {
    $c1 = $probe([PHP_BINARY, 'scripts/n1-c1-evidence-verify.php']);
    $checks['c1_evidence_valid'] = $c1['pass'];
    if ($c1['pass']) {
        $phase = 'source-identity';
        $progress = 65;
        $status = 'READY_SOURCE_RESUME';
        $nextAction = 'RUN_PKG1';
        $message = 'Exact-source C1 14/14 evidence is reusable; dependency and network stages can be skipped.';

        if (is_file($root.'/.env')) {
            $sourceStatus = $probe([PHP_BINARY, 'artisan', 'nexora:source:status', '--require-web-ack']);
            $checks['source_web_ack_valid'] = $sourceStatus['pass'];
            if (! $sourceStatus['pass']) {
                $status = 'WAITING_SOURCE_RESTART';
                $progress = 68;
                $nextAction = 'RELOAD_PHP_AND_RUN_PKG1';
                $message = 'C1 is reusable, but CLI/web source convergence is not acknowledged yet.';
                goto render;
            }

            $lock = $probe([PHP_BINARY, 'artisan', 'nexora:install:lock-status', '--assert-valid']);
            $checks['installation_lock_valid'] = $lock['pass'];
            if (! $lock['pass']) {
                $status = 'WAITING_INSTALL';
                $phase = 'installer';
                $progress = 72;
                $nextAction = 'OPEN_INSTALLER';
                $nextCommand = $baseUrl.'/install';
                $message = 'C1 and exact source convergence are PASS; complete the browser installer to 100%.';
                goto render;
            }

            $post = $probe([PHP_BINARY, 'artisan', 'nexora:runtime:post-install-status', '--assert-ready']);
            $checks['post_install_handoff_valid'] = $post['pass'];
            if (! $post['pass']) {
                $status = 'WAITING_POST_INSTALL';
                $phase = 'post-install';
                $progress = 85;
                $nextAction = 'RUN_PKG1';
                $message = 'The installation lock is valid, but post-install runtime handoff still needs reconciliation/reload.';
                goto render;
            }

            $status = 'WAITING_AUTH_SMOKE';
            $phase = 'usable-smoke';
            $progress = 92;
            $nextAction = 'FINALIZE_LOGIN_SMOKE';
            $nextCommand = 'scripts\\pkg1-finalize-login-smoke.bat "REAL NAME" '.$baseUrl;
            $message = 'Runtime handoff is ready; only the real Super Admin login → /admin smoke and sealed closure remain.';
        }
        goto render;
    }
    $checks['c1_evidence_detail'] = $c1['detail'];
}

if ($checks['reviewed_attestation_present']) {
    $review = $probe([PHP_BINARY, 'scripts/dependency-lock-review.php', '--verify-attestation', '--require-refresh-handoff']);
    $checks['reviewed_attestation_valid'] = $review['pass'];
    if ($review['pass']) {
        $status = 'READY_C1';
        $phase = 'c1';
        $progress = 30;
        $nextAction = 'RUN_PKG1';
        $message = 'Reviewed dependency locks are valid; PKG-1 can skip candidate generation/review and continue directly into C1.';
        goto render;
    }
    $checks['reviewed_attestation_detail'] = $review['detail'];
}

$candidateState = nexoraPkg1DependencyCandidateState($root);
$checks['candidate_present'] = $candidateState['present'];
$checks['candidate_valid'] = $candidateState['valid'];
if ($candidateState['present']) {
    if ($candidateState['valid']) {
        $status = 'WAITING_REVIEW';
        $phase = 'dependency-locks';
        $progress = 20;
        $nextAction = 'REVIEW_AND_PROMOTE';
        $nextCommand = 'scripts\\pkg1-usable-closure.bat --operator="REAL NAME" --reviewer="REAL NAME" --promote-reviewed --base-url='.$baseUrl;
        $message = 'An exact-source reproducible and supply-chain-admitted dependency candidate exists and requires explicit human review before promotion.';
        goto render;
    }
    $checks['candidate_errors'] = $candidateState['errors'];
    $status = 'STALE_CANDIDATE';
    $phase = 'dependency-locks';
    $progress = 12;
    $nextAction = 'RUN_PKG1';
    $nextCommand = 'scripts\\pkg1-usable-closure.bat --operator="REAL NAME" --base-url='.$baseUrl;
    $message = 'A stale/corrupt dependency candidate exists. PKG-1 will quarantine it and regenerate a fresh audited pair without touching root locks.';
    goto render;
}

$composer = nexoraLocateTargetComposer($root);
$checks['composer_available'] = (bool) ($composer['available'] ?? false);
$checks['composer_version'] = $composer['version'] ?? null;
$checks['composer_source'] = $composer['source'] ?? null;
if (($composer['available'] ?? false) === true) {
    $toolchain = nexoraCollectDependencyToolchain($root);
    $checks['dependency_toolchain_status'] = $toolchain['status'] ?? 'fail';
    $checks['dependency_toolchain_fingerprint_sha256'] = $toolchain['fingerprint_sha256'] ?? null;
    $checks['node_version'] = $toolchain['node']['version'] ?? null;
    $checks['npm_version'] = $toolchain['npm']['version'] ?? null;
    $checks['npm_execution_mode'] = $toolchain['npm']['execution_mode'] ?? null;
    $checks['npm_binary'] = $toolchain['npm']['binary'] ?? null;
    if (($toolchain['status'] ?? null) === 'pass') {
        $status = 'READY_CANDIDATE_GENERATION';
        $progress = 14;
        $nextAction = 'RUN_PKG1';
        $message = 'Composer, Node and npm are inside the certified ranges; PKG-1 can generate the audited reproducible dependency candidate pair.';
    } else {
        $status = 'BLOCKED_TOOLCHAIN';
        $progress = 12;
        $nextAction = 'FIX_TOOLCHAIN';
        $nextCommand = null;
        $toolchainErrors = array_values(array_map('strval', (array) ($toolchain['errors'] ?? [])));
        $checks['dependency_toolchain_errors'] = $toolchainErrors;
        $message = 'Dependency toolchain is not ready: '.($toolchainErrors !== [] ? implode(' ', $toolchainErrors) : 'unknown toolchain failure.');
    }
} else {
    $status = 'READY_COMPOSER_BOOTSTRAP';
    $progress = 10;
    $nextAction = 'RUN_PKG1';
    $message = 'No Composer is currently available; PKG-1 will attempt the verified local Composer bootstrap before candidate generation.';
}

render:
$payload = [
    'schema' => 1,
    'package' => 'PKG-1',
    'platform_version' => (string) ($platform['version'] ?? 'unknown'),
    'source_tree_sha256' => $source['tree_sha256'],
    'status' => $status,
    'phase' => $phase,
    'progress_percent' => $progress,
    'next_action' => $nextAction,
    'next_command' => $nextCommand,
    'resume_command' => $resumeCommand,
    'message' => $message,
    'checks' => $checks,
    'checked_at' => gmdate(DATE_ATOM),
];

if ($json) {
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    exit($status === 'COMPLETE' ? 0 : 2);
}

$filled = (int) floor(max(0, min(100, $progress)) / 5);
$bar = str_repeat('█', $filled).str_repeat('░', 20 - $filled);
fwrite(STDOUT, "PKG-1 {$progress}% · {$status} · phase={$phase}\n{$bar}\n");
fwrite(STDOUT, $message."\n");
fwrite(STDOUT, 'NEXT_ACTION='.$nextAction."\n");
if ($nextCommand !== null) {
    fwrite(STDOUT, 'NEXT_COMMAND='.$nextCommand."\n");
}
fwrite(STDOUT, 'RESUME_COMMAND='.$resumeCommand."\n");
exit($status === 'COMPLETE' ? 0 : 2);
