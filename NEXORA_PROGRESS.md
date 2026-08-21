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
- PR: `#1` — **DRAFT + MERGEABLE**, synchronized through N1.16
- Verified source head before this progress update: `b8b8641f92b5e1cfa0528afe5ff8f0c26f0e132d`
- Latest fully green source CI: `32505428674`
- Canonical ledger revision: `2.3`
- Open blocking issue: `#2 Nexora runtime identity mismatch` — still OPEN, N1.16 source checkpoint posted
- Completed source block: `N1.16 Multisite / Organizations` — **SOURCE DONE / TARGET PENDING**
- Current source block: `N1.17 SSO / Enterprise Governance`
- N1.17 state: **AUDIT IN PROGRESS**

---

## 2. Weighted Project Power Score

The **Power Score** is a weighted readiness indicator, not a claim that the product is production complete.

| Power plane | Weight | Current score | Weighted contribution | Meaning |
|---|---:|---:|---:|---|
| Architecture & core platform design | 10% | 98% | 9.8 | Core/module/capability/tenant architecture is mature |
| Source implementation | 35% | 98.5% | 34.475 | Major product workflows source-gated through N1.16; N1.17 audit active |
| Source verification / CI contracts | 15% | 100% | 15.0 | All current required source gates green through N1.16 |
| Real target functional verification | 20% | 50% | 10.0 | Broad current-branch Laragon/browser/runtime QA still pending |
| Database / portability target proof | 10% | 45% | 4.5 | Source/harness strong; real multi-engine matrix evidence pending |
| Release / operations / certification | 10% | 25% | 2.5 | Final reviewed locks, C1-C6 and release proof deferred |
| **TOTAL PROJECT POWER** | **100%** |  | **76.3%** | **Evidence-based weighted readiness** |

### Power bar

