# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE** — After **every meaningful apply**, update this file for every implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply.
>
> `NEXORA_AI_PROJECT_STATE.md` remains canonical append-only history. This dashboard is the human-readable Power view. **SOURCE DONE != TARGET VERIFIED.**

---

## 1. Current checkpoint

- Date: `2026-08-22`
- Branch: `dev/n1-0b-core-functional-qa`
- PR #1: **DRAFT + OPEN + MERGEABLE**, title/body synchronized through N1.26; not ready for merge
- Consolidated implementation CI: **`32533298397` SUCCESS** on `73deb80bfaeb2e2b416292c15dde1f1abb02c16c`.
- Latest complete green governance/source CI before this progress commit: **`32533537041` SUCCESS** on `c9f57881a4ef950ee041cda683e4d47829b588b3`.
- Consolidated certification passed Certification preflight, Source Guard, every product contract through **N1.26 Performance + Accessibility + Release**, and Unified Source Certification.
- Current runner policy: certification may run on **any idle self-hosted runner** via `runs-on: self-hosted`; there is no runner-name pin and no `LOCAL-WIN-03`-only guard.
- GitHub-hosted runners remain excluded. The current self-hosted fleet is Windows-based, so repository `run:` steps use `cmd` and verify local PHP/Node/npm before certification.
- Any-idle dispatch is proven across the pool: runs in this pass were claimed by different local runners including `LOCAL-WIN-01`, `LOCAL-WIN-03` and `LOCAL-WIN-4` depending on availability.
- Current source head before this progress-only commit: `9a5a32fbcd13c77617893a6d90a719e0e6144019`.
- Actions: **DEFERRED BY USER** earlier when hosted Actions quota was exhausted; current certification has resumed through the user-approved self-hosted runner pool.
- Canonical ledger: **revision `2.5`**, synchronized through N1.26 / pooled self-hosted certification in commit `9a5a32fbcd13c77617893a6d90a719e0e6144019`.
- Issue #2: **OPEN**; latest live rc.93 evidence still reports only `environment`, `activation`, `service`, `process` mismatches.
- **N1.18 Public APIs / Webhooks / SDK: SOURCE DONE / TARGET PENDING**
- **N1.19 Import / Export / WordPress migrations: SOURCE DONE / TARGET PENDING**
- **N1.20 Observability: SOURCE DONE / TARGET PENDING**
- **N1.21 Forge / Developer Experience: SOURCE DONE / TARGET PENDING**
- **N1.22 Sentinel 2.0: SOURCE DONE for current trust-hardening workflow / TARGET PENDING**
- **N1.23 Marketplace 2.0: SOURCE DONE for current hardening workflow / TARGET PENDING**
- **N1.24 Cloud / HA / Distributed Runtime: SOURCE DONE for current coordination/leadership workflow / TARGET PENDING**
- **N1.25 Backup / DR / Upgrade Certification: SOURCE DONE for current recovery-identity/restore-planning workflow / TARGET PENDING**
- **N1.26 Performance + Accessibility + Release: SOURCE DONE for current source workflow / TARGET + RELEASE EVIDENCE PENDING**

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Verified Power remains unchanged. The consolidated self-hosted run promotes N1.18–N1.26 to SOURCE DONE only. Target Power still requires real target execution; Release Power still requires real C1-C6/final evidence.

---

## 3. Roadmap

| Block | Source / implementation | Target | State |
|---|---:|---:|---|
| N1.9–N1.17 | 100% verified source | target pending | SOURCE DONE |
| N1.18 Public APIs/Webhooks/SDK | 100% current source contract | target pending | SOURCE DONE |
| N1.19 Import/Export/WP migrations | 100% current source contract | target pending | SOURCE DONE |
| N1.20 Observability | 100% current source contract | target pending | SOURCE DONE |
| N1.21 Forge / Developer Experience | 100% current source contract | target pending | SOURCE DONE |
| N1.22 Sentinel 2.0 | current trust-hardening workflow source-gated | target pending | SOURCE DONE FOR CURRENT WORKFLOW |
| N1.23 Marketplace 2.0 | current hardening workflow source-gated | target pending | SOURCE DONE FOR CURRENT WORKFLOW |
| N1.24 Cloud / HA / Distributed Runtime | current coordination/leadership workflow source-gated | target pending | SOURCE DONE FOR CURRENT WORKFLOW |
| N1.25 Backup / DR / Upgrade Certification | current recovery-identity/restore-planning workflow source-gated | target pending | SOURCE DONE FOR CURRENT WORKFLOW |
| N1.26 Performance + Accessibility + Release | current source workflow source-gated | target/release evidence pending | SOURCE DONE FOR CURRENT WORKFLOW |
| N2.0 Stable Production | source roadmap substantially closed | 0% final release | BLOCKED BY TARGET + C1-C6 EVIDENCE |

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

