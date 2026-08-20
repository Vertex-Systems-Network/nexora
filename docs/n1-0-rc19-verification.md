# N1.0 RC19 Verification Boundary

Source certification verifies the presence and wiring of bounded transaction retries, the portable transaction mutex, idempotency convergence, worker claims, transaction-time optimistic locks, after-commit workflow dispatch, and the explicit at-least-once external-effect boundary.

Dependency-backed certification must still run on the target database driver. `php artisan nexora:concurrency:doctor` verifies the configured driver, retry budget, concurrency mutex table, and required idempotency columns after migrations. Multi-process race behavior remains part of target evidence; source PASS alone is not a production concurrency PASS.

Current source gate: **11 critical mutation surfaces**, one portable transaction mutex, three stale-claim TTL policies, and zero direct critical `DB::transaction()` calls. Scheduled article publishing is included in this set.
