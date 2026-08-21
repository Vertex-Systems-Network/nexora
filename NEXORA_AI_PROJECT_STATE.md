# Nexora AI Project State & Execution Ledger

> **AI START HERE**
> This is the canonical cross-chat handoff file for Nexora. Any AI/agent working on Nexora must read this file before planning, modifying, auditing, packaging, or certifying the project, and must update it after every meaningful implementation/audit/release pass.
>
> This is operational documentation and is intentionally outside the immutable source-attestation roots so history/status updates do not create deployment/runtime drift.

---

## 0. Ledger metadata

- Ledger schema: `1`
- Ledger revision: `1.7`
- Project: `Nexora`
- Product class: advanced extensible web platform / CMS / site builder / application ecosystem
- Current development source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Ledger last updated: `2026-08-21`
- GitHub canonical repository: `Vertex-Systems-Network/nexora`
- GitHub default branch: `main`
- Active development branch: `dev/n1-0b-core-functional-qa`
- Active GitHub pull request: `#1` — draft, mergeable; title now `DEV-4/DEV-5: product QA, data workflows and database portability closure`
- Current branch head before this ledger-only commit: `ebbcc22e912749f92fae68e8e7e8d8faed5762a1`
- Latest complete green source-certification run: `32456834492`
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
16. **Disposable DB verification is mandatory for cross-engine claims.** Source contracts prove source alignment only; a database engine becomes TARGET VERIFIED only after the guarded real target matrix passes on that engine.

---

## 3. Platform architecture map

| Layer / subsystem | Purpose | Current state |
|---|---|---|
| Kernel / Core | Boot, lifecycle, shared primitives | Foundation implemented |
| Public Contracts | Stable APIs for modules/plugins/themes | Foundation implemented |
| Module Registry | Discover/register/activate modules | Foundation implemented |
| Capability Runtime | Controlled extension permissions | Foundation implemented |
| Admin Platform / Design System | Shared UI/navigation/forms/selects/themes/tooltips | Strong source closure; responsive/mobile/accessibility contract green; app-wide target QA pending |
| Document Engine | Structured page/content documents | Writer CRUD/revisions/autosave/concurrency/Media reuse + generic Collections SOURCE DONE; target execution pending |
| SEO Core | Metadata/canonical/schema/social/sitemap/publishing semantics | Publishing + SEO workflow SOURCE DONE; target execution pending |
| Theme Engine | Package/install/activate/render | Product workflow SOURCE DONE; target execution pending |
| Plugin / Extension Engine | Lifecycle/capabilities/migrations | Product workflow SOURCE DONE; target execution pending |
| Studio | Visual page/site builder | First create/edit/publish/public-render workflow SOURCE DONE; target execution pending; advanced builder features remain |
| Media / DAM | Upload/inspect/variants/folders/collections/reuse | Foundation + reusable picker SOURCE DONE; target execution pending |
| Forms / Data / Workflows | Tenant forms, public submissions, Automation bridge | SOURCE DONE for current product workflow; target execution pending |
| Data Connections | Auxiliary Mongo/Redis/AWS data-service handles | Product/security + DEV-5 portability source closure green; live connector target tests pending |
| Primary SQL Portability | MySQL/MariaDB/PostgreSQL/SQLite/SQL Server + managed aliases | Source/harness closure green; real engine matrix evidence pending |
| Forge / SDK | Developer extension tooling | Foundation/planned expansion |
| Sentinel | Theme/plugin trust/security | Foundation implemented; 2.0 later |
| Marketplace | Themes/apps distribution | Foundation/planned expansion |
| Commerce | Commerce primitives/services | Foundation; Commerce 2.0 later |
| CRM / Membership / Helpdesk | Business/customer capabilities | Foundation/roadmap |
| Multisite / Organizations / SSO | Enterprise tenancy/governance | Foundation exists; broad product/target closure remains |
| Cloud / HA Runtime | Distributed workers/storage/deployments | Later roadmap |
| Installer / Deployment / Recovery | Zero-state install/update/recovery/runtime handoff | rc.94 source stabilization + DB UX closure green; live rc.93 recovery evidence pending |

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
- Latest green CI run `32456834492` passed:
  - Certification preflight
  - Source Guard
  - Post-install runtime convergence contract
  - DEV-4 core functional source contract
  - Theme product source contract
  - Extension product source contract
  - Studio product source contract
  - Document product source contract
  - Content collection product source contract
  - Publishing + SEO product source contract
  - Admin UX product source contract
  - Forms + Data + Workflows product source contract
  - Data Connections product source contract
  - Primary SQL portability source contract
  - Installer database UX source contract
  - Unified source certification