## 5. Any-idle self-hosted runner policy

Certification may run on whichever self-hosted runner is idle and claims the GitHub Actions job first.

```yaml
runs-on: self-hosted
```

Consequences:
- No runner name such as `LOCAL-WIN-03`, `LOCAL-WIN-01`, or another local runner is preferred or required.
- GitHub-hosted runners are not eligible.
- Current runner fleet is Windows-based; the job reports `%RUNNER_NAME%`, `%RUNNER_OS%`, `%RUNNER_ARCH%` and verifies PHP >= 8.3 plus Node >= 22 before source gates run.
- Local toolchains are verified rather than mutated by hosted-runner setup actions.
- Repository `run:` steps use `cmd`, avoiding unsigned temporary PowerShell-script ExecutionPolicy failures on the current Windows fleet.
- If all self-hosted runners are busy/offline, the job queues until one becomes idle.

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
- N1.25 product contract is mandatory in Development Readiness and the self-hosted release workflow.
- This is source closure only. Real backup/restore rehearsal, upgrade rehearsal and recovery health evidence remain TARGET/RELEASE PENDING.

---

## 10. N1.26 Performance + Accessibility + Release implementation checkpoint

- Existing C5 architecture is preserved: source browser/RTL/a11y contracts, production build budgets, real target HTTP checks, browser matrix, assistive-technology evidence and Web Vitals remain separate evidence classes.
- Admin Inertia page resolution is explicitly source-guarded to remain `lazy: true`; eager page regression fails the performance contract.
- `config/nexora-performance.php` includes a dedicated first-load static JavaScript graph gzip ceiling, separate from total/per-asset JS ceilings.
- `scripts/performance-build-verify.php` walks only the app entry's static manifest `imports`, excludes dynamic route chunks from first-load accounting, records the first-load asset set and enforces the gzip ceiling after a real production build.
- Shared Modal focus trapping has an executable Vitest regression proving Tab wraps from the last dialog control back to the first, and the browser UX source contract guards focus trap + restore semantics.
- `scripts/development-readiness.php --full --tests` executes the frontend Vitest suite. A successful production Vite build is followed by `performance-build-verify.php`, so asset budgets/provenance are exercised in development target QA.
- `scripts/performance-accessibility-release-product-contract-verify.php` binds lazy route splitting, first-load budget, modal focus containment, executable frontend/build QA and the real C5 target evidence boundary.
- N1.26 product contract is mandatory in Development Readiness and the self-hosted release workflow.
- Source code does **not** claim Lighthouse/Web Vitals/WCAG target success. Chrome/Edge/Firefox responsive/RTL/theme observations, assistive-technology evidence, HTTP latency/security and Web Vitals remain C5 TARGET/RELEASE PENDING.

---

## 11. Main protection / target blockers

`main` remains recorded `protected=false`; current connector exposes no proven branch/ruleset mutation endpoint in this workflow. Desired policy remains PR required + Source certification + stale-review dismissal + review/conversation resolution + no force push/delete + admin enforcement. Issue #2 remains OPEN. Target Power remains 50%.

---

