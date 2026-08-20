<?php

declare(strict_types=1);

use App\Nexora\Foundation\Filesystem\AtomicFileWriter;

$root = dirname(__DIR__);
require_once $root.'/scripts/lib/source-attestation.php';
require_once $root.'/scripts/lib/n1-certification-session.php';
require_once $root.'/app/Nexora/Foundation/Filesystem/AtomicFileWriter.php';

$platform = require $root.'/config/nexora.php';
$version = (string) ($platform['version'] ?? 'unknown');
$source = nexoraComputeSourceAttestation($root);
$operator = 'operator-name';
$outputDirectory = '';

foreach ($argv as $argument) {
    if (str_starts_with($argument, '--operator=')) {
        $operator = trim(substr($argument, 11));
    } elseif (str_starts_with($argument, '--out=')) {
        $outputDirectory = trim(substr($argument, 6));
    }
}

$runId = gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));
$directory = $outputDirectory !== ''
    ? $outputDirectory
    : $root.'/storage/app/nexora/n1-c4/operator-kit/'.$runId;

$writer = new AtomicFileWriter();
$writer->ensureDirectory($directory);

$session = nexoraEnsureCertificationSession($root);
$sessionId = (string) $session['session_id'];
$timestamp = gmdate(DATE_ATOM);

