# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE** — update after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply.
>
> `NEXORA_AI_PROJECT_STATE.md` remains canonical append-only history. This dashboard is the human-readable Power view. **SOURCE DONE != TARGET VERIFIED.**

---

## 1. Current checkpoint

- Date: `2026-08-22`
- Branch: `dev/n1-0b-core-functional-qa`
- PR #1: **DRAFT + OPEN + MERGEABLE**, not ready for merge
- Last executable green source CI: `32509858655` on `45e527c43c69f89c5519dde13bad6c771d171915`
- Earlier self-hosted runs proved repository jobs could execute on other local runners, but those runners are no longer valid certification targets by user directive.
- Actions certification runner: **ONLY `LOCAL-WIN-03`** via `runs-on: [self-hosted, LOCAL-WIN-03]` plus a fail-closed `%RUNNER_NAME% == LOCAL-WIN-03` runtime assertion.
- `LOCAL-WIN-03` must have a custom GitHub Actions runner label named exactly `LOCAL-WIN-03`; if that label is absent/offline, certification intentionally remains queued rather than falling back to another self-hosted runner.
- Workflow shell: `cmd` for repository `run:` steps, avoiding host Windows PowerShell ExecutionPolicy dependence.
- Current N1.25 implementation head before this progress-only commit: `e46c97cfa0422450b4bf03b80257e5564c234d49`.
- Latest observed dedicated-runner run before N1.25 gate wiring: `32529031403` on `c86fe3af2cc5e4bcf68ddc283a678111c5af975f`, status **queued**.
- Ledger revision before this apply: `2.4`; ledger sync is required after this implementation pass.
- Issue #2: **OPEN**; latest live rc.93 evidence still reports only `environment`, `activation`, `service`, `process` mismatches.
- N1.18 Public APIs / Webhooks / SDK: implementation complete / LOCAL-WIN-03 certification pending
- N1.19 Import / Export / WordPress migrations: implementation complete / LOCAL-WIN-03 certification pending
- N1.20 Observability: implementation complete / LOCAL-WIN-03 certification pending
- N1.21 Forge / Developer Experience: implementation complete candidate / LOCAL-WIN-03 certification pending
- N1.22 Sentinel 2.0: first trust-hardening workflow implementation complete candidate / LOCAL-WIN-03 certification pending
- N1.23 Marketplace 2.0: first hardening workflow implementation complete candidate / LOCAL-WIN-03 certification pending
- N1.24 Cloud / HA / Distributed Runtime: first coordination/leadership hardening workflow implementation complete candidate / LOCAL-WIN-03 certification pending
- N1.25 Backup / DR / Upgrade Certification: first recovery-identity/restore-planning workflow implementation complete candidate / **LOCAL-WIN-03 certification pending**

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Verified Power remains unchanged. Implementation volume does not increase evidence-weighted Power. LOCAL-WIN-03 source certification can promote SOURCE DONE only; Target Power requires real target execution.

---

## 3. Roadmap

| Block | Source / implementation | Target | State |
|---|---:|---:|---|
| N1.9–N1.17 | 100% verified source | target pending | SOURCE DONE |
| N1.18 Public APIs/Webhooks/SDK | implementation complete | 0% current target | LOCAL-WIN-03 CERTIFICATION PENDING |
| N1.19 Import/Export/WP migrations | implementation complete | 0% current target | LOCAL-WIN-03 CERTIFICATION PENDING |
| N1.20 Observability | implementation complete | 0% current target | LOCAL-WIN-03 CERTIFICATION PENDING |
| N1.21 Forge / Developer Experience | implementation complete candidate | 0% current target | LOCAL-WIN-03 CERTIFICATION PENDING |
| N1.22 Sentinel 2.0 | first trust-hardening workflow candidate | 0% current target | LOCAL-WIN-03 CERTIFICATION PENDING |
| N1.23 Marketplace 2.0 | first hardening workflow candidate | 0% current target | LOCAL-WIN-03 CERTIFICATION PENDING |
| N1.24 Cloud / HA / Distributed Runtime | first coordination/leadership workflow candidate | 0% current target | LOCAL-WIN-03 CERTIFICATION PENDING |
| N1.25 Backup / DR / Upgrade Certification | first recovery-identity/restore-planning workflow candidate | 0% current target | **LOCAL-WIN-03 CERTIFICATION PENDING** |
| N1.26 Performance + Accessibility + Release | planned/partial | 0% | NEXT SOURCE BLOCK after N1.25 certification checkpoint |

---

## 4. N1.21 Forge implementation checkpoint