- Source/static gates are green through DEV-5 portability hardening.
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

### 5.5 DEV-5 target-evidence state

The real target matrix harness now exists, but no cross-engine result is allowed to be inferred from source CI.

Canonical runner:

```text
scripts/database-target-matrix.php
```

Durable secret-free evidence path when `--evidence` is used:

```text
storage/app/nexora/qa/database-target-matrix.json
```

No MySQL/MariaDB/PostgreSQL/SQL Server or managed AWS engine is TARGET VERIFIED from the current source-only CI. SQLite also requires an actual target run of the runner before this new matrix gate can call it target-verified for the current branch generation.

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
- `MediaPicker.tsx` is the shared reusable chooser and supports canonical selection + clear/removal.
- Settings logo, Writer image blocks, Publishing hero image and SEO social image reuse MediaPicker rather than fixed/preloaded URL selectors.
- Writer persists canonical `media_asset_id`; Publishing/SEO retain external URL only as optional fallback where supported.
- Media feature test source verifies picker returns active assets and excludes Trash.
- Shared UI/file flow remains within Nexora components.

### Theme Engine product workflow source closure

- Safe theme ZIP upload enters quarantine/Sentinel before Theme Engine promotion.
- Installation requires completed Sentinel `ALLOW` and immutable approved archive digest.
- Only `nexora-safe-html` theme engine is accepted in this phase.
- Required platform slots preserve head/assets/schema/content semantics.
- Preview token does not change active theme.
- Activation, version switch, design tokens and rollback paths are present.
- Shared FilePicker exposes accessible validation errors (`aria-invalid`, announced error text).
- Theme preview failures are visible instead of silently swallowed.
- End-to-end acceptance test source creates a real ZIP fixture and covers scan -> install -> preview -> activate -> public render -> rollback.
- `scripts/theme-product-contract-verify.php` guards the workflow in CI/development readiness.

### Plugin / Extension product workflow source closure

- Extension upload UX routes packages through existing Sentinel quarantine; upload never installs or executes code.
- Only users with both extension-install and Sentinel scan/view permissions see the direct upload workflow.
- Verified supply-chain artifacts remain the only install input.
- Capability grants are deny-by-default; unregistered/missing requested capabilities block enablement.
- Dependencies, trusted-PHP execution policy, schema rollback safety and dependent-extension uninstall guards remain enforced.
- Destructive uninstall requires shared `ConfirmDialog` confirmation.
- End-to-end declarative extension acceptance-test source covers Sentinel -> artifact -> install -> enable -> disable -> uninstall -> lifecycle evidence.
- `scripts/extension-product-contract-verify.php` guards the workflow in CI/development readiness.

### Studio first product workflow source closure

- Existing Studio visual editor provides typed elements, layers, responsive desktop/tablet/mobile editing, drag/drop, undo/redo, bindings, design tokens and reusable components.
- Studio Manager uses row locks + lock versions to reject stale writes and creates revision snapshots on save.
- Studio validator enforces node/depth budgets, stable IDs, allowed props/styles/bindings and safe link/target normalization.
- Published document-scoped canvases integrate into public `ThemePageController`; document renderer remains safe fallback when Studio canvas is absent/draft.
- Studio renderer escapes output and emits responsive tablet/mobile rules.
- Acceptance-test source expanded to prove create/save/publish, public Studio render, document-title binding, responsive CSS, unpublish fallback, stale-write rejection and unsafe `javascript:` URL normalization.
- `scripts/studio-product-contract-verify.php` guards the workflow in CI/development readiness.

### Document / Writer + Content Collections source closure

