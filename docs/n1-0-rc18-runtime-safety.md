# N1.0 RC18 — Runtime limits, queue safety and graceful cancellation

RC18 hardens Nexora's long-running runtime boundary without adding a product domain. The new `config/nexora-runtime.php` policy owns HTTP request ceilings, explicit trusted-proxy configuration, minimum PHP capacity requirements and queue worker timeout/retry relationships.

## Request / proxy boundary

`ConfigureTrustedProxies` accepts only explicit proxy IP/CIDR entries from `NEXORA_TRUSTED_PROXIES`; wildcard trust is rejected. `EnforceRequestLimits` is prepended before application feature middleware and rejects malformed or oversized `Content-Length` values with HTTP 400/413. The application limit defaults to 64 MiB and `nexora:runtime:doctor` verifies that PHP `post_max_size` and `upload_max_filesize` are not lower.

## Queue worker boundary

All four first-party queue jobs have explicit `tries`, `timeout`, `failOnTimeout=true`, and explicit retry backoff when retries are enabled. The longest first-party job remains 1800 seconds. Database, Redis and Beanstalkd `retry_after` defaults are 1860 seconds so a timed-out worker cannot make the same job visible while the original process is still allowed to execute.

Long-lived workers clear `TenantContext` after successful jobs, on job exceptions, and before the next loop. A configurable memory threshold requests a graceful worker restart before another job is reserved. Operators can use the worker command emitted by `php artisan nexora:runtime:doctor` together with a process supervisor.

## Graceful crawler cancellation

Queued SEO crawls can be cancelled immediately; running crawls transition to `cancel_requested`. `SeoCrawler` checks the persisted state between URL operations and exits as `cancelled` without killing PHP mid-write. The Admin crawl page exposes the cancellation action through the shared Nexora UI layer.

Installer/deployment cancellation remains governed by the existing safe-stage rules: schema-changing protected stages are not force-killed.
