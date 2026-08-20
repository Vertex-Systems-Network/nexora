<?php

declare(strict_types=1);

return [
    // Laravel retries transactions when the driver reports a deadlock/serialization failure.
    // Keep this bounded: business validation errors are never retried by this policy.
    'transaction_attempts' => max(1, min(10, (int) env('NEXORA_DB_TRANSACTION_ATTEMPTS', 3))),

    // A duplicate worker may only take over a record left in a running/sending state after
    // the original worker's normal timeout window has elapsed.
    'workflow_claim_ttl_seconds' => max(180, min(1800, (int) env('NEXORA_WORKFLOW_CLAIM_TTL_SECONDS', 240))),
    'webhook_claim_ttl_seconds' => max(60, min(900, (int) env('NEXORA_WEBHOOK_CLAIM_TTL_SECONDS', 90))),
    'newsletter_claim_ttl_seconds' => max(120, min(1800, (int) env('NEXORA_NEWSLETTER_CLAIM_TTL_SECONDS', 180))),

    // External SMTP/HTTP calls are at-least-once. Nexora suppresses concurrent duplicates,
    // persists stable idempotency/message identifiers, and retries stale claims, but it does
    // not claim impossible cross-provider exactly-once delivery semantics.
    'external_effect_semantics' => 'at-least-once',

    'supported_drivers' => ['mysql', 'mariadb', 'pgsql', 'sqlite', 'sqlsrv'],
];
