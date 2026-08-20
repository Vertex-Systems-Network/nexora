<?php

declare(strict_types=1);

return [
    'required_nodes' => max(2, (int) env('NEXORA_HA_REQUIRED_NODES', 2)),
    'allowed_clock_skew_seconds' => max(1, (int) env('NEXORA_HA_CLOCK_SKEW_SECONDS', 10)),
    'fresh_node_seconds' => max(30, (int) env('NEXORA_HA_FRESH_NODE_SECONDS', 180)),
    'shared_cache_stores' => array_values(array_filter(array_map('trim', explode(',', (string) env('NEXORA_HA_SHARED_CACHE_STORES', 'redis,database,memcached,dynamodb'))))),
    'shared_session_drivers' => array_values(array_filter(array_map('trim', explode(',', (string) env('NEXORA_HA_SHARED_SESSION_DRIVERS', 'redis,database,dynamodb'))))),
    'async_queue_connections' => array_values(array_filter(array_map('trim', explode(',', (string) env('NEXORA_HA_ASYNC_QUEUE_CONNECTIONS', 'database,redis,sqs,beanstalkd'))))),
    'shared_storage_drivers' => array_values(array_filter(array_map('trim', explode(',', (string) env('NEXORA_HA_SHARED_STORAGE_DRIVERS', 's3'))))),
    'evidence_max_age_hours' => max(1, (int) env('NEXORA_HA_EVIDENCE_MAX_AGE_HOURS', 168)),
];
