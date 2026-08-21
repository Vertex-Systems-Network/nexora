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
- Runner restriction head: `ea7b87a8e3ce774285f154a22b24a059cc74f74b`.
- Actions certification runner: **ONLY `LOCAL-WIN-03`** via `runs-on: [self-hosted, LOCAL-WIN-03]` plus a fail-closed `%RUNNER_NAME% == LOCAL-WIN-03` runtime assertion.
- `LOCAL-WIN-03` must have a custom GitHub Actions runner label named exactly `LOCAL-WIN-03`; if that label is absent, certification intentionally remains queued rather than falling back to another self-hosted runner.
- Workflow shell: `cmd` for repository `run:` steps, avoiding host Windows PowerShell ExecutionPolicy dependence.
- PR certification trigger: restored; temporary dev-branch push trigger remains during runner stabilization.
- Ledger: `2.4` — governance sync for N1.18–N1.23 and dedicated-runner policy pending consolidated certification result
- Issue #2: **OPEN**
- N1.18 Public APIs / Webhooks / SDK: implementation complete / certification pending
- N1.19 Import / Export / WordPress migrations: implementation complete / certification pending
- N1.20 Observability: implementation complete / certification pending
- N1.21 Forge / Developer Experience: implementation complete candidate / certification pending
- N1.22 Sentinel 2.0: first trust-hardening workflow implementation complete candidate / certification pending
- N1.23 Marketplace 2.0: first generation/authorization/transfer hardening workflow implementation complete candidate / certification pending

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
| N1.21 Forge / Developer Experience | implementation complete candidate | 0% current target | SELF-HOSTED CERTIFICATION PENDING |
| N1.22 Sentinel 2.0 | first trust-hardening workflow implementation complete candidate | 0% current target | SELF-HOSTED CERTIFICATION PENDING |
| N1.23 Marketplace 2.0 | first hardening workflow implementation complete candidate | 0% current target | **LOCAL-WIN-03 CERTIFICATION PENDING** |
| N1.24–N1.26 | planned/partial | 0% | Later roadmap |

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

Workflow enforcement:

```yaml
runs-on: [self-hosted, LOCAL-WIN-03]
```

Runtime defense-in-depth:

```bat
if /I not "%RUNNER_NAME%"=="LOCAL-WIN-03" exit /b 1
```

Consequences:
- `LOCAL-WIN-01`, `LOCAL-WIN-4`, or any other runner is not valid certification evidence.
- If the custom label `LOCAL-WIN-03` is missing from that runner, jobs remain queued intentionally.
- No GitHub-hosted setup actions are used to mutate the local PHP/Node toolchain.
- Repository `run:` steps use `cmd`, avoiding unsigned temporary PowerShell-script ExecutionPolicy failures.

---

## 6. N1.22 Sentinel 2.0 implementation checkpoint

- Sentinel/package scanner foundation retained: bounded ZIP inspection, static scanners, capability mismatch detection, digest recheck/TOCTOU block and RiskEngine.
- Quarantine retains internal UUID names, atomic copy, source-size limits, path guard and restrictive file permissions.
- `SentinelFailureReference` creates opaque `SNT-*` references and a non-secret exception-class fingerprint without embedding raw exception text in durable Admin-facing state.
- Private server diagnostics log the same opaque `SNT-*` reference so operator-visible failure references are correlatable with server logs.
- `ScanRecorder` persists only generic failure text + opaque reference/fingerprint metadata; raw throwable details remain server-log-only.
- Forward migration `2026_08_22_000300_sanitize_sentinel_scan_failures.php` irreversibly scrubs legacy persisted raw scan error strings.
- Sentinel Admin finding severity ordering uses portable SQL `CASE`, replacing MySQL-only `FIELD(...)`.
- `SentinelApprovalGuard` binds promotion to completed ALLOW, package ownership, bounded package state and unchanged package/scan SHA-256.
- Promotion rejects newer scans and ambiguous equal-timestamp competing scans.
- Theme and Extension installers invoke the approval guard server-side.
- Acceptance + static contract + Development Readiness + self-hosted workflow gate are present.

---

## 7. N1.23 Marketplace 2.0 implementation checkpoint

