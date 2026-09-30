<?php

declare(strict_types=1);

return [
    'slow_request_ms' => (int) env('NEXORA_OBSERVABILITY_SLOW_REQUEST_MS', 1500),
    'incident_retention_days' => (int) env('NEXORA_OBSERVABILITY_INCIDENT_RETENTION_DAYS', 30),
    'audit_retention_days' => (int) env('NEXORA_OBSERVABILITY_AUDIT_RETENTION_DAYS', 365),
    'prune_time' => env('NEXORA_OBSERVABILITY_PRUNE_TIME', '04:50'),
];
