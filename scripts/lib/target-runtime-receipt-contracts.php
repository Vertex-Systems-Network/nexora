<?php

declare(strict_types=1);

/**
 * Verify that a purported PASS target-runtime receipt represents the full,
 * ordered set of mandatory producer steps. A lone successful preflight must
 * never be enough to certify an entire target runtime.
 *
 * This validates receipt structure and completeness, not external authenticity.
 * The normal source/lock binding and log containment checks still apply.
 *
 * @param array<string,mixed> $summary
 * @return list<string>
 */
function nexoraTargetRuntimePassReceiptErrors(array $summary): array
{
    if (($summary['schema'] ?? null) !== 2) {
        return ['PASS target-runtime summary schema must be 2'];
    }
    if (!is_string($summary['run_id'] ?? null)
        || preg_match('/^[0-9]{8}-[0-9]{6}-[a-f0-9]{6}$/D', $summary['run_id']) !== 1) {
        return ['PASS target-runtime run_id is missing or malformed'];
    }
    $status = $summary['status'] ?? null;
    if (!in_array($status, ['target-readiness-pass', 'target-certification-pass'], true)) {
        return ['This contract validates only target-runtime PASS summaries'];
    }
    $environment = $summary['environment'] ?? null;
    if (!is_array($environment)
        || !is_bool($environment['full_certification_requested'] ?? null)
        || !is_bool($environment['install_dependencies_requested'] ?? null)) {
        return ['PASS runtime environment is missing explicit execution-mode flags'];
    }
    $full = $environment['full_certification_requested'];
    $install = $environment['install_dependencies_requested'];
    if (($status === 'target-certification-pass') !== $full) {
        return ['PASS runtime status contradicts full_certification_requested'];
    }
    $steps = $summary['steps'] ?? null;
    if (!is_array($steps) || !array_is_list($steps)) {
        return ['PASS runtime steps must be an ordered list'];
    }

    // Mirror the concrete producer sequence in scripts/target-runtime-run.php.
    // An added/removed required gate must deliberately update this contract.
    $expected = [
        'source-preflight',
        'target-bootstrap',
        'target-runtime-contract',
        'inertia-contract',
        'dependency-lock-review',
        'dependency-contract-strict',
        'dependency-runtime',
    ];
    if ($install) {
        array_push($expected, 'composer-install', 'npm-ci');
    }
    array_push($expected,
        'frontend-typecheck', 'frontend-tests', 'frontend-build',
        'frontend-build-budget', 'package-discover', 'optimize-clear',
        'artisan-about', 'route-list', 'schedule-list',
        'database-doctor', 'environment-doctor', 'filesystem-doctor',
        'transfer-doctor', 'runtime-doctor', 'concurrency-doctor'
    );
    if ($full) {
        $expected[] = 'isolated-full-certification';
    }
    $expected[] = 'closure-status'; // Informational, explicitly optional.

    if (count($steps) !== count($expected)) {
        return [sprintf('PASS runtime step coverage mismatch: expected %d steps, got %d', count($expected), count($steps))];
    }
    foreach ($expected as $index => $expectedId) {
        $step = $steps[$index];
        if (!is_array($step) || ($step['id'] ?? null) !== $expectedId) {
            return [sprintf('PASS runtime step %d must be [%s]', $index + 1, $expectedId)];
        }
        $required = $expectedId !== 'closure-status';
        if (($step['required'] ?? null) !== $required) {
            return [sprintf('PASS runtime step [%s] has incorrect required flag', $expectedId)];
        }
        if (!in_array($step['status'] ?? null, ['pass', 'fail'], true)
            || !is_int($step['exit_code'] ?? null)
            || (($step['status'] === 'pass') !== ($step['exit_code'] === 0))) {
            return [sprintf('PASS runtime step [%s] has inconsistent result', $expectedId)];
        }
        if ($required && $step['status'] !== 'pass') {
            return [sprintf('PASS runtime contains failed mandatory step [%s]', $expectedId)];
        }
    }
    return [];
}
