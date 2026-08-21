# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE** — update after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply.
>
> `NEXORA_AI_PROJECT_STATE.md` remains the canonical append-only architecture/history ledger. This file is the human-readable weighted dashboard. **SOURCE DONE != TARGET VERIFIED.**

---

## 1. Current checkpoint

- Project: `Nexora`
- Date: `2026-08-22`
- Source: `1.0.0-rc.94` / installer `v5.29` / generation `n1-v5.29`
- Branch: `dev/n1-0b-core-functional-qa`
- PR #1: **DRAFT + MERGEABLE**, synchronized through N1.17
- Latest fully green executable source CI: `32509858655` on N1.17 governance head `45e527c43c69f89c5519dde13bad6c771d171915`
- GitHub Actions: **DEFERRED BY USER — quota exhausted; do not trigger further Actions until restored**
- Canonical ledger: revision `2.4`
- Issue #2 runtime identity mismatch: **OPEN**
- N1.18 Public APIs / Webhooks / SDK: **implementation complete / executable CI deferred**
- Active block: N1.19 Import / Export / WordPress migrations — **~45% source candidate**

---

## 2. Weighted Project Power Score

| Plane | Weight | Score | Contribution | Evidence state |
|---|---:|---:|---:|---|
| Architecture/core design | 10% | 98% | 9.8 | Mature modular/tenant architecture |
| Source implementation | 35% | 99.0% | 34.65 | Verified through N1.17; deferred blocks not promoted |
| Source verification/CI | 15% | 100% | 15.0 | Last executable required gates green through N1.17 |
| Real target functional verification | 20% | 50% | 10.0 | Broad target QA pending |
| DB/portability target proof | 10% | 45% | 4.5 | Real matrix pending |
| Release/operations/certification | 10% | 25% | 2.5 | Final proof deferred |
| **TOTAL PROJECT POWER** | **100%** |  | **76.5%** | Held while executable verification is deferred |

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

---

## 3. Roadmap power by block

| Block | Source | Target | Status |
|---|---:|---:|---|
| DEV-0..DEV-5 | source strong | partial | Final runtime/DB/release evidence pending |
| N1.9–N1.17 | 100% source | 0% current target | SOURCE DONE / target pending |
| N1.18 Public APIs/Webhooks/SDK | implementation complete | 0% | **CI DEFERRED; not SOURCE DONE** |
| N1.19 Import/Export/WP migrations | **~45% candidate** | 0% | **ACTIVE** |
| N1.20 Observability | foundation/partial | 0% | Planned |
| N1.21 Forge/DX | foundation | 0% | Planned |
| N1.22 Sentinel 2.0 | foundation | 0% | Planned |
| N1.23 Marketplace 2.0 | first flow done | 0% | Later expansion |
| N1.24 Cloud/HA | foundation | 0% | Later roadmap |
| N1.25 Backup/DR/Upgrade | partial | 0% | Later roadmap |
| N1.26 Performance/A11y/Release | partial | 0% | Final closure |

---

## 4. N1.18 implementation checkpoint

Tenant-owned hash-only API tokens, abilities, expiry/revocation, rate limiting, `/api/v1/documents`, explicit post-auth tenant resource lookup, browser-local one-time token display, stable public API descriptors/docs, install/runtime fences, acceptance source and product verifier wiring are implemented. Actions did not execute N1.18 gates because quota/startup failed before steps; therefore N1.18 remains implementation-complete but not SOURCE DONE.

---

## 5. N1.19 current implementation

### APPLY-01 persistence / replay foundation
- Added tenant-owned `nx_content_migration_runs` with source SHA, internal source path, status/cursor/counters, bounded result/error metadata and unique tenant+source-type+source-hash replay fence.
- Added tenant-owned `nx_content_migration_items` with unique run+source-key identity so resumable retries cannot create the same destination item twice.
- Added `ContentMigrationRun` and `ContentMigrationItem` models using `BelongsToTenant`.

### APPLY-02 WordPress WXR substrate
- Added streaming `WordPressWxrReader`; requires local readable file, maximum 50 MB, `LIBXML_NONET`, DTD loading disabled, entity substitution disabled and only WordPress `post` / `page` items emitted.
- Added `WordPressContentImporter` using the existing `DocumentRepositoryContract`, preserving normal content validation + revision snapshots.
- Per-item import is transaction-bound and replay-safe; imported destination ID is stored in the migration-item ledger.
- WordPress `post` maps to `blog_post`; `page` maps to neutral `document`; published/trash/draft states map to Nexora status/workflow states.
- WordPress HTML is converted to bounded plain-text paragraph blocks instead of trusting source HTML.
- Per-item raw content capped at 2 MB; blocks capped to 500 × 20k chars.
- Remote source URLs are metadata-only; Core performs **no remote media fetch** in this workflow, preventing migration-time SSRF.
- Source URLs accept only validated HTTP(S); raw exceptions are reduced to deterministic error codes in item state.

N1.19 is **NOT SOURCE DONE**. Job orchestration, staging, export stream, Admin routes/UI, acceptance tests and product contract still remain.

---

## 6. GitHub Actions operating rule while quota is exhausted

Do not trigger/re-run Actions. Continue source/tests/contracts. Deferred blocks keep implementation-complete/candidate status. When quota returns, run one consolidated certification for all deferred blocks.

---

## 7. Main protection / target blockers

`main` remains `protected=false` because the current GitHub connector has no branch/ruleset mutation action. Desired server policy is recorded. Issue #2 remains OPEN. Target Power remains 50%; only real Laragon/browser/PHPUnit/provider/DB execution can increase it.

---

## 8. Progress protocol — mandatory

Update this file after every meaningful apply with head/evidence, active block, Power, defects/fixes, blockers and exact next action. Actions quota exhaustion changes cadence, not evidence standards.

---

## 9. Apply Log

| Apply | Date | Head / evidence | Apply | Power impact |
|---:|---|---|---|---|
| 001–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | Dashboard + N1.16/N1.17 closures/governance | Project 76.5%, Source 99%, Target 50% |
| 014–020 | 2026-08-21 | through `95eb4bd4…`; zero-step Actions runs | N1.18 API/token/SDK implementation + self-audit | N1.18 implementation complete; verified Power held |
| 021 | 2026-08-22 | user directive | Actions deferred after quota exhaustion; consolidated-verification mode | Power unchanged |
| 022 | 2026-08-22 | through `9443ad4b…` | N1.19 tenant migration run/item schema + WXR streaming reader + replay-safe Document importer | N1.19 audit -> **~45% candidate**; verified Power unchanged |

---

## 10. Exact next action

```text
N1.19 APPLY-03
  1. add secure local-upload staging manager + queue job using TenantExecutionScope
  2. repair/lock parser attribute handling during streaming
  3. add streaming Nexora JSON document export
  4. add Admin routes/page for upload, run status/resume and export
  5. add tests + static N1.19 product contract
  6. update THIS FILE after each meaningful apply
  7. DO NOT trigger GitHub Actions
```
