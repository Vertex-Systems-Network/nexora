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
- Active source block: **N1.20 Observability**

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Verified Power remains unchanged because N1.18/N1.19 have no executable consolidated certification yet. Static implementation completion is tracked separately and never promotes Target Power.

---

## 3. Roadmap

| Block | Source / implementation | Target | State |
|---|---:|---:|---|
| N1.9–N1.17 | 100% verified source | 0% current target | SOURCE DONE / target pending |
| N1.18 Public APIs/Webhooks/SDK | implementation complete | 0% | executable verification deferred; NOT SOURCE DONE |
| N1.19 Import/Export/WP migrations | **implementation complete** | 0% | executable verification deferred; NOT SOURCE DONE |
| N1.20 Observability | foundation/partial | 0% | **ACTIVE** |
| N1.21–N1.26 | planned/partial | 0% | Later roadmap |

---

## 4. N1.19 implementation closure checkpoint

- Tenant-owned migration runs/items with source-SHA and source-key replay fences.
- Streaming WXR parsing with 50 MB source limit, `LIBXML_NONET`, DTD/entity substitution disabled and post/page allow-list.
- Admission fails closed when PHP `XMLReader` is unavailable; Admin UI exposes runtime readiness and disables WXR actions while export remains available.
- Server-controlled tenant staging paths; client filenames cannot select filesystem locations.
- Source signature + SHA-256 dedupe before run creation.
- Canonical `DocumentRepositoryContract` import preserves content validation + revisions.
- Source HTML is reduced to bounded plain-text blocks; no Core remote media/network fetch path.
- Service-level and queue execution-time `documents.create` reauthorization.
- Queue tenant restoration through `TenantExecutionScope`, atomic run claim, 20k item safety cap and replay-safe partial resume.
- Failed item ledger state persists sanitized deterministic error references.
- Successful staging source cleanup; partial/failed source retention for resume.
- Versioned tenant-scoped `nexora.documents.export.v1` streaming export with `chunkById(100)` + private/no-store response.
- Admin import/export status/resume UI + tenant-bound routes/provider/navigation.
- Executable acceptance source covers staging/dedupe/import, failed item state, stale creator denial and tenant export isolation.
- `scripts/content-migration-product-contract-verify.php` required in Development Readiness and release workflow source.
- Static portability sweep found no `DB::statement`, no schema `->after(...)`, and no direct `Http::` / URL-fetch implementation in the migration flow.

**Evidence boundary:** N1.19 is implementation complete but **NOT SOURCE DONE** until the consolidated executable source certification runs after Actions quota returns.

---

## 5. Actions quota mode

- Release workflow PR auto-trigger is temporarily removed; `push: main` + `workflow_dispatch` remain.
- Do not trigger/re-run Actions while quota is exhausted.
- Continue source/tests/contracts and mark new blocks implementation-complete when appropriate.
- When capacity returns: restore `pull_request`, then run one consolidated certification across every deferred block.

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
| 022–023 | 2026-08-22 | through `d186f2bd…` | N1.19 schema/parser/import/staging/queue/resume | ~65% candidate |
| 024–026 | 2026-08-22 | through `3eab8980…` + workflow `e5ce1cd7…` | Export/Admin/UI/tests/contract/auth revalidation + PR Actions deferral | ~94% candidate |
| 027 | 2026-08-22 | through `8afd1f36…`; readiness `449602cb…` | XMLReader admission/UI readiness, N1.19 Development Readiness wiring, static portability/network sweep | **N1.19 implementation complete / verification deferred**; verified Power unchanged |

---

## 8. Exact next action

```text
N1.20 OBSERVABILITY
  1. audit current logs, runtime metrics, health, audit telemetry, queue/scheduler activity and tenant boundaries
  2. identify missing correlation / bounded retention / incident visibility / export boundary
  3. implement smallest production-shaped observability workflow
  4. add executable acceptance source + static product contract
  5. update THIS FILE after every meaningful apply
  6. DO NOT trigger GitHub Actions

DEFERRED CONSOLIDATED CERTIFICATION
  - restore PR Actions trigger when capacity returns
  - certify N1.18 + N1.19 + later deferred blocks in one pass
```
