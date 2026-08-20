# Nexora AI Project State & Execution Ledger

> **AI START HERE**
> This is the canonical cross-chat handoff file for Nexora. Any AI/agent working on Nexora must read this file before planning, modifying, auditing, packaging, or certifying the project, and must update it after every meaningful implementation/audit/release pass.
>
> This is operational documentation and is intentionally outside the immutable source-attestation roots so history/status updates do not create deployment/runtime drift.

---

## 0. Ledger metadata

- Ledger schema: `1`
- Ledger revision: `1.5`
- Project: `Nexora`
- Product class: advanced extensible web platform / CMS / site builder / application ecosystem
- Current development source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Ledger last updated: `2026-08-21`
- GitHub canonical repository: `Vertex-Systems-Network/nexora`
- GitHub default branch: `main`
- Active development branch: `dev/n1-0b-core-functional-qa`
- Active GitHub pull request: `#1` — draft, mergeable; keep draft until real-target evidence is available
- Current branch head before this ledger-only commit: `2035528bae27222446138e65bda50ea32feb9e17`
- Latest complete green source-certification run: `32427092798`
- Open GitHub issues at this checkpoint: `#2 Nexora runtime identity mismatch`
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
14. **GitHub is the canonical source-control workflow.** Meaningful source changes go through a development branch/PR; do not push unverified runtime changes directly to `main`.
15. **GitHub issues are an execution input, not a separate backlog.** At the start and end of every meaningful pass, inspect open repository issues. Fix applicable source defects alongside roadmap work, add regression protection, and do not close runtime/environment issues until the required real-target evidence exists.

---

## 3. Platform architecture map

| Layer / subsystem | Purpose | Current state |
|---|---|---|
| Kernel / Core | Boot, lifecycle, shared primitives | Foundation implemented |
| Public Contracts | Stable APIs for modules/plugins/themes | Foundation implemented |
| Module Registry | Discover/register/activate modules | Foundation implemented |
| Capability Runtime | Controlled extension permissions | Foundation implemented |
| Admin Platform / Design System | Shared UI/navigation/forms/selects/themes/tooltips | Strong foundation; app-wide live QA pending |
| Document Engine | Structured page/content documents | Foundation implemented; next product closure block |
| SEO Core | Metadata/canonical/schema/publishing semantics | Foundation implemented |
| Theme Engine | Package/install/activate/render | Product workflow SOURCE DONE; target execution pending |
| Plugin / Extension Engine | Lifecycle/capabilities/migrations | Product workflow SOURCE DONE; target execution pending |
| Studio | Visual page/site builder | First create/edit/publish/public-render workflow SOURCE DONE; target execution pending; advanced builder features remain |
| Media / DAM | Upload/inspect/variants/folders/collections/reuse | Foundation + reusable picker SOURCE DONE; target execution pending |
| Forge / SDK | Developer extension tooling | Foundation/planned expansion |
| Sentinel | Theme/plugin trust/security | Foundation implemented; 2.0 later |
| Marketplace | Themes/apps distribution | Foundation/planned expansion |
| Commerce | Commerce primitives/services | Foundation; Commerce 2.0 later |
| CRM / Membership / Helpdesk | Business/customer capabilities | Foundation/roadmap |
| Multisite / Organizations / SSO | Enterprise tenancy/governance | Major upcoming block |
| Cloud / HA Runtime | Distributed workers/storage/deployments | Later roadmap |
| Installer / Deployment / Recovery | Zero-state install/update/recovery/runtime handoff | rc.94 source stabilization closed; live rc.93 recovery evidence pending |

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

### 5.1 Development source

- Source release: `1.0.0-rc.94`
- Protocol: `v5.29`
- Generation: `n1-v5.29`
- Active branch: `dev/n1-0b-core-functional-qa`
- PR `#1` is draft and mergeable.
- Latest green CI run `32427092798` passed:
  - Certification preflight
  - Source Guard
  - Post-install runtime convergence contract
  - DEV-4 core functional source contract
  - Theme product source contract
  - Extension product source contract
  - Studio product source contract
  - Unified source certification
- `composer.lock` and `package-lock.json` are not committed; deterministic dependency/release certification remains deferred.

### 5.2 Open GitHub issue status

#### Issue #2 — Nexora runtime identity mismatch

Live rc.93 reports only:

```text
environment
activation
service
process
```

as mismatches. Version, generation, deployment/source, database, storage, host, resources, policy, Laravel framework and runtime dependencies match.

