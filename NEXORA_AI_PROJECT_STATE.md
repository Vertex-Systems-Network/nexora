# Nexora AI Project State & Execution Ledger

> **AI START HERE**
> This is the canonical cross-chat handoff file for Nexora. Any AI/agent working on Nexora must read this file before planning, modifying, auditing, packaging, or certifying the project, and must update it after every meaningful implementation/audit/release pass.
>
> This is operational documentation and is intentionally outside the immutable source-attestation roots so history/status updates do not create deployment/runtime drift.

---

## 0. Ledger metadata

- Ledger schema: `1`
- Ledger revision: `1.3`
- Project: `Nexora`
- Product class: advanced extensible web platform / CMS / site builder / application ecosystem
- Current development source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Ledger last updated: `2026-08-21`
- GitHub canonical repository: `Vertex-Systems-Network/nexora`
- GitHub default branch: `main`
- Active development branch: `dev/n1-0b-core-functional-qa`
- Active GitHub pull request: `#1` — DEV-4: establish GitHub-first functional QA gates
- Current target environment: Windows + Laragon (real-target development test environment)
- Current target path: `D:\laragon\www\nexora`
- Final target: portable/self-hostable beyond Laragon; Laragon is a real development target, not an architectural dependency.

---

## 1. Product definition — what Nexora is

Nexora is a modern extensible platform intended to combine and advance the strongest ideas from WordPress, Webflow, Wix, Shopify and enterprise SaaS systems without inheriting their architectural limitations.

Intended user experience:

```text
Install Nexora
  -> create/manage site or workspace
  -> upload/install/activate themes
  -> upload/install/activate plugins/apps
  -> build pages visually in Studio
  -> manage CMS/content/media/SEO/forms/users
  -> enable optional modules such as commerce, LMS, booking, CRM, membership, etc.
  -> publish, operate, update and scale
```

### Core architecture principle

Themes/extensions operate through Nexora public contracts, capabilities and security/trust boundaries. They must not bypass core security, tenancy, admin, publishing semantics, deployment/runtime rules or platform contracts.

### Product-family principle

Vertical products such as Books, CV/Profile, LMS, Booking, Projects and future verticals should be independent modules/apps/extensions rather than permanently hard-coded into Core.

---

## 2. Non-negotiable architecture rules

1. **Core remains stable and generic.** Product-specific features should be modules/extensions where practical.
2. **Themes are presentation systems, not unrestricted core overrides.**
3. **Plugins/apps use contracts + capabilities + lifecycle controls.**
4. **Admin UI uses shared Nexora UI/design-system components.** Avoid ad-hoc raw controls where a library component exists.
5. **Security is fail-closed.** Never solve blockers by silently disabling runtime, tenancy, auth, source-integrity or update-security boundaries.
6. **Runtime certification is not the product.** It supports the platform and must not permanently block product development.
7. **SOURCE DONE != TARGET VERIFIED.** Always report them separately.
8. **No cross-project files/code.**
9. **No stale generated/runtime/certification state in clean source packages.**
10. **Migrations are portable and fresh-install safe.**
11. **Real target evidence is required before marking runtime/product behavior complete.**
12. **Final dependency/release certification remains late, after usability is proven.**
13. **Ledger/history updates must not mutate deployed source identity.**
14. **GitHub is now the canonical source-control workflow.** Meaningful source changes go to a development branch/PR; do not push unverified runtime changes directly to `main`.

---

## 3. Platform architecture map

