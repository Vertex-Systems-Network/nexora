# N1.0 RC10 — Backup / Restore / Multi-node HA / Final Evidence Closure

RC10 is the final major N1.0 certification block. It does not claim that a single-process source scan proves high availability or disaster recovery. Instead it separates safe application-level rehearsals from fail-closed operator evidence gathered on disposable restore targets and two or more independent runtime nodes.

## Application-level HA readiness

`HaReadinessService` reports strict HA readiness only when the configured runtime uses a shared cache candidate, shared server-side sessions, an asynchronous queue, shared object storage, at least two fresh active nodes running the same Nexora version, and an active scheduler leader lease.

`php artisan nexora:ha:rehearse` safely verifies lease exclusion/failover and deep readiness on the current runtime. This is useful regression evidence but is explicitly not equivalent to a multi-host rehearsal.

## Backup / restore rehearsal

`php artisan nexora:backup:rehearse <backup>` validates the checksum-sealed artifact and creates a guarded restore plan with `automatic_destructive_restore=false`. It never restores production automatically. Final recovery certification requires the operator to restore the artifact to a disposable target and record migration, health, login and data spot-check evidence.

## Final evidence

RC10 adds fail-closed evidence files:

- `browser-evidence.json`
- `http-performance.json`
- `build-assets.json`
- `backup-restore-evidence.json`
- `ha-evidence.json`

`scripts/final-evidence-verify.php` verifies and SHA-256 seals the five evidence domains into `final-evidence.json`. Production packaging now requires that report to be PASS for the exact platform version.

Set `NEXORA_CERT_FINAL_EVIDENCE=1` only after real browser, target-server HTTP, disposable-target restore and multi-node rehearsals have been completed. When enabled, the unified certification runner makes those gates mandatory and only then allows production ZIP packaging.
