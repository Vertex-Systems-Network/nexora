# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE** — update after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply.
>
> `NEXORA_AI_PROJECT_STATE.md` remains canonical append-only history. This dashboard is the human-readable Power view. **SOURCE DONE != TARGET VERIFIED.**

---

## 1. Current checkpoint

- Date: `2026-08-22`
- Branch: `dev/n1-0b-core-functional-qa`
- PR #1: **DRAFT + MERGEABLE**, formal metadata through N1.17
- Last executable green source CI: `32509858655` on `45e527c43c69f89c5519dde13bad6c771d171915`
- GitHub Actions: **DEFERRED BY USER — quota exhausted; PR auto-trigger temporarily disabled; do not trigger/re-run**
- Ledger: `2.4`
- Issue #2: **OPEN**
- N1.18 Public APIs / Webhooks / SDK: **implementation complete / executable verification deferred**
- N1.19 Import / Export / WordPress migrations: **implementation complete / executable verification deferred**
- Active source block: **N1.20 Observability — ~62% implementation candidate**
- Latest N1.20 implementation head before this progress commit: `edf59676dbde8570d9c12c78acd2d0a67d70a167`

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Verified Power remains unchanged because N1.18+ deferred blocks have no consolidated executable certification yet. Static implementation completion is tracked separately and never promotes Target Power.

---

## 3. Roadmap

| Block | Source / implementation | Target | State |
|---|---:|---:|---|
| N1.9–N1.17 | 100% verified source | 0% current target | SOURCE DONE / target pending |
| N1.18 Public APIs/Webhooks/SDK | implementation complete | 0% | executable verification deferred; NOT SOURCE DONE |
| N1.19 Import/Export/WP migrations | implementation complete | 0% | executable verification deferred; NOT SOURCE DONE |
| N1.20 Observability | **~62% candidate** | 0% | **ACTIVE** |
| N1.21–N1.26 | planned/partial | 0% | Later roadmap |

---

## 4. N1.19 implementation closure checkpoint

N1.19 remains implementation complete / executable verification deferred: tenant-safe replayable WXR import, local-only bounded XML parsing, canonical Document repository mapping, service + queue authorization rechecks, streaming tenant JSON export, Admin import/export UX, tests and product contract are source-complete. No Actions are being spent while quota is exhausted.

---

## 5. N1.20 implemented so far

### Audit isolation / privacy
- Forward migration adds nullable `tenant_id` to `nx_audit_logs` and a tenant+time index.
- Legacy audit rows backfill only when the actor has exactly one active organization membership; ambiguous/system rows remain null and therefore fail closed in tenant-scoped views.
- `AuditLog` now applies a current-tenant global scope without default-tenant write fallback.
- `AuditManager` writes the explicit active tenant ID instead of relying on implicit/default organization assignment.
- Audit event/request/subject/user-agent values are bounded; IP is validated.
- `TelemetrySanitizer` recursively redacts secret-like keys, bounds nesting/entry/string size and rejects unsupported object payloads.

### Durable incident correlation
- `nx_observability_incidents` stores privacy-minimal correlation: nullable tenant/user, request ID, category/severity/code, named route, HTTP method/status, duration, node key, sanitized metadata and time.
- `ObservabilityIncident` applies current-tenant read scoping while preserving null-tenant platform incident semantics.
- `ObservabilityRecorder` records only 5xx or configurable slow requests (default 1500 ms).
- Raw request body, query values, arbitrary headers and raw exception messages are never persisted.
- Exception identity is stored only as a SHA-256 class fingerprint.
- API incidents derive tenant identity from the authenticated API token request attribute; web incidents use resolved `TenantContext`.
- Recorder is best-effort/fail-open so telemetry persistence failure cannot mask the original application failure.
- `ObserveRequestOutcome` records normal responses or thrown HTTP/application failures and then preserves/rethrows the original exception.
- Web middleware runs after enterprise tenant resolution; API middleware can consume the token attribute left by route authentication.

### Bounded retention
- Configured slow-request threshold, audit retention, incident retention and prune time live in `config/nexora_observability.php`.
- Audit retention is bounded to 30–3650 days; incident retention 7–365 days; runtime metric retention reuses the existing bounded cloud policy.
- `nexora:observability:prune` prunes audit, incident and runtime metric rows through a dedicated service.
- Observability pruning is scheduled leader-only, daily, without overlap.
- Provider/middleware bootstrap is registered in `bootstrap/providers.php` and `bootstrap/app.php`.

**Evidence boundary:** N1.20 is still an implementation candidate. Admin incident visibility, raw diagnostic cleanup and acceptance/product-contract wiring remain.

---

## 6. Actions quota mode

- Release workflow PR auto-trigger remains temporarily removed; `push: main` + `workflow_dispatch` remain.
- Do not trigger/re-run Actions while quota is exhausted.
- Continue source/tests/contracts and mark new blocks implementation-complete only after static/executable source artifacts are complete.
- When capacity returns: restore `pull_request`, then run one consolidated certification across every deferred block.

---

## 7. Main protection / target blockers

`main` remains reported `protected=false`; the connector still has no branch/ruleset mutation endpoint. Desired policy remains PR required + Source certification + stale approval dismissal + review/conversation resolution + no force push/delete + admin enforcement. Issue #2 remains OPEN. Target Power remains 50%.

---

## 8. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | N1.16/N1.17 verified closures/governance | Project 76.5%, Source 99%, Target 50% |
| 014–020 | 2026-08-21 | through `95eb4bd4…`; zero-step Actions | N1.18 API/token/SDK implementation | implementation complete; verified Power held |
| 021 | 2026-08-22 | user directive | Actions deferred after quota exhaustion | Power unchanged |
| 022–027 | 2026-08-22 | through `8afd1f36…` + progress `9f17b1ea…` | N1.19 import/export/WXR implementation, runtime readiness, tests/contracts | implementation complete; verified Power held |
| 028 | 2026-08-22 | through `e282d883…` | N1.20 audit tenantization, legacy backfill, incident schema/model, sanitized tenant-aware audit recording | ~30% candidate |
| 029 | 2026-08-22 | through `edf59676…` | 5xx/slow request incident recorder+middleware, privacy-minimal fingerprints, bounded retention command/service, leader-only scheduling and bootstrap wiring | N1.20 **~62% candidate**; verified Power unchanged |

---

## 9. Exact next action

```text
N1.20 OBSERVABILITY APPLY-03
  1. expose request ID search/display and current-tenant incidents in Admin Audit UI
  2. sanitize raw operational exception surfaces (queue backlog and similar diagnostics)
  3. add acceptance tests for tenant audit isolation, secret redaction, incident thresholds and retention
  4. add static N1.20 product contract + Development Readiness/workflow source wiring
  5. static portability/privacy audit
  6. implementation complete => move active block to N1.21 while keeping executable certification deferred
  7. update THIS FILE after each meaningful apply
  8. DO NOT trigger GitHub Actions
```