| Layer / subsystem | Purpose | Current state |
|---|---|---|
| Kernel / Core | Boot, lifecycle, shared primitives | Foundation implemented |
| Public Contracts | Stable APIs for modules/plugins/themes | Foundation implemented |
| Module Registry | Discover/register/activate modules | Foundation implemented |
| Capability Runtime | Controlled extension permissions | Foundation implemented |
| Admin Platform / Design System | Shared UI/navigation/forms/selects/themes/tooltips | Strong foundation; app-wide live QA pending |
| Document Engine | Structured page/content documents | Foundation implemented |
| SEO Core | Metadata/canonical/schema/publishing semantics | Foundation implemented |
| Theme Engine | Package/install/activate/render | Foundation; real workflow QA pending |
| Plugin / Extension Engine | Lifecycle/capabilities/migrations | Foundation; real workflow QA pending |
| Studio | Visual page/site builder | Foundation; major product UX/features ahead |
| Forge / SDK | Developer extension tooling | Foundation/planned expansion |
| Sentinel | Theme/plugin trust/security | Foundation; 2.0 later |
| Marketplace | Themes/apps distribution | Foundation/planned expansion |
| Commerce | Commerce primitives/services | Foundation; Commerce 2.0 later |
| CRM / Membership / Helpdesk | Business/customer capabilities | Foundation/roadmap |
| Multisite / Organizations / SSO | Enterprise tenancy/governance | Major upcoming block |
| Cloud / HA Runtime | Distributed workers/storage/deployments | Later roadmap |
| Installer / Deployment / Recovery | Zero-state install/update/recovery/runtime handoff | Stabilization nearly closed |

---

## 4. Completion semantics

- `SOURCE DONE` — code/static contract exists and source/static checks pass.
- `TARGET VERIFIED` — behavior executed successfully on the real target.
- `PARTIAL` — meaningful implementation exists but end-to-end closure is missing.
- `BLOCKED` — known blocker prevents next gate.
- `PLANNED` — not yet implemented.
- `DEFERRED CERTIFICATION` — intentionally postponed until product/runtime usability closes.

Never report a feature as simply “100% complete” when only source/static verification exists.

---

## 5. Current development checkpoint

### 5.1 Current development source

- Source release: `1.0.0-rc.94`
- Protocol: `v5.29`
- Generation: `n1-v5.29`
- Source/package intent: **Post-Install Runtime Stabilization Closure**
- rc.94 finalizes installed runtime fingerprints in a **fresh HTTP runtime-handoff request** after committed `.env`, DB-backed session/cache behavior and installed deployment mode load.

### 5.2 GitHub state

- `main` currently starts from commit `f555fe396cda0e82efd4445ba016f709de3398c8` (`init`) containing the full rc.94 tree.
- `NEXORA_AI_PROJECT_STATE.md` was present on `main` and matched the rc.94 ledger when GitHub canonicalization began.
- Active source-work branch: `dev/n1-0b-core-functional-qa`.
- Draft PR: `#1` to `main`.
- `composer.lock` and `package-lock.json` are not committed; deterministic dependency certification remains pending/deferred.

### 5.3 Current live Laragon installation

The live target was installed from **rc.93** before rc.94 fresh-request runtime stabilization existed.

Latest live compatibility evidence:

```text
Version                     MATCH
Generation                  MATCH
Deployment/source           PASS
Database data plane         MATCH
Storage                     MATCH
Host                        MATCH
Resources                   MATCH
Policy                      MATCH
Laravel framework           MATCH
Runtime dependencies        MATCH

Mismatches:
- environment
- activation
- service
- process
```

Interpretation: source/DB/upgrade drift was not observed. Four post-install fingerprints were sealed before final installed context stabilized. A fail-closed external rc.93 repair pack exists and must verify immutable planes before touching only those four permitted stale fingerprints.

### 5.4 Dependency review state

Runtime dependencies match current locks on the live target, but formal reviewed-lock attestation is missing. This is **not the current usability blocker** and remains final N1.0/C1-C6 certification work.

---

## 6. Work completed so far

### Installer / bootstrap / runtime stabilization

