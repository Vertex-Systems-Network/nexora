# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE** — update after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply.
>
> `NEXORA_AI_PROJECT_STATE.md` remains the canonical append-only architecture/history ledger. This file is the human-readable weighted dashboard. **SOURCE DONE != TARGET VERIFIED.**

---

## 1. Current checkpoint

- Project: `Nexora`
- Date: `2026-08-21`
- Source: `1.0.0-rc.94` / installer `v5.29` / generation `n1-v5.29`
- Branch: `dev/n1-0b-core-functional-qa`
- PR #1: **DRAFT + MERGEABLE**, synchronized through N1.17
- Current N1.18 head before this progress commit: `351f1c5147c73058135303879af0b37a6ddc39d5`
- Latest fully green source CI: `32509858655` on N1.17 governance head `45e527c43c69f89c5519dde13bad6c771d171915`
- Canonical ledger: revision `2.4`
- Open blocker: issue #2 runtime identity mismatch — **OPEN**
- Completed source block: N1.17 SSO / Enterprise Governance — **SOURCE DONE / TARGET PENDING**
- Active source block: N1.18 Public APIs / Webhooks / SDK — **60% source candidate / unverified**

---

## 2. Weighted Project Power

| Plane | Weight | Score | Contribution | Evidence state |
|---|---:|---:|---:|---|
| Architecture/core design | 10% | 98% | 9.8 | Mature modular/tenant architecture |
| Source implementation | 35% | 99.0% | 34.65 | Verified through N1.17; N1.18 partial is not counted yet |
| Source verification/CI | 15% | 100% | 15.0 | All required gates green through N1.17 |
| Real target functional verification | 20% | 50% | 10.0 | Broad Laragon/browser/runtime QA pending |
| DB/portability target proof | 10% | 45% | 4.5 | Source/harness strong; real matrix pending |
| Release/operations/certification | 10% | 25% | 2.5 | Reviewed locks/C1-C6/final proof deferred |
| **TOTAL PROJECT POWER** | **100%** |  | **76.5%** | Evidence-based; held during unverified N1.18 work |

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

**Rule:** static/source changes may move an active block's candidate percentage, but verified Source Power moves only after required green source gates; Target Power moves only on real target execution.

---

## 3. Roadmap power by block

| Block | Source | Target | Status |
|---|---:|---:|---|
| DEV-0 Package/bootstrap | 90% | 70% | Final dependency certification deferred |
| DEV-1 Installer | 100% | 75% | Clean rc.94 target install confirmation pending |
| DEV-2 TypeScript/Vite | 100% | 100% previously reported | Current branch full build still in final QA |
| DEV-3 Runtime | 80% | 75% | Issue #2 rc.93 repair evidence pending |
| DEV-4 Core product QA | 98% | 30% | Broad current-branch product/browser QA pending |
| DEV-5 DB/services portability | 95% | 10% | Real multi-engine matrix pending |
| N1.9 Marketplace | 100% | 0% | SOURCE DONE |
| N1.10 Commerce 2.0 | 100% | 0% | SOURCE DONE |
| N1.11 CRM/Membership/Portal | 100% | 0% | SOURCE DONE |
| N1.12 Search 2.0 | 100% | 0% | SOURCE DONE |
| N1.13 Collaboration | 100% | 0% | SOURCE DONE |
| N1.14 Automation | 100% | 0% | SOURCE DONE |
| N1.15 AI Platform | 100% | 0% | SOURCE DONE |
| N1.16 Multisite/Organizations | 100% | 0% | SOURCE DONE |
| N1.17 SSO/Enterprise Governance | 100% | 0% | SOURCE DONE |
| N1.18 Public APIs/Webhooks/SDK | **60% candidate** | 0% | **ACTIVE; integrated gate pending** |
| N1.19 Import/Export/WP migrations | planned | 0% | Planned |
| N1.20 Observability | foundation/partial | 0% | Planned |
| N1.21 Forge/DX | foundation | 0% | Planned |
| N1.22 Sentinel 2.0 | foundation | 0% | Planned |
| N1.23 Marketplace 2.0 | first flow done | 0% | Later expansion |
| N1.24 Cloud/HA | foundation | 0% | Later roadmap |
| N1.25 Backup/DR/Upgrade | partial | 0% | Later roadmap |
| N1.26 Performance/A11y/Release | partial | 0% | Final closure |
| N2.0 Stable Production | 0% certified | 0% | Requires all final gates |

---

## 4. N1.17 closure evidence

N1.17 remains **SOURCE DONE, TARGET PENDING**. Run `32508900897` passed the SSO / Enterprise Governance Product Contract and Unified Source Certification. Final governance head `45e527c43c69f89c5519dde13bad6c771d171915` was reconfirmed fully green by `32509858655`. Ledger revision 2.4, PR metadata and issue #2 source checkpoint are synchronized.

---

## 5. N1.18 Public APIs / Webhooks / SDK — active

### Audit conclusions

- There was no dedicated versioned public bearer-token API surface; the existing webhook engine is substantially more mature.
- Existing inbound webhooks already provide signed timestamped requests, bounded payloads, endpoint idempotency and safe header persistence; N1.18 reuses that security boundary rather than duplicating it.
- First public API workflow is dependency-neutral and tenant-native: hashed bearer tokens + versioned read API.
- API resource lookup must occur after token tenant context is installed; implicit model binding is not trusted for tenant resources.

### Implemented candidate source

