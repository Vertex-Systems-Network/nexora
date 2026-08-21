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
- Ledger: `2.4` — governance sync for deferred N1.18–N1.20 remains pending
- Issue #2: **OPEN**
- N1.18 Public APIs / Webhooks / SDK: **implementation complete / executable verification deferred**
- N1.19 Import / Export / WordPress migrations: **implementation complete / executable verification deferred**
- N1.20 Observability: **implementation complete / executable verification deferred**
- Active source block: **N1.21 Forge / Developer Experience**
- Latest N1.20 implementation head before this progress commit: `97824bd42e405ee357d841ffff8ffd829fe7a267`

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Verified Power remains unchanged because N1.18–N1.20 have no consolidated executable certification yet. Static implementation completion is tracked separately and never promotes Target Power.

---

## 3. Roadmap

| Block | Source / implementation | Target | State |
|---|---:|---:|---|
| N1.9–N1.17 | 100% verified source | 0% current target | SOURCE DONE / target pending |
| N1.18 Public APIs/Webhooks/SDK | implementation complete | 0% | executable verification deferred; NOT SOURCE DONE |
| N1.19 Import/Export/WP migrations | implementation complete | 0% | executable verification deferred; NOT SOURCE DONE |
| N1.20 Observability | **implementation complete** | 0% | executable verification deferred; NOT SOURCE DONE |
| N1.21 Forge / Developer Experience | foundation/planned | 0% | **ACTIVE** |
| N1.22 Sentinel 2.0 | foundation | 0% | Next |
| N1.23–N1.26 | planned/partial | 0% | Later roadmap |

---

## 4. Deferred implementation-complete blocks

### N1.18 Public APIs / Webhooks / SDK
Tenant-bound hash-only API credentials, explicit abilities, `/api/v1/documents`, bounded cursor pagination, tenant re-resolution, one-time browser-local token display, stable public API descriptor, API tests/contracts and preserved webhook replay/signature safety are source-complete; executable certification deferred.

### N1.19 Import / Export / WordPress migrations
Tenant-safe replayable WXR import, bounded local-only XML parsing, canonical Document repository mapping, service+queue authorization rechecks, streaming tenant JSON export, Admin UI, tests/contracts and runtime readiness are source-complete; executable certification deferred.

### N1.20 Observability
- `nx_audit_logs` forward tenantization with only unambiguous legacy membership backfill; ambiguous/system history remains null and fail-closed in tenant views.
- Tenant-scoped Audit model + explicit tenant writes in `AuditManager`.
- Recursive telemetry sanitizer redacts password/token/authorization/cookie/session/credential/API/private-key style metadata, bounds depth/count/string size and drops unsupported objects.
- Privacy-minimal `nx_observability_incidents` for request ID, tenant/user, named route, 5xx/slow category, status, duration, node and sanitized metadata.
- Only 5xx or configurable slow requests are retained; healthy fast requests are ignored.
- Raw body/query values/arbitrary headers/raw exception messages are not persisted; exception class is SHA-256 fingerprinted.
- API token tenant correlation + web TenantContext correlation.
- Observability persistence is best-effort/fail-open and never replaces the original application exception.
- `AuditManager`/`ObservabilityRecorder` use scoped lifecycle to avoid long-lived tenant capture.
- Audit & Incidents Admin surface supports request-ID search, tenant-only incident visibility and 24h failure/latency summaries.
- Bounded audit/incident retention with leader-only daily prune; runtime metric retention remains single-owned by existing `nexora:runtime:prune`.
- Queue backlog diagnostics no longer expose raw provider/driver exception messages.
- Acceptance source: tenant audit isolation/redaction, incident threshold/privacy, tenant incident isolation, retention preservation/pruning.
- `scripts/observability-product-contract-verify.php` is required by Development Readiness and release workflow source.
- Static portability/privacy review: no N1.20 raw SQL migration statement, no `->after(...)`, no intended raw request content/header/query persistence.

**Evidence boundary:** N1.20 is implementation complete but **NOT SOURCE DONE** until consolidated executable source certification is available after Actions quota restoration.

---

## 5. Actions quota mode

- Release workflow PR auto-trigger remains temporarily removed; `push: main` + `workflow_dispatch` remain.
- Do not trigger/re-run Actions while quota is exhausted.
- Continue source/tests/contracts and mark blocks implementation-complete separately from SOURCE DONE.
- When capacity returns: restore `pull_request`, then run one consolidated certification across N1.18+ deferred blocks.

---

## 6. Main protection / target blockers

`main` remains reported `protected=false`; the connector still has no branch/ruleset mutation endpoint. Desired policy remains PR required + Source certification + stale approval dismissal + review/conversation resolution + no force push/delete + admin enforcement. Issue #2 remains OPEN. Target Power remains 50%.

---

## 7. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | N1.16/N1.17 verified closures/governance | Project 76.5%, Source 99%, Target 50% |
| 014–020 | 2026-08-21 | through `95eb4bd4…`; zero-step Actions | N1.18 API/token/SDK implementation | implementation complete; verified Power held |
| 021 | 2026-08-22 | user directive | Actions deferred after quota exhaustion | Power unchanged |
| 022–027 | 2026-08-22 | through `8afd1f36…` + progress `9f17b1ea…` | N1.19 import/export/WXR implementation, runtime readiness, tests/contracts | implementation complete; verified Power held |
| 028–030 | 2026-08-22 | through `ba056be4…` | N1.20 audit tenantization, incidents, retention, Admin correlation, lifecycle + diagnostic hardening | ~82% candidate |
| 031 | 2026-08-22 | through `97824bd4…`; tests `5353d908…`; contract `3c5c3b45…`; readiness `767a9f20…`; workflow `84d27bda…` | N1.20 acceptance/product contract/gate wiring + lazy console schedule correction | **N1.20 implementation complete / executable verification deferred**; verified Power unchanged |

---

## 8. Exact next action

```text
N1.21 FORGE / DEVELOPER EXPERIENCE
  1. audit CLI/generator/scaffolding/package-development foundations
  2. identify unsafe filesystem/code generation or missing deterministic templates
  3. implement smallest production-shaped Forge workflow with dry-run/overwrite safety
  4. add tests + static product contract
  5. update THIS FILE after each meaningful apply
  6. DO NOT trigger GitHub Actions

GOVERNANCE FOLLOW-UP
  - update canonical ledger from 2.4 with N1.18–N1.20 implementation-complete / verification-deferred state
  - keep PR #1 draft; do not imply deferred blocks are SOURCE DONE
```
