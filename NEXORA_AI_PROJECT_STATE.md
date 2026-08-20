# Nexora AI Project State & Execution Ledger

> **AI START HERE**
> This is the canonical cross-chat handoff file for the Nexora project. Any AI/agent working on Nexora must read this file before planning, modifying, auditing, packaging, or certifying the project. Update this file at the end of every meaningful implementation/audit/release pass.
>
> This file is intentionally operational documentation and is **not part of the immutable source-attestation roots**. Updating it must not create deployment/runtime drift.

---

## 0. Ledger metadata

- Ledger schema: `1`
- Ledger revision: `1.1`
- Project: `Nexora`
- Product class: advanced extensible web platform / CMS / site builder / application ecosystem
- Current development source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Ledger last updated: `2026-08-21`
- Current target environment: Windows + Laragon (real-target development test environment)
- Current target path used in live testing: `D:\laragon\www\nexora`
- Final product target: portable/self-hostable and deployable beyond Laragon; Laragon is a test target, not an architectural dependency.

---

## 1. Product definition — what Nexora is

Nexora is being built as a modern, extensible platform intended to combine and advance the strongest ideas from WordPress, Webflow, Wix, Shopify and enterprise SaaS platforms without inheriting their architectural limitations.

The intended user experience is:

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

### Core architectural principle

Themes and extensions must operate through Nexora's public contracts, capabilities and security/trust boundaries. They must not arbitrarily replace or bypass core security, tenancy, admin, publishing semantics, deployment/runtime rules or platform contracts.

### Product-family principle

Vertical products such as Books, CV/Profile, LMS, Booking, Projects and future verticals should be independent modules/apps/extensions, not hard-coded permanently into Nexora Core.

---

## 2. Non-negotiable architecture rules

Any AI/agent must preserve these rules unless the user explicitly changes the architecture:

1. **Core remains stable and generic.** Product-specific features should be modules/extensions where practical.
2. **Themes are presentation systems, not unrestricted core overrides.**
3. **Plugins/apps use contracts + capabilities + lifecycle controls.**
4. **Admin UI must use the shared Nexora UI/design-system components.** Avoid ad-hoc raw controls where a library component exists.
5. **Security boundaries are fail-closed.** Do not solve development blockers by silently disabling runtime, tenancy, auth, source-integrity or update-security controls.
6. **Runtime certification is not the product.** Runtime hardening must support the platform, not permanently block product development.
7. **Source-complete is not the same as runtime-verified.** Always report both separately.
8. **No cross-project files/code.** Nexora packages must contain only Nexora materials.
9. **No stale generated/runtime/certification state in clean source packages.**
10. **Migrations must be portable and fresh-install safe.**
11. **Real target verification is required before marking runtime/product behavior complete.**
12. **Final dependency/release certification is intentionally a late phase, after application usability is proven.**
13. **Documentation/history updates must not mutate deployed source identity.** This ledger is deliberately outside source-attestation roots.

---

## 3. Platform architecture map

| Layer / subsystem | Purpose | Current state |
|---|---|---|
| Kernel / Core | Boot, lifecycle, shared platform primitives | Foundation implemented |
| Public Contracts | Stable APIs for modules/plugins/themes | Foundation implemented |
| Module Registry | Discover/register/activate modules | Foundation implemented |
| Capability Runtime | Controlled extension permissions/capabilities | Foundation implemented |
| Admin Platform / Design System | Shared UI, navigation, forms, selects, themes, tooltips | Strong foundation; application-wide functional UX QA still needed |
| Document Engine | Structured page/content documents | Foundation implemented |
| SEO Core | Metadata, canonical rules, schema/publishing semantics | Foundation implemented |
| Theme Engine | Theme packaging/install/activate/render | Foundation implemented; end-to-end product workflow QA pending |
| Plugin / Extension Engine | Package lifecycle, capabilities, migrations, activation | Foundation implemented; product workflow QA pending |
| Studio | Visual page/site builder | Foundation exists; major product-grade UX/features still ahead |
| Forge / SDK | Developer extension tooling | Foundation/planned expansion |
| Sentinel | Theme/plugin trust and security validation | Foundation implemented; 2.0 hardening later |
| Marketplace | Themes/apps distribution | Foundation implemented/planned expansion |
| Commerce | Commerce primitives and services | Foundation exists; Commerce 2.0 later |
| CRM / Membership / Helpdesk | Business/customer capabilities | Foundation/roadmap |
| Multisite / Organizations / SSO | Enterprise tenancy/governance | Major upcoming block |
| Cloud / HA runtime | Distributed workers/storage/deployments | Later roadmap |
| Installer / Deployment / Recovery | Zero-state install, upgrade, backup/recovery, runtime handoff | Current stabilization focus |