- Stable `nexora:make:extension` CLI with `ForgeExtensionScaffolder` service boundary.
- Deterministic zero-write `--dry-run`.
- Guarded `--force` only for same-identifier Forge-owned scaffold marker.
- Portable path + symlink + file/directory collision protections.
- Authoritative Extension manifest self-validation.
- Developer files preserved on force refresh; scaffold directory is never deleted.
- Stable generated source contract for manifest/composer/src/resources/migrations/tests.
- Forge remains source-only and cannot bypass Sentinel/install/trust/capability lifecycle.
- Acceptance tests + developer guide + static Forge product contract are present.
- Forge gate is required by Development Readiness and release workflow.

---

## 5. Dedicated self-hosted runner policy

Certification must run only on the organization runner named `LOCAL-WIN-03`.

```yaml
runs-on: [self-hosted, LOCAL-WIN-03]
```

```bat
if /I not "%RUNNER_NAME%"=="LOCAL-WIN-03" exit /b 1
```

Consequences:
- `LOCAL-WIN-01`, `LOCAL-WIN-4`, or any other runner is not valid certification evidence.
- If the custom label `LOCAL-WIN-03` is missing or the runner is offline, jobs remain queued intentionally.
- No GitHub-hosted setup actions mutate the local PHP/Node toolchain.
- Repository `run:` steps use `cmd`, avoiding unsigned temporary PowerShell-script ExecutionPolicy failures.

---

## 6. N1.22 Sentinel 2.0 implementation checkpoint

- Sentinel/package scanner foundation retained: bounded ZIP inspection, static scanners, capability mismatch detection, digest recheck/TOCTOU block and RiskEngine.
- Quarantine retains internal UUID names, atomic copy, source-size limits, path guard and restrictive file permissions.
- `SentinelFailureReference` creates opaque `SNT-*` references and a non-secret exception-class fingerprint without embedding raw exception text in durable Admin-facing state.
- Private server diagnostics log the same opaque reference so operator-visible failures correlate with server logs.
- Forward migration scrubs legacy persisted raw scan error strings.
- Finding severity ordering uses portable SQL `CASE`.
- `SentinelApprovalGuard` binds promotion to completed ALLOW, package ownership, bounded package state and unchanged package/scan SHA-256; newer/ambiguous scans fail closed.
- Theme and Extension installers invoke the guard server-side.
- Acceptance + static contract + Development Readiness + self-hosted workflow gate are present.

---

## 7. N1.23 Marketplace 2.0 implementation checkpoint

- Catalog source/item generation identity is explicit; historical generation-null rows fail closed until fresh sync.
- Catalog fetch is streamed to temporary storage with explicit 8 MiB budget before JSON decode.
- Successful sync publishes one UUID generation atomically across retained items + source.
- Resume clears generation/last-sync/error and forces fresh synchronization.
- Admin visibility and staging require exact source/item generation equality.
- Dynamic stage permission remains package-type aware and requires both global RBAC + current-organization tenant authorization.
- Existing trusted-publisher, digest, transfer budget, quarantine, Sentinel and signature checks remain intact.
- Acceptance + `marketplace2-product-contract-verify.php` are mandatory in readiness/workflow.

---

## 8. N1.24 Cloud / HA / Distributed Runtime implementation checkpoint

- Existing Cloud foundation retained: node identity/heartbeat, runtime topology, health/readiness, distributed lock abstraction, scheduler leadership, database leases, process/runtime fingerprints and HA rehearsal services.
- `RuntimeLeaseManager::acquireOrRenew()` and barrier-aware acquisition now fail closed when `nx_runtime_leases` is unavailable.
- Owner-bound `release()` remains bootstrap-safe no-op when lease storage is absent.
- Lease acquisition uses transaction + `lockForUpdate()` and rejects a live competing owner.
- `HaReadinessService` requires scheduler leader lease owner to resolve to a fresh active runtime node.
- Missing lease/node tables, ghost owners, stale owners and inactive owners fail readiness.
- Product work uses shared `ClusterLeadership`; node/process heartbeats remain independent on every runtime.
- Acceptance + `cloud-ha-product-contract-verify.php` are mandatory in readiness/workflow.
- Multi-host HA remains TARGET PENDING.

---

## 9. N1.25 Backup / DR / Upgrade implementation checkpoint

