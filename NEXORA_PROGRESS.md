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
- Current implementation head before this progress update: `39f991c396e69f44a83cbf2a354e9e5d6bb75ec7`
- Latest fully green source CI: `32505428674` on the N1.16 final governance checkpoint
- Canonical ledger revision: `2.3`
- Open blocking issue: `#2 Nexora runtime identity mismatch` — still OPEN
- Completed source block: `N1.16 Multisite / Organizations` — **SOURCE DONE / TARGET PENDING**
- Current source block: `N1.17 SSO / Enterprise Governance`
- N1.17 state: **APPLY-01 IMPLEMENTED — ACCEPTANCE/CI VERIFICATION PENDING**

---

## 2. Weighted Project Power Score

The **Power Score** is a weighted readiness indicator, not a claim that the product is production complete.

| Power plane | Weight | Current score | Weighted contribution | Meaning |
|---|---:|---:|---:|---|
| Architecture & core platform design | 10% | 98% | 9.8 | Core/module/capability/tenant architecture is mature |
| Source implementation | 35% | 98.5% | 34.475 | Verified source closure through N1.16; N1.17 implementation awaits dedicated gate |
| Source verification / CI contracts | 15% | 100% | 15.0 | Latest completed required source gates are green through N1.16 |
| Real target functional verification | 20% | 50% | 10.0 | Broad current-branch Laragon/browser/runtime QA still pending |
| Database / portability target proof | 10% | 45% | 4.5 | Source/harness strong; real multi-engine matrix evidence pending |
| Release / operations / certification | 10% | 25% | 2.5 | Final reviewed locks, C1-C6 and release proof deferred |
| **TOTAL PROJECT POWER** | **100%** |  | **76.3%** | **Held until N1.17 source verification completes** |

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
| Platform source implementation | ~98.5% verified | Strong source closure through N1.16; N1.17 implementation under verification |
| Source certification | 100% for last completed required gates | GREEN through N1.16; N1.17 gate not yet created/passed |
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
| N1.13 Collaboration | 100% | 0% | SOURCE DONE; queue/browser target pending |
| N1.14 Automation | 100% | 0% | SOURCE DONE; queue/webhook target pending |
| N1.15 AI Platform | 100% | 0% | SOURCE DONE; real adapter/provider target evidence pending |
| N1.16 Multisite / Organizations | 100% source | 0% | SOURCE DONE; real organization/browser/runtime execution pending |
| N1.17 SSO / Enterprise Governance | 55% implementation | 0% | **ACTIVE** — root fixes implemented; impersonation + acceptance/contract/CI pending |
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

N1.16 remains **SOURCE DONE, TARGET PENDING**. Final source evidence is ledger revision 2.3 head `b8b8641f92b5e1cfa0528afe5ff8f0c26f0e132d`, release-certification run `32505428674` GREEN. PR #1 is synchronized through N1.16 and stays draft. Issue #2 remains open because no real rc.93 target evidence changed.

---

## 6. Current N1.17 SSO / Enterprise Governance

### Confirmed defects found

1. `enforce_for_members` existed in the SSO provider model/UI but local password authentication ignored it, making enforcement metadata-only.
2. SSO callback state did not bind the saved flow back to both organization + provider identity and did not re-check adapter protocol at callback time.
3. Adapter redirect URLs and identity payloads needed a Core trust boundary instead of being trusted as arbitrary adapter output.
4. Public SSO `configuration` could carry secret-like values despite the separate encrypted `secret_payload` field.
5. SCIM tokens did not explicitly fail closed when their organization became inactive/suspended.
6. SCIM `active` response was derived only from global `User.status`, while SCIM PATCH changed organization membership status, producing inconsistent tenant-local lifecycle behavior.
7. SCIM POST could attach an already-existing platform identity to another tenant and could demote an existing same-tenant owner/admin to member.
8. SCIM could deactivate privileged owner/admin memberships.
9. Multiple pending invitation tokens for the same organization/email could coexist; an older token could later replay an obsolete role.
10. Invitation acceptance could overwrite/demote an existing privileged organization membership and did not explicitly select the accepted organization in the session.
11. Governed impersonation still requires nested-session and service-level actor-authority hardening before APPLY-01 is complete.