- Composer availability/bootstrap handling.
- Windows npm execution bridge (`npm.cmd` / `node + npm-cli.js`).
- npm bundled/inBundle integrity handling for Tailwind WASM nested packages.
- Development-first workflow; final audit moved later.
- Installer flow: Requirements -> Database -> Application + Super Admin -> Review + Install.
- Progress %, stages and log output.
- Cancellation endpoint/UX.
- Existing DB detection.
- Backup path + explicit no-backup destructive consent.
- Password-strength flow.
- Installer-created first admin is email-verified.
- Installer System/Light/Dark appearance.
- Shared-select/UI direction.
- SQL primary DB support: MySQL/MariaDB/PostgreSQL/SQLite/SQL Server + SQL-compatible AWS variants.
- MongoDB/Atlas/DocumentDB, Redis/ElastiCache and DynamoDB modeled as auxiliary services rather than incorrectly forced into the relational primary DB model.
- Auxiliary service credentials/test/enable foundation.
- Pre-install web pipeline isolation from installed runtime/tenant/auth DB assumptions.
- Installer-specific error diagnostics.
- Blade component resolution guard.
- Browser-vs-CLI deployment identity convergence.
- rc.94 fresh-request post-install runtime handoff.
- Historical TypeScript blockers remediated at source level (`76/76`).
- Real Laragon frontend build previously reported clean.

### Runtime planes already observed matching on live rc.93

- platform version
- source generation
- deployment/source tree
- frontend manifest
- database data plane
- storage
- host
- resources
- policy
- Laravel framework
- runtime dependency fingerprint

### GitHub-first DEV-4 source workflow

- Root `AGENTS.md` requires agents to read/update this ledger and preserve source-vs-target semantics.
- Added `scripts/dev4-core-functional-contract-verify.php`.
- DEV-4 source gate covers auth/admin/core routes, required controller methods/pages, auth safety markers, raw interactive HTML controls and shared UI exports.
- DEV-4 gate is wired into `scripts/development-readiness.php` and GitHub Actions.
- Source-only GitHub workflow no longer requests npm cache from absent `package-lock.json`.
- Draft PR `#1` opened for this batch.
- Local branch verification: certification preflight PASS, Source Guard PASS, DEV-4 Core Functional Contract PASS, unified source certification PASS.
- Current branch source attestation: `1460` files, SHA-256 `532abda62c1bf7aee06be92c8b8e63a3f27e8fec4aae262d48b0f9a1c05ad00c`.

---

## 7. Current progress dashboard

### Platform implementation

```text
█████████████████░░░  ~85%
```

### Real functional verification

```text
██████████░░░░░░░░░░  ~50%
```

| Phase | Progress | Status |
|---|---:|---|
| DEV-0 Package/bootstrap | 90% | PARTIAL — final dependency certification deferred |
| DEV-1 Installer | 100% source | SOURCE DONE — clean live rc.94 confirmation still required |
| DEV-2A Historical TypeScript remediation | 100% | SOURCE DONE |
| DEV-2B TypeScript/Vite target build | 100% reported | TARGET VERIFIED for reported Laragon run |
| DEV-3 Laravel/install runtime | 75% | PARTIAL — live rc.93 needs safe convergence confirmation |
| DEV-4 Login/admin/core functional QA | 35% source / 30% live | PARTIAL — GitHub source gate now active; live QA next |
| DEV-5 DB/services portability | 60% | PARTIAL |
| DEV-6 Final C1-C6/release certification | 10% | DEFERRED CERTIFICATION |

---

## 8. NEXT ACTION — exact execution order

### Immediate live-target gate

Do **not** overwrite installed rc.93 with rc.94 merely to repair four fingerprints.

1. Run prepared **rc.93 Post-Install Identity Repair Pack** externally against `D:\laragon\www\nexora`.
2. Verify:

```bat
php artisan nexora:runtime:compatibility-status --deep
```

Expected:

```text
status: pass
mismatches: []
compatible: true
mode: installed-data-plane
```

3. Verify:

```bat
php artisan nexora:runtime:post-install-status --assert-ready
```

4. If both pass, test:

```text
http://nexora/login
http://nexora/admin
```