Permanent rc.94 source fix is present and CI-guarded through `scripts/post-install-runtime-convergence-contract-verify.php`. The contract guarantees:

- fresh `/install/runtime-handoff` request
- exact reconciliation allow-list limited to `environment`, `activation`, `service`, `process`
- immutable mismatch rejection
- service/process health gates
- one-time post-install identity finalization
- post-write compatibility reassessment

Issue #2 remains **OPEN** because the existing rc.93 Laragon target still needs real recovery verification.

### 5.3 Current live Laragon installation

The live target was installed from **rc.93** before rc.94 fresh-request stabilization existed.

Latest live evidence:

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

Do not overwrite installed rc.93 with rc.94 merely to repair this state.

### 5.4 Dependency review state

Runtime dependencies match the live installed lock state, but formal reviewed-lock attestation is missing. This is not the current usability blocker and remains final N1.0/C1-C6 certification work.

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
- MongoDB/Atlas/DocumentDB, Redis/ElastiCache and DynamoDB modeled as auxiliary services.
- Auxiliary service credentials/test/enable foundation.
- Pre-install web pipeline isolation from installed runtime/tenant/auth DB assumptions.
- Installer-specific error diagnostics.
- Blade component resolution guard.
- Browser-vs-CLI deployment identity convergence.
- rc.94 fresh-request post-install runtime handoff.
- Post-install runtime convergence source regression gate added to development readiness and CI.
- Historical TypeScript blockers remediated at source level (`76/76`).
- Real Laragon frontend build previously reported clean.

### GitHub-first DEV-4 foundation

- Root `AGENTS.md` requires agents to read/update this ledger.
- `scripts/dev4-core-functional-contract-verify.php` guards auth/admin/core routes, controller/page surfaces, shared UI controls, global settings and reusable media-selection contracts.
- GitHub Actions + `scripts/development-readiness.php` run the product-facing source gates.
- PR #1 kept draft until target evidence is available.

### Site identity / regional settings

- Application/site name, logo, default display timezone and default language implemented with existing theme/primary/density/radius settings.
- Logo exposed through shared Inertia props and used by Admin/Auth layouts.
- Installed default locale resolves from Nexora settings when no user/session/cookie override exists.
- Tenant context resolves before locale middleware.
- Display/business timezone remains separate from certified infrastructure/runtime timezone.
- Settings validation tests cover unsafe logo schemes, invalid timezones and unsupported locales.

### Media reusable-selection source closure

- Media Library already supports upload, MIME/checksum inspection, image variants, folders, collections, metadata, usage tracking, Trash/restore and guarded permanent delete.
- Media Library picker JSON mode added for reusable module selection.
- `MediaPicker.tsx` added as a shared reusable chooser.
- Settings logo consumes MediaPicker rather than requiring manual URLs only.
- Media feature test source verifies picker returns active assets and excludes Trash.
- Shared UI/file flow remains within Nexora components.

### Theme Engine product workflow source closure

- Safe theme ZIP upload enters quarantine/Sentinel before Theme Engine promotion.
- Installation requires completed Sentinel `ALLOW` and immutable approved archive digest.
- Only `nexora-safe-html` theme engine is accepted in this phase.
- Required platform slots preserve head/assets/schema/content semantics.
- Preview token does not change active theme.
- Activation, version switch, design tokens and rollback paths are present.
- Shared FilePicker now exposes accessible validation errors (`aria-invalid`, announced error text).
- Theme preview failures are visible instead of silently swallowed.
- End-to-end acceptance test source creates a real ZIP fixture and covers scan -> install -> preview -> activate -> public render -> rollback.
- `scripts/theme-product-contract-verify.php` guards the workflow in CI/development readiness.

### Plugin / Extension product workflow source closure

- Extension upload UX routes packages through existing Sentinel quarantine; upload never installs or executes code.
- Only users with both extension-install and Sentinel scan/view permissions see the direct upload workflow.
- Verified supply-chain artifacts remain the only install input.
- Capability grants are deny-by-default; unregistered/missing requested capabilities block enablement.
- Dependencies, trusted-PHP execution policy, schema rollback safety and dependent-extension uninstall guards remain enforced.
- Destructive uninstall now requires shared `ConfirmDialog` confirmation.
- End-to-end declarative extension acceptance-test source covers Sentinel -> artifact -> install -> enable -> disable -> uninstall -> lifecycle evidence.
- `scripts/extension-product-contract-verify.php` guards the workflow in CI/development readiness.

