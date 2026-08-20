<?php

declare(strict_types=1);

$get = static function (string $name, mixed $default=null): mixed {
    if (function_exists('env')) return env($name,$default);
    $value=getenv($name); return $value===false||$value===''?$default:$value;
};

return [
    'supported_source' => $get('NEXORA_UPGRADE_SUPPORTED_SOURCE', '>=0.34 <2.0'),
    'plan_path' => $get('NEXORA_UPGRADE_PLAN_PATH', dirname(__DIR__).'/storage/app/nexora/upgrade/active-plan.json'),
    'history_path' => $get('NEXORA_UPGRADE_HISTORY_PATH', dirname(__DIR__).'/storage/app/nexora/upgrade/history'),
    'lock_path' => $get('NEXORA_UPGRADE_LOCK_PATH', dirname(__DIR__).'/storage/app/nexora/upgrade/upgrade.lock'),
    'transaction_journal_path' => $get('NEXORA_UPGRADE_TRANSACTION_JOURNAL_PATH', dirname(__DIR__).'/storage/app/nexora/upgrade/transaction.json'),
    'transaction_history_path' => $get('NEXORA_UPGRADE_TRANSACTION_HISTORY_PATH', dirname(__DIR__).'/storage/app/nexora/upgrade/transaction-history'),
    'maintenance_lease_path' => $get('NEXORA_UPGRADE_MAINTENANCE_LEASE_PATH', dirname(__DIR__).'/storage/app/nexora/upgrade/maintenance-lease.json'),
    'recovery_decision_path' => $get('NEXORA_UPGRADE_RECOVERY_DECISION_PATH', dirname(__DIR__).'/storage/app/nexora/upgrade/recovery-decision.json'),
    'recovery_decision_history_path' => $get('NEXORA_UPGRADE_RECOVERY_DECISION_HISTORY_PATH', dirname(__DIR__).'/storage/app/nexora/upgrade/recovery-decisions'),
    'require_backup' => filter_var($get('NEXORA_UPGRADE_REQUIRE_BACKUP', true), FILTER_VALIDATE_BOOL),
    'require_restore_readiness' => filter_var($get('NEXORA_UPGRADE_REQUIRE_RESTORE_READINESS', true), FILTER_VALIDATE_BOOL),
    'block_preexisting_maintenance' => filter_var($get('NEXORA_UPGRADE_BLOCK_PREEXISTING_MAINTENANCE', true), FILTER_VALIDATE_BOOL),
    'backup_evidence_ttl_hours' => max(1, (int) $get('NEXORA_UPGRADE_BACKUP_EVIDENCE_TTL_HOURS', 72)),
    'plan_ttl_minutes' => max(15, (int) $get('NEXORA_UPGRADE_PLAN_TTL_MINUTES', 120)),
    'transaction_stale_minutes' => max(5, (int) $get('NEXORA_UPGRADE_TRANSACTION_STALE_MINUTES', 15)),
    'recovery_decision_ttl_hours' => max(1, (int) $get('NEXORA_UPGRADE_RECOVERY_DECISION_TTL_HOURS', 168)),
    'post_health_min_routes' => max(1, (int) $get('NEXORA_UPGRADE_POST_HEALTH_MIN_ROUTES', 1)),
    'require_migration_ledger' => filter_var($get('NEXORA_UPGRADE_REQUIRE_MIGRATION_LEDGER', true), FILTER_VALIDATE_BOOL),
    'require_cluster_quiescence' => filter_var($get('NEXORA_UPGRADE_REQUIRE_CLUSTER_QUIESCENCE', true), FILTER_VALIDATE_BOOL),
    'cluster_lease_name' => (string) $get('NEXORA_UPGRADE_CLUSTER_LEASE_NAME', 'platform-upgrade'),
    'cluster_lease_seconds' => max(120, min(3600, (int) $get('NEXORA_UPGRADE_CLUSTER_LEASE_SECONDS', 1800))),
    'cluster_recovery_hold_seconds' => max(900, (int) $get('NEXORA_UPGRADE_CLUSTER_RECOVERY_HOLD_SECONDS', 86400)),
    'cluster_fresh_node_seconds' => max(30, (int) $get('NEXORA_UPGRADE_CLUSTER_FRESH_NODE_SECONDS', 180)),
    'cluster_require_scheduler_owner' => filter_var($get('NEXORA_UPGRADE_CLUSTER_REQUIRE_SCHEDULER_OWNER', true), FILTER_VALIDATE_BOOL),
    'cluster_require_shared_maintenance' => filter_var($get('NEXORA_UPGRADE_CLUSTER_REQUIRE_SHARED_MAINTENANCE', true), FILTER_VALIDATE_BOOL),
    'cluster_require_runtime_quiescence' => filter_var($get('NEXORA_UPGRADE_CLUSTER_REQUIRE_RUNTIME_QUIESCENCE', true), FILTER_VALIDATE_BOOL),
    'cluster_quiescence_wait_seconds' => max(5, min(300, (int) $get('NEXORA_UPGRADE_CLUSTER_QUIESCENCE_WAIT_SECONDS', 60))),
    'cluster_quiescence_poll_milliseconds' => max(100, min(2000, (int) $get('NEXORA_UPGRADE_CLUSTER_QUIESCENCE_POLL_MILLISECONDS', 250))),
    'cluster_require_empty_queue' => filter_var($get('NEXORA_UPGRADE_CLUSTER_REQUIRE_EMPTY_QUEUE', true), FILTER_VALIDATE_BOOL),
    'cluster_queue_names' => array_values(array_filter(array_map('trim', explode(',', (string) $get('NEXORA_UPGRADE_CLUSTER_QUEUE_NAMES', 'default'))))),
    'activity_ttl_web_seconds' => max(30, min(3600, (int) $get('NEXORA_UPGRADE_ACTIVITY_TTL_WEB_SECONDS', 600))),
    'activity_ttl_queue_seconds' => max(300, min(7200, (int) $get('NEXORA_UPGRADE_ACTIVITY_TTL_QUEUE_SECONDS', 3900))),
    'activity_ttl_scheduler_seconds' => max(300, min(7200, (int) $get('NEXORA_UPGRADE_ACTIVITY_TTL_SCHEDULER_SECONDS', 3900))),
    'block_destructive_pending_migrations' => filter_var($get('NEXORA_UPGRADE_BLOCK_DESTRUCTIVE_PENDING_MIGRATIONS', true), FILTER_VALIDATE_BOOL),
    'runtime_admission_barrier_required' => filter_var($get('NEXORA_UPGRADE_RUNTIME_ADMISSION_BARRIER_REQUIRED', true), FILTER_VALIDATE_BOOL),
    'queue_payload_schema' => max(13, (int) $get('NEXORA_QUEUE_PAYLOAD_SCHEMA', 13)),
    'queue_payload_require_metadata' => filter_var($get('NEXORA_QUEUE_PAYLOAD_REQUIRE_METADATA', true), FILTER_VALIDATE_BOOL),
    'queue_payload_require_exact_version' => filter_var($get('NEXORA_QUEUE_PAYLOAD_REQUIRE_EXACT_VERSION', true), FILTER_VALIDATE_BOOL),
    'queue_payload_require_exact_generation' => filter_var($get('NEXORA_QUEUE_PAYLOAD_REQUIRE_EXACT_GENERATION', true), FILTER_VALIDATE_BOOL),
    'queue_payload_require_exact_environment' => filter_var($get('NEXORA_QUEUE_PAYLOAD_REQUIRE_EXACT_ENVIRONMENT', true), FILTER_VALIDATE_BOOL),
    'runtime_generation_require_installed_match' => filter_var($get('NEXORA_RUNTIME_GENERATION_REQUIRE_INSTALLED_MATCH', true), FILTER_VALIDATE_BOOL),
    'client_generation_fence_required' => filter_var($get('NEXORA_CLIENT_GENERATION_FENCE_REQUIRED', true), FILTER_VALIDATE_BOOL),
    'client_generation_require_json_header' => filter_var($get('NEXORA_CLIENT_GENERATION_REQUIRE_JSON_HEADER', true), FILTER_VALIDATE_BOOL),
];
