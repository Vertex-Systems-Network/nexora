<?php

declare(strict_types=1);

require_once __DIR__.'/n1-certification-session.php';

/** @return array<string,mixed>|null */
function nexoraEvidenceJson(string $path): ?array
{
    if (! is_file($path)) return null;
    try {
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        return is_array($data) ? $data : null;
    } catch (Throwable) {
        return null;
    }
}




function nexoraEvidenceMaxAgeHours(string $root, string $domain, int $default): int
{
    $path = $root.'/config/nexora-certification-evidence.php';
    if (! is_file($path)) return $default;
    $config = require $path;
    return max(1, (int) (($config['max_age_hours'][$domain] ?? $default)));
}

function nexoraNormalizeEvidenceBaseUrl(mixed $value): ?string
{
    $url = trim((string) $value);
    if ($url === '') return null;
    $parts = parse_url($url);
    if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) return null;
    $scheme = strtolower((string) $parts['scheme']);
    if (! in_array($scheme, ['http','https'], true)) return null;
    $host = strtolower(rtrim((string) $parts['host'], '.'));
    if ($host === '') return null;
    $port = isset($parts['port']) ? ':'.(int) $parts['port'] : '';
    $path = isset($parts['path']) ? '/'.trim((string) $parts['path'], '/') : '';
    if ($path === '/') $path = '';
    return $scheme.'://'.$host.$port.$path;
}

function nexoraEvidenceBaseUrlErrors(string $root, array $data, string $label, ?string $expected = null, bool $requireHttps = false): array
{
    $actual = nexoraNormalizeEvidenceBaseUrl($data['base_url'] ?? null);
    if ($actual === null) return ["{$label} base_url must be a valid http(s) target URL"];
    $errors = [];
    if ($requireHttps && ! str_starts_with($actual, 'https://')) $errors[] = "{$label} base_url must use HTTPS for final target certification";
    if ($expected !== null && ! hash_equals($expected, $actual)) $errors[] = "{$label} base_url does not match the certified target [{$expected}]";
    return $errors;
}

function nexoraCurrentSourceTreeSha256(string $root): string
{
    require_once $root.'/scripts/lib/source-attestation.php';
    return (string)nexoraComputeSourceAttestation($root)['tree_sha256'];
}

/** @return list<string> */
function nexoraValidateEvidenceSourceBinding(string $root,array $data,string $label): array
{
    $actual=nexoraCurrentSourceTreeSha256($root);
    $provided=strtolower(trim((string)($data['source_tree_sha256']??'')));
    if(preg_match('/^[a-f0-9]{64}$/',$provided)!==1) return ["{$label} source_tree_sha256 must be a real SHA-256 digest"];
    return hash_equals($actual,$provided)?[]:["{$label} source_tree_sha256 does not match the current certified source tree"];
}

/** @return list<string> */
function nexoraValidateZeroInstallEvidence(string $root,array $data): array
{
    $platform=require $root.'/config/nexora.php';$errors=[];
    if(($data['schema']??null)!==1)$errors[]='zero-install evidence schema must be 1';
    if(($data['platform_version']??null)!==($platform['version']??null))$errors[]='zero-install evidence platform_version mismatch';
    if(trim((string)($data['operator']??''))===''||($data['operator']??'')==='operator-name')$errors[]='zero-install evidence requires a real operator';
    if(!nexoraEvidenceTimestampFresh($data['completed_at']??null,nexoraEvidenceMaxAgeHours($root,'zero_install',168)))$errors[]='zero-install completed_at must be recent';
    foreach(['fresh_source_no_env','browser_installer_reached','dependency_bootstrap_completed','database_install_completed','admin_login_completed','installed_lockout','interrupted_install_recovery','stale_deployment_recovery','no_second_destructive_reset','clean_restart'] as $check) if(($data['checks'][$check]??null)!=='pass')$errors[]="zero-install check [{$check}] must be pass";
    return array_merge($errors,nexoraValidateEvidenceSourceBinding($root,$data,'zero-install evidence'),nexoraValidateEvidenceSessionBinding($root,$data,'zero-install evidence'));
}