### Studio first product workflow source closure

- Existing Studio visual editor provides typed elements, layers, responsive desktop/tablet/mobile editing, drag/drop, undo/redo, bindings, design tokens and reusable components.
- Studio Manager uses row locks + lock versions to reject stale writes and creates revision snapshots on save.
- Studio validator enforces node/depth budgets, stable IDs, allowed props/styles/bindings and safe link/target normalization.
- Published document-scoped canvases integrate into public `ThemePageController`; document renderer remains safe fallback when Studio canvas is absent/draft.
- Studio renderer escapes output and emits responsive tablet/mobile rules.
- Acceptance-test source expanded to prove:
  - create -> save -> publish
  - real public document rendering from Studio
  - dynamic `document.title` binding
  - responsive CSS output
  - unpublish -> Document Engine fallback
  - stale-write rejection without overwrite
  - unsafe `javascript:` button URL normalization
- `scripts/studio-product-contract-verify.php` guards the workflow in CI/development readiness.
- First Studio contract CI attempt exposed a wrong static marker; the gate itself was corrected and final CI run `32427092798` passed.

---

## 7. Current progress dashboard

### Platform implementation

```text
██████████████████░░  ~88%
```

### Real functional verification

```text
██████████░░░░░░░░░░  ~50%
```

Real verification intentionally did not rise with source-only Theme/Extension/Studio work.

| Phase | Progress | Status |
|---|---:|---|
| DEV-0 Package/bootstrap | 90% | PARTIAL — final dependency certification deferred |
| DEV-1 Installer | 100% source | SOURCE DONE — clean live rc.94 install confirmation still required |
| DEV-2A Historical TypeScript remediation | 100% | SOURCE DONE |
| DEV-2B TypeScript/Vite target build | 100% reported | TARGET VERIFIED for the reported Laragon build |
| DEV-3 Laravel/install runtime | 80% source / 75% live | PARTIAL — source convergence gate green; live rc.93 repair evidence pending |
| DEV-4 Login/admin/core functional QA | 70% source / 30% live | PARTIAL — settings/media/theme/extensions/Studio source workflows gated; target QA pending |
| DEV-4A Site settings + media reuse | 100% source / target pending | SOURCE DONE |
| DEV-4B Theme workflow | 100% source contract / target pending | SOURCE DONE |
| DEV-4C Extension workflow | 100% source contract / target pending | SOURCE DONE |
| DEV-4D Studio first publish workflow | 100% source contract / target pending | SOURCE DONE |
| DEV-5 DB/services portability | 60% | PARTIAL |
| DEV-6 Final C1-C6/release certification | 10% | DEFERRED CERTIFICATION |

---

## 8. NEXT ACTION — exact execution order

### Immediate live-target gate

Do **not** overwrite installed rc.93 with rc.94 merely to repair four fingerprints.

1. Run the prepared rc.93 Post-Install Identity Repair Pack externally against `D:\laragon\www\nexora`.
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

5. Only after those pass may GitHub issue #2 be closed.
6. Keep the installed rc.93 recovery target separate from the rc.94 development checkout.

### Development-target verification after live recovery

Use a separate development checkout of `dev/n1-0b-core-functional-qa`, then run:

```bat
php scripts\development-readiness.php --full
php artisan test --filter=SettingsFlowTest
php artisan test --filter=MediaLibraryFlowTest
php artisan test --filter=ThemeEngineFlowTest
php artisan test --filter=ExtensionsAdminFlowTest
php artisan test --filter=StudioFlowTest
```

Do not report Theme/Extension/Studio as TARGET VERIFIED until these execute successfully in the real target environment.

### Next source implementation order

```text
CMS / Documents / Collections product closure
  -> Media selection inside content/editor workflows
  -> SEO / Publishing end-to-end closure
  -> Admin Design System application-wide UX pass
  -> Forms / Data / Workflows
```

During every block, inspect GitHub open issues first and again before final handoff.

---

## 9. Product roadmap

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
3. Query open GitHub issues for `Vertex-Systems-Network/nexora`; classify each as source, target/runtime, UX, data or certification.
4. Identify dev source version, installed target version, blocker class, current PR/branch and latest CI state.
5. Do not repeat completed work without regression evidence.
6. Prefer the next roadmap gate while solving applicable open issues in the same pass.

### During work

