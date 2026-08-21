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
- Current progress head before this commit: `95eb4bd44f9448f6b1826b03eef222b0f5f233e9`
- Latest fully green executable source CI: `32509858655` on N1.17 governance head `45e527c43c69f89c5519dde13bad6c771d171915`
- GitHub Actions state: **DEFERRED BY USER — Actions quota/limit exhausted. Do not spend further Actions capacity until restored.** Runs `32517367269` and `32517785822` failed before executing any workflow steps and are not source-contract failures.
- Canonical ledger: revision `2.4`
- Open blocker: issue #2 runtime identity mismatch — **OPEN**
- N1.18 Public APIs / Webhooks / SDK: **implementation complete / executable CI deferred**
- Active source block: **N1.19 Import / Export / WordPress migrations**

---

## 2. Weighted Project Power Score

| Plane | Weight | Score | Contribution | Evidence state |
|---|---:|---:|---:|---|
| Architecture/core design | 10% | 98% | 9.8 | Mature modular/tenant architecture |
| Source implementation | 35% | 99.0% | 34.65 | Verified through N1.17; later blocks continue without claiming CI closure |
| Source verification/CI | 15% | 100% | 15.0 | Last executable required source gates green through N1.17; Actions quota now deferred |
| Real target functional verification | 20% | 50% | 10.0 | Broad target QA pending |
| DB/portability target proof | 10% | 45% | 4.5 | Real matrix pending |
| Release/operations/certification | 10% | 25% | 2.5 | Final proof deferred |
| **TOTAL PROJECT POWER** | **100%** |  | **76.5%** | Held; no evidence-plane inflation while Actions are deferred |

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

**Scoring rule:** source implementation may continue while Actions are deferred, but SOURCE DONE / verified Source Power only advances when the required executable gate is eventually run successfully (or an equivalent approved executable certification path is recorded). Target Power moves only on real target execution.

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
| N1.18 Public APIs/Webhooks/SDK | **implementation complete** | 0% | **CI DEFERRED — do not claim SOURCE DONE yet** |
| N1.19 Import/Export/WP migrations | **audit active** | 0% | Current source block |
| N1.20 Observability | foundation/partial | 0% | Planned |
| N1.21 Forge/DX | foundation | 0% | Planned |
| N1.22 Sentinel 2.0 | foundation | 0% | Planned |
| N1.23 Marketplace 2.0 | first flow done | 0% | Later expansion |
| N1.24 Cloud/HA | foundation | 0% | Later roadmap |
| N1.25 Backup/DR/Upgrade | partial | 0% | Later roadmap |
| N1.26 Performance/A11y/Release | partial | 0% | Final closure |
| N2.0 Stable Production | 0% certified | 0% | Requires final gates |

---

## 4. N1.18 implementation checkpoint

Implemented: tenant-owned hash-only API credentials, explicit abilities, active tenant/user/membership revalidation, expiry/revocation, per-token throttling, versioned `/api/v1/documents`, max-100 cursor pagination, post-auth explicit tenant resource lookup, scalar tenant-safe revoke, one-time browser-local plaintext display, stable `PublicApiContract`, v1 documentation, API install/runtime fencing, API acceptance source, required Public API/SDK verifier wiring, and preserved webhook HMAC/replay/idempotency boundaries.

**Evidence boundary:** N1.18 is implementation-complete but not SOURCE DONE because Actions quota exhausted before an executable N1.18 certification run. The two observed failing runs executed zero steps, so they are not red source evidence.

---

## 5. GitHub Actions operating rule while quota is exhausted

- Do **not** trigger, re-run, or depend on GitHub Actions during normal source applies.
- Continue GitHub source implementation, static contract hardening, documentation, tests and governance files.
- Keep blocks in `implementation complete / executable verification deferred` state when their required CI cannot run.
- When Actions capacity returns, run one consolidated certification pass across all deferred blocks instead of spending quota per small apply.
- Never reinterpret zero-step/quota startup failures as application regressions.

---

## 6. Main branch protection

GitHub reports `main` as `protected=false`; repo access is admin-level. Desired policy remains PR required + Source certification + stale approval dismissal + review/conversation resolution + no force push/delete + admin enforcement. Current connector still has no branch/ruleset mutation action, so server-side protection is not falsely claimed.

---

## 7. Target blockers

Issue #2 remains OPEN; rc.93 compatibility/post-install PASS + `/login` + `/admin` are still required. Current-branch full readiness/PHPUnit/build/browser QA and disposable DB matrix remain target work. **Target Power 50%.**

---

## 8. Progress protocol — mandatory

Update this file after every meaningful apply with head/evidence, active block, Power, defects/fixes, blockers, next action and a new Apply Log row. Actions quota exhaustion changes the execution cadence, not the evidence standard.

---

## 9. Apply Log

| Apply | Date | Head / evidence | Apply | Power impact |
|---:|---|---|---|---|
| 001 | 2026-08-21 | `11fbcd74…`; CI `32502604979` | Dashboard baseline after N1.15 | Project 76.1% |
| 002–005 | 2026-08-21 | through `b8b8641f…`; CI `32505428674` | N1.16 implementation/gate/governance | Project 76.3%, Source 98.5% |
| 006–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | N1.17 SSO/SCIM/invitation/impersonation + gates/governance | Project 76.5%, Source 99%, Target 50% |
| 014–020 | 2026-08-21 | through `95eb4bd4…`; zero-step runs `32517367269`, `32517785822` | N1.18 API/token/SDK implementation + self-audit; CI runner never executed gates | N1.18 implementation complete; verified Power held |
| 021 | 2026-08-22 | user directive: Actions quota exhausted | **Deferred GitHub Actions** and switched to consolidated-verification mode; moved active implementation to N1.19 | Power unchanged; evidence standard preserved |

---

## 10. Exact next action

```text
N1.19 IMPORT / EXPORT / WORDPRESS MIGRATIONS
  1. audit existing import/export and migration foundations
  2. define tenant-safe resumable import job + export boundary
  3. protect archive/XML/JSON parsing from path traversal, SSRF, oversized payload and duplicate replay
  4. add WordPress WXR mapping without coupling Core to WordPress runtime
  5. add executable tests + static product contract source
  6. update THIS FILE after each meaningful apply
  7. DO NOT trigger Actions while quota is exhausted

DEFERRED CERTIFICATION
  - when Actions quota returns, run one consolidated source certification for N1.18+ deferred blocks
```