- Writer CRUD, revisions, autosave, optimistic concurrency and structured block validation are source-gated.
- Writer image block selects from the complete searchable Media Library and stores canonical asset IDs.
- Public document renderer resolves canonical Media assets and ignores trashed/non-image invalid selections.
- Generic tenant-native Content Collections added without mutating the historical enterprise backfill migration.
- Collections support name/slug/status/document type, typed custom field schema, document membership, per-entry values, position, non-destructive detach/delete, audit and permission boundaries.
- Database source guard distinguishes historical tenant-manifest roots from forward tenant-native models while preserving the original 51-table historical backfill metric.
- Current tenant-aware model/table discovery is dynamic; Data Connections raised the current tenant root set without falsifying historical migration semantics.
- `scripts/document-product-contract-verify.php` and `scripts/collection-product-contract-verify.php` run in CI/development readiness.

### Publishing + SEO end-to-end source closure

- Publishing Article Settings no longer preload a fixed 250-image dropdown; shared searchable MediaPicker selects canonical hero assets.
- Hero assets are tenant-safe, image-only, public and non-trashed; optional external hero URL remains a fallback.
- SEO social metadata can persist canonical Media Library image IDs with usage tracking and an external URL fallback.
- Public SEO manager resolves canonical social title/description/image/type/Twitter card.
- Public document head emits route-correct canonical URL, robots index/follow + extra directives, Open Graph and Twitter metadata.
- `PublicDocumentVisibility` centralizes anonymous exclusion for membership-protected documents.
- Home, blog/taxonomy/author/series archives, series navigation, related content and sitemap generation exclude protected published documents from anonymous discovery.
- Publishing/SEO acceptance-test source covers scheduling/taxonomy/series plus protected archive non-disclosure and real public OG/Twitter/robots output.
- `scripts/publishing-seo-product-contract-verify.php` runs in CI/development readiness.

### Application-wide Admin UX source closure pass

- Existing browser UX analyzer guards shared component usage, raw-control bans, logical RTL, focus-visible, modal focus, DataTable semantics, command palette semantics, reduced-motion and browser evidence structure.
- OrganizationSwitcher and LanguageSwitcher are reusable across responsive surfaces rather than desktop-only wrappers.
- Mobile navigation exposes tenant/organization switching and language switching; desktop header retains responsive selectors.
- Language flag images use lazy/async loading.
- Global toast feedback uses canonical Lucide-compatible icons, remains live-region accessible, is mobile-width safe and supports explicit dismissal through shared IconButton.
- Shared Select exposes invalid state to React Aria, visual danger state and announced error message.
- Route progress tracks/cleans both delayed-show and delayed-hide timers to avoid stale post-navigation updates.
- `scripts/admin-ux-product-contract-verify.php` composes these product invariants with the existing browser UX analyzer and runs in CI/development readiness.

### Forms + Data + Workflows source closure

- Tenant-native `nx_forms` and `nx_form_submissions` plus tenant-local form slugs and form permissions are source-gated.
- Controlled form schema allows text/email/textarea/number/select/checkbox/date with a 50-field budget and stable payload keys.
- Public submission validation is derived from saved schema and stores validated values only.
- Submission metadata is privacy-minimal; raw IP/user-agent persistence is explicitly rejected by the contract.
- `form.submitted` bridges into Automation using a stable submission idempotency key and queued workflow execution.
- Public routes are fail-closed by lifecycle status, CSRF-protected, honeypot-protected and throttled.
- Initial lifecycle is non-destructive: draft/active/paused/archived rather than delete-first behavior.
- Forms Admin surfaces consume shared Nexora Admin UI.
- `scripts/forms-workflow-product-contract-verify.php` is wired into CI/development readiness and is green in run `32456834492`.

### Data Connections product + auxiliary portability source closure

- `nx_data_connections` is enterprise-tenant-owned and uses encrypted secret payloads; browser payloads expose secret presence only, not decrypted secret values.
- Plaintext credentials embedded in endpoints are rejected; legacy unsafe credentials are quarantined/disabled by migration logic.
- Connectivity changes force disablement and invalidate stale health tests; enabled connections cannot be deleted.
- Connection names and records are tenant scoped.
- Connector catalog covers MongoDB, MongoDB Atlas, Redis, DocumentDB, ElastiCache Redis and DynamoDB.
- Connector capabilities are explicit: endpoint/database/user-password/region/AWS-key-pair requirements are driver-aware.
- DynamoDB is endpoint-optional, region-aware and supports the runtime IAM chain when static access/secret keys are both blank; partial static key pairs fail closed.
- ElastiCache default example is TLS-first; Redis `rediss://`/`tls://` normalizes to TLS for both PhpRedis and Predis, while unsupported URL schemes are rejected.
- DocumentDB example carries TLS/replica/read-preference/retry policy rather than a misleading bare endpoint.
- Feature coverage proves Dynamo endpoint-optional storage, paired-key validation and non-Dynamo endpoint requirements.
- Unit portability coverage proves Dynamo partial-key rejection and Redis TCP/TLS parser behavior.
- `scripts/data-connection-product-contract-verify.php` guards all of the above and is green in run `32456834492`.