---

## 4. Completion semantics used in this project

Every status must use one of these meanings:

- `SOURCE DONE` — code/static contract exists and passes source/static checks.
- `TARGET VERIFIED` — behavior was executed successfully on the real target environment.
- `PARTIAL` — meaningful implementation exists but end-to-end closure is missing.
- `BLOCKED` — a known blocker prevents the next gate.
- `PLANNED` — not yet implemented.
- `DEFERRED CERTIFICATION` — intentionally postponed until product/runtime usability is closed.

Never report a module as simply “100% complete” when only source/static verification exists.

---

## 5. Current development checkpoint

### 5.1 Current development source

- Source release: `1.0.0-rc.94`
- Protocol: `v5.29`
- Generation: `n1-v5.29`
- Package intent: **Post-Install Runtime Stabilization Closure**
- rc.94 changes the installation architecture so final installed runtime fingerprints are finalized in a **fresh HTTP runtime-handoff request**, after committed `.env`, database-backed session/cache behavior and installed deployment mode are loaded.

### 5.2 Current live Laragon installation

The currently installed live target was created from **rc.93** before the rc.94 stabilization architecture existed.

Latest live compatibility result:

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

Interpretation: the live rc.93 install is not showing source/DB corruption or upgrade drift. The four post-install fingerprints were sealed before the final installed runtime context stabilized.

A fail-closed external repair pack has been prepared for the current rc.93 installation. It must verify immutable identity planes before modifying only the four permitted stale fingerprints and must roll back if convergence fails.

### 5.3 Current dependency review state

Runtime dependencies match the lock state, but formal reviewed-lock attestation is still missing. This is **not the current runtime usability blocker**. It remains a final N1.0/C1-C6 certification requirement.

---

## 6. Work completed so far

### Installer / bootstrap / development closure

- Composer availability/bootstrap handling implemented.
- Windows npm execution bridge implemented (`npm.cmd` / `node + npm-cli.js` path).
- npm bundled/inBundle integrity handling implemented for Tailwind WASM nested packages.
- Development-first workflow adopted; audit/certification moved later.
- Simple installer flow implemented:
  1. Requirements
  2. Database
  3. Application + Super Admin
  4. Review + Install
- Installer progress percentage/stages/log output implemented.
- Installer cancellation endpoint and UX implemented/improved.
- Existing DB detection implemented.
- Backup path + explicit no-backup destructive consent implemented.
- Password-strength workflow implemented.
- First installer-created admin is created as email-verified.
- Installer theme support: System/Light/Dark.
- Sidebar collapse/tooltips and admin appearance foundation implemented.
- Shared select/UI direction enforced.
- Primary SQL database support includes MySQL/MariaDB/PostgreSQL/SQLite/SQL Server plus SQL-compatible AWS variants.
- MongoDB/Atlas/DocumentDB, Redis/ElastiCache and DynamoDB are treated as auxiliary services rather than incorrectly forced into the relational primary DB model.
- Auxiliary service credentials/test/enable workflow foundation implemented.
- Pre-install web pipeline isolated from installed-runtime/tenant/auth DB assumptions.
- Installer-specific error page/request diagnostics implemented.
- Installer Blade component resolution guard added.
- Browser-vs-CLI deployment identity convergence fix implemented.
- Post-install fresh-request runtime handoff architecture implemented in rc.94.
- Historical TypeScript blocker ledger remediated at source level (`76/76`).
- Real target build was reported clean in the rc.89+ sequence.