- Forward migration `2026_08_22_000400_harden_marketplace_catalog_generation.php` adds nullable `catalog_generation` on sources and `sync_generation` on catalog items.
- Historical rows intentionally remain generation-null; migration does not guess freshness. A successful fresh sync is required before display/staging.
- Marketplace catalog fetch is streamed to temporary storage with explicit 8 MiB budget before JSON decode; response progress, Content-Length and actual file size are bounded.
- `MarketplaceCatalogService` now retains `package_identifier` in normalized entries, fixing a latent undefined-key synchronization defect.
- Every successful catalog synchronization mints one UUID generation and publishes it atomically across retained items + source inside the catalog transaction.
- Resuming a source clears `catalog_generation`, `last_synced_at` and prior error state, forcing fresh synchronization.
- Catalog Admin visibility requires active source generation + item generation; exact source/item generation equality is checked before rendering retained entries.
- `MarketplacePackageStager` replaces timestamp tolerance with exact generation equality; stale/null/mismatched generations fail before download.
- Dynamic stage permission remains package-type aware (`themes.install` vs `extensions.install`) and now requires both global RBAC and `TenantAuthorizationService` current-organization authority.
- Marketplace Admin capability props for manage/install/marketplace-management now mirror current tenant authorization.
- Existing trusted-publisher, digest, download budget, quarantine, Sentinel and post-download signature checks remain intact.
- `tests/Feature/Marketplace/Marketplace2HardeningTest.php` covers null-generation visibility, matching-generation visibility, generation mismatch denial and tenant-role denial despite global install permission.
- `scripts/marketplace2-product-contract-verify.php` guards bounded catalog transfer, generation identity, tenant-aware dynamic authorization and fail-closed visibility.
- Existing N1.9 Marketplace contract was advanced from timestamp freshness assumptions to the stronger generation + tenant semantics so both gates describe one coherent product invariant.
- Marketplace 2.0 contract is required by Development Readiness and the self-hosted release workflow.
- Legacy `/extensions/marketplace/items/{item}/stage` route remains registered for compatibility, but server-side staging applies the same generation + tenant authorization guard; canonical UI route is `/admin/extensions/marketplace/catalog/{item}/stage`.

---

## 8. Main protection / target blockers

`main` remains reported `protected=false`; current connector exposes no branch/ruleset mutation endpoint. Desired policy remains PR required + Source certification + stale review dismissal + review/conversation resolution + no force push/delete + admin enforcement. Issue #2 remains OPEN. Target Power remains 50%.

---

## 9. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | N1.16/N1.17 verified closures/governance | Project 76.5%, Source 99%, Target 50% |
| 014–020 | 2026-08-21 | through `95eb4bd4…`; zero-step hosted Actions | N1.18 API/token/SDK implementation | implementation complete; verified Power held |
| 021 | 2026-08-22 | user directive | hosted Actions deferred after quota exhaustion | Power unchanged |
| 022–027 | 2026-08-22 | through `8afd1f36…` | N1.19 import/export/WXR implementation | implementation complete; verified Power held |
| 028–031 | 2026-08-22 | through `97824bd4…` | N1.20 observability/privacy/correlation/retention + tests/contracts | implementation complete; verified Power held |
| 032–033 | 2026-08-22 | through Forge contract `39beaac0…` | N1.21 Forge hardening/tests/docs/contract | implementation-complete candidate |
| 034 | 2026-08-22 | readiness `74ca8c89…`; workflow `8e612c5a…` | self-hosted runner + PR trigger + Forge gate wiring | verified Power unchanged |
| 035 | 2026-08-22 | earlier local-runner executions | generic self-hosted runner stabilization; those runners are no longer valid certification targets | verified Power unchanged |
| 036 | 2026-08-22 | Sentinel files through `d31e8524…`; workflow `e412df46…` | N1.22 privacy-safe correlated failures, legacy scrub, portable ordering, latest/tie-safe immutable approval, Theme/Extension replay prevention, tests + static/CI gate | implementation-complete candidate; verified Power unchanged pending consolidated run |
| 037 | 2026-08-22 | contract correction `06750699…` | fixed PHP interpolation-sensitive Sentinel contract needles | verified Power unchanged pending consolidated run |
| 038 | 2026-08-22 | migration `372a551f…`; catalog `62a2d4aa…`; stager `85a319e7…`; controller `b0e42d2c…`; config `14eedb5d…`; tests `512f2ad2…`; contract `c635a2fc…`; readiness `66bf801f…`; workflow `eac15d72…`; base contract `3c170397…` | N1.23 bounded catalog download, explicit atomic sync generation, fail-closed legacy/resume freshness, tenant-aware dynamic stage permission and compatibility-gate migration | implementation-complete candidate; verified Power unchanged pending consolidated run |
| 039 | 2026-08-22 | runner restriction `ea7b87a8…` | certification pinned exclusively to `LOCAL-WIN-03` with custom-label scheduling + runtime runner-name assertion; other local runners invalidated as certification evidence | verified Power unchanged pending LOCAL-WIN-03 run |

---

## 10. Exact next action

```text
LOCAL-WIN-03 CONSOLIDATED CERTIFICATION
  1. execute latest branch head only on runner LOCAL-WIN-03
  2. verify RUNNER_NAME == LOCAL-WIN-03 and installed PHP/Node/npm toolchain
  3. run every source/product gate through Sentinel 2.0 + Marketplace 2.0 + Unified Source certification
  4. inspect exact failed job logs and fix only real failures until green
  5. after green only: promote N1.18-N1.23 SOURCE DONE where justified
  6. update canonical ledger, PR #1 and issue #2 source checkpoint
  7. continue N1.24 Cloud / HA / Distributed Runtime

TARGET BOUNDARY
  - issue #2 stays OPEN until rc.93 compatibility + post-install + /login + /admin evidence
  - LOCAL-WIN-03 source CI never raises Target Power by itself
  - real DB/provider/SSO/product target evidence remains separately required
```