/** @return list<string> */
function nexoraValidateUpgradeRehearsalEvidence(string $root, array $data): array
{
    $platform = require $root.'/config/nexora.php';
    $errors = [];

    if (($data['schema'] ?? null) !== 1) {
        $errors[] = 'upgrade rehearsal schema must be 1';
    }

    if (($data['target_version'] ?? null) !== ($platform['version'] ?? null)) {
        $errors[] = 'upgrade rehearsal target_version mismatch';
    }

    $sourceVersion = trim((string) ($data['source_version'] ?? ''));
    if ($sourceVersion === '' || $sourceVersion === ($platform['version'] ?? null)) {
        $errors[] = 'upgrade rehearsal must identify a real older source_version';
    } elseif (preg_match('/^\d+\.\d+\.\d+$/', $sourceVersion) !== 1) {
        $errors[] = 'upgrade rehearsal source_version must be a concrete x.y.z release';
    } else {
        require_once $root.'/app/Nexora/Foundation/Runtime/VersionConstraintMatcher.php';
        $matcher = new App\Nexora\Foundation\Runtime\VersionConstraintMatcher();
        $constraint = trim((string) (getenv('NEXORA_UPGRADE_SUPPORTED_SOURCE') ?: ''));

        if ($constraint === '') {
            $rawConfig = (string) @file_get_contents($root.'/config/nexora-upgrade.php');
            if (preg_match(
                "/'supported_source'\s*=>\s*env\('NEXORA_UPGRADE_SUPPORTED_SOURCE',\s*'([^']+)'\)/",
                $rawConfig,
                $matches,
            ) === 1) {
                $constraint = $matches[1];
            }
        }

        $constraint = $constraint !== '' ? $constraint : '>=0.34 <2.0';
        if (! $matcher->matches($sourceVersion, $constraint)) {
            $errors[] = 'upgrade rehearsal source_version is outside the supported upgrade window ['
                .$constraint.']';
        }

        $targetVersion = preg_replace('/-.*/', '', (string) ($platform['version'] ?? '0.0.0'));
        if (version_compare($sourceVersion, $targetVersion, '>=')) {
            $errors[] = 'upgrade rehearsal source_version must be older than the target release';
        }
    }

    $operator = trim((string) ($data['operator'] ?? ''));
    if ($operator === '' || $operator === 'operator-name') {
        $errors[] = 'upgrade rehearsal requires a real operator';
    }

    if (! nexoraEvidenceTimestampFresh(
        $data['completed_at'] ?? null,
        nexoraEvidenceMaxAgeHours($root, 'upgrade_rehearsal', 168),
    )) {
        $errors[] = 'upgrade rehearsal completed_at must be recent';
    }

    $requiredChecks = [
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

    foreach ($requiredChecks as $check) {
        if (($data['checks'][$check] ?? null) !== 'pass') {
            $errors[] = "upgrade rehearsal check [{$check}] must be pass";
        }
    }

    return array_merge(
        $errors,
        nexoraValidateEvidenceSourceBinding($root, $data, 'upgrade rehearsal evidence'),
        nexoraValidateEvidenceSessionBinding($root, $data, 'upgrade rehearsal evidence'),
    );
}

function nexoraEvidenceTimestampFresh(mixed $value, int $maxAgeHours): bool
{
    $time = strtotime((string) $value);
    if ($time === false) return false;
    $root=dirname(__DIR__,2);$config=is_file($root.'/config/nexora-certification-evidence.php')?require $root.'/config/nexora-certification-evidence.php':[];
    $future=max(0,(int)($config['max_future_clock_skew_seconds']??300));
    $age = time() - $time;
    return $age >= -$future && $age <= ($maxAgeHours * 3600);
}

/** @return list<string> */
function nexoraValidateBackupRestoreEvidence(string $root, array $data): array
{
    $platform = require $root.'/config/nexora.php';
    $maxAgeHours = nexoraEvidenceMaxAgeHours($root, 'backup_restore', 168);
    $errors = [];
    if (($data['schema'] ?? null) !== 1) $errors[] = 'schema must be 1';
    if (($data['platform_version'] ?? null) !== ($platform['version'] ?? null)) $errors[] = 'platform_version must match config/nexora.php';
    if (trim((string) ($data['operator'] ?? '')) === '' || ($data['operator'] ?? '') === 'operator-name') $errors[] = 'operator must identify the real operator';
    if (! nexoraEvidenceTimestampFresh($data['completed_at'] ?? null, $maxAgeHours)) $errors[] = 'completed_at must be recent and valid';
    foreach (['backup_created','backup_checksum_verified','restore_to_disposable_target','restore_completed','migration_status_clean','application_health_pass','admin_login_pass','data_spot_check_pass','source_backup_unchanged','production_not_overwritten'] as $check) {
        if (($data['checks'][$check] ?? null) !== 'pass') $errors[] = "check [{$check}] must be pass";
    }
    $checksum = strtolower(trim((string) ($data['backup_sha256'] ?? '')));
    if (preg_match('/^[a-f0-9]{64}$/', $checksum) !== 1) $errors[] = 'backup_sha256 must be a real SHA-256 digest';
    if (trim((string)($data['backup_id'] ?? '')) === '' || ($data['backup_id'] ?? '') === 'backup-id') $errors[] = 'backup_id must identify the rehearsed backup';
    if (trim((string)($data['restore_target'] ?? '')) === '' || ($data['restore_target'] ?? '') === 'disposable-target') $errors[] = 'restore_target must identify the disposable restore target';
    return array_merge($errors,nexoraValidateEvidenceSourceBinding($root,$data,'backup/restore evidence'),nexoraValidateEvidenceSessionBinding($root,$data,'backup/restore evidence'));
}

/** @return list<string> */
function nexoraValidateHaEvidence(string $root, array $data): array
{
    $platform = require $root.'/config/nexora.php';
    $maxAgeHours = nexoraEvidenceMaxAgeHours($root, 'multi_node_ha', 24);
    $requiredNodes = max(2, (int) (getenv('NEXORA_HA_REQUIRED_NODES') ?: 2));
    $errors = [];
    if (($data['schema'] ?? null) !== 1) $errors[] = 'schema must be 1';
    if (($data['platform_version'] ?? null) !== ($platform['version'] ?? null)) $errors[] = 'platform_version must match config/nexora.php';
    $errors = array_merge($errors, nexoraEvidenceBaseUrlErrors($root,$data,'HA evidence'));
    if (trim((string) ($data['operator'] ?? '')) === '' || ($data['operator'] ?? '') === 'operator-name') $errors[] = 'operator must identify the real operator';
    if (! nexoraEvidenceTimestampFresh($data['completed_at'] ?? null, $maxAgeHours)) $errors[] = 'completed_at must be recent and valid';
    $nodes = is_array($data['nodes'] ?? null) ? $data['nodes'] : [];
    $required = $requiredNodes;
    if (count($nodes) < $required) $errors[] = "at least {$required} independent node observations are required";
    $keys = [];
    foreach ($nodes as $index => $node) {
        if (! is_array($node)) {
            $errors[] = "node {$index} must be an object";
            continue;
        }

        $nodeErrors = nexoraValidateHaNodeEvidence(
            $node,
            $index,
            (string) ($platform['version'] ?? ''),
        );
        $errors = array_merge($errors, $nodeErrors);

        $nodeKey = trim((string) ($node['node_key'] ?? ''));
        if ($nodeKey !== '') {
            $keys[] = $nodeKey;
        }
    }
    if (count(array_unique($keys)) !== count($keys)) $errors[] = 'node_key values must be unique';
    foreach (['shared_cache_cross_node','shared_session_cross_node','shared_object_storage_cross_node','async_queue_distribution','scheduler_single_leader','scheduler_failover','node_drain_readiness','worker_drain_restart','node_failure_recovery','version_consistency','deployment_generation_consistency','deep_deployment_integrity_each_node','cache_generation_namespace_consistency','session_schema_consistency','runtime_environment_fingerprint_consistency','runtime_activation_epoch_cache_consistency','runtime_engine_fingerprint_consistency','runtime_database_data_plane_consistency','runtime_storage_data_plane_consistency','shared_backup_storage_cross_node','runtime_service_data_plane_consistency','runtime_host_profile_consistency','database_clock_skew_within_limit','runtime_resource_policy_consistency','runtime_resource_capacity_minimums','runtime_policy_plane_consistency','runtime_policy_status_pass','runtime_process_policy_consistency','web_process_quorum','queue_process_quorum','scheduler_process_quorum','laravel_framework_version_consistency','runtime_dependency_fingerprint_consistency','dependency_review_status_pass'] as $check) {
        if (($data['checks'][$check] ?? null) !== 'pass') $errors[] = "check [{$check}] must be pass";
    }
    return array_merge($errors,nexoraValidateEvidenceSourceBinding($root,$data,'HA evidence'),nexoraValidateEvidenceSessionBinding($root,$data,'HA evidence'));
}



/**
 * Validate the identity and readiness evidence captured for one HA node.
 *
 * @param array<string,mixed> $node
 * @return list<string>
 */
function nexoraValidateHaNodeEvidence(array $node, int $index, string $platformVersion): array
{
    $errors = [];
    $nodeKey = trim((string) ($node['node_key'] ?? ''));

    if ($nodeKey === '' || str_starts_with($nodeKey, 'node-example')) {
        $errors[] = "node {$index} requires a real node_key";
    }

    if (($node['status'] ?? null) !== 'active') {
        $errors[] = "node {$index} status must be active";
    }

    if (($node['platform_version'] ?? null) !== $platformVersion) {
        $errors[] = "node {$index} platform_version mismatch";
    }

    $shaFields = [
        'deployment_generation',
        'runtime_environment_fingerprint',
        'runtime_engine_fingerprint',
        'runtime_database_fingerprint',
        'runtime_storage_fingerprint',
        'runtime_service_fingerprint',
        'runtime_resource_fingerprint',
        'runtime_policy_fingerprint',
        'runtime_process_fingerprint',
        'resource_deep_probe_sha256',
        'runtime_host_fingerprint',
        'runtime_dependency_fingerprint',
    ];

    foreach ($shaFields as $field) {
        if (! nexoraIsSha256($node[$field] ?? null)) {
            $errors[] = "node {$index} {$field} must be a real SHA-256 digest";
        }
    }

    foreach ([
        'runtime_policy_status',
        'runtime_process_policy_status',
        'resource_status',
        'dependency_review_status',
        'readiness',
    ] as $field) {
        if (strtolower(trim((string) ($node[$field] ?? ''))) !== 'pass') {
            $errors[] = "node {$index} {$field} must be pass";
        }
    }

    $runningLaravel = trim((string) ($node['laravel_framework_version'] ?? ''));
    $lockedLaravel = trim((string) ($node['laravel_framework_locked_version'] ?? ''));

    if (! nexoraLaravel13VersionIsSupported($runningLaravel)) {
        $errors[] = "node {$index} laravel_framework_version must be >=13.24.0 and <14.0.0";
    }

    if (! nexoraLaravel13VersionIsSupported($lockedLaravel)) {
        $errors[] = "node {$index} laravel_framework_locked_version must be >=13.24.0 and <14.0.0";
    }

    if ($runningLaravel !== '' && $lockedLaravel !== '' && $runningLaravel !== $lockedLaravel) {
        $errors[] = "node {$index} running Laravel version must match the reviewed lock";
    }

    $clockSkew = $node['clock_skew_ms'] ?? null;
    if (! is_int($clockSkew) && ! is_float($clockSkew)) {
        $errors[] = "node {$index} clock_skew_ms must be numeric";
    } else {
        $maximumSkew = (float) (getenv('NEXORA_HOST_MAX_DB_CLOCK_SKEW_MS') ?: 5000);
        if (abs((float) $clockSkew) > $maximumSkew) {
            $errors[] = "node {$index} clock_skew_ms exceeds policy";
        }
    }

    return $errors;
}

function nexoraIsSha256(mixed $value): bool
{
    return preg_match('/^[a-f0-9]{64}$/', strtolower(trim((string) $value))) === 1;
}

function nexoraLaravel13VersionIsSupported(string $version): bool
{
    return $version !== ''
        && version_compare($version, '13.24.0', '>=')
        && version_compare($version, '14.0.0', '<');
}

/** @return list<string> */
function nexoraValidateDatabaseMatrixEvidence(string $root,array $data): array
{
    $platform=require $root.'/config/nexora.php';$errors=[];$required=['mysql','mariadb','pgsql','sqlite','sqlsrv'];
    if(($data['schema']??null)!==2)$errors[]='database matrix schema must be 2';
    if(($data['platform_version']??null)!==($platform['version']??null))$errors[]='database matrix platform_version mismatch';
    if(($data['strict']??null)!==true)$errors[]='database matrix must be strict';
    $requested=array_map('strtolower',array_values((array)($data['requested_drivers']??[])));
    foreach($required as $driver) if(!in_array($driver,$requested,true))$errors[]="database matrix missing requested driver [{$driver}]";
    $seen=[];foreach((array)($data['results']??[]) as $row){if(!is_array($row))continue;$driver=strtolower((string)($row['driver']??''));if($driver!=='')$seen[$driver]=(string)($row['status']??'');}
    foreach($required as $driver) if(($seen[$driver]??null)!=='pass')$errors[]="database matrix driver [{$driver}] must pass";
    return array_merge($errors,nexoraValidateEvidenceSourceBinding($root,$data,'database matrix evidence'));
}

/** @return list<string> */
function nexoraValidateBrowserEvidenceForFinal(string $root, array $data): array
{
    $platform = require $root.'/config/nexora.php';
    $config = require $root.'/config/nexora-browser-certification.php';
    $errors=[];
    if (($data['schema'] ?? null)!==2) $errors[]='browser evidence schema must be 2';
    if (($data['platform_version'] ?? null)!==($platform['version'] ?? null)) $errors[]='browser evidence version mismatch';
    if (trim((string)($data['auditor'] ?? ''))==='' || ($data['auditor'] ?? '')==='operator-name') $errors[]='browser evidence requires a real auditor';
    if (!nexoraEvidenceTimestampFresh($data['completed_at'] ?? null, nexoraEvidenceMaxAgeHours($root,'browser',72))) $errors[]='browser completed_at must be recent';
    $required=[];
    foreach ((array)$config['browsers'] as $browser) foreach ((array)$config['viewports'] as $viewport) foreach ((array)$config['directions'] as $direction) foreach ((array)$config['themes'] as $theme) {
        $required[$browser.'|'.$viewport['name'].'|'.$viewport['width'].'|'.$direction.'|'.$theme]=false;
    }
    foreach (($data['matrix'] ?? []) as $row) {
        if (!is_array($row)) continue;
        $key=strtolower((string)($row['browser']??'')).'|'.($row['viewport']??'').'|'.($row['width']??'').'|'.($row['direction']??'').'|'.($row['theme']??'');
        if (array_key_exists($key,$required) && ($row['status']??null)==='pass') $required[$key]=true;
    }
    foreach($required as $key=>$pass) if(!$pass) $errors[]='browser matrix missing PASS '.$key;
    foreach ((array)$config['browsers'] as $browser) {
        $environment=null;
        foreach ((array)($data['environments']??[]) as $row) if(is_array($row)&&strtolower((string)($row['browser']??''))===$browser){$environment=$row;break;}
        if(!is_array($environment)){$errors[]="browser environment missing [{$browser}]";continue;}
        if(trim((string)($environment['version']??''))==='')$errors[]="browser environment [{$browser}] requires version";
        if(trim((string)($environment['os']??''))==='')$errors[]="browser environment [{$browser}] requires OS";
    }
    foreach ((array)$config['checks'] as $check) if (($data['checks'][$check] ?? null)!=='pass') $errors[]="browser check [{$check}] must be pass";
    $at=(array)($data['assistive_technology']??[]);
    if(($at['status']??null)!=='pass')$errors[]='assistive_technology status must be pass';
    if(trim((string)($at['name']??''))===''||($at['name']??'')==='screen-reader-name')$errors[]='assistive_technology requires a real screen reader/tool';
    if(trim((string)($at['browser']??''))==='')$errors[]='assistive_technology requires observed browser';
    return array_merge($errors,nexoraValidateEvidenceSourceBinding($root,$data,'browser evidence'),nexoraValidateEvidenceSessionBinding($root,$data,'browser evidence'));
}
