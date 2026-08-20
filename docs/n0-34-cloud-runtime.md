# N0.34 — Cloud / HA / Distributed Runtime

N0.34 completes the current Nexora Core feature roadmap by making the runtime explicit about single-node versus horizontally scaled operation. It does not require cloud infrastructure for a normal installation.

## Runtime topology

`RuntimeTopology` reports database, cache, queue, session, object-storage and scheduler coordination choices. A deployment is never labelled HA-ready merely because it is online: node-local cache/session/storage and synchronous queues are surfaced as actionable warnings.

## Node identity and readiness

Each installation node has a stable protected identity. Web requests are throttled into periodic heartbeats, queue job processing records worker heartbeats, and scheduler cron records a node heartbeat every minute.

Node status is `active`, `draining` or `maintenance`. Draining/maintenance nodes deliberately return HTTP 503 from `/health/ready`; `/health/live` stays minimal and indicates only that the application process can boot.

## Scheduler leadership

Nexora uses a database-backed lease named `scheduler-leader`. The shared primary database is already required for horizontally scaled application state, so scheduler leadership does not depend on a local filesystem mutex. Existing maintenance schedules are filtered through the leader lease; only node heartbeat is intentionally executed by every node.

## Distributed locks

Extensions and Core services can consume `DistributedLockContract`. The first implementation delegates to Laravel atomic cache locks. A truly multi-node deployment therefore needs an atomic-lock capable shared cache such as Redis/database; the Operations screen reports local cache drivers as a scaling warning.

## Object storage

`ObjectStorageContract` is the runtime boundary. The configured disk comes from `NEXORA_OBJECT_STORAGE_DISK`. Local storage is valid for a single node; shared/S3-compatible storage is the intended horizontal topology. Core does not hard-code a cloud vendor SDK.

## Operational metrics

`nexora:runtime:metrics` captures database/cache latency, queue backlog, failed jobs and peak process memory. `nexora:runtime:prune` enforces metric retention.

## Backup and restore safety

`nexora:backup:create` reuses Nexora's existing native in-app MySQL/MariaDB and SQLite snapshot strategies. Unsupported drivers fail explicitly and must use their managed/external snapshot mechanism.

Successful backup artifacts are stored in protected storage and sealed with SHA-256. `nexora:backup:verify` re-checks the artifact. `nexora:restore:plan` produces an offline restore plan and one-time confirmation. N0.34 does not run an unattended destructive restore from a live web process.

## Operational CLI

- `php artisan nexora:cloud:status`
- `php artisan nexora:node:heartbeat`
- `php artisan nexora:node:drain`
- `php artisan nexora:node:activate`
- `php artisan nexora:runtime:metrics`
- `php artisan nexora:runtime:prune`
- `php artisan nexora:backup:create`
- `php artisan nexora:backup:verify <backup-id>`
- `php artisan nexora:restore:plan <backup-id>`

## N1.0 next gate

N1.0 is release-candidate certification: full dependency installation, clean migrations and seed on supported database targets, PHP/Laravel tests, TypeScript/Vite production build, browser flows, accessibility, security, failure recovery, performance budgets and final customer packaging.