5. Continue DEV-4 live functional QA; do not return to final C1-C6 yet.

### DEV-4 first live/source batch

1. Super Admin login/logout/session persistence.
2. Direct URL navigation/refresh.
3. Admin shell without runtime/tenant errors.
4. Sidebar collapse/tooltips/responsive behavior.
5. Light/Dark/System persistence.
6. Users/profile/password reset/self-service auth.
7. Roles/permissions/capabilities + tenant boundaries.
8. Settings: site title/logo/colors/timezone/default language; commerce currency remains in commerce where appropriate.
9. Media upload/select/use.
10. Core CRUD/errors.
11. Theme upload -> validate -> install -> preview -> activate.
12. Plugin upload -> validate -> capabilities -> install -> activate/deactivate/uninstall.
13. Studio entry + first real page/document edit flow.

### Current source gap discovered for next DEV-4 code batch

Global Settings currently covers app name + appearance theme/primary/density/radius, but platform-level **logo, default timezone and default language** are not yet complete end-to-end across Settings UI, shared Inertia props and request runtime behavior. This is the next source implementation target after the live runtime gate is proven or in parallel on the development branch.

---

## 9. Product roadmap after runtime closure

```text
N1.0A  Installation + Runtime Closure
N1.0B  Super Admin + Core Application QA
N1.1   Admin Design System / UX Closure
N1.2   Theme Engine Product Closure
N1.3   Plugin / Extension Product Closure
N1.4   Studio / Visual Builder
N1.5   CMS / Documents / Collections
N1.6   Media / DAM
N1.7   SEO / Publishing
N1.8   Forms / Data / Workflows
N1.9   Marketplace
N1.10  Commerce 2.0
N1.11  CRM / Membership / Customer Portal
N1.12  Search 2.0
N1.13  Collaboration
N1.14  Automation
N1.15  AI Platform Capabilities
N1.16  Multisite / Organizations
N1.17  SSO / Enterprise Governance
N1.18  Public APIs / Webhooks / SDK
N1.19  Import / Export / WordPress migrations
N1.20  Observability
N1.21  Developer Experience / Forge
N1.22  Sentinel 2.0
N1.23  Marketplace 2.0
N1.24  Cloud / HA / Distributed Runtime
N1.25  Backup / DR / Upgrade Certification
N1.26  Performance + Accessibility + Release
N2.0   Stable Production
```

---

## 10. AI execution protocol

### Before work

1. Read this entire file.
2. Inspect current GitHub branch/source before trusting old claims.
3. Identify dev source version, installed target version, blocker class, and current PR/branch.
4. Do not repeat completed work without regression evidence.
5. Prefer the next roadmap gate over unrelated hardening.

### During work

1. Make the smallest architecture-correct root-cause fix.
2. Add regression protection for repeated blocker classes.
3. Preserve trust boundaries.
4. Distinguish development convenience from release security.
5. Never mark target PASS from static checks alone.
6. Use development branch + PR for meaningful source changes.

### After work

Update this file before final response/merge/package:

- metadata/branch/PR/release if changed
- current checkpoint and live target state
- completed work
- progress dashboard
- `NEXT ACTION`
- append one history entry

Never delete prior history entries. Corrections are appended and explain what changed.

---

## 11. Required history-entry format

```markdown
### YYYY-MM-DD — rc.xx / vx.xx — Short title

- Trigger / observed blocker:
- Root cause:
- Changes applied:
- Verification completed:
- Real-target evidence:
- Remaining blocker:
- Next exact action:
```

Use `No release` when no rc release was produced.

---

## 12. Change History

### 2026-08-21 — No release — Canonical AI project-state ledger introduced

- Trigger / observed blocker: project knowledge, completion claims, roadmap and live state were spread across long chat/release history.
- Root cause: no single project-local append-only AI handoff source.
- Changes applied: added `NEXORA_AI_PROJECT_STATE.md` with product definition, architecture rules, completion semantics, current source/live state, roadmap, next action, AI protocol and history.
- Verification completed: ledger updates were proven outside source-attestation roots.
- Real-target evidence: documentation-only change; no target mutation.
- Remaining blocker: live rc.93 post-install convergence.
- Next exact action: repair/verify runtime, then DEV-4.