### Runtime identity areas already observed matching on live rc.93

- platform version
- source generation
- deployment/source tree
- frontend manifest
- database data plane
- storage plane
- host plane
- resource plane
- policy plane
- Laravel framework
- runtime dependency fingerprint

---

## 7. Current progress dashboard

The two percentages below must be kept separate.

### Platform implementation

```text
█████████████████░░░  ~85%
```

Meaning: a large architectural/source foundation exists, but major product-grade workflows and later modules are still incomplete.

### Real functional verification

```text
██████████░░░░░░░░░░  ~50%
```

Meaning: installer/build/runtime have advanced significantly, but live post-install convergence, login/admin/core workflows, theme/plugin real workflows, Studio and later product modules still require end-to-end verification.

### Phase status

| Phase | Progress | Status |
|---|---:|---|
| DEV-0 Package/bootstrap | 90% | PARTIAL — final dependency certification deferred |
| DEV-1 Installer | 100% source | SOURCE DONE — rc.94 fresh-request handoff; clean live confirmation still required |
| DEV-2A Historical TypeScript remediation | 100% | SOURCE DONE |
| DEV-2B TypeScript/Vite target build | 100% reported | TARGET VERIFIED for the reported Laragon run |
| DEV-3 Laravel/install runtime | 75% | PARTIAL — live rc.93 needs safe post-install convergence confirmation |
| DEV-4 Login/admin/core functional QA | 30% | PARTIAL — next major product-facing phase |
| DEV-5 DB/services portability | 60% | PARTIAL |
| DEV-6 Final C1-C6/release certification | 10% | DEFERRED CERTIFICATION |

---

## 8. NEXT ACTION — exact execution order

### Immediate current-live target gate

Do **not** overwrite the already-installed rc.93 application with rc.94 merely to repair the four fingerprints. That would introduce a real source/version change and confuse recovery with upgrade.

1. Run the prepared **rc.93 Post-Install Identity Repair Pack** externally against `D:\laragon\www\nexora`.
2. Verify:

```bat
php artisan nexora:runtime:compatibility-status --deep
```

Expected target state:

```text
status: pass
mismatches: []
compatible: true
mode: installed-data-plane
```

3. Verify post-install readiness:

```bat
php artisan nexora:runtime:post-install-status --assert-ready
```

4. If both pass, open and test:

```text
http://nexora/login
```

5. Proceed directly to **DEV-4 Live Functional QA** instead of returning to deep certification work.

### DEV-4 first live QA batch

Test and fix in this order:

1. Super Admin login/logout/session persistence.
2. Direct URL navigation/route refresh (not only SPA clicks).
3. Admin shell boot without runtime/tenant errors.
4. Sidebar collapse + tooltips + responsive behavior.
5. Light/Dark/System appearance persistence.
6. Users/profile/password reset/self-service auth behavior required by the platform.
7. Roles/permissions/capabilities and organization/tenant boundaries.
8. Settings (site title/logo/colors/timezone/language/currency where applicable).
9. Media library upload/select/use flow.
10. Core CRUD/screens and error handling.
11. Theme upload -> validate -> install -> preview -> activate.
12. Plugin upload -> validate -> capabilities -> install -> activate/deactivate/uninstall.
13. Studio entry and first real page/document editing workflow.

Only after the above is genuinely usable should the roadmap move deeper into advanced product modules and final certification.

---

## 9. Product roadmap after runtime closure

The roadmap is normalized into product-oriented phases. Historical package numbering may differ; preserve history, but use this ordering for forward work.

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

Every AI/agent continuing this project must follow this sequence:

### Before work