### DEV-5 Primary SQL portability + installer DB UX source closure

- Primary/managed SQL registry covers MySQL, MariaDB, PostgreSQL, SQLite, SQL Server, RDS MySQL/MariaDB/PostgreSQL/SQL Server and Aurora MySQL/PostgreSQL.
- Managed aliases map to compatible Laravel/PDO drivers while preserving provider policy; managed database creation remains disabled.
- Version policy, Laravel connection configuration, installer environment generation and backup policy are unit-covered without requiring network access.
- Migration source analyzer rejects known non-portable constructs such as column placement, enum/set, full-text/spatial, generated columns and raw DB statements; portable nullable-unique handling is accounted for.
- `DatabaseRoundTripCompatibilityTest` dynamically discovers tenant-aware model tables instead of freezing the historical 51-root count; Forms, Collections and Data Connections are explicitly covered.
- Installer database selector carries registry metadata; SQLite disables network inputs; managed services disable create; driver switches clear the previous password and use driver defaults without blindly overwriting deliberate user values.
- A successful DB test is invalidated by subsequent driver/host/port/database/username/password/create changes; Continue/Install requires a fresh test.
- `scripts/primary-sql-portability-contract-verify.php` and `scripts/installer-database-ux-contract-verify.php` are CI/development-readiness gates and are green in run `32456834492`.

### DEV-5 guarded real database target matrix

- `scripts/database-target-matrix.php` is the canonical real-engine compatibility runner.
- Only disposable `nexora_matrix_*` network databases and `nexora_matrix_*.sqlite` basenames are accepted.
- Non-empty databases are refused before test mutation.
- The runner never rewrites `.env` and never drops database containers.
- It executes `DatabaseRoundTripCompatibilityTest` through target PHPUnit and cleans matrix-created objects afterward.
- Managed aliases reuse registry create policy and therefore require a pre-created empty matrix database.
- `--list` exposes available engines and required environment prefixes.
- `--evidence` writes `storage/app/nexora/qa/database-target-matrix.json` using an explicit secret-free allow-list.
- Evidence schema `2` records platform/source/PHP identity, selected drivers, server versions, pre-test object counts, PHPUnit exit codes and cleanup state; host/user/password/verbose diagnostics are excluded.
- `docs/DEV5_DATABASE_TARGET_MATRIX.md` is the operator runbook for native and managed engines.
- The target matrix itself is source-gated by the Primary SQL Portability contract.

---

## 7. Current progress dashboard

### Platform implementation

```text
███████████████████░  ~95%
```

### Real functional verification

```text
██████████░░░░░░░░░░  ~50%
```

Source implementation increased; real verification intentionally did not rise because the new DEV-4/DEV-5 work has not yet been executed across the required real targets.

