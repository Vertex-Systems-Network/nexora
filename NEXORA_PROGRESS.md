# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE** — update after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply.
>
> `NEXORA_AI_PROJECT_STATE.md` remains canonical history. **SOURCE DONE != TARGET VERIFIED.**

---

## 1. Current checkpoint

- Date: `2026-08-22`
- Branch: `dev/n1-0b-core-functional-qa`
- PR #1: **DRAFT + MERGEABLE**, metadata through N1.17
- Last executable green source CI: `32509858655` on `45e527c43c69f89c5519dde13bad6c771d171915`
- GitHub Actions: **DEFERRED BY USER — quota exhausted; do not trigger/re-run**
- Ledger: `2.4`
- Issue #2: **OPEN**
- N1.18: **implementation complete / executable verification deferred**
- N1.19 Import / Export / WordPress migrations: **~65% source candidate**

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Verified Power stays unchanged while deferred blocks lack executable certification. Source implementation may continue; Target Power only moves on real target execution.

---

## 3. Roadmap

| Block | Source | Target | State |
|---|---:|---:|---|
| N1.9–N1.17 | 100% | 0% current target | SOURCE DONE / target pending |
| N1.18 Public APIs/Webhooks/SDK | implementation complete | 0% | CI deferred; not SOURCE DONE |
| N1.19 Import/Export/WP migrations | **~65% candidate** | 0% | ACTIVE |
| N1.20 Observability | foundation/partial | 0% | Next |
| N1.21–N1.26 | planned/partial | 0% | Later roadmap |

---

## 4. N1.19 implemented so far

### Persistence / replay
- `nx_content_migration_runs`: tenant/source SHA identity, internal source path, status/cursor/counters, result/error metadata.
- `nx_content_migration_items`: unique run+source-key replay ledger and destination identity.
- Both models use `BelongsToTenant`.

### WordPress WXR safety
- Streaming `XMLReader` parser; 50 MB source cap.
- `LIBXML_NONET`; DTD loading, entity substitution and validation disabled.
- Only `post` and `page` first-flow resources emitted.
- Category attributes captured before streaming node read and bounded.
- No network/remote attachment fetch in Core; source HTTP(S) URLs are metadata-only.

### Import mapping
- Existing `DocumentRepositoryContract` is used so normal content validation + revisions remain authoritative.
- `post` -> `blog_post`; `page` -> neutral `document`.
- WordPress HTML is reduced to bounded plain-text paragraph blocks; no source HTML execution/trust.
- 2 MB per-item raw content cap, max 500 text blocks, max 20k chars/block.
- Published/trash/draft statuses map to Nexora publishing/editorial states.
- Tenant-scoped unique slug allocation.

### Staging / queue / resumability
- Upload staging uses a server-generated tenant/run path; client filename never controls filesystem path.
- Header checks require RSS + WordPress export signature before staging.
- SHA-256 duplicate source is reused instead of creating a second run.
- Queue job resolves tenant ID unscoped, then executes through `TenantExecutionScope`.
- Atomic run claim prevents duplicate queue workers from processing one run simultaneously.
- Hard 20,000-item run cap.
- Successful runs delete the staged source; partial/failed runs retain it for explicit resume.
- `completed_with_errors` can resume; already imported items are skipped by the item ledger.
- Failed item state now commits sanitized `error_code` metadata instead of being rolled back with the failed destination transaction.

N1.19 remains **NOT SOURCE DONE**. Export/Admin/tests/product-contract work remains.

---

## 5. Actions quota mode

Do not trigger GitHub Actions. Continue code/tests/contracts. When capacity returns, run one consolidated certification for all deferred blocks; zero-step quota/startup failures do not count as source regressions.

---

## 6. Main protection / target blockers

`main` is still reported `protected=false`; current connector exposes no server branch/ruleset mutation. Desired protection policy remains recorded. Issue #2 remains OPEN. Target Power remains 50%.

---

## 7. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | N1.16/N1.17 source closures/governance | Project 76.5%, Source 99%, Target 50% |
| 014–020 | 2026-08-21 | through `95eb4bd4…`; Actions zero-step | N1.18 API/token/SDK implementation | Implementation complete; verified Power held |
| 021 | 2026-08-22 | user directive | Actions deferred after quota exhaustion | Power unchanged |
| 022 | 2026-08-22 | through `9443ad4b…` | N1.19 schema + WXR parser + importer | N1.19 ~45% candidate |
| 023 | 2026-08-22 | through `d186f2bd…` | Parser attribute fix, sanitized failed-item persistence, secure staging manager, tenant queue job and partial-run resume | N1.19 **~65% candidate**; verified Power unchanged |

---

## 8. Exact next action

```text
N1.19 APPLY-04
  1. streaming Nexora JSON document export
  2. Admin import/export routes + run status/resume UI
  3. provider/navigation registration
  4. executable tenant/replay/parser/export acceptance tests
  5. static N1.19 product contract + readiness/Actions wiring (do not run Actions)
  6. update THIS FILE after each apply
```