1. Make the smallest architecture-correct root-cause fix.
2. Add regression protection for repeated blocker classes.
3. Preserve trust boundaries.
4. Distinguish development convenience from release security.
5. Never mark target PASS from static checks alone.
6. Use development branch + PR for meaningful source changes.
7. Do not close runtime/environment GitHub issues from source CI alone.

### After work

1. Re-query open GitHub issues and update issue comments/state with source/target evidence.
2. Update this file before final response/merge/package:
   - metadata/branch/PR/release/CI
   - open issue state
   - current checkpoint and live target state
   - completed work
   - progress dashboard
   - `NEXT ACTION`
   - append history entry
3. Keep PR #1 draft until the real-target gate is satisfied.

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
- Verification completed: repository access includes admin/push; `main` commit `f555fe396cda0e82efd4445ba016f709de3398c8`; repository ledger matched local rc.94 ledger.
- Real-target evidence: no Laragon mutation in this synchronization pass.
- Remaining blocker: live rc.93 post-install convergence.
- Next exact action: continue DEV-4 source audit while obtaining live convergence/login evidence.

### 2026-08-21 — No release — DEV-4 GitHub source gate and draft PR opened

- Trigger / observed blocker: GitHub workflow needed an enforceable product-facing DEV-4 source gate rather than chat/package claims alone.
- Root cause: auth/admin/core surfaces existed, but no single DEV-4 contract ran both locally and in CI; source-only CI referenced absent `package-lock.json` for npm caching.
- Changes applied: added `scripts/dev4-core-functional-contract-verify.php`; wired it into development readiness and GitHub Actions; removed lockfile-dependent npm cache; opened PR #1.
- Verification completed: certification preflight, Source Guard, DEV-4 contract and unified source certification PASS.
- Real-target evidence: no new Laragon target execution in this source pass.
- Remaining blocker: live rc.93 convergence.
- Next exact action: verify/repair rc.93 -> `/login` + `/admin`; continue product-facing source work.

### 2026-08-21 — No release — DEV-4 site identity and regional settings source closure

- Trigger / observed blocker: global Settings only exposed application name and appearance tokens; platform-level logo, default display timezone and default language were incomplete.
- Root cause: settings keys, validation, shared Inertia props, branding and installed default-locale resolution were incomplete; locale middleware also ran before tenant context resolution.
- Changes applied: added validated `app.logo_url`, `app.default_timezone`, `app.default_locale`; expanded Settings UI; shared props; Admin/Auth logo; installed default locale; tenant-before-locale ordering; tests/contracts.
- Verification completed: PHP syntax, Laravel Runtime, Security, Frontend, Inertia, Source Guard, preflight and unified source certification PASS; GitHub Actions source run successful.
- Real-target evidence: no Laragon execution for this branch batch.
- Remaining blocker: live rc.93 convergence + target settings execution.
- Next exact action: runtime recovery then target DEV-4 tests.

### 2026-08-21 — No release — Runtime issue #2 source regression gate

- Trigger / observed blocker: GitHub issue #2 records live rc.93 `environment/activation/service/process` identity mismatch.
- Root cause: rc.93 sealed install-sensitive fingerprints before final installed runtime context stabilized.
- Changes applied: added `scripts/post-install-runtime-convergence-contract-verify.php`; wired it into development readiness/CI; exact mutable-plane allow-list is guarded and immutable planes remain fail-closed. A first CI parser bug in the contract itself was fixed.
- Verification completed: GitHub Actions run `32425880362` SUCCESS with post-install convergence contract PASS.
- Real-target evidence: no new rc.93 repair result; source evidence only.
- Remaining blocker: issue #2 stays open until live compatibility + post-install readiness PASS.
- Next exact action: run safe rc.93 repair and the two required Artisan verification commands.

### 2026-08-21 — No release — Reusable Media + Theme product workflow source closure

- Trigger / observed blocker: Media needed central reusable selection and Theme Engine needed a real user workflow/acceptance contract rather than foundation-only claims.
- Root cause: Media reuse was not a shared chooser; Theme tests did not prove scan/install/preview/activate/public-render/rollback in one acceptance flow; upload/preview errors were weakly surfaced.
- Changes applied: Media picker JSON mode + shared `MediaPicker`; Settings logo integration; picker feature-test source; accessible shared FilePicker error API; Theme preview error UX; Theme real-ZIP acceptance-test source; Theme Product source contract.
- Verification completed: GitHub Actions run `32426200738` SUCCESS with runtime, DEV-4, Theme and unified source contracts PASS.
- Real-target evidence: no Laragon execution for the new Media/Theme branch work.
- Remaining blocker: target execution pending.
- Next exact action: continue Extension workflow source closure while live rc.93 recovery remains the target gate.