- `BackupOrchestrator` now refuses to claim a recovery-ready backup without complete deployment identity.
- Completed runtime backup manifests seal `platform_version`, `deployment_generation`, `source_tree_sha256`, persisted artifact SHA-256, DB driver and backup storage identity alongside existing data-plane/resource evidence.
- Stored artifacts are streaming-written and streaming-reverified for byte count + SHA-256; public verification failures remain generic/server-log-backed.
- New `BackupRecoveryCompatibility` rejects unsupported/legacy/ambiguous/tampered manifest identity, driver mismatch, storage-disk mismatch and record/manifest checksum mismatch.
- Exact current source version + deployment generation allows direct planning; a different valid source generation does **not** invalidate the backup but requires an isolated recovery runtime matching the sealed source runtime.
- `RestorePlanner` first verifies the artifact, then recovery identity and storage identity. It records source/current runtime comparison, storage drift, operator steps and always sets `automatic_destructive_restore=false`.
- Cross-generation recovery plans explicitly require a matching isolated source runtime before applying the backup. Recovery execution remains operator-controlled and disposable-target based.
- `BackupRestoreRehearsalService` exposes source/current generation comparison without claiming a real restore happened.
- `tests/Feature/Cloud/BackupRecoveryIdentityTest.php` covers exact identity, cross-generation fencing, legacy identity rejection and manifest checksum mismatch rejection.
- `scripts/backup-dr-upgrade-product-contract-verify.php` guards backup provenance, compatibility fail-closed semantics, non-destructive planning and the existing real disposable-target final evidence boundary.
- N1.25 product contract is mandatory in Development Readiness and the dedicated `LOCAL-WIN-03` release workflow.
- This is SOURCE implementation only. Real backup/restore rehearsal, upgrade rehearsal and recovery health evidence remain TARGET/RELEASE PENDING.

---

## 10. Main protection / target blockers

`main` remains reported `protected=false`; current connector exposes no branch/ruleset mutation endpoint. Desired policy remains PR required + Source certification + stale-review dismissal + review/conversation resolution + no force push/delete + admin enforcement. Issue #2 remains OPEN. Target Power remains 50%.

---

## 11. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | N1.16/N1.17 verified closures/governance | Project 76.5%, Source 99%, Target 50% |
| 014–020 | 2026-08-21 | through `95eb4bd4…`; zero-step hosted Actions | N1.18 API/token/SDK implementation | implementation complete; verified Power held |
| 021 | 2026-08-22 | user directive | hosted Actions deferred after quota exhaustion | Power unchanged |
| 022–027 | 2026-08-22 | through `8afd1f36…` | N1.19 import/export/WXR implementation | implementation complete; verified Power held |
| 028–031 | 2026-08-22 | through `97824bd4…` | N1.20 observability/privacy/correlation/retention + tests/contracts | implementation complete; verified Power held |
| 032–033 | 2026-08-22 | through Forge contract `39beaac0…` | N1.21 Forge hardening/tests/docs/contract | implementation-complete candidate |
| 034 | 2026-08-22 | readiness `74ca8c89…`; workflow `8e612c5a…` | self-hosted runner + PR trigger + Forge gate wiring | verified Power unchanged |
| 035 | 2026-08-22 | earlier local-runner executions | generic self-hosted runner stabilization; other local runners invalidated later | verified Power unchanged |
| 036 | 2026-08-22 | through `d31e8524…`; workflow `e412df46…` | N1.22 Sentinel 2.0 trust/privacy hardening + tests/gate | implementation-complete candidate |
| 037 | 2026-08-22 | `06750699…` | literal-safe Sentinel contract correction | verified Power unchanged |
| 038 | 2026-08-22 | through `3c170397…` | N1.23 bounded catalog + generation + tenant authorization hardening | implementation-complete candidate |
| 039 | 2026-08-22 | `ea7b87a8…` | certification pinned exclusively to `LOCAL-WIN-03` with label + runtime guard | verified Power unchanged |
| 040 | 2026-08-22 | lease `780a169a…`; HA `820c65d8…`; tests `44367739…`; contract `19e4336b…`; workflow `9d166d69…`; readiness `4c053ba4…` | N1.24 fail-closed coordination + fresh-active scheduler ownership | implementation-complete candidate |
| 041 | 2026-08-22 | backup identity `52cb9ca2…`; planner `c86fe3af…`; rehearsal `16caa03d…`; tests `4ed881a4…`; contract `0dc78c11…`; workflow `796b5cb0…`; readiness `e46c97cf…` | N1.25 recovery identity, cross-generation fencing, non-destructive restore planning, tests + mandatory source gate | implementation-complete candidate; verified Power unchanged pending LOCAL-WIN-03 run |

---

## 12. Exact next action

```text
LOCAL-WIN-03 CONSOLIDATED CERTIFICATION
  1. execute latest branch head only on runner LOCAL-WIN-03
  2. verify RUNNER_NAME == LOCAL-WIN-03 and installed PHP/Node/npm toolchain
  3. run every source/product gate through N1.25 Backup/DR/Upgrade + Unified Source certification
  4. inspect exact failed job logs and fix only real failures until green
  5. after green only: promote N1.18-N1.25 SOURCE DONE where justified
  6. update canonical ledger, PR #1 and issue #2 source checkpoint
  7. continue N1.26 Performance + Accessibility + Release source work

TARGET BOUNDARY
  - issue #2 stays OPEN until rc.93 compatibility + post-install + /login + /admin evidence
  - LOCAL-WIN-03 source CI never raises Target Power by itself
  - real backup restore/upgrade rehearsal requires disposable-target evidence
  - multi-host HA, real DB/provider/SSO/product target evidence remains separately required
```