1. Read this entire file.
2. Inspect the current package/source before assuming a previous claim is still true.
3. Identify:
   - current development source version
   - current installed target version
   - current blocker
   - whether the blocker is source, build, runtime, data, UX or certification
4. Do not repeat already-completed work unless regression evidence exists.
5. Prefer the next roadmap gate over unrelated hardening.

### During work

1. Make the smallest architecture-correct change that solves the root cause.
2. Add regression protection for repeated blocker classes.
3. Preserve source/runtime trust boundaries.
4. Distinguish development convenience from release security.
5. Do not mark real-target behavior PASS based only on static/source checks.

### After work

Update this file before packaging/final response:

- Ledger metadata/version if release changed.
- Current development checkpoint.
- Current live-target state.
- Completed work.
- Progress dashboard.
- `NEXT ACTION` section.
- Append one new history entry under **Change History**.

Never delete historical entries. Corrections should be appended with a note explaining what previous assumption changed.

---

## 11. Required history-entry format

Append entries in this format:

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

If no release was produced, use `No release` instead of an rc number.

---

## 12. Change History

### 2026-08-21 — No release — Canonical AI project-state ledger introduced

- Trigger / observed blocker: project knowledge, completion claims, roadmap and live-target state were spread across long chat history and release notes, creating a high risk that a new AI/session would repeat work or confuse source completion with real verification.
- Root cause: there was no single project-local, AI-readable, append-only operational handoff file.
- Changes applied: added `NEXORA_AI_PROJECT_STATE.md` at project root with product definition, architecture invariants, completion semantics, current source/live state, progress dashboard, exact next action, normalized roadmap, AI execution protocol and append-only history.
- Verification completed: source-attestation SHA remained unchanged before and after adding/updating this ledger, proving that operational documentation updates do not create deployment identity drift.
- Real-target evidence: no target mutation was required for this documentation-only change.
- Remaining blocker: live rc.93 still requires post-install identity convergence verification.
- Next exact action: run the prepared rc.93 repair, verify runtime/post-install PASS, then begin DEV-4 Super Admin and core functional QA.

### 2026-08-21 — rc.94 / v5.29 — Post-install runtime stabilization architecture

- Trigger / observed blocker: live rc.93 installation reported runtime incompatibility only for `environment`, `activation`, `service` and `process`, while version/generation/source/database/storage/host/resources/policy/framework/dependencies all matched.
- Root cause: final runtime fingerprints could be persisted inside the long-running installer request before the newly committed `.env`, installed cache/session context and final deployment mode were loaded by a fresh PHP request.
- Changes applied: rc.94 moved final runtime fingerprint reconciliation/sealing to a fresh `/install/runtime-handoff` HTTP request after installation commit; immutable identity planes remain fail-closed. A separate external rc.93 repair pack was prepared for the already-installed live target.
- Verification completed: source/static installer/runtime contracts reported PASS for rc.94; package remained zero-state.
- Real-target evidence: live rc.93 showed source/deployment/database and other immutable planes matching, with only the four post-install planes stale.
- Remaining blocker: run safe rc.93 repair and prove `compatibility-status --deep` + `post-install-status --assert-ready` PASS.
- Next exact action: repair live rc.93, verify convergence, then start DEV-4 Super Admin/login/core functional QA.

### 2026-08-21 — rc.93 / v5.28 — Browser/CLI deployment identity convergence

- Trigger / observed blocker: browser installer reported deployed source-tree mismatch while CLI `nexora:runtime:install-readiness --json` reported 8/8 PASS.
- Root cause: browser request could hold a memoized `source-fallback` deployment identity while CLI always ran in a fresh PHP process.
- Changes applied: pre-install readiness refreshes deployment identity and performs controlled source-fallback re-attestation before failing; persisted production/admitted/installed mismatches remain hard failures.
- Verification completed: installer/runtime source contracts passed.
- Real-target evidence: CLI readiness showed source/deployment PASS.
- Remaining blocker: post-install runtime stabilization discovered after installation commit.
- Next exact action: stabilize post-install identity planes.

