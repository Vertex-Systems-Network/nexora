# N1.0 RC19 — Concurrency / Idempotency Safety

RC19 hardens state that can be mutated by concurrent HTTP callbacks, scheduler runs, queue workers, browser sessions, or retry delivery. The goal is not to promise serial execution globally; the goal is to make each critical invariant explicit and database-enforced.

## Core policy

- `ConcurrencyGuard::transaction()` uses bounded Laravel transaction attempts so driver-reported deadlock/serialization conflicts can retry safely.
- `ConcurrencyGuard::mutex()` uses the portable `nx_concurrency_mutexes` table and `SELECT ... FOR UPDATE` instead of vendor-specific advisory-lock APIs.
- Unique indexes remain the final arbiter for idempotency races. Check-then-insert is never treated as sufficient by itself.
- Worker claims use a row lock plus a bounded stale-claim TTL. A second live worker does not execute the same claimed action concurrently.
- External SMTP/HTTP side effects are **at-least-once**. Stable Message-ID/Idempotency-Key values reduce duplicate impact, but RC19 deliberately does not claim cross-provider exactly-once delivery.

## Hardened surfaces

Commerce payment/refund recording locks mutable aggregates before totals change and converges duplicate provider callbacks through unique idempotency keys. Automation event fan-out creates runs and marks the event processed in one transaction, with queue dispatch deferred until commit. Inbound webhook receipt creation and event emission share a transaction. Newsletter and outbound webhook workers claim a delivery before performing the external effect. Workflow runs and steps use stale-aware database claims and atomic run-count increments.

Documents and Studio now perform optimistic-lock comparison only after acquiring the current database row with `lockForUpdate()`, eliminating the stale-controller-model race between validation and persistence. Revision numbering is serialized through the parent row lock. Scheduled article publishing also locks and re-reads each due document inside the transaction, rechecking draft status and due time before publishing so scheduler failover cannot create duplicate publication transitions.

## Target verification

After migrations run:

```bat
php artisan nexora:concurrency:doctor
php scripts\concurrency-contract-verify.php
```

Full N1.0 certification runs both source contracts and the target doctor automatically.

Source certification currently covers **11 critical mutation surfaces** and rejects direct unbounded `DB::transaction()` usage inside the critical RC19 service set.