```text
PROJECT POWER   76.3%  ███████████████░░░░░
SOURCE POWER    98.5%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

**Rule:** Project Power may rise only when the corresponding weighted plane actually improves. Source CI alone cannot increase Target Power.

---

## 3. Source vs Target status

| Dimension | Progress | State |
|---|---:|---|
| Platform source implementation | ~98.5% | Strong source closure through N1.16; N1.17 audit active |
| Source certification | 100% for current required gates | GREEN through N1.16 |
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
| N1.16 Multisite / Organizations | 100% source | 0% | SOURCE DONE; real organization/browser/runtime execution pending |
| N1.17 SSO / Enterprise Governance | 10% audit | 0% | **ACTIVE** — SSO/SCIM/invitation/roles/impersonation lifecycle audit |
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

## 5. N1.16 Multisite / Organizations closure

### Root cause closed

The critical boundary was a **current-tenant vs route-organization confused-deputy gap**: permission middleware resolved permissions for organization **A**, but `EnterpriseOrganization` is the tenant root and has no `tenant_id`, so a route parameter for organization **B** was not rejected by the generic route-binding guard.

### SOURCE DONE implementation

1. `EnsureTenantRouteBinding` treats `EnterpriseOrganization` as the tenant root and requires its primary key to equal the active `TenantContext` ID; mismatched organization routes fail with 404.
2. Organization list **Manage** switches tenant first and then visits the selected organization management route.
3. Organization switching validates UUID shape, resolves only active organizations, checks access, and returns 404 for inaccessible/nonexistent targets without a global `exists` disclosure validator.
4. Enterprise UI action capability props compose platform RBAC with `TenantAuthorizationService`, matching route authorization semantics.
5. Ordinary organization admins no longer receive a platform-wide user directory. Existing platform identities can be directly attached only by a Super Admin; tenant admins retain invitation-by-email.
6. Direct member attachment accepts active users only and remains server-enforced Super Admin-only.
7. Impersonation target validation is scoped to active members of the selected organization; UI options are derived from organization members, not the platform user directory.
8. SSO adapter health diagnostics are generic/fail-closed; arbitrary adapter-provided text is not surfaced to Admin.
9. `AGENTS.md` mandates reading/updating this progress dashboard after every meaningful apply and prohibits raising Target Power from source CI alone.

### Regression/source verification

- `tests/Feature/Enterprise/MultisiteOrganizationIsolationTest.php` covers cross-org route-root replay rejection, hidden switching, platform-user non-disclosure/direct-attach denial, invitation preservation, member-scoped impersonation and nested-resource rejection.
- `scripts/multisite-organizations-product-contract-verify.php` is required by development readiness and GitHub Actions.
- Integrated source run `32504705855` passed the Multisite / Organizations Product Contract and Unified Source Certification.
- Governance/progress sync run `32504935527` remained green.
- Canonical ledger revision 2.3 commit `b8b8641f92b5e1cfa0528afe5ff8f0c26f0e132d` then passed final release-certification run `32505428674`, reconfirming every source gate.
- PR #1 title/body are synchronized through N1.16 and remain DRAFT.
- Issue #2 received an N1.16 source-only checkpoint and remains OPEN because no real rc.93 recovery evidence changed.

### Target boundary

N1.16 is **SOURCE DONE, TARGET PENDING**. Real organization switching, member/invitation/domain/SSO/SCIM/impersonation browser/runtime tests remain part of the separate current-branch target QA pass.

---

## 6. Current N1.17 SSO / Enterprise Governance audit

### Audit objectives

1. SSO provider lifecycle: adapter registration, configuration/secrets, enabled/enforced states, start/callback tenant/provider binding and safe failure behavior.
2. SSO login enforcement: determine whether `enforce_for_members` is actually enforced for password-login/admin access or is currently metadata-only.
3. SCIM: bearer-token tenant resolution, revocation/expiry, user create/update membership scoping and response non-disclosure.
4. Invitations: token storage/expiry/single-use semantics, acceptance tenant binding and role safety.
5. Enterprise roles: system-role mutation restrictions, wildcard handling, actor authority and membership lifecycle.
6. Governed impersonation: actor/target tenant membership, session restoration, nested impersonation prevention and audit completeness.
7. Secret/config trust boundaries: provider/adapter-controlled messages and secret-bearing configuration must not leak into UI/audit/session/history.

### State

N1.17 is **AUDIT IN PROGRESS**, not SOURCE DONE. No Power increase is recorded until an architecture-correct implementation and dedicated source gate pass.

---

## 7. Blocking target work

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

## 8. Progress update protocol — mandatory

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

## 9. Apply Log

| Apply | Date | Head / evidence | What changed | Power impact |
|---:|---|---|---|---|
| 001 | 2026-08-21 | pre-file head `11fbcd744f641d241be9bca509dc4b2d64a18020`; CI `32502604979` | Created mandatory detailed weighted progress dashboard after N1.15 SOURCE DONE; N1.16 audit recorded | Baseline Project Power = **76.1%**; no Target Power increase |
| 002 | 2026-08-21 | implementation head `5e255f8dcf0fc9bf4c2999510067c0161f12705f`; verification pending | N1.16 APPLY-01: current-tenant organization route binding, non-disclosing switching, tenant-aware UI permissions, Super Admin-only direct platform identity attach, member-scoped impersonation, generic SSO health diagnostics | N1.16 source block **15% -> 60%**; Project Power held at **76.1%** until regression/CI evidence |
| 003 | 2026-08-21 | gate head `df50de19f91d03fd6f0aa45c928edb11be39ea28`; integrated CI pending | N1.16 APPLY-02: six acceptance regressions, dedicated product contract, readiness/Actions gate, mandatory progress governance in `AGENTS.md`, verifier self-fix | N1.16 source block **60% -> 90%**; Project Power held at **76.1%** pending green evidence |
| 004 | 2026-08-21 | verified head `e6c884f714e6419794b1c11566e978987a73ecad`; CI `32504705855` GREEN | N1.16 APPLY-03: integrated source gate passed with all prior gates + Multisite/Organizations contract + Unified Source Certification | N1.16 **90% -> 100% SOURCE DONE**; Source Power **98.0% -> 98.5%**; Project Power **76.1% -> 76.3%**; Target Power remains **50.0%** |
| 005 | 2026-08-21 | final governance head `b8b8641f92b5e1cfa0528afe5ff8f0c26f0e132d`; CI `32505428674` GREEN | N1.16 governance closure: canonical ledger 2.3 verified, PR #1 synchronized through N1.16, issue #2 source checkpoint posted, N1.17 audit activated | Power unchanged at **76.3%**; Target remains **50.0%** because governance/source evidence is not real-target execution |

---

## 10. Exact next action

```text
N1.17 APPLY-01 — SSO / ENTERPRISE GOVERNANCE AUDIT + ROOT FIXES
  1. inspect SsoController + SsoProviderRegistry/contracts/adapters
  2. inspect login path for enforce_for_members semantics
  3. inspect ScimController + ScimTokenManager authentication/revocation/expiry and membership updates
  4. inspect InvitationController + InvitationManager single-use/expiry/tenant binding
  5. inspect enterprise roles and ImpersonationManager lifecycle authority/nested-session rules
  6. implement smallest fail-closed fixes
  7. update THIS FILE immediately after implementation apply
  8. add N1.17 acceptance regressions + enterprise-governance product contract
  9. wire development-readiness + Actions and require full GREEN before SOURCE DONE
```
