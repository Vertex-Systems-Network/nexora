<?php

declare(strict_types=1);

return [
    'node_id' => env('NEXORA_NODE_ID', ''),
    'node_role' => env('NEXORA_NODE_ROLE', 'application'),
    'scheduler_lease_seconds' => (int) env('NEXORA_SCHEDULER_LEASE_SECONDS', 90),
    'object_storage_disk' => env('NEXORA_OBJECT_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
    'queues' => array_values(array_filter(array_map('trim', explode(',', (string) env('NEXORA_QUEUES', 'critical,default,media,notifications,low'))))),
    'metric_retention_days' => (int) env('NEXORA_METRIC_RETENTION_DAYS', 30),
    'node_stale_seconds' => (int) env('NEXORA_NODE_STALE_SECONDS', 180),
];
