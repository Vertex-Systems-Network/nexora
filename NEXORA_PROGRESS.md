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
- Current N1.18 correction head before this progress commit: `afcefe103e4eefb570d0f71fc27149fcf681c06d`
- Latest fully green source CI: `32509858655` on N1.17 governance head `45e527c43c69f89c5519dde13bad6c771d171915`
- Canonical ledger: revision `2.4`
- Open blocker: issue #2 runtime identity mismatch — **OPEN**
- Active source block: N1.18 Public APIs / Webhooks / SDK — **96% source candidate / integrated CI pending**

---

## 2. Weighted Project Power Score

| Plane | Weight | Score | Contribution | Evidence state |
|---|---:|---:|---:|---|
| Architecture/core design | 10% | 98% | 9.8 | Mature modular/tenant architecture |
| Source implementation | 35% | 99.0% | 34.65 | Verified through N1.17; N1.18 candidate not counted yet |
| Source verification/CI | 15% | 100% | 15.0 | Completed required gates green through N1.17 |
| Real target functional verification | 20% | 50% | 10.0 | Broad target QA pending |
| DB/portability target proof | 10% | 45% | 4.5 | Real matrix pending |
| Release/operations/certification | 10% | 25% | 2.5 | Final proof deferred |
| **TOTAL PROJECT POWER** | **100%** |  | **76.5%** | Held until N1.18 green or target evidence |

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Verified Source Power moves only after required green source gates. Target Power moves only on real target execution.

---

## 3. Roadmap power by block

| Block | Source | Target | Status |
|---|---:|---:|---|
| DEV-0 Package/bootstrap | 90% | 70% | Final dependency certification deferred |
| DEV-1 Installer | 100% | 75% | Clean rc.94 target install pending |
| DEV-2 TypeScript/Vite | 100% | 100% previously reported | Current branch build in final QA |
| DEV-3 Runtime | 80% | 75% | Issue #2 evidence pending |
| DEV-4 Core product QA | 98% | 30% | Broad target QA pending |
| DEV-5 DB/services portability | 95% | 10% | Real DB matrix pending |
| N1.9–N1.17 product blocks | 100% source | 0% current target | SOURCE DONE / target pending |
| N1.18 Public APIs/Webhooks/SDK | **96% candidate** | 0% | **ACTIVE; first integrated CI pending** |
| N1.19 Import/Export/WP migrations | planned | 0% | Planned |
| N1.20 Observability | foundation/partial | 0% | Planned |
| N1.21 Forge/DX | foundation | 0% | Planned |
| N1.22 Sentinel 2.0 | foundation | 0% | Planned |
| N1.23 Marketplace 2.0 | first flow done | 0% | Later expansion |
| N1.24 Cloud/HA | foundation | 0% | Later roadmap |
| N1.25 Backup/DR/Upgrade | partial | 0% | Later roadmap |
| N1.26 Performance/A11y/Release | partial | 0% | Final closure |
| N2.0 Stable Production | 0% certified | 0% | Requires final gates |

---

## 4. N1.17 closure evidence

N1.17 remains **SOURCE DONE, TARGET PENDING**. Run `32508900897` passed Enterprise Governance + Unified Source Certification; governance head `45e527c43c69f89c5519dde13bad6c771d171915` reconfirmed green in `32509858655`. Ledger 2.4, PR metadata and issue #2 checkpoint are synchronized.

---

## 5. N1.18 source candidate

Implemented boundaries:

- tenant-owned hash-only API credentials; no plaintext token column;
- explicit abilities, active tenant/user/membership revalidation, expiry/revocation and audit lifecycle;
- stateless bearer auth, per-token throttling and request context cleanup;
- versioned `/api/v1/documents` read API with max-100 cursor pagination;
- post-auth explicit tenant resource re-resolution; no implicit detail/revoke binding;
- stable `PublicApiContract` descriptors rather than public internal models;
- Admin one-time token issuance via direct JSON + React-local plaintext state only;
- documented v1/SDK compatibility rules;
- executable API isolation/lifecycle acceptance source;
- required Public API / SDK verifier in Development Readiness + Actions;
- Automation webhook HMAC/replay/idempotency boundaries preserved;
- API middleware group now inherits `AssignRequestId`, performance headers, `RedirectIfNotInstalled` and `RuntimeNodeHeartbeat` **before token route middleware executes**, preventing pre-install API DB access.

N1.18 remains **NOT SOURCE DONE** until current-head CI passes every prior gate + Public API / SDK Product Contract + Unified Source Certification.

---

## 6. Main branch protection

GitHub reports `main` as `protected=false`; repo access is admin-level. Desired policy remains PR required + Source certification + stale approval dismissal + review/conversation resolution + no force push/delete + admin enforcement. Current connector still has no branch/ruleset mutation action, so server-side protection is not falsely claimed.

---

## 7. Target blockers

Issue #2 remains OPEN; rc.93 compatibility/post-install PASS + `/login` + `/admin` are still required. Current-branch full readiness/PHPUnit/build/browser QA and disposable DB matrix remain target work. **Target Power 50%.**

---

## 8. Progress protocol — mandatory

Update this file after every meaningful apply with head/CI, block state, Power, defects/fixes, blockers, next action and a new Apply Log row. Never raise Target Power from source CI.

---

## 9. Apply Log

| Apply | Date | Head / evidence | Apply | Power impact |
|---:|---|---|---|---|
| 001 | 2026-08-21 | `11fbcd74…`; CI `32502604979` | Dashboard baseline after N1.15 | Project 76.1% |
| 002–005 | 2026-08-21 | through `b8b8641f…`; CI `32505428674` | N1.16 implementation/gate/governance | Project 76.3%, Source 98.5% |
| 006–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | N1.17 SSO/SCIM/invitation/impersonation + gates/governance | Project 76.5%, Source 99%, Target 50% |
| 014 | 2026-08-21 | through `676a6aff…` | N1.18 token/API substrate | N1.18 -> 55% candidate |
| 015 | 2026-08-21 | `351f1c51…` | Post-auth tenant document re-resolution | 55% -> 60% |
| 016 | 2026-08-21 | `2e5d4189…` | API routes + public contract/provider + developer bootstrap | 60% -> 72% |
| 017 | 2026-08-21 | `42ba9ffa…` | Tenant revoke + one-time browser-local token UI | 72% -> 82% |
| 018 | 2026-08-21 | `ad2eb0b0…` | Acceptance source + docs + Public API/SDK required gates | 82% -> 95% candidate |
| 019 | 2026-08-21 | `afcefe10…` | Added install/runtime fencing to API middleware group before bearer-token DB lookup | N1.18 **95% -> 96% candidate**; verified Project/Source held; Target 50% |

---

## 10. Exact next action

```text
N1.18 APPLY-06 — VERIFY / CORRECT / CLOSE
  1. inspect current-head Actions
  2. require all old gates + Public API / SDK gate + Unified Source Certification
  3. root-fix any red result and update this file after each correction
  4. full green => N1.18 SOURCE DONE + conservative verified Power update
  5. ledger 2.5 + PR #1 N1.18 sync + issue #2 source checkpoint

MAIN PROTECTION
  - main remains protected=false; no ruleset mutation capability exposed
```