## 12. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | N1.16/N1.17 verified closures/governance | Project 76.5%, Source 99%, Target 50% |
| 014–020 | 2026-08-21 | through `95eb4bd4…`; zero-step hosted Actions | N1.18 API/token/SDK implementation | implementation complete; verified Power held |
| 021 | 2026-08-22 | user directive | hosted Actions deferred after quota exhaustion | Power unchanged |
| 022–027 | 2026-08-22 | through `8afd1f36…` | N1.19 import/export/WXR implementation | implementation complete; verified Power held |
| 028–031 | 2026-08-22 | through `97824bd4…` | N1.20 observability/privacy/correlation/retention + tests/contracts | implementation complete; verified Power held |
| 032–033 | 2026-08-22 | through Forge contract `39beaac0…` | N1.21 Forge hardening/tests/docs/contract | implementation-complete candidate |
| 034 | 2026-08-22 | readiness `74ca8c89…`; workflow `8e612c5a…` | self-hosted runner + PR trigger + Forge gate wiring | verified Power unchanged |
| 035 | 2026-08-22 | earlier local-runner executions | generic self-hosted runner stabilization | verified Power unchanged |
| 036 | 2026-08-22 | through `d31e8524…`; workflow `e412df46…` | N1.22 Sentinel 2.0 trust/privacy hardening + tests/gate | implementation-complete candidate |
| 037 | 2026-08-22 | `06750699…` | literal-safe Sentinel contract correction | verified Power unchanged |
| 038 | 2026-08-22 | through `3c170397…` | N1.23 bounded catalog + generation + tenant authorization hardening | implementation-complete candidate |
| 039 | 2026-08-22 | `ea7b87a8…` | certification temporarily pinned exclusively to `LOCAL-WIN-03` | superseded by Apply 043; Power unchanged |
| 040 | 2026-08-22 | lease `780a169a…`; HA `820c65d8…`; tests `44367739…`; contract `19e4336b…`; workflow `9d166d69…`; readiness `4c053ba4…` | N1.24 fail-closed coordination + fresh-active scheduler ownership | implementation-complete candidate |
| 041 | 2026-08-22 | backup identity `52cb9ca2…`; planner `c86fe3af…`; rehearsal `16caa03d…`; tests `4ed881a4…`; contract `0dc78c11…`; workflow `796b5cb0…`; readiness `e46c97cf…` | N1.25 recovery identity, cross-generation fencing, non-destructive restore planning, tests + mandatory source gate | implementation-complete candidate; verified Power unchanged |
| 042 | 2026-08-22 | budget `2b116577…`; build verifier `1b65643e…`; perf contract `35dfa8cc…`; modal test `21b85cb7…`; browser contract `c6fe39ab…`; product gate `4a616965…`; workflow `e4bf0547…`; readiness `d728df74…` | N1.26 lazy-route/first-load-budget + modal focus regression + executable frontend/build QA + C5 boundary source hardening | implementation-complete candidate; verified Power unchanged |
| 043 | 2026-08-22 | runner selector `736ad761…`; API-token shared UI `c263fc52…`; approved migration queue set `b64ca91f…`; RC18/current queue metric alignment `acbd16ea…`; run `32532484673` | changed certification to any idle self-hosted runner; fixed preflight Admin UI + queue contract; Source Guard and gates through AI Platform green; synchronized progress protocol after Multisite governance failure | verified Power unchanged pending fresh consolidated green run |
| 044 | 2026-08-22 | Enterprise progress gate `084caed8…`; API bootstrap gate `777591b1…`; Observability Actions governance `5ab8c4f9…`; Forge structural conflict gate `73deb80b…`; CI **`32533298397` SUCCESS** | removed stale formatting-only CI assumptions without weakening product invariants; any-idle self-hosted consolidated certification passed every gate through N1.26 + Unified Source Certification | N1.18–N1.26 promoted to SOURCE DONE; Target 50% / Release 25% unchanged |
| 045 | 2026-08-22 | progress/governance CI **`32533537041` SUCCESS** on `c9f57881…`; ledger revision `2.5` commit `9a5a32fb…`; PR #1 synchronized through N1.26 | closed source-governance handoff after pooled certification; canonical ledger now records N1.18–N1.26 and any-idle runner policy | Source remains 99%; Target 50% / Release 25% unchanged |

---

## 13. Exact next action

```text
LIVE RC.93 RECOVERY / ISSUE #2
  1. recover the existing D:\laragon\www\nexora rc.93 installation without replacing it with rc.94
  2. require `php artisan nexora:runtime:compatibility-status --deep` PASS with no mismatches
  3. require `php artisan nexora:runtime:post-install-status --assert-ready` PASS
  4. exercise /login and /admin
  5. only then close issue #2

DEVELOPMENT TARGET / RELEASE CLOSURE
  1. on a separate development checkout run full development QA with PHP tests + Vitest + TypeScript + Vite + production asset budgets
  2. obtain real five-engine SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix evidence on disposable `nexora_matrix_*` databases
  3. exercise major product workflows N1.9-N1.26 on the target; controlled provider/identity/API/import/observability/Sentinel/Marketplace evidence where applicable
  4. perform real disposable-target backup/restore + upgrade rehearsal
  5. complete C5 real Chrome/Edge/Firefox + assistive-tech + HTTP + Web Vitals evidence
  6. complete C6 multi-node/final release evidence and reviewed dependency locks
  7. only when all required target/release gates are real and exact-source bound: close eligible issues, mark PR Ready and merge automatically

EVIDENCE RULE
  - self-hosted source CI never raises Target Power by itself
  - N1.26 source checks never substitute for observed WCAG/browser/Web Vitals evidence
  - N2.0 remains blocked until all required source + target + release evidence is real and exact-source bound
```
