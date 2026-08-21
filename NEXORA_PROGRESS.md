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
- Current N1.18 head before this progress commit: `42ba9ffab1c436ae64b6c0381bcecfd22485dbed`
- Latest fully green source CI: `32509858655` on N1.17 governance head `45e527c43c69f89c5519dde13bad6c771d171915`
- Canonical ledger: revision `2.4`
- Open blocker: issue #2 runtime identity mismatch — **OPEN**
- Completed source block: N1.17 SSO / Enterprise Governance — **SOURCE DONE / TARGET PENDING**
- Active source block: N1.18 Public APIs / Webhooks / SDK — **82% source candidate / acceptance + gate pending**

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
| N1.18 Public APIs/Webhooks/SDK | **82% candidate** | 0% | **ACTIVE; acceptance/product gate pending** |
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

### Implemented candidate source

- Hash-only tenant API-token schema/model with actor ownership, explicit abilities, expiry/revocation and no plaintext database field.
- Token manager requires active tenant/user/membership, 1–365 day expiry and request-time revalidation; issue/revoke actions are audited.
- Stateless bearer middleware installs/restores tenant/auth context and rate-limits per token; ability middleware returns scope denial.
- `/api/v1/documents` + `/api/v1/documents/{document}` use `documents.read`; pagination is cursor-based and capped at 100.
- Resource detail is explicitly re-resolved after token tenant context rather than implicit model binding.
- Stable `PublicApiContract` exposes version, ability and endpoint descriptors only; internal models are not part of the SDK contract.
- `ApiServiceProvider` binds the contract, boots developer routes and Admin navigation; API route/middleware registration is wired in bootstrap.
- Admin token routes use sensitive enterprise identity permission + tenant binding.
- Revoke resolves token by scalar UUID through current-tenant `ApiAccessToken::query()` after web tenant resolution.
- Admin `API & Integrations` UI lists only hash-safe metadata, issues tokens via direct JSON, holds newly issued plaintext only in React state, supports copy/dismiss, and revokes without re-exposing credentials.

### Required before SOURCE DONE

1. executable acceptance tests for plaintext non-persistence, cross-tenant resource isolation, cross-tenant revoke denial, expiry/revocation/stale-member denial, scope 403 and pagination max 100;
2. static Public API / SDK product contract, including stable contract descriptor and preservation of Automation webhook signature/replay/idempotency boundaries;
3. required gate in Development Readiness + GitHub Actions;
4. full green current-head CI.

---

## 6. Main branch protection

GitHub reports `main` as `protected=false`; authenticated repo access is admin-level. Desired server policy: PR required, Source certification required, stale approvals dismissed, review/conversation resolution required, force pushes blocked, deletion blocked, admins included unless explicit emergency bypass exists.

The connected GitHub tool surface does not expose branch-protection/ruleset mutation. **Server-side protection is not claimed as applied.**

---

## 7. Target blockers

Issue #2 remains OPEN. Existing rc.93 still requires compatibility + post-install readiness PASS, then `/login` and `/admin`. Separate current-branch target QA still requires full readiness/PHPUnit/build/product browser QA and disposable real DB matrix evidence. Target Power remains 50% until real execution.

---

## 8. Progress protocol — mandatory

After **every meaningful apply**, update current head/CI, block state, evidence-based Power, defects/fixes/blockers, exact next action and Apply Log. Never raise Target Power from source CI.

---

## 9. Apply Log

| Apply | Date | Head / evidence | Apply | Power impact |
|---:|---|---|---|---|
| 001 | 2026-08-21 | `11fbcd74…`; CI `32502604979` | Created weighted dashboard after N1.15 | Project 76.1% baseline |
| 002 | 2026-08-21 | `5e255f8d…` | N1.16 root authorization/privacy fixes | Candidate 15% -> 60%; verified held |
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
| 013 | 2026-08-21 | `45e527c4…`; CI `32509858655` GREEN | N1.17 governance reconfirmation | unchanged |
| 014 | 2026-08-21 | through `676a6aff…` | N1.18 token/API substrate | N1.18 -> 55% candidate |
| 015 | 2026-08-21 | `351f1c51…` | Post-auth tenant document re-resolution | 55% -> 60% |
| 016 | 2026-08-21 | `2e5d4189…` | API routes + public contract/provider + developer route/bootstrap | 60% -> 72% |
| 017 | 2026-08-21 | `42ba9ffa…` | Scalar tenant-scoped revoke + one-time browser-local token Admin UI | N1.18 **72% -> 82% candidate**; verified Project/Source held; Target 50% |

---

## 10. Exact next action

```text
N1.18 APPLY-05 — ACCEPTANCE + REQUIRED GATE
  - API tenant/token lifecycle feature tests
  - preserve webhook security contract
  - Public API / SDK product source verifier
  - Development Readiness + Actions wiring
  - progress update

N1.18 APPLY-06 — VERIFY/CORRECT/CLOSE
  - current-head integrated CI
  - root-fix any red gate without weakening security
  - full green => N1.18 SOURCE DONE + conservative Power recalculation
  - ledger 2.5 + PR/issue sync

MAIN PROTECTION
  - main remains protected=false; connector still lacks ruleset mutation
```