### 2026-08-21 — No release — Extension product workflow source closure

- Trigger / observed blocker: Extensions workspace lacked direct package-upload UX and destructive uninstall happened without explicit confirmation; existing tests did not prove the complete trust/lifecycle chain.
- Root cause: upload existed only via Sentinel workspace; Extensions UI started after verified artifact creation; uninstall UX was too easy to trigger.
- Changes applied: Extensions upload modal sends package to Sentinel quarantine only when operator has extension-install + Sentinel scan/view permissions; invalid summary icon fixed; shared FilePicker errors used; uninstall requires ConfirmDialog; declarative real-ZIP acceptance-test source covers Sentinel -> artifact -> install -> enable -> disable -> uninstall; Extension Product source contract added.
- Verification completed: GitHub Actions run `32426515463` SUCCESS with Extension Product contract PASS alongside previous gates.
- Real-target evidence: no Laragon execution for this branch batch.
- Remaining blocker: target execution pending; issue #2 still open.
- Next exact action: Studio product workflow closure + live rc.93 recovery evidence.

### 2026-08-21 — No release — Studio first publish/public-render workflow source closure

- Trigger / observed blocker: Studio had substantial visual-builder code, but acceptance evidence only proved create/save/status-publish and did not prove actual public rendering, fallback, concurrency or unsafe-link normalization.
- Root cause: product acceptance coverage stopped before ThemePage public integration and critical stale-write/safe-output behavior.
- Changes applied: expanded `StudioFlowTest` source to cover create/save/publish -> public document Studio render, document title binding, responsive CSS, unpublish -> Document Engine fallback, stale lock rejection and `javascript:` URL normalization; added `scripts/studio-product-contract-verify.php`; wired Studio gate into development readiness and CI.
- Verification completed: first Studio contract run correctly failed on a wrong static UI marker; marker was corrected. GitHub Actions run `32427092798` then passed preflight, Source Guard, runtime convergence, DEV-4, Theme, Extension, Studio and unified source certification.
- Real-target evidence: no Laragon execution of the expanded Studio test yet; SOURCE DONE only.
- Remaining blocker: live issue #2 + target execution of current branch workflows.
- Next exact action: recover/verify rc.93 live target; in parallel move source work to CMS/Documents/Collections product closure.

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

Do not let these pull work away from: **live runtime convergence -> login/admin target QA -> current product workflow target tests -> CMS/SEO product closure**.

---

## 14. AI quick resume card

```text
PROJECT: Nexora
GOAL: Advanced extensible WordPress/Webflow/Wix/Shopify-class platform ecosystem
GITHUB: Vertex-Systems-Network/nexora
DEV SOURCE: rc.94 / v5.29 / n1-v5.29
DEV BRANCH: dev/n1-0b-core-functional-qa
PR: #1 DRAFT + MERGEABLE; DO NOT MERGE BEFORE TARGET EVIDENCE
BRANCH HEAD BEFORE LEDGER COMMIT: 2035528bae27222446138e65bda50ea32feb9e17
LATEST GREEN CI: 32427092798
OPEN ISSUE: #2 runtime identity mismatch
LIVE TARGET: rc.93 installed on Laragon
LIVE BLOCKER: post-install environment/activation/service/process fingerprints stale
SOURCE/DEPLOYMENT/DB ON LIVE EVIDENCE: matching
DEPENDENCY RUNTIME: matching
LOCK REVIEW: missing, deferred
SOURCE DONE NOW: runtime convergence regression + site settings + MediaPicker + Theme workflow + Extension workflow + first Studio publish/public-render workflow
NEXT LIVE: safe rc.93 repair -> compatibility PASS -> post-install PASS -> /login -> /admin -> issue #2 close only after evidence
NEXT TARGET TESTS: development-readiness --full + Settings/Media/Theme/Extensions/Studio feature tests on separate dev checkout
NEXT SOURCE: CMS/Documents/Collections -> content Media selection -> SEO/Publishing closure
ISSUE RULE: inspect open GitHub issues every pass and solve applicable defects alongside roadmap work
DO NOT: overwrite installed rc.93 with rc.94 as repair; do not merge PR #1 or return to C1-C6 before target usability evidence
```