$zeroInstallChecks = [
    'fresh_source_no_env',
    'browser_installer_reached',
    'dependency_bootstrap_completed',
    'database_install_completed',
    'admin_login_completed',
    'installed_lockout',
    'interrupted_install_recovery',
    'stale_deployment_recovery',
    'no_second_destructive_reset',
    'clean_restart',
];
$upgradeChecks = [
    'clone_created',
    'preflight_pass',
    'backup_verified',
    'restore_readiness_verified',
    'preexisting_maintenance_takeover_rejected',
    'plan_sealed',
    'apply_completed',
    'migrations_clean',
    'runtime_sync_pass',
    'pre_metadata_health_pass',
    'post_metadata_health_pass',
    'admin_login_pass',
    'installation_id_preserved',
    'installed_at_preserved',
    'target_version_recorded',
    'tampered_plan_rejected',
    'recovery_status_drill',
    'recovery_decision_recorded',
    'distributed_upgrade_lock_verified',
    'peer_quiescence_verified',
    'runtime_activity_zero_verified',
    'queue_backlog_zero_verified',
    'current_node_quiescence_verified',
    'scheduler_fencing_verified',
    'mixed_version_runtime_fence_verified',
    'old_node_reactivation_rejected',
    'compatibility_drift_rejected',
    'migration_safety_plan_bound',
    'destructive_pending_migration_rejected',
    'migration_ledger_plan_bound',
    'migration_ledger_converged',
    'cluster_recovery_lock_drill',
    'post_upgrade_node_convergence_verified',
    'atomic_cutover_barrier_verified',
    'web_cutover_barrier_503',
    'scheduler_cutover_barrier_rejected',
    'legacy_queue_payload_rejected',
    'same_major_old_queue_payload_rejected',
    'exact_queue_payload_accepted',
    'target_deployment_generation_plan_bound',
    'deployment_generation_drift_rejected',
    'same_version_wrong_generation_peer_rejected',
    'queue_wrong_generation_rejected',
    'inertia_asset_version_reload_verified',
    'stale_json_client_rejected',
    'cache_generation_namespace_verified',
    'session_schema_guard_verified',
    'deep_deployment_integrity_verified',
    'runtime_environment_fingerprint_verified',
    'same_version_environment_drift_rejected',
    'queue_wrong_environment_rejected',
    'app_key_rotation_previous_key_required',
    'app_key_rotation_cluster_convergence',
    'app_key_rotation_commit_lineage',
    'runtime_activation_epoch_rotated',
    'runtime_activation_cache_fingerprint_verified',
    'framework_cache_drift_rejected',
    'stale_queue_process_epoch_rejected',
    'queue_wrong_activation_rejected',
    'activation_cluster_convergence_verified',
    'activation_deep_status_verified',
    'opcache_restart_evidence_if_required',
    'runtime_engine_fingerprint_verified',
    'php_patch_drift_rejected',
    'extension_profile_drift_rejected',
    'pdo_driver_set_verified',
    'queue_wrong_engine_rejected',
    'engine_cluster_convergence_verified',
    'engine_deep_status_verified',
    'database_data_plane_fingerprint_verified',
    'database_session_profile_verified',
    'database_schema_plan_bound',
    'manual_schema_drift_rejected',
    'backup_schema_binding_verified',
    'database_schema_post_migration_attested',
    'queue_wrong_database_data_plane_rejected',
    'database_cluster_convergence_verified',
    'database_deep_status_verified',
    'storage_data_plane_fingerprint_verified',
    'shared_object_storage_verified',
    'media_storage_roundtrip_verified',
    'backup_storage_roundtrip_verified',
    'storage_plan_bound',
    'storage_drift_rejected',
    'backup_storage_binding_verified',
    'queue_wrong_storage_data_plane_rejected',
    'storage_cluster_convergence_verified',
    'storage_deep_status_verified',
    'runtime_service_data_plane_fingerprint_verified',
    'cache_service_roundtrip_verified',
    'queue_service_visibility_verified',
    'redis_service_ping_if_used',
    'mail_service_dns_verified',
    'tls_ca_bundle_profile_verified',
    'proxy_profile_consistency_verified',
    'outbound_private_destination_rejected',
    'outbound_dns_pin_policy_verified',
    'service_cluster_convergence_verified',
    'runtime_host_fingerprint_verified',
    'timezone_locale_determinism_verified',
    'database_clock_skew_within_limit',
    'queue_future_timestamp_rejected',
    'distributed_lease_db_clock_anchor_verified',
    'temp_atomic_flock_entropy_verified',
    'host_cluster_convergence_verified',
    'runtime_resource_envelope_verified',
    'upgrade_low_memory_rejected',
    'upgrade_low_disk_rejected',
    'backup_scratch_capacity_verified',
    'queue_wrong_resource_policy_rejected',
    'resource_policy_drift_rejected',
    'resource_cluster_convergence_verified',
    'resource_deep_status_verified',
    'effective_policy_fingerprint_verified',
    'policy_production_fail_closed_verified',
    'policy_env_override_drift_rejected',
    'queue_wrong_policy_plane_rejected',
    'cross_node_policy_convergence_verified',
    'update_trust_policy_invariants_verified',
    'release_supply_chain_policy_invariants_verified',
    'transfer_http_limit_relationship_verified',
    'runtime_process_policy_fingerprint_verified',
    'web_process_heartbeat_fresh_verified',
    'queue_worker_idle_heartbeat_verified',
    'scheduler_process_heartbeat_verified',
    'queue_indefinite_blocking_rejected_for_ha',
    'queue_wrong_process_policy_rejected',
    'process_policy_drift_rejected',
    'web_process_quorum_loss_rejected',
    'queue_process_quorum_loss_rejected',
    'scheduler_process_quorum_loss_rejected',
    'laravel_13_minor_update_reviewed',
    'dependency_only_generation_drift_detected',
    'dependency_reconcile_requires_maintenance',
    'unreviewed_lock_rejected',
    'locked_framework_runtime_match_verified',
    'dependency_reconcile_activation_rotated',
    'dependency_reconcile_queue_restart_signaled',
    'dependency_reconcile_receipt_verified',
    'laravel_14_major_rejected',
    'compatibility_mismatch_diagnostics_verified',
    'fresh_install_missing_review_bootstrap_verified',
    'fresh_install_bootstrap_requires_exact_lockfiles',
    'fresh_install_running_laravel_matches_lock',
    'fresh_install_installed_composer_runtime_matches_lock',
    'fresh_install_package_manifest_lock_match_verified',
    'fresh_install_corrupt_review_rejected',
    'fresh_install_bootstrap_receipt_verified',
    'bootstrap_install_runtime_identity_accepted_without_review_503',
    'reviewed_dependency_provenance_sync_verified',
    'review_sync_generation_unchanged',
    'installer_doctor_dependency_trust_preflight_verified',
    'installation_lock_retry_after_missing_review_succeeds',
    'sealed_install_lock_schema_verified',
    'sealed_install_lock_sha256_verified',
    'corrupt_install_lock_fail_closed',
    'corrupt_install_lock_does_not_reopen_installer',
    'legacy_install_lock_backward_compatibility_verified',
    'legacy_install_lock_resealed_on_metadata_update',
    'bootstrap_receipt_staged_until_final_attestation',
    'orphan_bootstrap_receipt_cleared_before_retry',
    'bootstrap_receipt_integrity_verified_before_publish',
    'installed_lock_commit_point_verified',
    'post_commit_cleanup_failure_nonfatal',
    'post_commit_run_telemetry_failure_nonfatal',

    'installer_dependency_trust_resolved_before_database_mutation',
    'installer_protocol_version_visible',
    'recoverable_database_resume_choice_verified',
    'recoverable_database_reset_choice_verified',
    'recoverable_database_reset_requires_backup_or_no_backup_consent',
    'final_install_button_visible_on_review_step',
    'weak_password_consent_gate_verified',
    'low_medium_password_consent_gate_verified',
    'strong_password_no_consent_verified',
    'password_hard_floor_rejected_even_with_consent',
    'password_and_database_consent_audit_metadata_verified',
    'interrupted_install_resume_provenance_recorded',
    'exact_resume_provenance_allows_resume',
    'changed_source_resume_rejected',
    'changed_migration_manifest_resume_rejected',
    'changed_dependency_lock_resume_rejected',
    'legacy_resume_without_provenance_rejected',
    'incompatible_recovery_forces_start_clean_consent',

    'stale_tenant_context_cleared_before_seed',
    'default_tenant_re_resolved_after_migration',
    'crm_pipeline_tenant_fk_seed_verified',
    'helpdesk_sla_tenant_fk_seed_verified',
    'newsletter_list_tenant_fk_seed_verified',
    'tenant_seed_idempotency_after_context_reset',
    'queue_tenant_context_cleared_before_job',
    'queue_tenant_scope_fresh_organization_verified',
    'deleted_queue_tenant_rejected_before_job_logic',
    'suspended_queue_tenant_rejected_before_job_logic',
    'queue_tenant_context_cleared_after_success',
    'queue_tenant_context_cleared_after_exception',
    'scheduler_tenant_context_isolation_verified',
    'tenant_default_seed_transaction_verified',
    'cross_tenant_queue_context_bleed_rejected',


];
$backupChecks = [
    'backup_created',
    'backup_checksum_verified',
    'restore_to_disposable_target',
    'restore_completed',
    'migration_status_clean',
    'application_health_pass',
    'admin_login_pass',
    'data_spot_check_pass',
    'source_backup_unchanged',
    'production_not_overwritten',
];