### 2026-08-21 — rc.94 / v5.29 — Post-install runtime stabilization architecture

- Trigger / observed blocker: live rc.93 failed only `environment`, `activation`, `service`, `process` while immutable/core runtime planes matched.
- Root cause: final runtime fingerprints could be sealed in the long-running installer request before final `.env`/cache/session/deployment context loaded.
- Changes applied: rc.94 moved final reconciliation/sealing to a fresh `/install/runtime-handoff` request; immutable planes remain fail-closed; separate safe rc.93 repair pack prepared.
- Verification completed: rc.94 installer/runtime source contracts PASS; package zero-state.
- Real-target evidence: rc.93 immutable/runtime core planes matched; four post-install planes stale.
- Remaining blocker: safe rc.93 repair + compatibility/post-install PASS.
- Next exact action: repair live rc.93, then login/admin QA.

### 2026-08-21 — rc.93 / v5.28 — Browser/CLI deployment identity convergence

- Trigger / observed blocker: browser reported source mismatch while CLI install-readiness was 8/8 PASS.
- Root cause: memoized browser `source-fallback` identity vs fresh CLI process.
- Changes applied: refresh deployment identity + controlled source-fallback re-attestation; persisted identities still hard-fail on mismatch.
- Verification completed: installer/runtime source contracts PASS.
- Real-target evidence: CLI source/deployment PASS.
- Remaining blocker: post-install stabilization.
- Next exact action: stabilize post-install planes.

### 2026-08-21 — rc.92 / v5.27 — Installer Blade component closure

- Trigger / observed blocker: `/install` failed on missing `lucide-circle` component.
- Root cause: invalid per-icon Blade alias.
- Changes applied: shared Lucide component pattern + component-resolution regression check.
- Verification completed: unresolved installer Blade components reduced to zero.
- Real-target evidence: installer progressed to next exact blocker.
- Remaining blocker: deployment identity readiness.
- Next exact action: browser/CLI identity convergence.

### 2026-08-21 — rc.91 / v5.26 — Installer bootstrap isolation closure

- Trigger / observed blocker: `/install` returned HTTP 500 after Composer/build.
- Root cause: pre-install requests still traversed middleware touching tenant/auth/DB/runtime state.
- Changes applied: installer routes isolated; pre-install DB/auth touches reduced; installer-specific error surface added.
- Verification completed: bootstrap isolation/static contracts PASS.
- Real-target evidence: browser reached installer Blade and exposed next error.
- Remaining blocker: invalid Blade icon component.
- Next exact action: Blade component closure.

### 2026-08-21 — rc.90 / v5.25 — Runtime bootstrap fence closure

- Trigger / observed blocker: CLI compatibility PASS in bootstrap mode while browser readiness failed.
- Root cause: global heartbeat/readiness enforcement before installation.
- Changes applied: pre-install/bootstrap bypasses installed-runtime heartbeat fences; installed runtime keeps enforcement.
- Verification completed: bootstrap contracts PASS.
- Real-target evidence: CLI bootstrap compatibility PASS.
- Remaining blocker: broader installer middleware isolation.
- Next exact action: isolate installer web pipeline.

### 2026-08-21 — rc.89 / v5.24 — Development Closure Batch A

- Trigger / observed blocker: certification-heavy workflow before usability closure.
- Root cause: development and final audit mixed.
- Changes applied: development-first plan, installer UX/auxiliary service/package hygiene/dependency bootstrap/theme/select/cancel/429 improvements.
- Verification completed: source/static + PHP lint PASS; real build delegated to Laragon.
- Real-target evidence: user later reported clean build and bootstrap compatibility PASS.
- Remaining blocker: installer/browser runtime errors.
- Next exact action: close real installer blockers.