| Phase | Progress | Status |
|---|---:|---|
| DEV-0 Package/bootstrap | 90% | PARTIAL — final dependency certification deferred |
| DEV-1 Installer | 100% source | SOURCE DONE — clean live rc.94 install confirmation still required |
| DEV-2A Historical TypeScript remediation | 100% | SOURCE DONE |
| DEV-2B TypeScript/Vite target build | 100% reported | TARGET VERIFIED for the previously reported Laragon build |
| DEV-3 Laravel/install runtime | 80% source / 75% live | PARTIAL — source convergence gate green; live rc.93 repair evidence pending |
| DEV-4 Login/admin/core functional QA | 95% source / 30% live | PARTIAL — major product workflows source-gated; broad target QA pending |
| DEV-4A Site settings + media reuse | 100% source / target pending | SOURCE DONE |
| DEV-4B Theme workflow | 100% source contract / target pending | SOURCE DONE |
| DEV-4C Extension workflow | 100% source contract / target pending | SOURCE DONE |
| DEV-4D Studio first publish workflow | 100% source contract / target pending | SOURCE DONE |
| DEV-4E Documents + Collections | 100% source contract / target pending | SOURCE DONE |
| DEV-4F Publishing + SEO | 100% source contract / target pending | SOURCE DONE |
| DEV-4G Admin UX source pass | 100% source contract / target pending | SOURCE DONE for current pass |
| DEV-4H Forms + Data + Workflows | 100% source contract / target pending | SOURCE DONE |
| DEV-4I Data Connections product | 100% source contract / target pending | SOURCE DONE |
| DEV-5 DB/services portability | ~95% source / real matrix pending | PARTIAL — source/harness green, TARGET VERIFIED evidence pending |
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
scripts\development-readiness.bat --full
php artisan test
npm run build
php scripts\database-target-matrix.php --list
php scripts\database-target-matrix.php --drivers=sqlite,mysql,mariadb,pgsql,sqlsrv --evidence
```

For network engines, configure only disposable databases whose names start with `nexora_matrix_` as documented in `docs/DEV5_DATABASE_TARGET_MATRIX.md`.

At minimum explicitly exercise Settings, Media, Theme, Extensions, Studio, Documents, Collections, Publishing, SEO, Forms, Data Workflows, Data Connections and responsive Admin navigation on the real target.

For auxiliary connectors, execute real connection tests only where the appropriate adapter/service is available; record connector-specific target evidence separately from primary SQL matrix evidence.

Do not report current source closures or an engine as TARGET VERIFIED until the corresponding real target execution passes.

### Remaining source/target sequence

```text
Live rc.93 runtime recovery evidence
  -> separate dev checkout full PHPUnit/build/product browser QA
  -> real SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix evidence
  -> optional managed AWS/auxiliary connector target evidence where services exist
  -> close remaining DEV-5 target gaps
  -> final DEV-6 reviewed locks + C1-C6/release certification
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
8. For DB portability, never point the target matrix at customer/staging/production/shared data; use the guarded disposable naming contract only.

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

### 2026-08-21 — No release — Documents, Media reuse and Content Collections source closure

- Trigger / observed blocker: Writer media choice had to scale beyond fixed lists and Nexora lacked a generic Webflow/WordPress-class Content Collections workflow.
- Root cause: Writer preview/media ownership was split across caller/static assumptions; no generic tenant-native collection model/schema/routes/admin workflow existed; historical tenant source guard assumed all future tenant models must be in the original 51-table backfill manifest.
- Changes applied: Writer uses shared searchable MediaPicker and canonical `media_asset_id`; added tenant-native Content Collections, typed custom fields, document membership/per-entry data, permissions/audit/non-destructive lifecycle, acceptance tests and product contract; source guard now distinguishes historical manifest roots from forward tenant-native models without mutating historical migration semantics.
- Verification completed: GitHub Actions run `32429295616` SUCCESS with Document + Content Collection gates and unified source certification PASS.
- Real-target evidence: no Laragon execution of these new workflows; SOURCE DONE only.
- Remaining blocker: live issue #2 + target execution.
- Next exact action: Publishing/SEO end-to-end source closure while preserving live target boundary.

### 2026-08-21 — No release — Publishing, SEO and protected public visibility source closure

- Trigger / observed blocker: Article Settings preloaded a fixed 250-image dropdown; social SEO media lacked durable Media Library references; saved social/extra robots fields were not fully emitted in public head; membership-protected documents could surface in anonymous discovery lists.
- Root cause: Publishing/SEO workflows were built from strong foundations but were not fully connected through canonical media, public metadata output and one shared public-visibility policy.
- Changes applied: Publishing hero + SEO social MediaPicker integration; canonical social media IDs + usage tracking; resolved social SEO contract; route-correct canonical fallback; robots/OG/Twitter public tags; `PublicDocumentVisibility`; filtering across home/blog/taxonomy/authors/series/related/sitemap; expanded acceptance tests; added Publishing + SEO product contract.
- Verification completed: GitHub Actions run `32430956498` SUCCESS after performance delegation and contract-marker regressions were corrected; all product gates and unified source certification passed.
- Real-target evidence: no Laragon execution of Publishing/SEO changes; SOURCE DONE only.
- Remaining blocker: live issue #2 + target execution.
- Next exact action: application-wide Admin UX source pass and live recovery evidence.