$zeroInstallEvidence = [
    'schema' => 1,
    'platform_version' => $version,
    'source_tree_sha256' => $source['tree_sha256'],
    'certification_session_id' => $sessionId,
    'operator' => $operator,
    'completed_at' => $timestamp,
    'target' => 'fresh-disposable-target',
    'checks' => array_fill_keys($zeroInstallChecks, 'fail'),
    'notes' => 'Fail-closed. Change a check to pass only after direct observation on a disposable fresh target.',
];

$upgradeEvidence = [
    'schema' => 1,
    'source_version' => 'replace-with-real-older-version',
    'target_version' => $version,
    'source_tree_sha256' => $source['tree_sha256'],
    'certification_session_id' => $sessionId,
    'operator' => $operator,
    'completed_at' => $timestamp,
    'target' => 'disposable-existing-install-clone',
    'checks' => array_fill_keys($upgradeChecks, 'fail'),
    'notes' => 'Fail-closed. Use a clone of a real older Nexora installation; never rehearse on production.',
];

$backupEvidence = [
    'schema' => 1,
    'platform_version' => $version,
    'source_tree_sha256' => $source['tree_sha256'],
    'certification_session_id' => $sessionId,
    'operator' => $operator,
    'completed_at' => $timestamp,
    'backup_id' => 'backup-id',
    'backup_sha256' => 'replace-with-64-character-sha256',
    'restore_target' => 'disposable-target',
    'checks' => array_fill_keys($backupChecks, 'fail'),
    'notes' => 'Fail-closed. The source backup must remain immutable; restore only to a disposable target.',
];

