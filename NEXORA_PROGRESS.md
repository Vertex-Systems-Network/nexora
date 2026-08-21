# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE**
> This file is the human-readable live progress dashboard for Nexora. Every AI/agent must update it after **every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply**.
>
> Canonical architecture/history remains in `NEXORA_AI_PROJECT_STATE.md`. This file is optimized for fast progress inspection and must never replace the SOURCE DONE vs TARGET VERIFIED distinction.

---

## 1. Current checkpoint

- Project: `Nexora`
- Date: `2026-08-21`
- Source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Generation: `n1-v5.29`
- Branch: `dev/n1-0b-core-functional-qa`
- PR: `#1` — **DRAFT + MERGEABLE**
- Current source head before this progress update: `df50de19f91d03fd6f0aa45c928edb11be39ea28`
- Latest fully green source CI: `32502604979` on the N1.15 checkpoint; first N1.16 integrated CI is now pending
- Canonical ledger revision: `2.2`
- Open blocking issue: `#2 Nexora runtime identity mismatch`
- Current source block: `N1.16 Multisite / Organizations`
- N1.16 state: **SOURCE GATE IMPLEMENTED — FIRST INTEGRATED CI PENDING**

---

## 2. Weighted Project Power Score

The **Power Score** is a weighted readiness indicator, not a claim that the product is production complete.

| Power plane | Weight | Current score | Weighted contribution | Meaning |
|---|---:|---:|---:|---|
| Architecture & core platform design | 10% | 98% | 9.8 | Core/module/capability/tenant architecture is mature |
| Source implementation | 35% | 98% | 34.3 | Major product workflows implemented through N1.15; N1.16 awaits integrated CI |
| Source verification / CI contracts | 15% | 100% | 15.0 | Last completed certified checkpoint green through N1.15; new N1.16 required gate pending |
| Real target functional verification | 20% | 50% | 10.0 | Broad current-branch Laragon/browser/runtime QA still pending |
| Database / portability target proof | 10% | 45% | 4.5 | Source/harness strong; real multi-engine matrix evidence pending |
| Release / operations / certification | 10% | 25% | 2.5 | Final reviewed locks, C1-C6 and release proof deferred |
| **TOTAL PROJECT POWER** | **100%** |  | **76.1%** | **Held until N1.16 integrated verification finishes** |

### Power bar