### 2026-08-21 — No release — Application-wide Admin UX responsive/accessibility source closure pass

- Trigger / observed blocker: desktop-only organization switching made tenant changes impossible from mobile Admin; global toast feedback had no explicit dismiss action/canonical status icons; shared Select did not expose invalid state as strongly as Input/Textarea; route-progress hide timers could outlive navigation lifecycle.
- Root cause: shared primitives were strong but a few cross-app interaction details remained outside the existing browser/UX static gate.
- Changes applied: responsive OrganizationSwitcher/LanguageSwitcher APIs; mobile sidebar tenant/language controls; responsive header selectors; lazy/async flag images; dismissible icon-based toast; Select `isInvalid` + announced error state; route-progress show/hide timer cleanup; dedicated Admin UX product contract composed with existing browser UX analyzer.
- Verification completed: GitHub Actions run `32431449676` SUCCESS — preflight, Source Guard, runtime, all Theme/Extension/Studio/Document/Collection/Publishing+SEO/Admin UX product gates and Unified Source Certification PASS.
- Real-target evidence: no new Laragon execution for this Admin UX pass; SOURCE DONE only.
- Remaining blocker: live rc.93 issue #2 and broad target functional QA.
- Next exact action: safe rc.93 recovery -> compatibility/post-install PASS -> login/admin; continue Forms/Data/Workflows + DEV-5 source review in parallel.

### 2026-08-21 — No release — Forms + Data + Workflows source closure

- Trigger / observed blocker: Nexora needed a complete first forms/data workflow rather than isolated form/schema primitives.
- Root cause: public submission semantics, privacy-minimal storage, Automation bridging, tenant permissions and non-destructive lifecycle needed one enforceable product contract.
- Changes applied: tenant form/submission schema and models, controlled form definition validator, schema-derived public validation/storage, CSRF/honeypot/throttle/status gates, `form.submitted` Automation bridge, Admin workflow and acceptance contract.
- Verification completed: Forms + Data + Workflows product source gate PASS in latest full source run `32456834492`.
- Real-target evidence: no new Laragon execution for this workflow; SOURCE DONE only.
- Remaining blocker: target functional execution.
- Next exact action: include Forms/Data/Workflows in the separate dev-checkout PHPUnit/browser QA pass.

### 2026-08-21 — No release — Data Connections tenancy/product source closure

- Trigger / observed blocker: auxiliary data-service records required enterprise tenant ownership, encrypted credential handling, safe rotation and reusable runtime testers.
- Root cause: the original global connection shape and historical tenant manifest assumptions were insufficient for a multi-organization product workflow.
- Changes applied: tenant-scoped `nx_data_connections`, migration/backfill/quarantine rules, encrypted secrets, non-disclosing Admin payload, fresh-health requirements, test/enable/remove lifecycle, dynamic tenant-root round-trip coverage and dedicated product gate.
- Verification completed: Data Connections product gate PASS; historical 51-root migration metric remains frozen while current tenant-aware model discovery validates the expanded set.
- Real-target evidence: no external connector target execution yet.
- Remaining blocker: connector-specific target evidence + broad target QA.
- Next exact action: continue DEV-5 connector and primary SQL portability review.

### 2026-08-21 — No release — Primary SQL portability and installer database UX source closure

- Trigger / observed blocker: supported SQL labels existed, but DEV-5 needed enforceable native/managed driver mapping, version/config/backup/migration coverage and installer state correctness.
- Root cause: managed aliases had incomplete test coverage; runtime tenant test had a stale historical count; installer could preserve a successful DB test after configuration changes or carry stale defaults between drivers.
- Changes applied: complete registry/version/provisioner unit matrix, dynamic tenant compatibility coverage, Primary SQL source gate, registry-driven installer DB defaults/policy and mandatory re-test invalidation; managed create remains disabled.
- Verification completed: GitHub Actions run `32435144381` first closed the installer DB UX gate; latest run `32456834492` reconfirmed Primary SQL + Installer Database UX + Unified Source Certification PASS.
- Real-target evidence: no new cross-engine real matrix run yet.
- Remaining blocker: real disposable engine execution.
- Next exact action: run the guarded target matrix on the separate dev checkout.

