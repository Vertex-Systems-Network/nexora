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
- Current N1.18 head before this progress commit: `2e5d4189f99679588895f05cdbe9ba69379f37ac`
- Latest fully green source CI: `32509858655` on N1.17 governance head `45e527c43c69f89c5519dde13bad6c771d171915`
- Canonical ledger: revision `2.4`
- Open blocker: issue #2 runtime identity mismatch — **OPEN**
- Completed source block: N1.17 SSO / Enterprise Governance — **SOURCE DONE / TARGET PENDING**
- Active source block: N1.18 Public APIs / Webhooks / SDK — **72% source candidate / integrated gate pending**

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

**Rule:** candidate progress may move during implementation, but verified Source Power moves only after required green source gates; Target Power moves only on real target execution.

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
| N1.18 Public APIs/Webhooks/SDK | **72% candidate** | 0% | **ACTIVE; UI/acceptance/gate pending** |
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

### Security/architecture findings

- No dedicated versioned bearer-token public API existed; webhook foundations were stronger.
- Existing inbound webhooks retain signed timestamp/replay/idempotency/payload boundaries and are reused rather than duplicated.
- API auth is tenant-native and dependency-neutral: one-time bearer tokens, hash-only persistence, bounded explicit abilities.
- Tenant resources are explicitly re-resolved after stateless token middleware installs tenant context; implicit pre-auth model binding is not trusted.

### Implemented candidate source

- `nx_api_access_tokens` forward schema: tenant + actor ownership, hash, non-secret hint, abilities, expiry/revocation/last-use; no plaintext token field.
- `ApiAccessToken`, bounded `ApiAbilityRegistry`, `ApiTokenManager`, stateless token middleware and explicit ability middleware.
- Token resolver rechecks active tenant/user/membership per request; middleware enforces 120 requests/minute per token and cleans tenant/auth context.
- Versioned `/api/v1/documents` list/detail routes are registered behind bearer auth and `documents.read` ability; list uses cursor pagination capped at 100.
- Document detail explicitly re-resolves through tenant-scoped `Document::query()` after authentication.
- Admin token lifecycle routes are isolated under `/admin/developer/api-tokens` with existing sensitive enterprise-identity permission and tenant route binding.
- Stable `PublicApiContract` + `CorePublicApiContract` expose only version/abilities/resource descriptors, not internal Eloquent models.
- `ApiServiceProvider` binds the contract, loads developer routes and registers `API & Integrations` Admin navigation.
- `bootstrap/app.php` now registers API routing and `api.token` / `api.ability` middleware aliases; `ApiServiceProvider` is bootstrapped.

### Required before SOURCE DONE

1. finish Admin token UI and explicit scalar revoke lookup; plaintext token only in current browser state;
2. add executable acceptance coverage for hash-only persistence, tenant isolation, expiry/revocation/stale-member denial, missing scope 403 and pagination cap;
3. add required Public API / SDK product contract to Development Readiness + Actions;
4. preserve existing inbound/outbound webhook product gates;
5. one current-head full green integrated CI.

---

## 6. Main branch protection

GitHub reports `main` as `protected=false`; authenticated repo access is admin-level. Desired server policy: PR required, Source certification required, stale approvals dismissed, review/conversation resolution required, force pushes blocked, deletion blocked, admins included unless explicit emergency bypass exists.

The connected GitHub tool surface does not expose branch-protection/ruleset mutation. **Server-side protection is not claimed as applied.**

---

## 7. Target blockers

Issue #2 remains OPEN. Existing rc.93 must pass:

```bat
php artisan nexora:runtime:compatibility-status --deep
php artisan nexora:runtime:post-install-status --assert-ready
```

Then `/login` and `/admin`. Separate current-branch target QA still requires full readiness/PHPUnit/build/product browser QA and disposable real DB matrix evidence. Target Power remains 50% until real execution.

---

## 8. Progress protocol — mandatory

After **every meaningful apply**, update current head/CI, block state, evidence-based Power, defects/fixes/blockers, exact next action and Apply Log. Never raise Target Power from source CI.

---

## 9. Apply Log

| Apply | Date | Head / evidence | Apply | Power impact |
|---:|---|---|---|---|
| 001 | 2026-08-21 | `11fbcd74…`; CI `32502604979` | Created weighted dashboard after N1.15 | Project 76.1% baseline |
| 002 | 2026-08-21 | `5e255f8d…` | N1.16 root authorization/privacy fixes | Candidate 15% -> 60%; verified Power held |
| 003 | 2026-08-21 | `df50de19…` | N1.16 acceptance/gate + progress governance | 60% -> 90%; held |
| 004 | 2026-08-21 | `e6c884f7…`; CI `32504705855` GREEN | N1.16 integrated closure | Project 76.3%, Source 98.5% |
| 005 | 2026-08-21 | `b8b8641f…`; CI `32505428674` GREEN | N1.16 governance sync | unchanged |
| 006 | 2026-08-21 | `39f991c3…` | N1.17 SSO/SCIM/invitation fixes | 10% -> 55% |
| 007 | 2026-08-21 | `61027bbd…` | N1.17 impersonation hardening | 55% -> 65% |
| 008 | 2026-08-21 | `c316b7c8…` | N1.17 acceptance + product gate | 65% -> 90% candidate |
| 009 | 2026-08-21 | `6856de41…`; CI `32508054237` | Semantic SSO/SCIM Source Guard | held |
| 010 | 2026-08-21 | `255ed88b…`; CI `32508273140` | Number-independent progress contract | held |
| 011 | 2026-08-21 | `1b86f397…`; CI `32508900897` GREEN | N1.17 integrated closure | Project 76.5%, Source 99%, Target 50% |
| 012 | 2026-08-21 | `72a0cbbb…` | Ledger 2.4 + branch protection evidence | unchanged |
| 013 | 2026-08-21 | `45e527c4…`; CI `32509858655` GREEN | PR N1.17 + issue #2 + governance reconfirmation | unchanged |
| 014 | 2026-08-21 | through `676a6aff…` | N1.18 token/API substrate | N1.18 -> 55% candidate; verified held |
| 015 | 2026-08-21 | `351f1c51…` | Explicit post-auth tenant document re-resolution | 55% -> 60%; verified held |
| 016 | 2026-08-21 | `2e5d4189…` | API route registration, stable public contract/provider, developer routes/navigation/bootstrap | N1.18 **60% -> 72% candidate**; verified Project/Source unchanged; Target 50% |

---

## 10. Exact next action

```text
N1.18 APPLY-04
  - Admin API-token UI + scalar tenant-scoped revoke lookup
  - plaintext token only in immediate browser-local state
  - progress update

N1.18 APPLY-05
  - tenant/scope/expiry/revocation/plaintext/pagination acceptance tests
  - Public API / SDK product contract
  - Development Readiness + Actions required gate
  - full green CI => SOURCE DONE only then

MAIN PROTECTION
  - main remains protected=false
  - apply server-side protection only when branch/ruleset mutation capability becomes available
```
