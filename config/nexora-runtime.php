<?php

declare(strict_types=1);

$csv = static function (string $value): array {
    return array_values(array_filter(array_map('trim', explode(',', $value)), static fn (string $item): bool => $item !== ''));
};

return [
    'http' => [
        // Application-level ceiling. The web server/PHP post_max_size must be >= this value.
        'max_body_bytes' => max(1_048_576, (int) env('NEXORA_HTTP_MAX_BODY_BYTES', 67_108_864)), // 64 MiB
        // Empty by default. Configure explicit IP/CIDR entries for reverse proxies/load balancers.
        'trusted_proxies' => $csv((string) env('NEXORA_TRUSTED_PROXIES', '')),
    ],

    'php' => [
        'minimum_memory_bytes' => max(134_217_728, (int) env('NEXORA_PHP_MIN_MEMORY_BYTES', 536_870_912)), // 512 MiB
        'minimum_post_bytes' => max(8_388_608, (int) env('NEXORA_PHP_MIN_POST_BYTES', 67_108_864)),
        'minimum_upload_bytes' => max(8_388_608, (int) env('NEXORA_PHP_MIN_UPLOAD_BYTES', 67_108_864)),
        'minimum_execution_seconds' => max(30, (int) env('NEXORA_PHP_MIN_EXECUTION_SECONDS', 120)),
        'minimum_input_seconds' => max(30, (int) env('NEXORA_PHP_MIN_INPUT_SECONDS', 60)),
        'minimum_input_vars' => max(1000, (int) env('NEXORA_PHP_MIN_INPUT_VARS', 3000)),
        'minimum_file_uploads' => max(1, (int) env('NEXORA_PHP_MIN_FILE_UPLOADS', 20)),
    ],

    'queue' => [
        'max_job_timeout_seconds' => max(60, (int) env('NEXORA_QUEUE_MAX_JOB_TIMEOUT', 1800)),
        'retry_after_margin_seconds' => max(15, (int) env('NEXORA_QUEUE_RETRY_MARGIN', 60)),
        'worker_timeout_seconds' => max(60, (int) env('NEXORA_QUEUE_WORKER_TIMEOUT', 1800)),
        'worker_max_time_seconds' => max(300, (int) env('NEXORA_QUEUE_WORKER_MAX_TIME', 3600)),
        'worker_restart_memory_mb' => max(128, (int) env('NEXORA_QUEUE_WORKER_MEMORY_MB', 384)),
        'worker_sleep_seconds' => max(0, (int) env('NEXORA_QUEUE_WORKER_SLEEP', 1)),
    ],
    'deployment' => [
        'cache_generation_fencing' => filter_var(env('NEXORA_CACHE_GENERATION_FENCING', true), FILTER_VALIDATE_BOOL),
        'session_schema_enforced' => filter_var(env('NEXORA_SESSION_SCHEMA_ENFORCED', true), FILTER_VALIDATE_BOOL),
        'session_schema' => max(1, (int) env('NEXORA_SESSION_SCHEMA', 1)),
        'json_client_generation_fence' => filter_var(env('NEXORA_CLIENT_GENERATION_REQUIRE_JSON_HEADER', true), FILTER_VALIDATE_BOOL),
        'environment_fingerprint_enforced' => filter_var(env('NEXORA_RUNTIME_ENVIRONMENT_FINGERPRINT_ENFORCED', true), FILTER_VALIDATE_BOOL),
        'key_rotation_require_maintenance' => filter_var(env('NEXORA_KEY_ROTATION_REQUIRE_MAINTENANCE', true), FILTER_VALIDATE_BOOL),
        'key_rotation_require_previous_key' => filter_var(env('NEXORA_KEY_ROTATION_REQUIRE_PREVIOUS_KEY', true), FILTER_VALIDATE_BOOL),
        'key_rotation_receipt_ttl_minutes' => max(15, (int) env('NEXORA_KEY_ROTATION_RECEIPT_TTL_MINUTES', 120)),
        'key_rotation_cluster_convergence_required' => filter_var(env('NEXORA_KEY_ROTATION_CLUSTER_CONVERGENCE_REQUIRED', true), FILTER_VALIDATE_BOOL),
        'key_rotation_receipt_path' => env('NEXORA_KEY_ROTATION_RECEIPT_PATH', dirname(__DIR__).'/storage/app/nexora/runtime/key-rotation.json'),
        'key_rotation_history_path' => env('NEXORA_KEY_ROTATION_HISTORY_PATH', dirname(__DIR__).'/storage/app/nexora/runtime/key-rotation-history'),
    ],

];