$evidenceFiles = [
    'zero-install-evidence.json' => $zeroInstallEvidence,
    'upgrade-rehearsal-evidence.json' => $upgradeEvidence,
    'backup-restore-evidence.json' => $backupEvidence,
];

foreach ($evidenceFiles as $name => $payload) {
    $writer->write(
        $directory.'/'.$name,
        json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL,
        0755,
        0640,
    );
}

$runbook = <<<MARKDOWN
# Nexora N1.0-C4 Operator Runbook

Platform: `{$version}`  
Source: `{$source['tree_sha256']}`  
Session: `{$sessionId}`

## 1. Fresh install + interrupted recovery
Use a disposable target with no `.env`, `vendor`, `node_modules` or `public/build`. Reach the browser installer, complete install/login, confirm
installer lockout, then rehearse interrupted installation and stale deployment recovery. Do not mark `no_second_destructive_reset` PASS unless
database state proves recovery resumed instead of resetting again.

Before opening the installer, run `php artisan nexora:install:doctor`. A clean source package may legitimately have no reviewed-lock attestation.
In that case, fresh installation may continue only when deterministic `composer.lock` and `package-lock.json` exist, the installed Composer
runtime matches `composer.lock`, the running Laravel version matches the lock, and package.json matches the package-lock root metadata. Capture
the bootstrap receipt. A present-but-corrupt/stale review file must block bootstrap. After formal lock review, run `php artisan
nexora:runtime:dependency-review-sync --operator="REAL OPERATOR" --confirm=SYNC` and prove deployment generation did not change. Re-run the
installation-lock step after an earlier missing-review failure and prove it completes without a second destructive database reset.

## 2. Existing-install upgrade
Clone a real older installation and keep the clone isolated from production traffic. Run `php artisan nexora:upgrade:preflight`, create and
verify a backup, seal the upgrade plan, inspect it, and only then apply it. On multi-node targets, prove distributed locking, peer draining,
scheduler fencing, zero in-flight runtime activity, empty queue backlog, and the atomic cutover barrier before migrations.

For dependency refreshes, use the reviewed-lock workflow while maintenance mode is enabled. Laravel 13.x dependency changes must be reviewed,
installed from the accepted lock, checked with `php artisan nexora:runtime:dependency-status`, diagnosed with `php artisan
nexora:runtime:compatibility-status --deep`, and reconciled only with `php artisan nexora:runtime:dependency-reconcile --operator="REAL OPERATOR"
--confirm=RECONCILE`. The reconcile command must rotate activation state, signal queue restart, write an auditable receipt, and leave maintenance
mode enabled. Laravel 14+ must remain rejected until a separate major-version compatibility review is completed.

The exact-current queue payload must use schema 13 and match deployment, environment, activation, engine, database, storage, service, host,
resource, policy and process-policy identities. Rehearse every corresponding wrong-identity rejection in the evidence template.

Run the deep runtime status commands for activation, engine, database, storage, service, resource, policy and process planes. Rehearse protected
failure/recovery behavior only on disposable clones. Never convert a source contract into a PASS observation without direct target evidence.

## 3. Backup + disposable restore
Run `php artisan nexora:backup:create`, `php artisan nexora:backup:verify <id>`, `php artisan nexora:backup:rehearse <id>`, and `php artisan
nexora:restore:plan <id>`. Perform the actual restore only on a disposable target, then validate migration status, health, admin login and
representative data.

## 4. Certify C4
Place the completed JSON files in one directory and run `scripts\n1-c4-operations-certify.bat --evidence=<directory>`.
MARKDOWN;

$writer->write($directory.'/RUNBOOK.md', $runbook, 0755, 0640);

fwrite(
    STDOUT,
    "[N1.0-C4 Evidence Kit] Created {$directory}\n"
    .count($upgradeChecks)." upgrade checks remain FAIL until a real operator changes them after observation.\n",
);
