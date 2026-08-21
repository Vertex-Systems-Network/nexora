# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE** — update after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply.
>
> `NEXORA_AI_PROJECT_STATE.md` remains canonical append-only history. This dashboard is the human-readable Power view. **SOURCE DONE != TARGET VERIFIED.**

---

## 1. Current checkpoint

- Date: `2026-08-22`
- Branch: `dev/n1-0b-core-functional-qa`
- PR #1: **DRAFT + OPEN**, not ready for merge
- Last executable green source CI: `32509858655` on `45e527c43c69f89c5519dde13bad6c771d171915`
- Self-hosted run `32523602178` executed on runner `LOCAL-WIN-01`; checkout PASS, then failed before source gates because `shivammathur/setup-php` required missing `pwsh`.
- Workflow correction head: `7310223dd951995245be69124639a51904a8c320` — hosted-style setup actions removed; installed Windows PHP/Node/npm now verified and used directly.
- Actions runner: **Windows local runner via `runs-on: self-hosted`**.
- PR certification trigger: restored; temporary dev-branch push trigger remains during runner stabilization.
- Ledger: `2.4` — governance sync for N1.18–N1.21 pending after certification result
- Issue #2: **OPEN**
- N1.18 Public APIs / Webhooks / SDK: implementation complete / certification pending
- N1.19 Import / Export / WordPress migrations: implementation complete / certification pending
- N1.20 Observability: implementation complete / certification pending
- N1.21 Forge / Developer Experience: implementation complete candidate / certification pending
- Active parallel source audit: **N1.22 Sentinel 2.0**

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Verified Power remains unchanged. Self-hosted source certification can promote SOURCE DONE only; Target Power requires real target execution.

---

## 3. Roadmap

| Block | Source / implementation | Target | State |
|---|---:|---:|---|
| N1.9–N1.17 | 100% verified source | target pending | SOURCE DONE |
| N1.18 Public APIs/Webhooks/SDK | implementation complete | 0% current target | certification pending |
| N1.19 Import/Export/WP migrations | implementation complete | 0% current target | certification pending |
| N1.20 Observability | implementation complete | 0% current target | certification pending |
| N1.21 Forge / Developer Experience | implementation complete candidate | 0% current target | **SELF-HOSTED CERTIFICATION PENDING** |
| N1.22 Sentinel 2.0 | audit active | 0% | **ACTIVE PARALLEL SOURCE BLOCK** |
| N1.23–N1.26 | planned/partial | 0% | Later roadmap |

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

## 5. Self-hosted runner evidence

Run `32523602178` proves the attached runner is registered and accepting repository jobs:

```text
Runner: LOCAL-WIN-01
Machine: ABDUL-HANAN
Runner version: 2.336.0
Checkout exact source: PASS
Failure: setup-php action could not locate pwsh
```

Correction:
- removed `shivammathur/setup-php` and `actions/setup-node` from self-hosted certification;
- upgraded checkout to `actions/checkout@v5`;
- added Windows PowerShell toolchain verification for PHP >= 8.3, Node >= 22 and npm;
- certification uses the machine's installed toolchain instead of mutating the local runner.

---

## 6. N1.22 Sentinel audit findings so far

- Sentinel/package scanner foundation is strong: bounded ZIP inspection, static scanners, capability mismatch detection, digest recheck/TOCTOU block and RiskEngine.
- Quarantine uses internal UUID file names, atomic copy, source-size limits, quarantine path guard and restrictive file permissions.
- Confirmed defects to close:
  - `ScanRecorder` persists raw scanner exception messages to `SecurityScan.error`.
  - `SentinelController` audits raw exception message on scan failure and exposes persisted raw scan error to Admin.
  - findings sorting uses MySQL-specific `FIELD(...)`, violating PostgreSQL/SQLite/SQL Server portability.
- Sentinel/Supply Chain are tied to the global package-installation plane; tenancy changes will not be applied blindly without preserving global Theme/Extension trust semantics.

---

## 7. Main protection / target blockers

`main` remains reported `protected=false`; current connector exposes no branch/ruleset mutation endpoint. Desired policy remains PR required + Source certification + stale review dismissal + review/conversation resolution + no force push/delete + admin enforcement. Issue #2 remains OPEN. Target Power remains 50%.

---

## 8. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | N1.16/N1.17 verified closures/governance | Project 76.5%, Source 99%, Target 50% |
| 014–020 | 2026-08-21 | through `95eb4bd4…`; zero-step hosted Actions | N1.18 API/token/SDK implementation | implementation complete; verified Power held |
| 021 | 2026-08-22 | user directive | hosted Actions deferred after quota exhaustion | Power unchanged |
| 022–027 | 2026-08-22 | through `8afd1f36…` | N1.19 import/export/WXR implementation | implementation complete; verified Power held |
| 028–031 | 2026-08-22 | through `97824bd4…` | N1.20 observability/privacy/correlation/retention + tests/contracts | implementation complete; verified Power held |
| 032–033 | 2026-08-22 | through Forge contract `39beaac0…` | N1.21 Forge hardening/tests/docs/contract | implementation-complete candidate |
| 034 | 2026-08-22 | readiness `74ca8c89…`; workflow `8e612c5a…` | self-hosted runner + PR trigger + Forge gate wiring | verified Power unchanged |
| 035 | 2026-08-22 | run `32523602178`; workflow correction `7310223d…` | real LOCAL-WIN-01 execution; diagnosed missing `pwsh`; switched certification to installed Windows toolchain | verified Power unchanged pending rerun |

---

## 9. Exact next action

```text
SELF-HOSTED CERTIFICATION
  1. inspect run triggered by workflow correction/progress commit
  2. verify local PHP/Node/npm toolchain step
  3. execute every source/product gate through Unified Source certification
  4. fix real gate failures until green
  5. after green only: promote N1.18-N1.21 SOURCE DONE

N1.22 SENTINEL 2.0 PARALLEL
  1. replace raw scan failure persistence/disclosure with generic error + non-secret reference/fingerprint
  2. replace MySQL FIELD severity ordering with portable CASE ordering
  3. verify promotion/rescan uses immutable current package + trustworthy scan/artifact state
  4. add acceptance/static regression gates

TARGET BOUNDARY
  - issue #2 stays OPEN until rc.93 compatibility + post-install + /login + /admin evidence
  - self-hosted source CI never raises Target Power by itself
```