### 2026-08-21 — rc.92 / v5.27 — Installer Blade component closure

- Trigger / observed blocker: `/install` failed because Blade component `lucide-circle` did not exist.
- Root cause: invalid per-icon Blade alias usage.
- Changes applied: migrated to the shared Lucide component pattern and added installer component-resolution regression checks.
- Verification completed: unresolved installer Blade component count reduced to zero in source verification.
- Real-target evidence: installer progressed beyond the prior generic/middleware failures to the exact Blade render blocker.
- Remaining blocker: deployment identity mismatch during installation readiness.
- Next exact action: fix browser/CLI deployment identity convergence.

### 2026-08-21 — rc.91 / v5.26 — Installer bootstrap isolation closure

- Trigger / observed blocker: after Composer/build, `/install` returned HTTP 500.
- Root cause: pre-install web requests still traversed middleware that could touch tenant/auth/DB/runtime state before DB installation was complete.
- Changes applied: installer routes isolated from runtime/tenant/Inertia assumptions; pre-install DB/auth touches reduced; installer-specific error surface added.
- Verification completed: bootstrap isolation/static contracts passed.
- Real-target evidence: subsequent browser request reached installer Blade rendering and exposed the next exact component error.
- Remaining blocker: invalid Blade icon component.
- Next exact action: close Blade component resolution.

### 2026-08-21 — rc.90 / v5.25 — Runtime bootstrap fence closure

- Trigger / observed blocker: CLI runtime compatibility reported PASS in bootstrap mode, while browser reported runtime readiness could not be verified.
- Root cause: global runtime heartbeat/readiness enforcement executed before Nexora was installed.
- Changes applied: pre-install/bootstrap requests bypass installed-runtime heartbeat/readiness fencing; installed runtime still enforces the fences.
- Verification completed: runtime bootstrap source contracts passed.
- Real-target evidence: CLI bootstrap compatibility was PASS.
- Remaining blocker: broader pre-install middleware isolation.
- Next exact action: isolate entire installer web pipeline.

### 2026-08-21 — rc.89 / v5.24 — Development Closure Batch A

- Trigger / observed blocker: project had become certification-heavy before basic product/runtime usability was closed.
- Root cause: development and final audit workflows were mixed.
- Changes applied: development-first plan; installer UX improvements; auxiliary service workflow foundation; package hygiene; dependency bootstrap behavior; theme/select/cancel/429 improvements.
- Verification completed: source/static contracts and PHP lint passed; real build was delegated to Laragon target.
- Real-target evidence: user later reported no build error and runtime bootstrap compatibility PASS.
- Remaining blocker: installer/browser runtime errors.
- Next exact action: iterate on real installer blockers until installation completes.

---

## 13. Known deferred work / do not confuse with current blocker

The following are known but intentionally not the immediate blocker:

- formal reviewed-lock attestation
- C1-C6 final certification
- release signing/provenance finalization
- broad cross-platform matrix
- full cross-database live matrix
- HA/distributed runtime
- final performance/accessibility certification
- complete marketplace 2.0 / Sentinel 2.0 / Commerce 2.0

These must not pull work away from the current gate: **live runtime convergence -> login -> core product functional QA -> theme/plugin/Studio product closure**.

---

## 14. AI quick resume card

```text
PROJECT: Nexora
GOAL: Advanced extensible WordPress/Webflow/Wix/Shopify-class platform ecosystem
DEV SOURCE: rc.94 / v5.29 / n1-v5.29
LIVE TARGET: rc.93 installed on Laragon
LIVE BLOCKER: post-install environment/activation/service/process fingerprints stale
SOURCE/DEPLOYMENT/DB: matching
DEPENDENCY RUNTIME: matching
FINAL LOCK REVIEW: missing, deferred to certification
NEXT: run rc.93 safe repair -> compatibility PASS -> post-install PASS -> /login -> DEV-4 QA
DO NOT: overwrite installed rc.93 with rc.94 as a repair; do not return to C1-C6 before usability closure
```