### 2026-08-21 — No release — Guarded real DB matrix + auxiliary connector portability hardening

- Trigger / observed blocker: DEV-5 had source portability checks but no safe reproducible way to prove each real engine; auxiliary connector audit also found Dynamo endpoint validation and Redis TLS behavior inconsistent with their actual deployment models.
- Root cause: no disposable multi-engine runner/evidence artifact; Admin treated every auxiliary service as endpoint/user-password shaped; PhpRedis path did not explicitly preserve TLS transport from `rediss://`.
- Changes applied: `scripts/database-target-matrix.php` with strict `nexora_matrix_*` empty-database safety, real round-trip test/cleanup, no `.env` rewrite/no DB-container drop; secret-free schema-2 `--evidence`; operator runbook; Dynamo driver-aware endpoint/region/IAM/static-key rules; Redis TLS normalization across PhpRedis/Predis; feature/unit regression coverage; contracts strengthened.
- Verification completed: GitHub Actions run `32456834492` FULL SUCCESS — all product/runtime/DEV-5 source gates and Unified Source Certification PASS.
- Real-target evidence: source/harness only; no current-branch real SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix artifact has been supplied yet.
- Remaining blocker: live rc.93 recovery + separate dev-checkout product/runtime QA + real DB matrix evidence.
- Next exact action: obtain those target results before claiming DEV-5 TARGET VERIFIED or moving PR #1 out of draft.

---

## 13. Known deferred work / not the current blocker

- formal reviewed-lock attestation
- C1-C6 final certification
- release signing/provenance finalization
- broad cross-platform matrix
- real cross-database target matrix execution/evidence
- managed AWS SQL target verification where test services are available
- auxiliary Mongo/Redis/AWS connector target verification where adapters/services are available
- HA/distributed runtime
- final performance/accessibility certification
- Marketplace 2.0 / Sentinel 2.0 / Commerce 2.0

Do not let these pull work away from: **live runtime convergence -> login/admin target QA -> current product workflow target tests -> guarded DB target matrix -> final DEV-6 certification**.

---

## 14. AI quick resume card

```text
PROJECT: Nexora
GOAL: Advanced extensible WordPress/Webflow/Wix/Shopify-class platform ecosystem
GITHUB: Vertex-Systems-Network/nexora
DEV SOURCE: rc.94 / v5.29 / n1-v5.29
DEV BRANCH: dev/n1-0b-core-functional-qa
PR: #1 DRAFT + MERGEABLE; MARK READY ONLY AFTER REQUIRED TARGET EVIDENCE
BRANCH HEAD BEFORE LEDGER COMMIT: ebbcc22e912749f92fae68e8e7e8d8faed5762a1
LATEST GREEN CI: 32456834492
OPEN ISSUE: #2 runtime identity mismatch
LIVE TARGET: rc.93 installed on Laragon
LIVE BLOCKER: post-install environment/activation/service/process fingerprints stale
SOURCE/DEPLOYMENT/DB ON LIVE EVIDENCE: matching
DEPENDENCY RUNTIME: matching
LOCK REVIEW: missing, deferred
SOURCE DONE NOW: runtime convergence regression + settings + reusable Media + Theme + Extension + Studio + Documents + Content Collections + Publishing/SEO + Admin UX + Forms/Data/Workflows + Data Connections + Primary SQL portability + Installer DB UX + guarded real-DB matrix harness
DEV-5: ~95% SOURCE; real engine TARGET VERIFIED evidence pending
DB MATRIX: scripts/database-target-matrix.php; use only empty nexora_matrix_* targets; --evidence -> storage/app/nexora/qa/database-target-matrix.json
NEXT LIVE: safe rc.93 repair -> compatibility PASS -> post-install PASS -> /login -> /admin -> issue #2 close only after evidence
NEXT TARGET TESTS: development-readiness --full + full PHPUnit/build + major product browser QA + real DB target matrix on separate dev checkout
NEXT SOURCE/TARGET: close any defects exposed by target QA/matrix; then DEV-6 reviewed locks/C1-C6
ISSUE RULE: inspect open GitHub issues every pass and solve applicable defects alongside roadmap work
DO NOT: overwrite installed rc.93 with rc.94 as repair; do not mark PR #1 Ready or claim DB TARGET VERIFIED from source CI alone
```