- Forward `nx_api_access_tokens` schema: UUID, tenant + actor ownership, unique token hash, non-secret hint, explicit abilities, expiry/revocation/last-used timestamps; no plaintext-token database field.
- Tenant-aware `ApiAccessToken` model with hidden token hash.
- Bounded `ApiAbilityRegistry` rather than arbitrary scope strings.
- `ApiTokenManager` for one-time token issue, hash-only storage, 1–365 day expiry, active tenant/user/membership checks, request-time revalidation and revocation audit.
- Stateless bearer middleware with per-token 120 requests/minute, tenant/auth context installation and cleanup, API version/rate headers.
- Explicit ability middleware.
- Current-tenant Admin token controller foundation.
- `/Api/V1/DocumentController` foundation with cursor pagination capped at 100 and bounded filters.
- Document detail lookup now accepts a scalar key and explicitly runs `Document::query()->whereKey(...)` after token middleware establishes tenant context, preventing pre-auth implicit resource binding.

### Required before SOURCE DONE

1. register `routes/api.php` and API middleware aliases;
2. wire `/api/v1/documents` + detail endpoint behind `content.documents.read`;
3. add Admin token permission/routes/UI with plaintext shown only once in immediate browser-local response;
4. define first stable public SDK/integration contract without exposing internal models;
5. acceptance tests: hash-only persistence, tenant isolation, expiry/revocation/stale-member denial, scope 403, pagination cap;
6. required Public API / SDK product contract in Development Readiness + Actions;
7. full green integrated CI.

---

## 6. Main branch protection

GitHub directly reports `main` as `protected=false`. Authenticated repo access is admin-level. Desired server policy remains: PR required, Source certification required, stale approvals dismissed, review/conversation resolution required, force pushes blocked, deletion blocked, admins included unless explicit emergency bypass exists.

The connected GitHub tool surface does not expose branch-protection/ruleset mutation. Therefore **server-side protection is not claimed as applied**; a source workflow is not a substitute for GitHub protection.

---

## 7. Target blockers

Issue #2 remains OPEN. Existing rc.93 must pass:

```bat
php artisan nexora:runtime:compatibility-status --deep
php artisan nexora:runtime:post-install-status --assert-ready
```

Then `/login` and `/admin`. Known mismatches remain `environment`, `activation`, `service`, `process`.

Separate current-branch target QA:

```bat
scripts\development-readiness.bat --full
php artisan test
npm run build
php scripts\database-target-matrix.php --list
php scripts\database-target-matrix.php --drivers=sqlite,mysql,mariadb,pgsql,sqlsrv --evidence
```

---

## 8. Progress protocol — mandatory

After **every meaningful apply**, update current head/CI, block state, evidence-based Power, defects/fixes/blockers, exact next action and Apply Log. Never raise Target Power from source CI.

---

## 9. Apply Log

| Apply | Date | Head / evidence | Apply | Power impact |
|---:|---|---|---|---|
| 001 | 2026-08-21 | `11fbcd74…`; CI `32502604979` | Created weighted dashboard after N1.15 | Project 76.1% baseline |
| 002 | 2026-08-21 | `5e255f8d…` | N1.16 root authorization/privacy fixes | Candidate 15% -> 60%; verified Power held |
| 003 | 2026-08-21 | `df50de19…` | N1.16 acceptance/gate + progress governance | Candidate 60% -> 90%; held |
| 004 | 2026-08-21 | `e6c884f7…`; CI `32504705855` GREEN | N1.16 integrated closure | SOURCE DONE; Project 76.3%, Source 98.5% |
| 005 | 2026-08-21 | `b8b8641f…`; CI `32505428674` GREEN | Ledger/PR/issue N1.16 sync | unchanged |
| 006 | 2026-08-21 | `39f991c3…` | N1.17 SSO/SCIM/invitation fixes | Candidate 10% -> 55% |
| 007 | 2026-08-21 | `61027bbd…` | N1.17 impersonation authority/session hardening | 55% -> 65% |
| 008 | 2026-08-21 | `c316b7c8…` | N1.17 acceptance + required product gate | 65% -> 90% candidate |
| 009 | 2026-08-21 | `6856de41…`; CI `32508054237` | Semantic SSO/SCIM Source Guard | held at 90% |
| 010 | 2026-08-21 | `255ed88b…`; CI `32508273140` | Section-number-independent progress contract | held at 90% |
| 011 | 2026-08-21 | `1b86f397…`; CI `32508900897` GREEN | N1.17 integrated closure | SOURCE DONE; Project 76.5%, Source 99.0%, Target 50% |
| 012 | 2026-08-21 | `72a0cbbb…` | Ledger 2.4 + branch-protection evidence | unchanged |
| 013 | 2026-08-21 | `45e527c4…`; CI `32509858655` GREEN | PR N1.17 sync + issue #2 checkpoint + governance reconfirmation | unchanged |
| 014 | 2026-08-21 | through `676a6aff…` | N1.18 tenant token/API substrate + Admin/document controller foundations | N1.18 -> 55% candidate; verified Power held |
| 015 | 2026-08-21 | `351f1c51…` | Explicit post-auth tenant document re-resolution | N1.18 55% -> 60% candidate; verified Power held; Target 50% |

---

## 10. Exact next action

```text
N1.18 APPLY-03
  - API middleware aliases + routes/api.php registration
  - /api/v1/documents endpoints + content.documents.read scope
  - Admin token permission/routes/UI; no plaintext persistence
  - stable public SDK/integration contract
  - progress update

N1.18 APPLY-04
  - tenant/scope/expiry/revocation/plaintext/pagination acceptance tests
  - Public API / SDK product contract
  - Development Readiness + Actions required gate
  - full green CI => SOURCE DONE only then

MAIN PROTECTION
  - main remains protected=false
  - apply server-side policy only when branch/ruleset mutation capability is available
```