### APPLY-01 fixes already implemented

- Added `SsoEnforcementPolicy`:
  - local password login is blocked for active non-Super-Admin members of a tenant with an enabled enforced SSO provider;
  - Super Admin retains an explicit break-glass local-login path;
  - enforcement remains fail-closed if an enforced provider exists but its adapter disappears;
  - login context exposes only enabled providers backed by a registered protocol-compatible adapter.
- `AuthenticatedSessionController` now applies SSO enforcement after credential/account validation, audits `enterprise-sso-required`, logs out and invalidates the local session before returning a generic validation error.
- Login UI now surfaces current-organization SSO options and an unavailable-adapter warning without removing Super Admin recovery access.
- `SsoController` now:
  - requires an active organization;
  - binds one-time state to organization + provider + expiry;
  - rechecks adapter + protocol at start and callback;
  - catches adapter redirect/identity exceptions with generic Core failures;
  - restricts external redirect output to absolute HTTP(S) URLs with a host;
  - validates normalized provider email before account resolution;
  - requires an active account + active organization membership;
  - rotates authenticated session, selects tenant, records last login and emits a minimal enterprise SSO audit event.
- `EnterpriseSsoProvider` recursively rejects secret-like keys from unencrypted public configuration; secret credentials remain in hidden `encrypted:array` `secret_payload`.
- `ScimTokenManager` now rejects inactive organizations on issue, requires the `nxscim_` token prefix, and resolves tokens only when enabled, non-revoked, unexpired and attached to an active organization.
- `ScimController` now treats `active` as tenant-membership state:
  - active+suspended organization members are returned so deactivated resources remain visible as `active=false`;
  - existing platform identities cannot be silently attached to a new tenant via SCIM;
  - existing same-tenant roles are preserved instead of forced to `member`;
  - owner/admin deactivation via SCIM is rejected;
  - new SCIM users keep global account status active while organization membership carries SCIM active/suspended state;
  - PATCH is bounded and only supports boolean `active` add/replace operations;
  - audit stores an email hash / operation count, not raw provisioned email.
- `InvitationManager` now:
  - rejects inactive organizations and invalid invitation roles;
  - supersedes previous pending tokens for the same organization/email before creating a new one;
  - hashes invitation email in audit metadata;
  - accepts only active accounts into active organizations;
  - preserves existing owner/admin roles instead of invitation-driven demotion;
  - supersedes remaining stale pending tokens after acceptance.
- `InvitationController` now selects the accepted organization in session and routes Admin-capable vs portal users appropriately.

### Verification state

These N1.17 writes are **implementation evidence only**. The latest fully green repository run remains N1.16 run `32505428674`. N1.17 is not SOURCE DONE until dedicated acceptance tests/product contract and the full existing gate set pass on the same current head.

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
| 006 | 2026-08-21 | N1.17 implementation head `39f991c396e69f44a83cbf2a354e9e5d6bb75ec7`; CI verification pending | N1.17 APPLY-01 partial closure: actual SSO enforcement + break-glass, callback/adapter trust boundaries, encrypted-secret configuration fence, tenant-local SCIM lifecycle/privilege protection, replay-safe invitations and accepted-tenant session selection | N1.17 source work **10% -> 55% implementation**; Project/Source Power held at verified **76.3% / 98.5%** pending acceptance + full CI; Target remains **50.0%** |

---

## 10. Exact next action

```text
N1.17 APPLY-01 FINISH + APPLY-02 VERIFICATION GATE
  1. harden ImpersonationManager against nested sessions and unauthorized/inactive actors/organizations
  2. re-audit SSO/SCIM/invitation changes for syntax/static compatibility
  3. add EnterpriseIdentityGovernance acceptance tests covering enforced password denial, Super Admin break-glass, SSO state/provider binding, secret config rejection, SCIM tenant/privilege semantics, invitation replay/demotion safety and nested impersonation
  4. add SSO / Enterprise Governance product source contract
  5. wire contract into development-readiness + GitHub Actions
  6. update THIS FILE immediately after apply
  7. require all prior gates + new Enterprise Governance contract + Unified Source Certification GREEN
  8. only then mark N1.17 SOURCE DONE and recalculate evidence-based Power
```