```text
PROJECT POWER   76.1%  ███████████████░░░░░
SOURCE POWER    98.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

**Rule:** Project Power may rise only when the corresponding weighted plane actually improves. Source CI alone cannot increase Target Power.

---

## 3. Source vs Target status

| Dimension | Progress | State |
|---|---:|---|
| Platform source implementation | ~98% | Strong source closure through N1.15; N1.16 implementation + source gate now present |
| Source certification | 100% for last completed checkpoint | GREEN through N1.15; N1.16 integrated run pending |
| Real functional verification | ~50% | PARTIAL |
| DEV-5 SQL/services portability source | ~95% | SOURCE STRONG / TARGET PENDING |
| Real DB matrix | ~0% current-branch certified engines | TARGET PENDING |
| Live rc.93 runtime recovery | 75% evidence plane | BLOCKED on 4 mutable fingerprints |
| Final release certification | ~25% readiness | DEFERRED |

---

## 4. Roadmap progress by block

| Block | Source progress | Target progress | Status / next evidence |
|---|---:|---:|---|
| DEV-0 Package/bootstrap | 90% | 70% | Final reviewed dependency locks deferred |
| DEV-1 Installer | 100% | 75% | rc.94 clean target install still required |
| DEV-2 TypeScript/Vite | 100% | 100% previously reported | Existing target build evidence; current branch full build still part of final QA |
| DEV-3 Runtime | 80% | 75% | Issue #2 rc.93 repair evidence pending |
| DEV-4 Core product QA | 98% | 30% | Broad current-branch product/browser PHPUnit QA pending |
| DEV-5 DB/services portability | 95% | 10% | Guarded real SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix pending |
| N1.9 Marketplace | 100% | 0% | SOURCE DONE; target pending |
| N1.10 Commerce 2.0 | 100% | 0% | SOURCE DONE; provider/browser target pending |
| N1.11 CRM/Membership/Portal | 100% | 0% | SOURCE DONE; target pending |
| N1.12 Search 2.0 | 100% | 0% | SOURCE DONE; target pending |
| N1.13 Collaboration | 100% | 0% | SOURCE DONE; target pending |
| N1.14 Automation | 100% | 0% | SOURCE DONE; queue/webhook target pending |
| N1.15 AI Platform | 100% | 0% | SOURCE DONE; real adapter/provider target evidence pending |
| N1.16 Multisite / Organizations | 90% source / CI pending | 0% | **ACTIVE** — implementation + acceptance + required contract wired; full green needed |
| N1.17 SSO / Enterprise Governance | foundation | 0% | Planned after N1.16 |
| N1.18 Public APIs / Webhooks / SDK | foundation/partial | 0% | Planned |
| N1.19 Import / Export / WP migrations | planned | 0% | Planned |
| N1.20 Observability | foundation/partial | 0% | Planned |
| N1.21 Forge / Developer Experience | foundation | 0% | Planned |
| N1.22 Sentinel 2.0 | foundation | 0% | Planned |
| N1.23 Marketplace 2.0 | N1.9 first flow done | 0% | Later expansion |
| N1.24 Cloud / HA | foundation | 0% | Later roadmap |
| N1.25 Backup / DR / Upgrade certification | partial | 0% | Later roadmap |
| N1.26 Performance + Accessibility + Release | partial source | 0% final | Final closure |
| N2.0 Stable Production | 0% certified | 0% | Requires all final gates |

---

## 5. Current N1.16 Multisite / Organizations audit

### Confirmed strengths

- Enterprise organization model/membership/domain/roles/SSO/SCIM foundations already exist.
- Tenant context and session organization switching foundation exists.
- `RequirePermission` composes global platform RBAC with `TenantAuthorizationService`, so organization roles already restrict current-tenant permissions.
- Organization manager has an explicit `canAccess()` concept.
- Organization detail views scope many data collections by `organization_id`.

### Root cause confirmed

The critical boundary was a **current-tenant vs route-organization confused-deputy gap**: permission middleware resolves permissions for current organization **A**, while `EnterpriseOrganization` itself has no `tenant_id`, so generic tenant route binding did not stop an `{organization}` parameter for organization **B**. Several mutation endpoints could therefore receive a different organization root after authorization had already succeeded for A.

### APPLY-01 fixes completed

1. `EnsureTenantRouteBinding` now treats `EnterpriseOrganization` as the tenant root and requires its primary key to equal the active `TenantContext` ID; mismatched organization routes fail with 404.
2. Organization list **Manage** now switches tenant first and only then visits the organization management route.
3. Organization switching no longer uses a platform-wide `exists` validator; it validates UUID shape, resolves active organization, checks membership/access, and returns 404 for inaccessible/nonexistent targets.
4. `EnterpriseController` action visibility now composes platform RBAC with `TenantAuthorizationService`, matching server route authorization rather than global permission checks alone.
5. Platform-wide user chooser is no longer disclosed to ordinary organization admins. Existing platform identities can be directly attached only by a Super Admin; ordinary org admins use invitation-by-email.
6. Direct member attachment accepts active platform users only and remains server-enforced Super Admin-only.
7. Impersonation target validation is organization-membership scoped, and the UI target picker is derived from active users already present in the organization member list rather than a platform-wide directory.
8. SSO adapter health output is now generic/fail-closed; arbitrary adapter-provided diagnostic text is not flashed to the Admin UI.

### APPLY-02 verification infrastructure completed

- Added `tests/Feature/Enterprise/MultisiteOrganizationIsolationTest.php` with cross-org route-root, hidden switch, platform-user non-disclosure/direct-attach denial, invitation preservation, member-scoped impersonation and nested-resource rejection coverage.
- Added `scripts/multisite-organizations-product-contract-verify.php`.
- Wired the new product contract into `scripts/development-readiness.php` and `.github/workflows/release-certification.yml` as a required source gate.
- Updated `AGENTS.md`: all future agents must read and update `NEXORA_PROGRESS.md` after every meaningful apply; Target Power cannot rise from source CI alone.
- Self-audited and corrected verifier source-marker interpolation before relying on CI.

### Verification still required before SOURCE DONE

- First integrated GitHub Actions run must pass the new Multisite / Organizations product source contract.
- Unified Source Certification and all prior gates must remain green on the same head.
- Any first-run regression must be fixed without weakening tenant boundaries.
- Canonical ledger/PR must then be synchronized and a final ledger-only source run confirmed.

N1.16 is **not SOURCE DONE** until the above source gate is green.

---

## 6. Blocking target work

### Issue #2 — live rc.93 runtime identity mismatch

Still requires real target evidence for:

```bat
php artisan nexora:runtime:compatibility-status --deep
php artisan nexora:runtime:post-install-status --assert-ready
```

Then browser exercise:

```text
/login
/admin
```

Current known mismatches remain:

```text
environment
activation
service
process
```

Do not replace rc.93 with rc.94 merely to make this issue disappear.

### Separate current-branch development target QA

```bat
scripts\development-readiness.bat --full
php artisan test
npm run build
php scripts\database-target-matrix.php --list
php scripts\database-target-matrix.php --drivers=sqlite,mysql,mariadb,pgsql,sqlsrv --evidence
```

Only real execution may increase Target Power.

---

## 7. Progress update protocol — mandatory

After **every meaningful apply**, update this file in the same pass or immediately after it.

Required fields to update:

1. current source head / latest CI evidence;
2. active block and exact state;
3. Project/Source/Target/Release Power if evidence changed;
4. per-block source/target progress;
5. newly discovered defects;
6. fixes completed in the latest apply;
7. current blockers;
8. exact next action;
9. append an entry to the Apply Log below.

### Progress scoring rules

- Static code alone can raise **Source Implementation**, never Target Verification.
- A product contract must pass CI before a block may be called SOURCE DONE.
- Real browser/PHPUnit/queue/provider/DB execution is required for corresponding Target Power.
- Issue closure requires the evidence defined by that issue.
- Final PR Ready/Merge requires all mandatory source + target + issue + release gates.
- Never inflate percentages simply because many files changed.

---

## 8. Apply Log

| Apply | Date | Head / evidence | What changed | Power impact |
|---:|---|---|---|---|
| 001 | 2026-08-21 | pre-file head `11fbcd744f641d241be9bca509dc4b2d64a18020`; CI `32502604979` | Created mandatory detailed weighted progress dashboard after N1.15 SOURCE DONE; N1.16 audit recorded | Baseline Project Power = **76.1%**; no Target Power increase |
| 002 | 2026-08-21 | implementation head `5e255f8dcf0fc9bf4c2999510067c0161f12705f`; verification pending | N1.16 APPLY-01: current-tenant organization route binding, non-disclosing switching, tenant-aware UI permissions, Super Admin-only direct platform identity attach, member-scoped impersonation, generic SSO health diagnostics | N1.16 source block **15% -> 60%**; weighted Project Power held at **76.1%** until regression/CI evidence |
| 003 | 2026-08-21 | gate head `df50de19f91d03fd6f0aa45c928edb11be39ea28`; integrated CI pending | N1.16 APPLY-02: six acceptance regressions, dedicated product contract, readiness/Actions gate, mandatory progress governance in `AGENTS.md`, verifier self-fix | N1.16 source block **60% -> 90%**; Project Power held at **76.1%** pending green evidence |

---

## 9. Exact next action

```text
N1.16 APPLY-03 — VERIFY / CORRECT / CLOSE
  1. inspect GitHub Actions run for the current progress head
  2. if red, use exact failed step/log and patch root cause
  3. update THIS FILE after any correction apply
  4. require Multisite / Organizations Product Contract + Unified Source Certification + all prior gates GREEN
  5. after green: mark N1.16 SOURCE DONE, recalculate evidence-based Power Score
  6. update canonical NEXORA_AI_PROJECT_STATE.md + append history
  7. update PR #1 through N1.16 while keeping DRAFT until target gates pass
  8. recheck issue #2; keep OPEN without new real rc.93 evidence
  9. final ledger/progress-only CI must be GREEN before next source block N1.17
```