### 2026-08-21 — No release — GitHub canonical workflow activated

- Trigger / observed blocker: source moved to GitHub; future work needs central push/pull instead of ZIP-only handoffs.
- Root cause: archive-only iteration made branch history/review harder.
- Changes applied: verified `Vertex-Systems-Network/nexora` `main` as rc.94/v5.29; created `dev/n1-0b-core-functional-qa`; added `AGENTS.md`; established GitHub as canonical source workflow.
- Verification completed: repository access includes admin/push; `main` commit `f555fe396cda0e82efd4445ba016f709de3398c8`; repository ledger matched local rc.94 ledger; `AGENTS.md` itself does not affect source attestation.
- Real-target evidence: no Laragon mutation in this synchronization pass.
- Remaining blocker: live rc.93 post-install convergence; DEV-4 cannot be target-verified before it passes.
- Next exact action: continue DEV-4 source audit on development branch while obtaining live convergence/login evidence.

### 2026-08-21 — No release — DEV-4 GitHub source gate and draft PR opened

- Trigger / observed blocker: GitHub workflow needed an enforceable product-facing DEV-4 source gate rather than chat/package claims alone.
- Root cause: auth/admin/core surfaces existed, but no single DEV-4 contract ran both locally and in CI; source-only CI referenced absent `package-lock.json` for npm caching.
- Changes applied: added `scripts/dev4-core-functional-contract-verify.php`; wired it into development readiness and GitHub Actions; removed lockfile-dependent npm cache while lockfiles remain absent; opened draft PR `#1` from `dev/n1-0b-core-functional-qa` to `main`.
- Verification completed: certification preflight PASS; Source Guard PASS; DEV-4 Core Functional Contract PASS; unified source certification PASS; source attestation `1460` files / SHA-256 `532abda62c1bf7aee06be92c8b8e63a3f27e8fec4aae262d48b0f9a1c05ad00c`. `composer.lock`/`package-lock.json` remain absent, so deterministic dependency certification stays deferred.
- Real-target evidence: no new Laragon target execution in this source pass.
- Remaining blocker: live rc.93 convergence still must pass; next source gap is global settings completeness (logo/timezone/default language) plus live admin workflows.
- Next exact action: verify/repair rc.93 -> test `/login` + `/admin`; continue settings/media/theme/plugin/Studio fixes on the development branch; keep PR #1 draft until target evidence is available.

---

## 13. Known deferred work / not the current blocker

- formal reviewed-lock attestation
- C1-C6 final certification
- release signing/provenance finalization
- broad cross-platform matrix
- full cross-database live matrix
- HA/distributed runtime
- final performance/accessibility certification
- Marketplace 2.0 / Sentinel 2.0 / Commerce 2.0

Do not let these pull work away from: **live runtime convergence -> login -> core product functional QA -> theme/plugin/Studio closure**.

---

## 14. AI quick resume card

```text
PROJECT: Nexora
GOAL: Advanced extensible WordPress/Webflow/Wix/Shopify-class platform ecosystem
GITHUB: Vertex-Systems-Network/nexora
DEV SOURCE: rc.94 / v5.29 / n1-v5.29
DEV BRANCH: dev/n1-0b-core-functional-qa
DRAFT PR: #1
LIVE TARGET: rc.93 installed on Laragon
LIVE BLOCKER: post-install environment/activation/service/process fingerprints stale
SOURCE/DEPLOYMENT/DB: matching
DEPENDENCY RUNTIME: matching
LOCK REVIEW: missing, deferred
NEXT LIVE: safe rc.93 repair -> compatibility PASS -> post-install PASS -> /login -> /admin -> DEV-4 QA
NEXT SOURCE: global settings completeness -> media -> theme -> plugin -> Studio flows
DO NOT: overwrite installed rc.93 with rc.94 as repair; do not return to C1-C6 before usability closure
```
