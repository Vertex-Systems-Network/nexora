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
- Active source block: **N1.20 Observability — ~30% implementation candidate**
- Latest N1.20 implementation head before this progress commit: `e282d88356ea61e417a4f505c911f78a348bc825`

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
| N1.20 Observability | **~30% candidate** | 0% | **ACTIVE** |
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
- Audit event/request/subject/user-agent values are bounded.
- Audit IPs are validated before persistence.
- `TelemetrySanitizer` recursively redacts secret-like keys (`password`, tokens, authorization, cookies, sessions, credentials, API/private keys), bounds depth/entry count/string length and drops unsupported object payloads.

### Incident foundation
- New `nx_observability_incidents` table stores privacy-minimal operational correlation fields: nullable tenant/user, request ID, category/severity/code, route name, HTTP method/status, duration, node key, sanitized metadata and occurrence time.
- `ObservabilityIncident` uses tenant-aware read scoping while allowing null-tenant platform incidents.

**Evidence boundary:** this is an implementation candidate only. N1.20 is not implementation-complete and not SOURCE DONE.

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
| 028 | 2026-08-22 | through `e282d883…` | N1.20 forward audit tenantization, unambiguous legacy backfill, incident schema/model and sanitized tenant-aware audit recording | N1.20 **~30% candidate**; verified Power unchanged |

---

## 9. Exact next action

```text
N1.20 OBSERVABILITY APPLY-02
  1. add request-outcome incident recorder + middleware for 5xx/slow requests
  2. never persist request body/query/header values or raw exception messages
  3. expose request IDs + tenant-scoped incidents in Admin Audit UI
  4. add bounded audit/incident/runtime-metric retention service + scheduled leader-gated prune command
  5. sanitize raw operational exception surfaces (queue backlog, etc.)
  6. add acceptance tests + static product contract + readiness/workflow source wiring
  7. update THIS FILE after each meaningful apply
  8. DO NOT trigger GitHub Actions
```
