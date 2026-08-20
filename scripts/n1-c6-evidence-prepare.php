<?php

declare(strict_types=1);

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/n1-certification-session.php';
require_once $root.'/app/Nexora/Foundation/Filesystem/AtomicFileWriter.php';

$operator = '';
$outputDirectory = '';
$nodeCount = 2;

foreach ($argv as $argument) {
    if (str_starts_with($argument, '--operator=')) {
        $operator = trim(substr($argument, 11));
    } elseif (str_starts_with($argument, '--out=')) {
        $outputDirectory = trim(substr($argument, 6));
    } elseif (str_starts_with($argument, '--nodes=')) {
        $nodeCount = max(2, (int) substr($argument, 8));
    }
}

if ($operator === '' || $operator === 'operator-name') {
    fwrite(
        STDERR,
        "Usage: php scripts/n1-c6-evidence-prepare.php --operator=\"REAL NAME\" [--nodes=2] [--out=DIR]\n",
    );
    exit(2);
}

$platform = require $root.'/config/nexora.php';
$version = (string) ($platform['version'] ?? 'unknown');
$source = nexoraComputeSourceAttestation($root);
$session = nexoraEnsureCertificationSession($root);
$sessionId = (string) $session['session_id'];

$outputDirectory = $outputDirectory !== ''
    ? $outputDirectory
    : $root.'/storage/app/nexora/n1-c6/operator-kit-'.gmdate('Ymd-His');

$writer = new AtomicFileWriter();
$writer->ensureDirectory($outputDirectory);

$nodes = [];
for ($number = 1; $number <= $nodeCount; $number++) {
    $nodes[] = [
        'node_key' => 'node-example-'.$number,
        'platform_version' => $version,
        'deployment_generation' => 'replace-with-64-character-generation',
        'runtime_environment_fingerprint' => 'replace-with-64-character-environment-fingerprint',
        'runtime_engine_fingerprint' => 'replace-with-64-character-engine-fingerprint',
        'runtime_database_fingerprint' => 'replace-with-64-character-database-fingerprint',
        'runtime_storage_fingerprint' => 'replace-with-64-character-storage-fingerprint',
        'runtime_service_fingerprint' => 'replace-with-64-character-service-fingerprint',
        'runtime_host_fingerprint' => 'replace-with-64-character-host-fingerprint',
        'clock_skew_ms' => 999999,
        'runtime_resource_fingerprint' => 'replace-with-64-character-resource-fingerprint',
        'resource_deep_probe_sha256' => 'replace-with-64-character-resource-deep-probe-sha256',
        'resource_status' => 'fail',
        'runtime_policy_fingerprint' => 'replace-with-64-character-policy-fingerprint',
        'runtime_policy_status' => 'fail',
        'runtime_process_fingerprint' => 'replace-with-64-character-process-policy-fingerprint',
        'runtime_process_policy_status' => 'fail',
        'laravel_framework_version' => 'replace-with-running-laravel-version',
        'laravel_framework_locked_version' => 'replace-with-reviewed-locked-laravel-version',
        'runtime_dependency_fingerprint' => 'replace-with-64-character-dependency-fingerprint',
        'dependency_review_status' => 'fail',
        'status' => 'inactive',
        'readiness' => 'fail',
    ];
}

$requiredChecks = [
    'shared_cache_cross_node',
    'shared_session_cross_node',
    'shared_object_storage_cross_node',
    'async_queue_distribution',
    'scheduler_single_leader',
    'scheduler_failover',
    'node_drain_readiness',
    'worker_drain_restart',
    'node_failure_recovery',
    'version_consistency',
    'deployment_generation_consistency',
    'deep_deployment_integrity_each_node',
    'cache_generation_namespace_consistency',
    'session_schema_consistency',
    'runtime_environment_fingerprint_consistency',
    'runtime_activation_epoch_cache_consistency',
    'runtime_engine_fingerprint_consistency',
    'runtime_database_data_plane_consistency',
    'runtime_storage_data_plane_consistency',
    'shared_backup_storage_cross_node',
    'runtime_service_data_plane_consistency',
    'runtime_host_profile_consistency',
    'database_clock_skew_within_limit',
    'runtime_resource_policy_consistency',
    'runtime_resource_capacity_minimums',
    'runtime_policy_plane_consistency',
    'runtime_policy_status_pass',
    'runtime_process_policy_consistency',
    'web_process_quorum',
    'queue_process_quorum',
    'scheduler_process_quorum',
    'laravel_framework_version_consistency',
    'runtime_dependency_fingerprint_consistency',
    'dependency_review_status_pass',
];
$checkRows = array_fill_keys($requiredChecks, 'fail');

$payload = [
    'schema' => 1,
    'platform_version' => $version,
    'base_url' => 'https://replace-target/',
    'operator' => $operator,
    'completed_at' => null,
    'nodes' => $nodes,
    'checks' => $checkRows,
    'notes' => 'Fail-closed template. Mark PASS only after direct observation on independent runtime nodes.',
    'source_tree_sha256' => $source['tree_sha256'],
    'certification_session_id' => $sessionId,
];

$writer->write(
    $outputDirectory.'/ha-evidence.json',
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
    0755,
    0640,
);

$runbook = <<<MARKDOWN
# Nexora N1.0-C6 Multi-node HA Runbook

Platform: `{$version}`  
Source: `{$source['tree_sha256']}`  
Session: `{$sessionId}`

1. Deploy the exact same source and reviewed dependency build to at least two independent active nodes.
2. On every node run the HA status plus deep engine, database, storage, service, resource, policy and process status commands. Also run `php
artisan nexora:runtime:dependency-status` and `php artisan nexora:runtime:compatibility-status --deep`.
3. Verify each node runs the same reviewed Laravel 13.x version, that the running Laravel version equals the reviewed `composer.lock`, and that
`runtime_dependency_fingerprint` converges across the cluster with dependency review status PASS.
4. Run `php artisan nexora:ha:rehearse` and directly observe cache/session/object-storage sharing, queue distribution, scheduler
leadership/failover, process-role quorums, deployment/runtime identity convergence, clock skew, resource minima and all other checks in
`ha-evidence.json`.
5. Replace a FAIL only after direct observation. Use real node keys, a real operator and a real completion timestamp.
6. Keep C4/C5 evidence sealed, then run `scripts\n1-c6-final-certify.bat --base-url=https://TARGET --evidence={$outputDirectory}`.

C6 never auto-accepts dependency locks, auto-updates Laravel, or performs destructive database operations.
MARKDOWN;

$writer->write($outputDirectory.'/RUNBOOK.md', $runbook, 0755, 0640);

fwrite(
    STDOUT,
    "[N1.0-C6 Evidence Kit] Created {$outputDirectory}\n"
    .$nodeCount." node rows + ".count($requiredChecks)." HA checks remain FAIL until genuinely observed.\n",
);
