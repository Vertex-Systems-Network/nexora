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
- N1.19 Import / Export / WordPress migrations: **~94% implementation candidate / executable verification deferred**

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Verified Power is intentionally unchanged while N1.18/N1.19 executable certification is deferred. Static implementation progress is tracked separately and does not promote Target Power.

---

## 3. Roadmap

| Block | Source / implementation | Target | State |
|---|---:|---:|---|
| N1.9–N1.17 | 100% verified source | 0% current target | SOURCE DONE / target pending |
| N1.18 Public APIs/Webhooks/SDK | implementation complete | 0% | CI deferred; NOT SOURCE DONE |
| N1.19 Import/Export/WP migrations | **~94% candidate** | 0% | Implementation nearly complete; executable verification deferred |
| N1.20 Observability | foundation/partial | 0% | Next source block after N1.19 static closure |
| N1.21–N1.26 | planned/partial | 0% | Later roadmap |

---

## 4. N1.19 implementation checkpoint

### Tenant/replay persistence
- Tenant-owned migration run and per-item tables/models.
- Unique tenant+source-type+SHA source identity and unique run+source-key item identity prevent duplicate source/item replay.
- Run state includes cursor/counters/result/error lifecycle; item state stores destination identity and sanitized error reference.

### WXR parser / input safety
- Streaming `XMLReader`; 50 MB source cap.
- `LIBXML_NONET`; DTD loading, entity substitution and validation disabled.
- Only WordPress `post`/`page` first-flow items are emitted.
- Category attributes are read before stream advancement and bounded.
- Server-controlled staging path; client filename never selects filesystem path.
- Header signature + SHA-256 source fingerprint before run creation.
- Core does not fetch remote attachment/media URLs; validated HTTP(S) source URLs are metadata only.

### Import engine
- Uses canonical `DocumentRepositoryContract`, preserving normal document content validation and revision snapshots.
- `post` -> `blog_post`; `page` -> `document`; WordPress publication state mapped to Nexora state/workflow.
- Source HTML is reduced to bounded plain-text blocks; raw source HTML is not trusted/rendered.
- 2 MB per-item source cap, max 500 blocks, max 20k chars/block.
- Tenant-aware unique slug allocation.
- Already-imported item replay skips destination creation.
- Item failures persist generic metadata + deterministic error code without rolling back their failure ledger state.

### Queue/resume authority
- Dedicated `migrations` queue.
- Queue resolves run tenant unscoped, then restores it through `TenantExecutionScope::runRequired()`.
- Atomic `lockForUpdate` claim prevents duplicate workers processing the same queued run.
- 20,000 mapped-item run cap.
- Successful run deletes staged source; failed/partial run preserves source for explicit resume.
- `completed_with_errors` is resumable and imported items remain replay-skipped.
- Staging and resume re-check `documents.create` at service level.
- Queue execution re-checks the original creator is still active and still allowed `documents.create`; stale authorization fails closed.

### Export / Admin UX
- Versioned `nexora.documents.export.v1` document export streams with `chunkById(100)` and current tenant scope.
- Export response is private/no-store/nosniff.
- Admin page provides local WXR upload, run state/counters, resume and document export.
- Routes use authenticated Admin + `EnsureTenantRouteBinding`; import/resume use owning `documents.create`, listing/export uses `documents.view`.
- Resume uses scalar UUID then current-tenant query re-resolution; no cross-tenant implicit binding.
- `ContentMigrationServiceProvider` registers services, routes and Admin navigation; provider is bootstrapped.

### Acceptance / static contract
- `tests/Feature/Migrations/ContentMigrationProductTest.php` covers safe staging + dedupe + canonical import, failed-item persistence, stale creator authorization failure, and tenant-only streaming export.
- `scripts/content-migration-product-contract-verify.php` locks schema replay identity, XML parser restrictions, actor authorization, tenant queue scope, no remote fetch, bounded import/export, tenant routes/UI and acceptance markers.
- Release workflow contains the N1.19 contract step, but PR auto-trigger is temporarily disabled due exhausted Actions quota.

**N1.19 is NOT SOURCE DONE until executable consolidated certification runs after Actions capacity returns.**

---

## 5. Actions quota mode

- `pull_request` auto-trigger on release certification is temporarily removed to stop every source commit attempting unavailable Actions capacity.
- `push: main` and `workflow_dispatch` remain available.
- Do not manually trigger/re-run Actions while quota is exhausted.
- When capacity returns: restore `pull_request`, then run one consolidated source certification for N1.18 + N1.19 + any later deferred blocks.
- Zero-step quota/startup failures are infrastructure evidence, not application regression evidence.

---

## 6. Main protection / target blockers

`main` remains reported `protected=false`; current connector still exposes no branch/ruleset mutation endpoint. Desired protection policy remains PR required + Source certification + stale approval dismissal + review/conversation resolution + no force push/delete + admin enforcement. Issue #2 remains OPEN. Target Power remains 50%.

---

## 7. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | N1.16/N1.17 verified closures/governance | Project 76.5%, Source 99%, Target 50% |
| 014–020 | 2026-08-21 | through `95eb4bd4…`; zero-step Actions | N1.18 API/token/SDK implementation | Implementation complete; verified Power held |
| 021 | 2026-08-22 | user directive | Actions deferred after quota exhaustion | Power unchanged |
| 022 | 2026-08-22 | through `9443ad4b…` | N1.19 schema + WXR parser + importer | ~45% candidate |
| 023 | 2026-08-22 | through `d186f2bd…` | Staging + tenant queue + resumability + parser/failure fixes | ~65% candidate |
| 024 | 2026-08-22 | through `229521b3…` | Streaming export + Admin controller/routes/provider/UI | ~82% candidate; verified Power unchanged |
| 025 | 2026-08-22 | through `3eab8980…` | Acceptance tests + static product contract + service/queue actor reauthorization | ~92% candidate; verified Power unchanged |
| 026 | 2026-08-22 | workflow `e5ce1cd7…`; current source through `3eab8980…` | Temporarily disabled PR Actions auto-trigger, preserved main/manual certification, added N1.19 contract step | N1.19 **~94% implementation candidate**; verified Power unchanged |

---

## 8. Exact next action

```text
N1.19 STATIC CLOSURE
  1. add Content Migration product contract to Development Readiness
  2. self-audit parser/job/test/contract syntax + SQL portability without Actions
  3. mark N1.19 implementation complete / executable verification deferred (not SOURCE DONE)

THEN N1.20 OBSERVABILITY
  1. audit logs/metrics/traces/runtime health/audit telemetry and tenant boundaries
  2. implement smallest production-shaped observability workflow
  3. add tests + static product contract
  4. update THIS FILE after every meaningful apply
  5. DO NOT trigger GitHub Actions
```
