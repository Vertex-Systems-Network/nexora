# Nexora — Master Development Plan & Status — PKG-1 Usable Release + C1 Closure

Status legend: **DONE** = implemented. **CERTIFYING** = implemented RC work is under dependency/browser/operator certification and is not yet a stable-release claim. **NEXT** = immediate block after the current certification gate passes. **PLANNED** = approved but not yet implemented. **EXTERNAL** = intentionally excluded from Nexora Core and planned as an installable App/Extension/Theme.

## Internal Nexora Platform Roadmap

| Milestone | Scope | Status |
|---|---|---|
| N0.0 | Architecture constitution, package boundaries, coding/testing rules | DONE |
| N0.1 | Repository foundation, database/testing/admin standards | DONE |
| N0.2 | Laravel + Inertia + React + TypeScript premium Admin foundation | DONE |
| N0.3 | Identity, custom roles/permissions, users, audit, notifications, tables | DONE |
| N0.4 | Nexora Kernel, Contracts, Module Registry, Capability Runtime | DONE |
| N0.5 | Sentinel quarantine + static/package security scanning foundation | DONE |
| N0.6 | Secure installation wizard | DONE |
| N0.7 | Deployment bootstrap + runtime/cache hardening | DONE |
| N0.8 | Clean-domain / zero-CLI deployment experience | DONE |
| N0.9 | Portable Composer/Node toolchain environment | DONE |
| N0.10 | Observable deployment/install progress + cancellation foundation | DONE |
| N0.11 | Premium installer branding + resilient environment persistence | DONE |
| N0.12 | Deployment recovery, localization/RTL, premium file picker | DONE |
| N0.13 | Installer stabilization, password security, database backup consent | DONE |
| N0.14 | Relational database portability + safe reset/recovery | DONE |
| N0.15 | Data Connections (MongoDB/Redis/AWS auxiliary services) + premium selects | DONE |
| N0.16 | UI-library governance + universal Document Engine + revisions | DONE |
| N0.17 | Admin theme/collapse/tooltips + human labels + Writer block editor | DONE |
| N0.18 | Editorial workflow, review/comments, revision compare/restore, conflict-safe autosave | DONE |
| N0.19 | SEO Core, Schema Graph, canonical/robots/indexing policy, sitemap, internal links | DONE |
| N0.20 | Theme Engine + Design Tokens + theme install/preview/switch/rollback | DONE |
| N0.21 | Nexora Studio visual builder + responsive/dynamic data foundations | DONE |
| N0.22 | Blog & Article publishing, authors, taxonomy, series, scheduling and archives | DONE |
| N0.23 | Book publishing in base/internal platform | EXTERNAL → EXT-B01 |
| N0.24 | CV/Resume/Biography in base/internal platform | EXTERNAL → EXT-P01 |
| N0.25 | Media, newsletter, syndication and distribution adapters | DONE |
| N0.26 | Search, content analytics, SEO crawler/content audit | DONE |
| N0.27 | Automation/workflow engine, triggers/conditions/actions/webhooks | DONE |
| N0.28 | Sentinel advanced supply-chain controls: SBOM, signing, provenance, sandbox adapters | DONE |
| N0.29 | Extensions lifecycle, Forge developer SDK, Marketplace | DONE |
| N0.30 | Commerce + Billing foundation | DONE |
| N0.31 | CRM foundation | DONE |
| N0.32 | Membership + Helpdesk foundations; LMS/Booking/Projects remain external packages | DONE |
| N0.33 | Multisite, tenancy, organizations, SSO and enterprise controls | DONE |
| N0.34 | Cloud/HA/distributed runtime, queues, object storage, operational tooling | DONE |
| N1.0 | Release Candidate certification: zero-install, migrations, build, tests, security, accessibility, performance, backup/restore, HA and packaging | CERTIFYING — TARGET EXECUTION / C1-C6 REAL EVIDENCE |
| N1.1 | Admin UX / Design System certification and whole-system consistency audit | NEXT AFTER N1.0 PASS |
| N1.2 | Dynamic Content Model / Custom Fields / Relations 2.0 | PLANNED |
| N1.3 | Visual Site Builder 2.0 | PLANNED |
| N1.4 | Theme Studio 2.0 / Global Design System | PLANNED |
| N1.5 | Extension SDK 2.0 / Hooks / UI Slots / Runtime APIs | PLANNED |
| N1.6 | Frontend Runtime / Cache / CDN / Image Pipeline | PLANNED |
| N1.7 | Localization & Multilingual 2.0 | PLANNED |
| N1.8 | SEO + AI Visibility Intelligence | PLANNED |
| N1.9 | Forms / Lead Capture / Workflow Builder | PLANNED |
| N1.10 | Commerce 2.0 / Storefront / Checkout | PLANNED |
| N1.11 | Customer & Member Portal Builder | PLANNED |
| N1.12 | Search 2.0 / Facets / Index Providers | PLANNED |
| N1.13 | Media / DAM Studio 2.0 | PLANNED |
| N1.14 | Collaboration / Comments / Presence / Approvals | PLANNED |
| N1.15 | Nexora AI Platform / Agents | PLANNED |
| N1.16 | Observability / Diagnostics / Operations Center | PLANNED |
| N1.17 | Developer Experience / CLI / SDK / Docs | PLANNED |
| N1.18 | Migration Center — WordPress / Webflow / Drupal / Shopify adapters | PLANNED |
| N1.19 | Marketplace 2.0 / Publisher Economy | PLANNED |
| N1.20 | Sentinel 2.0 / Runtime Security | PLANNED |
| N1.21 | Enterprise Governance 2.0 | PLANNED |
| N1.22 | Accessibility / International Certification | PLANNED |
| N1.23 | Performance / Core Web Vitals Certification | PLANNED |
| N1.24 | Public API Platform / REST / GraphQL / OAuth / Webhooks | PLANNED |
| N1.25 | Import/Export / Configuration as Code | PLANNED |
| N1.26 | Update / Rollback / Disaster Recovery Platform | PLANNED |
| N2.0 | Stable Production Platform Release | BLOCKED UNTIL N1.x CERTIFICATION |

## External App / Extension / Theme Roadmap

These product families can be first-party Nexora packages later, but they must use public Nexora Contracts/Capabilities just like third-party packages. They are not allowed to introduce private shortcuts into Core.

| External ID | Package family | Packaging direction | Status |
|---|---|---|---|
| EXT-B01 | Books / Manuscripts / Chapters / Editions / EPUB / print exports | App/Extension + optional publishing themes | PLANNED EXTERNAL |
| EXT-P01 | Professional Profile / CV / Resume / Biography / portfolio publishing | App/Extension + profile/CV themes | PLANNED EXTERNAL |
| EXT-L01 | LMS / Courses / Lessons / Quizzes / Progress | App/Extension | PLANNED EXTERNAL |
| EXT-BK01 | Booking / Services / Staff / Availability / Appointments | App/Extension | PLANNED EXTERNAL |
| EXT-PR01 | Projects / Tasks / Boards / Milestones / Time tracking | App/Extension | PLANNED EXTERNAL |









## N1.0 chunk execution status

| Chunk | Scope | Status |
|---|---|---|
| N1.0-C1 | Target environment + dependency graph + frontend build | APPLIED / SOURCE PASS — TARGET PASS REQUIRED |
| N1.0-C2 | Laravel runtime + core database certification | APPLIED / SOURCE PASS — TARGET PASS REQUIRED |
| N1.0-C3 | Strict MySQL/MariaDB/PostgreSQL/SQLite/SQL Server matrix | APPLIED / SOURCE PASS — TARGET PASS REQUIRED |
| N1.0-C4 | Zero-install + upgrade + backup/restore recovery | APPLIED / SOURCE PASS — TARGET EVIDENCE REQUIRED |
| N1.0-C5 | Browser/A11y/RTL + HTTP/performance evidence | APPLIED / SOURCE PASS — TARGET EVIDENCE REQUIRED |
| N1.0-C6 | Real multi-node HA + final evidence + production release | APPLIED / SOURCE PASS — TARGET HA + FINAL EVIDENCE REQUIRED |

### N1.0-C1 Target Environment + Dependencies

C1 consolidates the former RC24-RC27 prerequisite/dependency work into one executable chunk. C1 was introduced at `1.0.0-rc.28`; the current platform source is `1.0.0-rc.43`; C1-C6 execution layers plus the maximum-closure safeguards are code-ready, while target evidence remains pending.

C1 owns: active target PHP/extensions, Composer/Node/npm policy, reviewed `composer.lock` + `package-lock.json`, locked installs, installed dependency graph verification, Inertia source contract, TypeScript, Vitest, Vite production build, dependency provenance/audit and RC9 build-asset budgets. Composer dependency installation uses `--no-scripts`; Laravel/application runtime boot is deliberately deferred to C2.

C1 never refreshes lockfiles, never accepts reviewed locks, never downloads PHP/Composer, and never modifies php.ini unless the operator explicitly invokes the existing Windows/Laragon-only `--apply-extensions` path.

Primary target command:

```bat
scripts\n1-c1-dependency-certify.bat --install-deps
```

C1 remains target-blocked until dependency-backed PASS on the current exact source/locks. C2 is code-ready but refuses runtime/database work until that C1 evidence is PASS. N1.1 remains blocked until C1-C6 all pass.


### N1.0-C2 Laravel Runtime + Core Database Certification

C2 is implemented as a fail-fast dependency-backed runtime/database chunk. It requires exact current-source C1 PASS evidence before executing Laravel or destructive database operations.

C2 owns: package discovery, `artisan about`, routes/scheduler boot, safe isolated certification database preparation, minimum DB-version doctor, `migrate:fresh --seed`, repeated seed, full migration reset/rebuild, runtime sync/cache, explicit PHPUnit certification DB binding, the full Laravel/PHPUnit suite, Pint, environment/filesystem/transfer/runtime/concurrency doctors and optimized Laravel cache boot.

C2 does **not** install/update dependencies, accept/refresh lockfiles, run the five-database matrix, or collect zero-install/browser/backup/HA evidence. C3 owns the strict five-family matrix; C4-C6 own operator/release evidence.

Primary target command after C1 PASS:

```bat
scripts\n1-c2-laravel-runtime-certify.bat
```

The default primary database is the isolated `nexora_certification` target. Credentials are supplied through `NEXORA_CERT_DB_*`; unsafe database names are rejected before any destructive command.



### N1.0-C6 Multi-node HA + Final Release Closure

C6 is code-ready and fail-closed behind exact-source C1-C5 PASS evidence. It requires a real target URL, `nexora:ha:status`, `nexora:ha:rehearse`, and operator evidence from at least two independent active nodes. Ten HA observations are mandatory: shared cache/session/object storage, async queue distribution, single scheduler leader, scheduler failover, node drain readiness, worker drain/restart, node failure recovery and version consistency.

Final evidence now requires C1-C5 chunk manifests in addition to the existing operator/automated evidence. After HA evidence is sealed, C6 runs unified operator evidence intake, final exact-version automated certification, all 11 closure domains, production packaging and independent ZIP re-verification. `N1.0 DONE` is written only when the final closure reports `n1_0_done=true`.

Primary command after C1-C5 target PASS:

```bat
scripts\n1-c6-final-certify.bat --base-url=https://TARGET --evidence=<C6-HA-KIT-DIR>
```

## N1.0 Release Candidate certification — RC27 Laragon prerequisite remediation status

RC27 does not add a product feature. It adds an explicit, reversible remediation layer for the prerequisite blockers isolated by the RC26 one-command target gate:

- platform version `1.0.0-rc.27`;
- `scripts/target-prerequisite-remediate.*` inspects the active PHP binary, loaded `php.ini`, extension directory, required extension DLL availability and Laragon Composer candidates without downloading or installing software;
- review-only is the default; `--apply-extensions` is explicit, Windows/Laragon-only, enables only required extensions whose matching DLL exists, checksum-verifies a timestamped `php.ini` backup and the published result, then requires a Laragon restart;
- if trusted Laragon Composer files exist but are not callable on PATH, RC27 writes a session-only `nexora-target-env.cmd` helper under protected runtime storage rather than changing global/user PATH;
- target intake and the RC26 orchestrator now surface the remediation command when prerequisite intake fails; lockfile acceptance remains a separate explicit maintainer action.
- `scripts/target-certification-orchestrator.{bat,ps1,sh}` runs prerequisite intake, reviewed-lock verification, target runtime readiness/full isolated certification, optional operator-evidence validation/sealing, closure dashboard and optional final production sealing in a fixed order;
- the orchestrator never accepts dependency locks on behalf of a maintainer, never edits php.ini, never runs `composer update`/unlocked npm install, and never calls destructive database reset/fresh commands directly;
- `--final` requires `--full`; operator evidence sealing requires explicit `--evidence=<path> --seal-evidence`;
- each run writes redacted first-blocker evidence under `storage/app/nexora/target-orchestrator/<run-id>/`, which is forbidden from strict source-zero and production archives;
- closure dashboard failures remain informational during readiness/full target work unless `--final` is requested, so pending browser/restore/HA evidence is not confused with a failed dependency-backed runtime test.

N1.0 remains **CERTIFYING — TARGET EXECUTION / C1-C6 REAL EVIDENCE**. C1 target PASS is still required before C2 can execute; five-DB matrix, zero-install/recovery, upgrade rehearsal, browser/accessibility/RTL, HTTP/performance, backup/restore, real multi-node HA, final evidence and production ZIP remain after C2. N1.1 remains blocked.

## N1.0 Release Candidate certification — RC25 unified target evidence status

RC25 adds a fail-closed operator evidence intake and closure dashboard without changing product scope:

- platform version `1.0.0-rc.25`; RC24 prerequisite/reviewed-lock intake remains mandatory before dependency-backed work;
- `target-evidence-intake` accepts a bounded directory/ZIP of known evidence, validates exact platform/source contracts, rejects archive traversal/symlinks and records evidence hashes;
- sealing imported evidence requires the current reviewed `composer.lock` / `package-lock.json` attestation, so evidence collection cannot be detached from the reviewed dependency graph;
- the five operator-owned domains (zero-install/recovery, upgrade rehearsal, browser/A11y/RTL, backup/restore and real multi-node HA) can be required together with `--require-complete`;
- `closure-dashboard.php` combines reviewed locks, target-runtime state and the existing eleven-domain closure ledger; it never upgrades PENDING/BLOCKED evidence to PASS;
- final evidence generation requires a matching RC25 target-evidence intake manifest, preserving the existing exact source/build/database/restore/HA requirements.

N1.0 remains **CERTIFYING — TARGET EXECUTION / C1-C6 REAL EVIDENCE**, not DONE. C1 target PASS is still the immediate prerequisite; after C2 passes, C3-C6 remain. N1.1 remains blocked behind N1.0 PASS.

## N1.0 Release Candidate certification — RC24 target prerequisite / lock intake status

N1.0 is intentionally a stabilization/certification gate rather than a broad feature release. RC24 adds Laragon-aware prerequisite intake and explicit reviewed-lock attestation on top of RC23 target bootstrap/resume and RC22 fail-fast runtime closure; it preserves RC21 frontend stabilization, RC20 final-closure integrity and RC19 concurrency protections:

- platform version `1.0.0-rc.24`; built-in theme/Forge package compatibility window remains compatible with N0.34-controlled packages through the 1.x release-candidate line;
- one cross-platform `scripts/certify-release.php` runner for source preflight, package discovery, route/scheduler boot, isolated destructive certification database, migrations, seed, runtime sync/cache, PHP tests, Pint, TypeScript typecheck, frontend tests, production build, framework optimize smoke, optional HTTP smoke and certified production packaging;
- `quality-check.bat`, PowerShell and shell wrappers now call the same certification engine instead of maintaining drifting command lists;
- certification reports persist under protected runtime storage with explicit PASS/FAIL/SKIP evidence and separate manual/operator gates;
- production release builder reads the platform version dynamically, requires a matching `certification-pass` report, verifies the built ZIP and never reuses the stale N0.26 filename/version;
- true browser zero-install preparation no longer pre-creates a root `.env`; zero-state verification proves installer/deployment locks and `.env` are absent before the browser flow;
- dedicated certification database creation supports MySQL/MariaDB, PostgreSQL, SQLite and SQL Server when their PDO drivers/credentials are available, while refusing unsafe destructive database names;
- source preflight now audits required runtime files/extensions, writable directories, migration portability/identifier lengths, unnamed overlapping scheduler callbacks, Admin UI raw controls/native dates, internal TypeScript imports and stale architecture-test platform versions;
- stale N0.28–N0.34 architecture assertions no longer freeze the mutable top-level platform version; historical tests now verify their feature boundaries while N1.0 owns RC platform-version certification.

- Core module graph certification now runs before Laravel package discovery and rejects missing modules, duplicate identifiers/classes, unregistered Core modules, self/circular dependencies and incompatible internal version constraints.
- RC2 fixes the Enterprise dependency typo from the nonexistent `nexora.identity` module to the registered `nexora.identity-access ^0.5` module, which was blocking Composer `package:discover`.

- RC3 fixes `RuntimeNodeHeartbeat` to use Laravel's standard two-argument middleware `handle(Request, Closure)` contract and constructor-inject runtime services, eliminating the reported four-argument middleware crash.
- RC3 performs the first dependency-backed frontend error sweep from the target Laragon build log: 76 TypeScript errors across 11 files were traced to Inertia v3 form-data constraints, non-chainable `transform()`, request payload typing, sidebar `NavLink` misuse, and recursive Writer payload typing.
- RC3 adds `scripts/frontend-contract-verify.php` and certification/Source Guard rules so middleware signature regressions, chained Inertia transforms, known `Record<string, unknown>` form regressions, and horizontal NavLink misuse fail before production packaging.

- RC4 adds a dependency-free Laravel runtime-contract analyzer across every local HTTP middleware, bootstrap middleware declaration/custom alias, scheduled command/callback, queued job and service provider before framework boot.
- RC4 rejects container/service dependencies placed after middleware `$next`, unknown custom route aliases, scheduled commands that are not actually registered, unnamed/duplicate scheduler callbacks, non-heartbeat cluster schedules that bypass scheduler leadership, queue jobs that reach into HTTP request/session context, and invalid provider `register()`/`boot()` parameter contracts.
- RC4 adds dependency-backed certification tests that explicitly boot `route:list` and `schedule:list`, while Source Guard and RC preflight now run the same runtime-contract analyzer before Composer/Laravel integration gates.

- RC5 adds dependency-free database contract certification across all migrations and seeders: duplicate/missing tables, FK target ordering, rollback `dropIfExists()` coverage, PostgreSQL-safe identifier length, forbidden DB-specific schema primitives, destructive certification database guards, and exact tenant model/table parity.
- RC5 makes Demo users and Helpdesk SLA defaults deterministic/idempotent so repeated `db:seed` does not create row-count drift or skip missing defaults.
- RC5 expands the full certification runner and database matrix with repeated seeding plus full `migrate:reset` → `migrate` → `db:seed` round-trips before runtime/tests, and adds a Compatibility suite that checks all 51 tenant roots receive `tenant_id` with no orphan seeded rows.

- RC6 adds a dependency-free security contract gate covering auth-route groups, CSRF exceptions, encrypted/HTTP-only/secure session defaults, authentication session rotation, password-session revocation, SSO state/membership checks, SCIM bearer/hash-only semantics, inbound webhook replay/signature limits, and explicit Admin tenant-bound route IDOR protection.
- RC6 hardens runtime behavior: registration and SSO now rotate session IDs/CSRF tokens; password change/reset rotate remember tokens and revoke database sessions; failed-login audits no longer persist raw submitted email; forgot-password responses do not reveal account existence; and globally suspended users cannot sign in through password or SSO paths.
- RC6 fixes multi-tenant identity isolation: organization SCIM suspension changes only that organization membership, not the shared global user account; Admin entry now requires both platform `admin.access` and current enterprise-role authorization.
- RC6 introduces `TenantExists` / `TenantMemberExists` request validation and removes raw `exists:nx_*` references for tenant-owned IDs across Commerce, CRM, Publishing, Media, Distribution, Membership, Helpdesk and Studio mutation paths.
- RC7 adds dependency-free zero-install/deployment/recovery contracts; true-zero runners now remove root/protected environment state, dependencies/build assets and private bootstrap tools before browser rehearsal.
- RC7 adds lock-aware stale deployment recovery and archives interrupted deployment state while persisting completion before releasing the OS lock.
- RC7 adds database-target-bound main-installer recovery journals; failed/interrupted protected runs can resume idempotent migrations/seeding for the same database without a second destructive reset, while different targets are rejected.
- RC7 hardens production release hygiene by excluding deployment access keys, run journals, installer/deployment control directories and database-backup state from certified customer packages.

- RC8 adds dependency-free browser/UX/accessibility/RTL source contracts covering skip navigation, main landmarks, focus visibility, reduced motion, forced colors, logical RTL utilities, dialog/input/DataTable/search/live-region semantics and feature-control governance.
- RC8 hardens shared inputs/textareas with described error/hint relationships, the command palette with modal focus behavior, DataTable with keyboard/sort/loading/pagination semantics, and core layout with logical start/end positioning.
- RC8 adds a fail-closed operator browser-evidence schema for mobile/tablet/desktop × LTR/RTL × light/dark plus keyboard/focus/zoom/reduced-motion/forced-color checks.

- RC9 adds dependency-free performance/packaging contracts for response cache/security headers, Vite production settings, Apache compression/immutable build caching, static-asset ceilings, lazy preview delivery and centralized release-file policy.
- RC9 adds dependency-backed `performance-build-verify.php`: the Vite manifest, hashed JS/CSS assets, source-map absence, local-path leakage, per-asset totals/counts and gzip JS totals must remain inside release budgets before packaging.
- RC9 hardens dynamic HTTP responses with `ApplyPerformanceHeaders`: Admin/Auth/Installer/SSO/SCIM/webhook/health and authenticated/error responses are explicitly `no-store`; secure responses can emit HSTS; public dynamic caching remains conservative until N1.6.
- RC9 hardens Apache delivery with Brotli-or-gzip text compression, one-year immutable caching for `/build/*` hashed assets and bounded caching for stable public brand/icon assets.
- RC9 production ZIP generation now reads `config/nexora-release.php`, requires a matching build-asset PASS report, records performance/release-policy hashes, verifies all required entries and rejects forbidden files/prefixes after reopening the ZIP.
- RC9 optimized-runtime certification now boots `artisan about`, routes and scheduler while Laravel optimization caches are active, and optional HTTP smoke checks latency ceilings plus request/security/cache headers before caches are cleared.


- RC10 adds strict HA readiness checks for shared cache, shared server-side sessions, asynchronous queues, shared object storage, two-or-more fresh active nodes, identical runtime versions and an active scheduler leader lease.
- RC10 adds safe application-level HA rehearsal for lease exclusion/failover and deep readiness, while explicitly refusing to treat that single-runtime rehearsal as proof of multi-host HA.
- RC10 adds guarded backup/restore rehearsal validation and fail-closed operator evidence requiring a real restore to a disposable target with checksum, migration, health, login and data spot checks.
- RC10 adds fail-closed multi-node evidence for shared cache/session/storage, queue distribution, scheduler single-leader/failover, node drain/readiness, worker restart and node failure recovery.
- RC10 adds `final-evidence.json`, which SHA-256 seals browser, HTTP performance, frontend build, backup/restore and HA evidence for the exact platform version. Certified production ZIP generation now requires that final evidence report to be PASS.


- RC11 adds `scripts/final-target-run.php` plus Windows/PowerShell/Linux wrappers for one-command prepare/final/status modes without weakening the existing certification gates.
- RC11 adds `closure-status.json` / `closure-status.md`, which fail closed across automated certification, build assets, target HTTP performance, browser matrix, disposable-target restore, multi-node HA, final evidence and production package sealing.
- RC11 keeps production packaging locked in `--final` mode until `NEXORA_CERT_FINAL_EVIDENCE=1` evidence passes for the exact platform version; placeholder/missing evidence remains BLOCKED rather than SKIP/PASS.
- RC12 adds `scripts/target-diagnostics.php` plus BAT/PowerShell/Linux wrappers. It captures source contracts, toolchain identity, Composer/Laravel bootstrap, frontend type/test/build output, optional full isolated certification and the final closure ledger into redacted per-step logs.
- RC12 diagnostics never dumps `.env` or ambient environment variables; password/token/cookie-shaped values are redacted, and the collector still produces a bundle when commands fail so the first real Laragon blocker can be diagnosed without losing later context.
- `--install-deps` captures Composer/npm installation failures. `--full` additionally invokes the existing isolated certification database/test runner; it does not weaken destructive database-name safeguards.
- RC13 adds `nexora:upgrade:preflight`, `nexora:upgrade:plan`, `nexora:upgrade:apply --yes` and `nexora:upgrade:status` for existing installations. The current source tree never assumes that replacing files makes the database safe to migrate.
- RC13 compares the installed lock version with the target source tree, rejects downgrades/out-of-window sources, checks pending core migrations, validates enabled extension and active-theme Nexora constraints, and surfaces forward-only extension migration rollback barriers.
- RC13 requires a checksum-verified runtime backup or fail-closed external backup evidence for the exact source version before creating a ready upgrade plan. Plans expire and are bound to the exact target version.
- RC13 enters maintenance mode before protected migrations, runs only forward `migrate --force` plus runtime sync/cache, updates installed-lock provenance atomically after success, and deliberately does **not** execute blind `migrate:rollback/reset/fresh` on failure. Protected-stage failures remain in maintenance mode for verified backup restoration.
- Production release manifests now publish the supported source range, verified-backup requirement and `automatic_database_rollback=false`; runtime upgrade plans/history are forbidden from customer ZIPs and true-zero rehearsals clear all upgrade state.

### N1.0 closure gate


- RC14 adds `nexora:environment:doctor`, a config-cache-safe runtime audit that never emits secret values; it validates the authoritative environment source, installed metadata/marker parity, APP_ENV/APP_DEBUG/APP_KEY/APP_URL, secure session settings, database selection, persistence drivers and stale config-cache timestamps.
- RC14 makes installed environment-marker selection fail closed: an explicit root/fallback marker may no longer silently fall through to a different environment file when the marked source is missing. Browser and CLI entrypoints now honor the same bootstrap failure boundary.
- RC14 invalidates `bootstrap/cache/config.php` whenever the installer writes environment values, adds a safe `.env.production.example`, prohibits runtime `env()` calls outside `config/*.php`, and makes RC13 upgrade compatibility include the environment doctor so stale/unsafe configuration blocks an upgrade plan.
- RC14 production-release policy retains the environment policy/template while continuing to forbid real `.env` and protected fallback secrets from customer archives.

- RC15 introduces `config/nexora-dependencies.php` plus dependency source/runtime verifiers. Full certification requires both `composer.lock` and `package-lock.json`; source-only certification may report them as pending but can never upgrade that state to a production PASS.
- RC15 removes unlocked npm fallback from final target certification. Dependency installation is deterministic: Composer installs the locked graph and npm uses `npm ci`; certification never creates or mutates lockfiles. A separate maintainer-only lock refresh workflow exists for intentional dependency updates.
- RC15 records and enforces certified PHP/Composer/Node/npm runtime ranges, checks package-manager metadata, and adds Composer/npm vulnerability audit evidence bound to the exact SHA-256 hashes of both lockfiles.
- RC15 production packaging now requires matching `dependency-audit.json` and `dependency-provenance.json` evidence and seals the dependency-policy, audit/provenance reports, Composer lock and npm lock hashes into `nexora-release.json`.
- The current RC15 source package intentionally remains unable to claim dependency-backed PASS while lockfiles/dependencies are absent on this execution host; the target Laragon run must create/review the lockfiles on a trusted maintainer machine and commit them before final certification.

- RC16 adds `AtomicFileWriter`, `PortablePath`, `FilesystemDoctor` and `nexora:filesystem:doctor`; critical installation/environment/node/upgrade/backup metadata writes use destination-local temporary files, flush/fsync where available, and fail closed instead of silently falling back to partial in-place writes.
- RC16 source certification rejects case-insensitive repository collisions, Windows reserved/trailing-dot/trailing-space/colon path components, over-budget relative paths, PSR-4 filename/class-case drift, missing case-sensitive `App\...` imports and hard-coded backslash separators inside Laravel path helpers.
- Theme and Extension package installation now rejects archive path traversal, Windows-nonportable names, case-insensitive duplicate paths and ZIP symbolic-link entries before publication, preventing packages that behave differently on Windows versus Linux.
- The pre-Laravel deployment/installer bootstrap uses an equivalent dependency-free atomic-state path for deployment access/state/recovery journals and bootstrap APP_KEY persistence. Mutable installation journals retain explicit `flock` concurrency and now flush/fsync state writes.
- RC16 retains the RC15 rule that missing reviewed lockfiles/dependencies block dependency-backed certification; filesystem source PASS is not a substitute for real Laragon/Linux runtime evidence.

- RC17 adds `config/nexora-transfers.php`, `TransferSafety`, `TransferDoctor` and `php artisan nexora:transfer:doctor`; protected transfer staging uses bounded chunked copies, destination-local atomic publication, partial-write loops, free-space preflight and fail-closed cleanup.
- Media uploads now verify stored byte count and streaming SHA-256 after publication, clean failed/partial objects, and cap GD image-variant source bytes before in-memory decode instead of claiming unbounded streaming image transforms.
- Marketplace downloads now use protected Nexora temporary storage, response progress/Content-Length limits and checksum verification; Theme/Extension ZIP installers enforce source, entry-count, per-entry, total-uncompressed and compression-ratio budgets before streamed extraction.
- Runtime database backup publication/verification no longer loads complete backup artifacts into PHP memory; installer MySQL/SQLite backup staging uses bounded writes, flush/fsync, SHA-256 metadata and atomic final publication.
- RC17 source certification rejects unbounded archive extraction/full-backup memory loads, keeps transfer staging out of release artifacts, and treats disk-space preflight as advisory only: a mid-stream disk/quota/write failure must still fail closed without publishing a partial destination.
- RC18 adds `config/nexora-runtime.php`, explicit trusted-proxy/request-size middleware, `php artisan nexora:runtime:doctor`, queue timeout/retry alignment, tenant-context cleanup for long-lived workers, graceful memory restart and cancellable SEO crawl execution.

RC10 was the final feature-scale implementation block inside N1.0. RC11–RC25 are closure/diagnostic/operational-hardening passes and do not introduce a new product domain. The next action is the **target-environment final evidence run**: Composer/npm install, Laravel migrations/seeds/tests, real Vite build + RC9 asset budgets, RC7 zero-install/recovery rehearsal, RC8 browser/accessibility/RTL matrix, RC9 target HTTP/header performance evidence, RC10 disposable-target backup/restore rehearsal and RC10 real multi-node HA rehearsal must all be green.



- RC20 removes forced PHPUnit DB overrides so the certification runner's isolated DB selection cannot be silently replaced by `phpunit.xml`; Feature/Compatibility tests assert the exact selected connection/database.
- RC20 seals automated certification, build/HTTP evidence, final evidence and production packaging to a deterministic source-tree SHA-256 attestation.
- Final mode now requires a strict MySQL + MariaDB + PostgreSQL + SQLite + SQL Server matrix and runs selected high-risk Commerce/CRM/Automation/Enterprise/Studio/concurrency flows on every available driver.
- Zero-install/recovery and existing-install upgrade rehearsals are first-class fail-closed closure domains rather than prose-only manual gates.
- Installer/runtime database checks enforce minimum supported server versions and expose `php artisan nexora:database:doctor`.
- Production closure independently reopens the ZIP, checks required/forbidden paths, internal lock/build hashes, release manifest source digest and SHA-256 sidecar.
- RC19 adds bounded deadlock/serialization retries through a central `ConcurrencyGuard`, a portable transaction-scoped `nx_concurrency_mutexes` row-lock table for invariants that have no pre-existing row, and `php artisan nexora:concurrency:doctor` for target verification.
- RC19 also serializes due-article publishing with a locked re-read/recheck before status transition, preventing duplicate scheduler/failover publication revisions.
- Commerce payment/refund idempotency checks now execute inside guarded transactions; database unique constraints remain the final duplicate arbiter, while order/invoice/payment rows are locked before aggregate money fields change so concurrent callbacks cannot lose paid/refunded increments.
- Automation event fan-out is atomic: idempotent event recovery locks the event row, workflow runs are created in the same transaction, jobs dispatch only after commit, and a failed fan-out can be safely retried without marking an event processed with missing runs.
- Inbound webhook receipt persistence and automation emission now share one transaction; duplicate receipt races converge through the unique endpoint/idempotency constraint instead of producing a 500 or silently losing the automation event.
- Workflow, outbound-webhook and newsletter workers take transaction-backed processing claims with stale-claim TTLs. Concurrent duplicate workers return without repeating the same action. External SMTP/HTTP effects remain explicitly **at-least-once**; Nexora does not claim impossible cross-provider exactly-once semantics.
- Document and Studio optimistic concurrency checks now run against `lockForUpdate()` rows inside the write transaction, closing the time-of-check/time-of-use overwrite window. Document revision numbering and Studio revision numbering are serialized by the parent-row lock.

- RC21 formalizes the real Laragon frontend build failure as a release-candidate gate: the supplied target log reported 76 TypeScript errors across 11 Admin files.
- Inertia v3 `useForm` nested payloads now use `FormDataConvertible`-compatible values, router helpers use `RequestPayload`, and recursive Writer payloads stay FormData-compatible instead of `Record<string, unknown>`.
- `useForm.transform()` is treated as a void mutator and may not be chained into `post/put/patch/delete`; known normalization sites transform first and submit separately.
- Helpdesk/Membership horizontal navigation uses shared `ButtonLink` instead of violating the sidebar `NavLink` label+icon contract.
- `scripts/inertia-frontend-contract-verify.php` permanently scans Admin TypeScript for these target-build regression classes before package discovery or production packaging.

- RC22 adds `scripts/target-runtime-run.php` plus Windows/PowerShell/Linux wrappers as the fail-fast counterpart to RC12 diagnostics: diagnostics keep collecting after failures, while the target runtime gate stops at the first required blocker by default.
- RC22 refuses unlocked dependency resolution, optionally installs only the reviewed Composer/npm lock graphs, then runs real TypeScript/Vitest/Vite/build-budget gates before Laravel package discovery, route/scheduler boot and all current runtime doctors.
- RC22 `--full` delegates destructive migrations/seeding/PHPUnit to the existing isolated certification database engine; the target-runtime runner itself never runs `migrate:fresh` or `migrate:reset` against the ambient project database.
- RC22 writes redacted per-step logs, machine-readable `first_blocker` evidence and an optional ZIP bundle so the next Laragon failure can be fixed from one artifact rather than copied terminal fragments.

- RC23 adds `scripts/target-environment-bootstrap.php` plus BAT/PowerShell/shell wrappers. It reports active PHP/php.ini, required PHP extensions, Composer/Node/npm range compatibility and reviewed lockfile presence without auto-downloading tools or mutating dependency locks.
- RC23 target-runtime evidence is bound to platform version, exact source-tree SHA-256, Composer/npm lock hashes and installed dependency fingerprints. `--resume-latest` / `--resume=<run-id>` may reuse only selected heavy frontend/install PASS steps when those exact fingerprints still match; Laravel/runtime doctors rerun so environment drift is not hidden.
- RC23 adds `target-runtime-evidence-verify.php` to reject unsafe ZIP paths, wrong-version/source/lock evidence, missing step logs and fake PASS bundles; exact PASS evidence can be sealed under protected certification storage.

- RC24 adds `target-prerequisite-intake` plus BAT/PowerShell/shell wrappers to report the active PHP binary, loaded/scanned php.ini, extension directory, Laragon detection and exact prerequisite remediation without auto-editing or auto-downloading the toolchain.
- RC24 distinguishes lockfile presence from lockfile review: `dependency-lock-review` binds explicit maintainer acceptance to SHA-256 of `composer.json`, `package.json`, `composer.lock` and `package-lock.json`; any manifest/lock drift invalidates the attestation.
- Target runtime and full certification require the reviewed-lock attestation before deterministic dependency installation/certification. Intake/review evidence remains runtime-local, zero-state-cleaned and excluded from production archives.
- Generated target-bootstrap and target-runtime evidence remains excluded from true-zero source packages and certified production archives.

N1.0 remains **CERTIFYING — N1.0 TARGET EXECUTION / MAXIMUM CLOSURE BATCH**, not DONE, until the target bootstrap is green, the fail-fast target runtime/full certification run and the remaining exact-version operator evidence are green, and the certified production ZIP is sealed. N1.1 remains blocked behind N1.0 PASS.

## N0.34 Cloud / HA / Distributed Runtime status

Implemented in N0.34:

- `nexora.cloud-runtime` Core module with node identity/heartbeat, node drain state, distributed coordination leases, runtime metrics, object-storage contract, protected database-backup orchestration and restore planning.
- Single-node deployments remain the default-compatible path; HA posture is reported rather than falsely inferred.
- Database-backed scheduler leader lease ensures only one healthy cluster node executes leader-gated scheduled maintenance while every node still publishes its own heartbeat.
- Existing scheduled publishing, distribution, analytics, crawler, automation, membership and Helpdesk maintenance is leader-gated without changing each feature's business logic.
- Shared `DistributedLockContract` backed by Laravel atomic cache locks for extension-safe critical sections.
- Shared `ObjectStorageContract` backed by the configured Laravel filesystem disk; local storage remains valid for one node and S3-compatible/shared disks can be configured for horizontal deployments.
- Public minimal `/health/live` and `/health/ready` probes plus deep Admin readiness checks. Draining or maintenance nodes intentionally fail readiness with HTTP 503 so a load balancer can remove them without killing in-flight requests.
- Runtime node heartbeats from web traffic, scheduler cron and queue job processing; stale-node detection remains observational rather than destructive.
- Operational metrics for database/cache latency, queue backlog, failed jobs and peak process memory with retention pruning.
- Protected database backup orchestration reuses the existing safe in-app MySQL/MariaDB/SQLite backup strategies, seals artifacts with SHA-256, and records failed/unsupported driver attempts instead of pretending a backup exists.
- Restore planning verifies backup checksum, issues a one-time confirmation and produces an offline drain/maintenance/restore/recovery sequence. N0.34 deliberately does not execute unattended destructive database restores.
- Admin `Cloud & Operations` workspace for topology, HA readiness warnings, nodes, leases, metrics, backup verification/download and restore plans.
- Forward-only N0.34 migration, Unit/Feature/Architecture tests and Source Guard regression gates.
- Current feature roadmap is complete through N0.34. N1.0 is a certification/stabilization gate, not another broad product-feature block.

## N0.33 Enterprise tenancy status

Implemented in N0.33:

- `nexora.enterprise` Core module with organizations, organization roles/members, verified domains, invitations, SSO provider records, SCIM tokens, impersonation sessions, enterprise audit and tenant settings.
- `tenant_id` boundary and central `BelongsToTenant` global scope across content, media, publishing, Commerce, CRM, Membership, Helpdesk, Automation, Discovery, SEO, Studio, Newsletter and webhook roots.
- Existing records are backfilled to a default organization; fresh/legacy Super Admin ownership and legacy Administrator membership are handled without exposing ordinary users.
- Organization-specific settings overlay the existing global Settings contract while preserving global defaults.
- Two-key authorization: platform RBAC grants the maximum permission set; enterprise roles can only restrict access in the current organization.
- Verified-domain tenant resolution, organization switcher and domain DNS TXT ownership verification.
- OIDC/SAML adapter contract and registry with encrypted provider secrets; no vendor-specific identity SDK is hard-coded in Core.
- SCIM bearer-token provisioning foundation with hash-only token persistence and organization membership provisioning.
- Reason-bound impersonation with target membership validation, persistent actor/target session records, enterprise audit events and a visible stop-impersonation Admin banner.
- Tenant context restoration for queue roots used by workflows, SEO crawls, newsletter delivery and webhooks.
- N0.33 forward-only migration, Unit/Feature/Architecture tests and Source Guard regression gates.

## N0.32 Membership + Helpdesk status

Implemented in N0.32:

- Separate `nexora.membership` and `nexora.helpdesk` Core modules with independent runtime capabilities and Admin permissions.
- Membership plans, typed entitlements, direct memberships, Commerce customer/subscription linkage and explicit membership lifecycle events.
- Commerce subscription synchronization only when a Membership plan explicitly maps to a Commerce price; billing identities remain owned by Commerce.
- Central Membership Access Manager for protected Nexora resources. Published Documents can be protected by plan and/or entitlement policy without moving content ownership into Membership.
- Membership expiry maintenance command and hourly schedule.
- Helpdesk tickets with requester links to Nexora users, CRM contacts and Commerce customers without merging those identities.
- Ticket conversations, internal notes, assignment, status, priority, categories, SLA policy linkage and append-only ticket events.
- First-response and resolution SLA deadlines with scheduled breach refresh. N0.32 uses elapsed-time targets; business-hours calendar evaluation is reserved behind future SLA adapters.
- Membership and Helpdesk lifecycle events are exposed to the existing Automation trigger registry.
- Admin workspaces for Membership overview/plans/members/access policies and Helpdesk overview/tickets/settings.
- Shared DataTable, Select and DateTimePicker remain the only feature UI foundations; no raw/native date-time or ad-hoc interactive controls are introduced.
- LMS, Booking and Projects remain external package families and do not gain private Core shortcuts.
- N0.32 forward-only migration, Unit/Feature/Architecture tests and Source Guard regression gates.


## N0.31 CRM Foundation status

Implemented in N0.31:

- `nexora.crm` Core module with Organizations, Contacts, Leads, Opportunities, Pipelines/Stages, Activities, Notes, timeline events and typed custom-field foundations.
- CRM identities remain independent from Commerce billing identities; `nx_crm_commerce_links` provides an explicit relationship without mutating historical billing/customer records.
- Default Sales Pipeline with New, Qualified, Proposal, Negotiation, Won and Lost stages.
- Lead conversion and opportunity stage changes execute through transaction-safe services, row locks, stage history, timeline records and Automation events.
- Opportunity monetary values use integer minor units and ISO currency codes instead of floating-point values.
- Activity provider registry is extension-safe and provider-neutral; Gmail/Outlook/etc. SDKs and OAuth secrets are not hard-coded into CRM Core.
- CRM Admin workspaces for overview, organizations, contacts, leads, opportunities, Commerce links and settings.
- Shared DataTable, Select and DateTimePicker remain the only UI interaction foundations for CRM feature screens.
- Admin global Search gains CRM destinations and organization/contact/opportunity results subject to `crm.view`.
- Explicit runtime capabilities and human permissions for CRM domains.
- N0.31 forward-only migration, Unit/Feature/Architecture tests and Source Guard regression gates.


## N0.29 Extensions Lifecycle, Forge SDK & Marketplace status

Implemented in N0.29:

- `nexora.extensions` Core module for verified extension/app/integration/Studio-pack lifecycle management.
- Install is allowed only from an immutable Supply Chain artifact whose Sentinel scan decision is `ALLOW`; Marketplace downloads never bypass quarantine.
- Extension package identity/version/content-digest immutability prevents same-version replacement with different code.
- Nexora compatibility constraints and extension dependency constraints are validated before activation.
- Explicit capability grants: requested runtime capabilities remain denied until a human grants registered capabilities; unknown capabilities are shown as unavailable rather than silently accepted.
- Runtime modes distinguish declarative packages from policy-gated `trusted-php`; trusted PHP activation/migrations require the N0.28 execution policy to permit execution. N0.29 does not automatically `require` arbitrary third-party PHP on every request.
- Forward-only extension migration policy with schema-compatible rollback declaration; unsafe automatic down-migration rollback is deliberately blocked.
- Enable, disable, version switch, guarded rollback and uninstall lifecycle history. Uninstall is blocked while an enabled dependent package exists.
- Installed version history, dependencies, requested capability grants and lifecycle events in the Admin Extensions workspace.
- Marketplace source registry with HTTPS/public-network URL policy, optional trusted-publishers-only enforcement, stable catalog item UUIDs and no automatic redirect following.
- Marketplace artifacts are SHA-256 checked when catalog metadata supplies a digest, then placed into Sentinel quarantine and scanned before installation becomes available.
- Forge scaffolding command (`nexora:make:extension`) and extension inventory command (`nexora:extension:list`) for developer workflows.
- Shared DataTable behavior standardized with sticky table headers and a sticky pagination/footer surface.
- Shared Select rebuilt on React Aria selection primitives without generic action-button scale/brightness feedback.
- Shared DatePicker, DateTimePicker and TimePicker use the existing React Aria/@internationalized/date primitives; feature pages no longer use raw browser date/time inputs.
- N0.29 forward-only migration, Unit/Feature/Architecture tests and Source Guard regression gates.


## N0.28 Sentinel Supply-Chain Security & Stability status

Implemented in N0.28:

- P0 scheduler regression fix: named callback events before `withoutOverlapping()`, preventing Laravel package discovery from crashing while loading scheduled callbacks.
- Media upload hardening for Windows/PHP temporary files using `UploadedFile::getPathname()`, effective PHP/product upload limits, explicit storage-write checks and request-reference error reporting.
- Standardized Inertia/JSON error presentation for HTTP failures so Admin requests no longer fall through to raw Laravel HTML exception screens.
- Shared Admin UI press feedback (`nx-pressable`) for buttons, links, menu/select actions and dismiss controls; processing controls retain explicit busy states.
- Trusted publisher registry containing only Ed25519 public verification keys; private signing keys are never stored by Nexora.
- Deterministic package content digest that excludes `nexora.signature.json` while remaining tamper-evident for all other package content.
- Detached Ed25519 artifact signature verification with publisher-key identity, revocation state and SHA-256 fingerprints.
- CycloneDX SBOM ingestion plus generated Composer/npm dependency inventory when a declared SBOM is not present.
- Provenance ingestion tied to the signed content digest, including builder/source/build-material metadata.
- Persisted artifact, dependency-component and attestation history for every supply-chain evaluation.
- Explicit trust tiers and policy sandbox profiles (`deny-execution`, `restricted`, `capability-gated`, `core`). N0.28 provides a policy/sandbox-adapter foundation and does not claim OS-level process isolation.
- Sentinel scan integration so SBOM/signature/provenance analysis runs after static package inspection without weakening Sentinel's block decision.
- Admin Supply Chain Security workspace with artifact trust inventory, publisher public-key management, revocation and Sentinel scan linkage.
- `nexora:supply-chain:verify` CLI command for re-evaluating an already scanned quarantined package.
- Forward-only N0.28 migration, architecture/unit tests and Source Guard regression boundaries.


## N0.27 Automation, Workflow Engine & Webhooks status

Implemented in N0.27:

- Event-driven `nexora.automation` runtime module with explicit trigger, condition and action registries rather than feature-to-feature controller coupling.
- Workflow definitions with Draft / Active / Paused lifecycle, stable UUID/slug identity, ordered actions, execution counts and last-run visibility.
- Domain trigger foundation for Document created/updated/published, Media uploaded, Newsletter subscription, zero-result public Search, verified inbound Webhook and authorized Manual runs.
- Server-side dotted-payload condition evaluation with explicit operators; workflow conditions never execute PHP/JavaScript or arbitrary expressions.
- Ordered action adapters for Admin notification, signed outbound Webhook and structured Audit Trail events.
- Queue-backed workflow execution with per-step checkpoints; successful steps are not repeated when a later action causes a retry.
- Observable workflow runs and step runs with attempts, context, output, errors and timestamps.
- Reusable outbound Webhook destinations with encrypted HMAC signing secrets, enable/disable state, timeout/retry policy and independent delivery history.
- Outbound protocol headers include event, delivery UUID, timestamp, `Idempotency-Key` and `X-Nexora-Signature: v1=<HMAC-SHA256>` over `timestamp.body`.
- Outbound redirect following disabled; production delivery requires HTTPS and blocks literal/resolved private/reserved network destinations before the request is sent.
- Inbound Webhook endpoints with encrypted secrets, HMAC verification, five-minute replay window, optional IP allow-list storage, 1 MB JSON payload ceiling and idempotent receipt records.
- Webhook secret rotation supports a 15-minute previous-secret grace window so integrations can be rolled safely without long dual-secret exposure.
- Inbound receipts store a keyed source hash instead of a raw source IP and automatically emit only verified `webhook.inbound` automation events.
- One-time Admin display of newly generated Webhook secrets; secrets are encrypted at rest and are not continuously exposed in normal Admin responses.
- Admin `Automation` workspace, workflow builder, condition/action editor, manual run action, run inspection, inbound endpoints and outbound destinations using `@nexora/admin-ui` interactive primitives.
- Automation event/receipt retention defaults plus daily prune command (`nexora:automation:prune`).
- Forward-only N0.27 migration, Unit/Feature/Architecture tests and Source Guard boundaries.


## N0.26 Search, Analytics & SEO Crawler status

Implemented in N0.26:

- First-party searchable projections for Documents/Articles/Blog Posts plus Media Library metadata; public search is restricted to published Documents while Admin global search can surface permitted media/content resources.
- Event-driven document/media indexing plus explicit `nexora:search:reindex` recovery/rebuild command.
- Public `/search` rendered through the active Theme Engine with `noindex,follow` search-result policy and normalized query-demand logging.
- Privacy-aware first-party page-view analytics: raw IP addresses are never persisted, visitor/session identifiers are HMAC-derived, and Global Privacy Control / DNT requests are excluded from tracking.
- Daily analytics aggregates for page views, daily-unique visitors, search demand, zero-result searches, referrals and engaged server-response observations.
- `nexora:analytics:aggregate` command plus hourly idempotent aggregation schedule.
- Same-host SEO crawler seeded from Nexora Sitemap URLs and constrained away from Admin/auth/installer/media utility surfaces.
- Crawler persists runs, pages and evidence-based findings for HTTP failures, response latency, titles/descriptions, canonical policy, indexing conflicts, heading hierarchy, Schema Graph presence, thin visible text observations and duplicate titles/canonicals.
- No synthetic SEO score is generated; the crawler reports individual evidence, severity and URL context.
- Admin `Search & Analytics` workspace with index freshness, content analytics, search-demand gaps, crawl status/findings and crawl detail views.
- Queue-backed Admin crawl start plus immediate CLI crawl command (`nexora:seo:crawl`).
- Search/Analytics/Crawler permissions and runtime capabilities remain separate from user role identity.
- Forward-only N0.26 migration, unit/feature/architecture tests and dynamic RuntimeSynchronizer integration assertions.

## N0.25 Media, Newsletter & Distribution status

Implemented in N0.25:

- First-party Media Library module with typed media records for images, video, audio and documents.
- MIME allow-list and file-size/dimension safety checks; SVG/HTML/PHP/executable active-content uploads remain blocked from the public media pipeline.
- SHA-256 payload identity, server-generated storage filenames and immutable stored media paths.
- Media folders, reusable collections, metadata, alt text, captions, descriptions and focal-point fields.
- Soft-delete Trash, restore and permanent-delete protections; permanent deletion is blocked while an asset still has usage references.
- Media usage registry so Documents/Articles and future modules can identify where assets are referenced.
- Optional GD-powered responsive WebP image variants at controlled widths with pixel-count safeguards.
- Public media delivery endpoint with ETag, immutable caching, MIME headers and no dependency on a public storage symlink.
- Article/Blog hero images can select a Media Library asset while retaining an external URL fallback.
- Writer receives an Image semantic block backed by Media Library asset IDs; public rendering validates asset state and emits responsive `srcset` when variants exist.
- Newsletter audience lists and consent-aware subscribers independent from authenticated user accounts.
- Unsubscribe tokens and a public confirmation flow; GET links never unsubscribe automatically.
- Newsletter campaign drafts/scheduling, delivery records and queue-based per-subscriber delivery jobs through the configured Laravel mail transport.
- Scheduled newsletter command (`nexora:distribution:run`) registered every minute without overlap.
- RSS 2.0 feed (`/feed.xml`) generated from the canonical published Article/Blog Document tree.
- Distribution Adapter Registry with first-party RSS and Newsletter adapters; future external adapters can extend the registry through public contracts.
- Admin Media Library and Newsletter & Distribution workspaces using `@nexora/admin-ui` only for interactive controls.
- Media/Distribution permissions and runtime capabilities remain distinct from human role permissions.
- Forward-only N0.25 migration plus Media, Distribution and Architecture tests.

## N0.22 Blog & Article Publishing status

Implemented in N0.22:

- `Nexora Blog & Article Publishing` runtime module with explicit dependencies on Documents, Editorial, SEO and Themes.
- First-party `Article` and `Blog post` Document Engine types; no second editor/content store was introduced.
- Public author profiles independent from authentication accounts, including guest/external author support.
- Multi-author bylines with stable author ordering.
- Categories, Topics and Tags using a reusable taxonomy layer.
- Ordered content Series with public archive routes and previous/next navigation.
- Article publishing metadata for scheduled publication, featured state, hero image, original source reference, sponsored state and future comment-provider opt-in.
- Scheduled publishing command (`nexora:publishing:run`) plus Laravel scheduler registration.
- Featured publishing items integrated into the public home renderer.
- Public Blog/Article index plus category/topic/tag/series/author archives.
- Related-content selection based on shared publishing taxonomy.
- SEO default URLs (`/articles/{slug}` and `/blog/{slug}`) created through the existing SEO Core rather than duplicate metadata.
- Article/BlogPosting Schema Graph output with WebPage → Article relationship, Person author nodes, publisher, dates, section, keywords and image.
- Author archive ProfilePage + Person structured data foundation.
- Sitemap expansion for Blog, taxonomy archives, Series and public Author pages when they contain published content.
- Admin workspaces for articles/blog posts, publishing settings, author profiles, taxonomy and series.
- Existing Writer receives a Publishing shortcut for Article/Blog document types.
- Publishing permissions/capabilities remain separate from user roles and are synchronized through the existing runtime.
- Forward-only N0.22 migration and publishing Feature/Architecture tests.

## N0.21 Studio status

Implemented in N0.21:

- Versioned Studio canvases, typed elements, responsive styles and allow-listed dynamic bindings.
- Element library, layers/navigator, inspector, canvas, responsive viewports, drag/drop foundation and local undo/redo.
- Reusable Studio components and Theme Design Token awareness.
- Published Document-bound Studio rendering through the Theme Engine, with semantic Document rendering as safe fallback.

## N0.20 Theme Engine status

Implemented in N0.20:

- Sentinel-gated non-executable theme packages and immutable theme versions.
- Built-in safe fallback theme, private preview, activation, switch, rollback and design-token overrides.
- Theme rendering consumes SEO/Document/Studio contracts instead of owning content semantics.

## N0.19 SEO Core status

Implemented in N0.19:

- Central SEO metadata, canonical/robots policy, sitemap, Schema Graph and internal-link analysis.
- Theme-independent structured data and site identity settings.
- Human-readable SEO audits rather than synthetic scores.


## N0.30 Commerce + Billing status

Implemented in N0.30:

- Provider-neutral Commerce runtime module with catalog, pricing, customer, order, invoice, payment, refund, subscription and billing-event capabilities.
- Monetary values persist as integer minor units; form decimal strings are converted without floating-point billing arithmetic.
- Commerce currencies are explicit records with configurable minor units and one default currency. Automatic FX conversion is intentionally not claimed or performed.
- Product catalog supports physical/general products, services and digital products plus one-time/monthly/yearly price definitions.
- Customer billing identities remain independent from authenticated user accounts so guest/external buyers are supported.
- Orders persist price/name/SKU snapshots in line items so later catalog changes cannot rewrite historical order economics.
- Explicit tax-rule engine supports basis-point percentages, optional country/region/tax-code scope and inclusive/exclusive tax handling. It does not claim automatic legal/tax compliance.
- Invoice creation from orders with immutable identifiers and provider-neutral amount-due/paid tracking.
- Payment transaction, refund and subscription lifecycle records with idempotency-key foundations and provider references.
- `PaymentProviderContract` + `PaymentProviderRegistry`; Nexora Core ships with no hard-coded gateway. Verified extensions register providers and retain responsibility for provider secrets/configuration.
- Provider configuration records store non-secret mode/readiness preferences plus secret references, not gateway private keys.
- Commerce events integrate with Automation (`commerce.order.created`, `commerce.order.placed`, `commerce.payment.succeeded`, `commerce.refund.created`).
- Admin Commerce workspace: Overview, Products & Prices, Customers, Orders, Billing and Settings using shared `@nexora/admin-ui` primitives and the sticky shared DataTable.
- Forward-only N0.30 migration, Unit/Feature/Architecture tests and Source Guard boundaries.

## Cross-cutting rules active for every remaining milestone

1. All Admin interactions use `@nexora/admin-ui`; feature code cannot use raw interactive controls or vendor components directly. Shared Select/Date/Time/Table behavior must be extended in the UI library rather than recreated inside a feature page.
2. Every feature includes loading, empty, error, retry, responsive, accessibility and authorization states where applicable.
3. Every database change is a new portable migration; clean migration + seed remains a release gate.
4. Unit, Feature, Architecture and security tests are part of Definition of Done.
5. No plugin/theme/extension executes before Sentinel trust evaluation.
6. Modules communicate through stable Nexora Contracts/Capabilities rather than private implementation coupling.
7. Heavy discovery/security compilation occurs during install/update, not on normal web requests.
8. Themes cannot own or destroy content semantics, editorial data, SEO metadata or publishing identity.
9. SEO metadata and Schema Graph belong to Nexora SEO Core, not Blog/Theme code.
10. Books, CV/Profile, LMS, Booking and Projects remain external packages and may only consume public Nexora APIs.

## RC24 immediate operator order

1. Run `scripts\target-prerequisite-intake.bat` on the trusted Laragon target.
2. Fix the active PHP/Composer prerequisites it reports; rerun until the toolchain portion is green.
3. Run `scripts\refresh-dependency-locks.bat` only for the intentional dependency-lock refresh, then review the generated lockfile diff.
4. Explicitly accept those exact hashes with `scripts\dependency-lock-review.bat --accept --reviewer=<name> --confirm=REVIEWED`.
5. Verify with `scripts\dependency-lock-review.bat --verify-attestation`.
6. Run `scripts\target-runtime-run.bat --install-deps`; after readiness is green, run `scripts\target-runtime-run.bat --full`.
7. Only after target/full certification is green proceed through the remaining N1.0 operator evidence domains. N1.1 remains blocked until N1.0 is fully PASS.


## RC25 immediate operator order

1. Run `scripts\target-prerequisite-intake.bat` on Laragon and close PHP/Composer prerequisites.
2. Generate/review locks and accept them with `scripts\dependency-lock-review.bat --accept --reviewer="..." --confirm=REVIEWED`.
3. Run `scripts\target-runtime-run.bat --install-deps`, then `--full` once readiness is green.
4. Collect real operator evidence using the existing fail-closed templates for zero-install, upgrade, browser/A11y/RTL, backup/restore and multi-node HA.
5. Import those five observed evidence files with `scripts\target-evidence-intake.bat --input=<evidence-folder-or-zip> --seal --require-complete`.
6. Run `php scripts\closure-dashboard.php`, then `scripts\final-target-run.bat --final` only when every domain is PASS.


## N1.0-C3 status

C3 code-side certification is implemented and fail-closed behind exact-source C2 PASS evidence. It requires all five database families (MySQL, MariaDB, PostgreSQL, SQLite, SQL Server), runs migrations/seeds/Compatibility plus Commerce, CRM, Automation, Enterprise, Studio and concurrency high-risk flows, and emits exact-source/lock-bound matrix evidence. C4-C6 remain separate.

**Current N1.0 status: CERTIFYING — N1.0-C3 STRICT FIVE-DATABASE MATRIX.**


## N1.0 Target Execution v2.3 — Maximum Closure Batch

The remaining code-side closure safeguards are consolidated at `1.0.0-rc.39`. `scripts\n1-target-next-action.bat` is a read-only state planner that reports the exact next safe command without accepting locks or fabricating evidence. C5 now requires browser, Web Vitals and HTTP evidence to refer to one normalized target URL; C6 additionally requires the same HTTPS target for HA evidence. Evidence freshness is centrally configured in `config/nexora-certification-evidence.php`. Upgrade rehearsal evidence must use a concrete older source version inside `config/nexora-upgrade.php`'s supported source window. Production packaging freezes all source/evidence/lock/build inputs and discards the artifact if any input changes during archive creation; independent artifact verification rechecks policy and current-host evidence hashes. N1.0 remains open until real C1-C6 target/operator evidence passes.


## N1.0 Target Execution v2.4 — Session Integrity & Final Release Seal

The current target-certification flow now uses a single exact-source/reviewed-lock certification session for C4-C6 operator evidence, rejects concurrent master target runs, enforces bounded future-clock skew and session freshness, and produces a sanitized certification evidence bundle plus external release seal alongside the production ZIP. The existing eleven-domain closure count is unchanged: `production_package` now represents the independently verified production ZIP + evidence bundle + release seal as one sealed release domain. Real Laragon/browser/HA observations remain mandatory.


## N1.0 Target Execution v2.5 — Signed Release Trust & Offline Verification

| Item | Status |
|---|---|
| Certified PHP/Composer/Node/npm toolchain freeze | APPLIED / SOURCE PASS |
| Toolchain binding through C1-C6/session/final evidence | APPLIED / SOURCE PASS |
| RSA detached release-seal signature | APPLIED / SOURCE PASS |
| Runtime-only signing-key boundary | APPLIED / SOURCE PASS |
| Portable/offline release verification | APPLIED / SOURCE PASS |
| Production/evidence ZIP archive hygiene | APPLIED / SOURCE PASS |
| Certification-session finalization receipt | APPLIED / SOURCE PASS |
| Real Laragon C1-C3 target execution | REQUIRED |
| C4-C5 operator/browser evidence | REQUIRED |
| C6 real 2+ node HA evidence | REQUIRED |
| Signed production customer release | BLOCKED until all target evidence passes |
| N1.1 | BLOCKED until N1.0 PASS |

The final signed delivery set is: production ZIP + certification evidence ZIP + release seal JSON + detached `.sig` + public key. The signing private key is never shipped.


## N1.0 Target Execution v2.7 — Trusted Update Admission

| Item | Status |
|---|---|
| Recipient out-of-band update trust anchor | APPLIED / SOURCE PASS |
| Explicit trust-anchor rotate/revoke lifecycle | APPLIED / SOURCE PASS |
| Strict signed-release admission receipt | APPLIED / SOURCE PASS |
| Anti-downgrade / same-version replay policy | APPLIED / SOURCE PASS |
| Empty-directory verified release staging | APPLIED / SOURCE PASS |
| Exact deployed-source attestation before upgrade | APPLIED / SOURCE PASS |
| Upgrade-plan admission-receipt hash binding | APPLIED / SOURCE PASS |
| Successful-upgrade signer/seal lineage metadata | APPLIED / SOURCE PASS |
| Release manifest/seal schema 4 update-trust policy binding | APPLIED / SOURCE PASS |
| Real target admission + staged upgrade rehearsal | REQUIRED |
| N1.1 | BLOCKED until N1.0 PASS |

N1.0 remains CERTIFYING. v2.7 does not claim a target upgrade PASS until a real prior installation admits a genuinely signed release through an independently trusted recipient anchor, stages the exact source, completes backup-protected upgrade rehearsal, and produces the required operator evidence.


### Signed certification candidate for C4 ordering

C4 upgrade rehearsal occurs before C6 can produce the final production release, so Nexora does not weaken production update trust to break that ordering cycle. `scripts/trusted-update-candidate.*` creates a short-lived signed **certification-candidate** bound to the exact source tree and reviewed dependency locks. A disposable prior installation can admit it with `scripts/trusted-update-admit-candidate.*`. Runtime acceptance requires the explicit `NEXORA_CERTIFICATION_UPGRADE_REHEARSAL=1` switch, a non-production `local/testing/certification` environment, and an isolated database whose name starts with `nexora_test` or `nexora_cert`. Production environments cannot use this candidate path and still require the complete signed production/evidence/seal/signature/public-key set.


## N1.0 Target Execution v2.8 — Crash-Safe Update Recovery & Trust Continuity

- Upgrade transaction journal: DONE (source-certified); real failure/recovery target rehearsal still required.
- Explicit upgrade CLI registration: DONE.
- Read-only recovery status + release-lineage export: DONE.
- Hash-linked recipient trust-anchor rotation lineage: DONE.
- Admission binding to trust-lineage head/depth: DONE.
- Staging quarantine records + managed TTL cleanup: DONE.
- Automatic destructive database recovery: DISABLED.

N1.0 remains CERTIFYING. v2.8 does not claim a recovery PASS until a real prior installation completes the backup-protected failure/recovery rehearsal and produces operator evidence.


## N1.0 Target Execution v2.9 — Restore Readiness / Maintenance Ownership / Health-Gated Upgrade

| Item | Status |
|---|---|
| Verified backup + guarded restore-readiness gate | APPLIED / SOURCE PASS |
| External backup schema-2 + freshness/database fingerprint | APPLIED / SOURCE PASS |
| Upgrade maintenance ownership lease | APPLIED / SOURCE PASS |
| Pre-existing maintenance takeover rejection | APPLIED / SOURCE PASS |
| Pre-metadata post-upgrade health gate | APPLIED / SOURCE PASS |
| Post-metadata health gate before traffic restore | APPLIED / SOURCE PASS |
| Backup/restore/health hashes in installed lineage | APPLIED / SOURCE PASS |
| Integrity-bound recovery decision record | APPLIED / SOURCE PASS |
| Safe stale maintenance-lease cleanup command | APPLIED / SOURCE PASS |
| C4 evidence expanded for failure/recovery drill | APPLIED / SOURCE PASS |
| Automatic database rollback/restore | DISABLED |
| Real Laragon C1-C3 target execution | REQUIRED |
| Real C4 protected-stage failure/recovery rehearsal | REQUIRED |
| C5 browser/performance evidence | REQUIRED |
| C6 real HA + signed production release | REQUIRED |
| N1.1 | BLOCKED until N1.0 real PASS |

N1.0 remains CERTIFYING. v2.9 is source-certified upgrade safety code; it is not evidence that a real backup can be restored, that Laravel migrations pass on the target, or that browser/HA certification has completed.


## N1.0 Target Execution v3.0 — Distributed Upgrade / Migration Convergence

| Item | Status |
|---|---|
| Global DB-backed `platform-upgrade` lease | APPLIED / SOURCE PASS |
| Explicit peer drain / maintenance state | APPLIED / SOURCE PASS |
| Draining web-node HTTP 503 fence | APPLIED / SOURCE PASS |
| Queue worker graceful drain fence | APPLIED / SOURCE PASS |
| Scheduler leadership drain fence | APPLIED / SOURCE PASS |
| Shared cache-backed maintenance requirement for multi-node | APPLIED / SOURCE PASS |
| Exact compatibility snapshot hash | APPLIED / SOURCE PASS |
| Pre-migration ledger plan binding | APPLIED / SOURCE PASS |
| Post-migration ledger convergence | APPLIED / SOURCE PASS |
| Recovery-held distributed lease | APPLIED / SOURCE PASS |
| Cluster status / node status / lock / scheduler-release commands | APPLIED / SOURCE PASS |
| Post-upgrade node-version/status convergence reporting | APPLIED / SOURCE PASS |
| Upgrade lineage migration/cluster hashes | APPLIED / SOURCE PASS |
| C4 evidence expanded for distributed rehearsal | APPLIED / SOURCE PASS |
| Automatic peer drain | DISABLED |
| Automatic destructive DB rollback | DISABLED |
| Real C1-C3 Laragon execution | REQUIRED |
| Real C4 distributed/failure rehearsal | REQUIRED |
| C5 browser/performance evidence | REQUIRED |
| C6 real multi-node HA / signed release | REQUIRED |
| N1.1 | BLOCKED until N1.0 real PASS |

N1.0 remains CERTIFYING. v3.0 proves source-level distributed upgrade invariants only; it does not claim that a real multi-node topology has been drained, migrated, converged or recovered successfully.


## N1.0 Target Execution v3.2 — Atomic Cutover / Frontend Regression

| Item | Status |
|---|---|
| Shared DB-row atomic runtime admission barrier | APPLIED / SOURCE PASS |
| Recovery-sticky cutover barrier | APPLIED / SOURCE PASS |
| Web cutover HTTP 503 fence | APPLIED / SOURCE PASS |
| Scheduler cutover fail-closed admission | APPLIED / SOURCE PASS |
| Queue payload schema 2 | APPLIED / SOURCE PASS |
| Legacy queue payload compatibility | DISABLED |
| Same-major old-version queue payload compatibility | DISABLED |
| Exact-version queue payload check | APPLIED / SOURCE PASS |
| Read-only `nexora:upgrade:cutover-status` | APPLIED / SOURCE PASS |
| Prior 76-error / 11-file Inertia v3 regression patterns source-guarded | APPLIED / SOURCE PASS |
| Real `tsc --noEmit` + Vite build | REQUIRED ON REVIEWED TARGET DEPENDENCIES |
| Real atomic barrier race rehearsal | REQUIRED IN C4 |
| N1.0 | CERTIFYING — TARGET EXECUTION / C1-C6 REAL EVIDENCE |
| N1.1 | BLOCKED UNTIL N1.0 PASS |


## N1.0 Target Execution v3.3 — Deployment Generation / Client / Cache / Session Fencing

| Item | Status |
|---|---|
| Deterministic deployment-generation identity | APPLIED / SOURCE PASS |
| Session-schema epoch included in generation | APPLIED / SOURCE PASS |
| Admitted/installed generation recomputation | APPLIED / SOURCE PASS |
| Same-version wrong-generation runtime/node rejection | APPLIED / SOURCE PASS |
| Queue payload schema 3 + exact generation | APPLIED / SOURCE PASS |
| Inertia asset-version stale-client reload fence | APPLIED / SOURCE PASS |
| Raw admin JSON generation header fence | APPLIED / SOURCE PASS |
| Generation-scoped cache namespace | APPLIED / SOURCE PASS |
| Runtime session-schema guard | APPLIED / SOURCE PASS |
| Signed update / upgrade-plan generation binding | APPLIED / SOURCE PASS |
| Installed lineage generation/material hashes | APPLIED / SOURCE PASS |
| `runtime:deployment-status --deep` | APPLIED / SOURCE PASS |
| C4 upgrade evidence checks | 54 REQUIRED ON REAL TARGET |
| C6 HA observations | 14 REQUIRED ON REAL 2+ NODE TARGET |
| Automatic cache purge | DISABLED |
| Same-version wrong-generation compatibility | DISABLED |
| Real C1-C3 Laragon execution | REQUIRED |
| Real C4 cutover/deployment-generation rehearsal | REQUIRED |
| C5 browser/A11y/RTL/Web-Vitals evidence | REQUIRED |
| C6 HA + signed production release | REQUIRED |
| N1.0 | CERTIFYING — TARGET EXECUTION |
| N1.1 | BLOCKED UNTIL N1.0 REAL PASS |

N1.0 remains target certification. v3.3 strengthens source/runtime invariants; it does not claim a reviewed dependency build, real database matrix, browser run, upgrade rehearsal or multi-node HA PASS.


## N1.0 Target Execution v3.4 — Runtime Environment / Key Continuity

| Item | Status |
|---|---|
| Non-secret runtime environment fingerprint | APPLIED / SOURCE PASS |
| APP_KEY fingerprint in environment identity | APPLIED / SOURCE PASS |
| Session/cookie/cache/queue/storage/maintenance compatibility binding | APPLIED / SOURCE PASS |
| Runtime node environment advertisement | APPLIED / SOURCE PASS |
| Queue payload schema 4 + exact environment | APPLIED / SOURCE PASS |
| HA environment-fingerprint consistency | APPLIED / SOURCE PASS |
| Upgrade plan/apply environment binding | APPLIED / SOURCE PASS |
| Explicit APP_PREVIOUS_KEYS continuity check | APPLIED / SOURCE PASS |
| Maintenance-only key rotation authorization | APPLIED / SOURCE PASS |
| Multi-node key-rotation convergence gate | APPLIED / SOURCE PASS |
| Explicit key-rotation commit/abort | APPLIED / SOURCE PASS |
| Secret values in runtime status/release metadata | DISABLED |
| Automatic APP_KEY mutation | DISABLED |
| Automatic maintenance/traffic transition during key rotation | DISABLED |
| C4 real upgrade/environment/key-rotation observations | REQUIRED |
| C6 runtime-environment consistency observation | REQUIRED |
| N1.1 | BLOCKED until N1.0 real PASS |


## N1.0 Target Execution v3.5 — Runtime Activation / Cache / Process Fencing

| Item | Status |
|---|---|
| Integrity-protected runtime activation epoch | APPLIED / SOURCE PASS |
| Framework-cache snapshot fingerprint | APPLIED / SOURCE PASS |
| Queue schema 5 exact activation fence | APPLIED / SOURCE PASS |
| Long-running process epoch fence | APPLIED / SOURCE PASS |
| Upgrade activation-plan binding/drift rejection | APPLIED / SOURCE PASS |
| Maintenance-protected activation rotation | APPLIED / SOURCE PASS |
| Queue restart signal before traffic restore | APPLIED / SOURCE PASS |
| Cluster activation convergence | APPLIED / SOURCE PASS |
| Runtime activation status/deep integrity commands | APPLIED / SOURCE PASS |
| OPCache policy observation | APPLIED / SOURCE PASS |
| Production release activation-policy binding | APPLIED / SOURCE PASS |
| Production input-freeze upgrade-policy asymmetry | FIXED |
| C4 real upgrade/recovery observations | 62 REQUIRED ON TARGET |
| C6 real HA observations | 16 REQUIRED ON TARGET |
| Automatic PHP-FPM restart | DISABLED |
| Automatic traffic restoration | DISABLED |
| Automatic destructive database rollback | DISABLED |
| C1-C3 real Laragon execution | REQUIRED |
| C4/C5/C6 real evidence | REQUIRED |
| N1.1 | BLOCKED until N1.0 real PASS |

N1.0 remains TARGET CERTIFICATION. v3.5 source certification proves activation/cache/process fencing contracts only; it does not claim real OPCache restart, queue worker turnover, browser evidence or multi-node activation convergence.


## N1.0 Target Execution v3.6 — PHP Runtime Engine / Extension Convergence

| Item | Status |
|---|---|
| Deterministic PHP runtime-engine fingerprint | APPLIED / SOURCE PASS |
| Exact PHP patch identity | APPLIED / SOURCE PASS |
| Extension set/version profile SHA-256 | APPLIED / SOURCE PASS |
| PDO driver-set SHA-256 | APPLIED / SOURCE PASS |
| OpenSSL/Sodium/ICU capability identity | APPLIED / SOURCE PASS |
| SAPI-independent engine vs process profile split | APPLIED / SOURCE PASS |
| Queue payload schema 6 engine fence | APPLIED / SOURCE PASS |
| Node heartbeat engine advertisement | APPLIED / SOURCE PASS |
| HA runtime-engine convergence | APPLIED / SOURCE PASS |
| Upgrade-plan/apply engine binding | APPLIED / SOURCE PASS |
| Installer/upgrade lineage engine hashes | APPLIED / SOURCE PASS |
| `runtime:engine-status --deep` | APPLIED / SOURCE PASS |
| C2 runtime-engine target gate | APPLIED / SOURCE PASS |
| C4 operator evidence | 69 CHECKS DEFINED / REAL TARGET REQUIRED |
| C6 HA evidence | 17 CHECKS DEFINED / REAL 2+ NODE TARGET REQUIRED |
| Automatic PHP runtime mutation | DISABLED |
| Real C1-C3 dependency/runtime/DB execution | REQUIRED |
| Real C4/C5/C6 operator evidence | REQUIRED |
| N1.1 | BLOCKED until N1.0 real PASS |

N1.0 remains TARGET CERTIFICATION. v3.6 proves source-level runtime-engine identity/fencing only; it does not claim the real Laragon/FPM/CLI/multi-node engine profile has passed.


## N1.0 Target Execution v3.7 — Database Data Plane / Schema Attestation

| Item | Status |
|---|---|
| Runtime database data-plane identity | APPLIED / SOURCE PASS |
| Exact DB server/session profile fencing | APPLIED / SOURCE PASS |
| Deterministic structural schema fingerprint | APPLIED / SOURCE PASS |
| Queue schema 7 DB data-plane fence | APPLIED / SOURCE PASS |
| Node/HA DB data-plane convergence | APPLIED / SOURCE PASS |
| Backup data-plane + schema binding | APPLIED / SOURCE PASS |
| Upgrade pre-plan schema/data-plane binding | APPLIED / SOURCE PASS |
| Manual schema drift rejection | APPLIED / SOURCE PASS |
| Post-migration structural attestation | APPLIED / SOURCE PASS |
| C2 fresh-vs-rebuild DB attestation | APPLIED / SOURCE PASS — 35 gates defined |
| C3 per-driver schema round-trip equality | APPLIED / SOURCE PASS |
| C4 operator rehearsal | 78 checks DEFINED / REAL TARGET REQUIRED |
| C6 HA rehearsal | 18 checks DEFINED / REAL TARGET REQUIRED |
| Automatic schema mutation approval | DISABLED |
| Automatic destructive database rollback | DISABLED |
| Real C1-C6 target evidence | REQUIRED |
| N1.1 | BLOCKED until real N1.0 PASS |

N1.0 remains TARGET CERTIFICATION. v3.7 source certification proves the database data-plane/schema-attestation implementation and contracts only; it does not claim any real target database family has completed structural attestation.


## N1.0 Target Execution v3.8 — Persistent Storage Data Plane / Shared Recovery

| Item | Status |
|---|---|
| Runtime persistent-storage data-plane identity | APPLIED / SOURCE PASS |
| Media/object/backup role fingerprints | APPLIED / SOURCE PASS |
| S3/local/FTP/SFTP non-secret locator identity | APPLIED / SOURCE PASS |
| Deep write/read/delete role probes | APPLIED / SOURCE PASS |
| Fresh-install local public-media link preparation | APPLIED / SOURCE PASS |
| Conflicting public storage-link overwrite protection | APPLIED / SOURCE PASS |
| Queue schema 8 storage-data-plane fence | APPLIED / SOURCE PASS |
| Node/HA storage convergence | APPLIED / SOURCE PASS |
| Configurable shared runtime-backup disk | APPLIED / SOURCE PASS |
| Backup storage/data-plane binding | APPLIED / SOURCE PASS |
| Non-shared restore external-copy + re-hash guard | APPLIED / SOURCE PASS |
| Upgrade plan/apply storage binding | APPLIED / SOURCE PASS |
| Installer/upgrade storage lineage | APPLIED / SOURCE PASS |
| Deployment-generation storage-policy binding | APPLIED / SOURCE PASS |
| C2 runtime certification | 36 GATES DEFINED / REAL TARGET REQUIRED |
| C4 operator rehearsal | 88 CHECKS DEFINED / REAL TARGET REQUIRED |
| C6 HA rehearsal | 20 CHECKS DEFINED / REAL 2+ NODE TARGET REQUIRED |
| Automatic storage migration/copy | DISABLED |
| Automatic destructive restore | DISABLED |
| Real C1-C6 target evidence | REQUIRED |
| N1.1 | BLOCKED until real N1.0 PASS |

N1.0 remains TARGET CERTIFICATION. v3.8 proves source-level persistent-storage identity/fencing, backup locality semantics and recovery contracts only; it does not claim a real shared object store, cross-node backup target, or media/restore rehearsal has passed.


## N1.0 Target Execution v3.9 — Service / Network Data Plane

| Item | Status |
|---|---|
| Runtime cache/session/queue/mail service identity | APPLIED / SOURCE PASS |
| Redis endpoint/database/cluster profile identity | APPLIED / SOURCE PASS |
| TLS CA/proxy/trusted-proxy identity | APPLIED / SOURCE PASS |
| Approved outbound network broker | APPLIED / SOURCE PASS |
| HTTPS/public-address/allowed-port policy | APPLIED / SOURCE PASS |
| DNS resolution + cURL pinning | APPLIED / SOURCE PASS |
| Private/reserved/credential URL rejection | APPLIED / SOURCE PASS |
| Same-origin SEO crawler policy | APPLIED / SOURCE PASS |
| Queue schema 9 service-data-plane fence | APPLIED / SOURCE PASS |
| Node/HA service convergence | APPLIED / SOURCE PASS |
| Install/upgrade service lineage | APPLIED / SOURCE PASS |
| Deployment/release network-policy binding | APPLIED / SOURCE PASS |
| C2 runtime certification | 37 GATES DEFINED / REAL TARGET REQUIRED |
| C4 operator rehearsal | 98 CHECKS DEFINED / REAL TARGET REQUIRED |
| C6 HA rehearsal | 21 CHECKS DEFINED / REAL 2+ NODE TARGET REQUIRED |
| Automatic external redirect following | DISABLED |
| Automatic private/reserved outbound access | DISABLED |
| Real C1-C6 target evidence | REQUIRED |
| N1.1 | BLOCKED until real N1.0 PASS |

N1.0 remains TARGET CERTIFICATION. v3.9 proves source-level service/network identity and outbound-policy contracts only; it does not claim real Redis, cache/session/queue, mail, DNS, CA/proxy or multi-node service convergence has passed.


## N1.0 Target Execution v4.0 — Host / Clock Data Plane

| Item | Status |
|---|---|
| Host/platform compatibility fingerprint | APPLIED / SOURCE PASS |
| UTC timezone + locale normalization contract | APPLIED / SOURCE PASS |
| Shared primary-DB clock anchor for distributed leases | APPLIED / SOURCE PASS |
| DB-clock node freshness / upgrade expiry decisions | APPLIED / SOURCE PASS |
| Queue schema 10 host fingerprint + generated timestamp | APPLIED / SOURCE PASS |
| Future-skewed queue payload rejection | APPLIED / SOURCE PASS |
| Deep temp/atomic-rename/flock/entropy/umask probes | APPLIED / SOURCE PASS |
| Node/HA host-profile convergence | APPLIED / SOURCE PASS |
| Installation/upgrade host lineage | APPLIED / SOURCE PASS |
| Signed host-policy release binding | APPLIED / SOURCE PASS |
| C2 host/clock architecture | 38 gates defined |
| C4 host/clock rehearsal architecture | 105 checks defined |
| C6 host/clock HA architecture | 23 HA checks defined |
| Automatic NTP/timezone/locale mutation | DISABLED |
| Real target host/clock observations | REQUIRED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until N1.0 real PASS |

N1.0 remains target certification. v4.0 is a source-level hardening claim only; it does not claim real Laragon clock skew, filesystem semantics or multi-node host convergence has passed.


## N1.0 Target Execution v4.1 — Runtime Resource / Capacity Envelope

| Item | Status |
|---|---|
| Deterministic runtime resource-policy fingerprint | APPLIED / SOURCE PASS |
| Existing PHP runtime-limit doctor integration | APPLIED / SOURCE PASS |
| Process memory headroom probe | APPLIED / SOURCE PASS |
| Queue-worker memory headroom probe | APPLIED / SOURCE PASS |
| Temp/storage/transfer/bootstrap free-space probes | APPLIED / SOURCE PASS |
| Protected DB-backup staging free-space probe | APPLIED / SOURCE PASS |
| POSIX/open-file soft-limit observation | APPLIED / SOURCE PASS |
| Upgrade plan/apply live-capacity admission | APPLIED / SOURCE PASS |
| Backup scratch/staging capacity admission | APPLIED / SOURCE PASS |
| Queue schema 11 resource-policy fence | APPLIED / SOURCE PASS |
| Runtime node deep resource status/digest | APPLIED / SOURCE PASS |
| HA resource-policy convergence | APPLIED / SOURCE PASS |
| HA minimum live-capacity convergence | APPLIED / SOURCE PASS |
| Install/upgrade resource lineage | APPLIED / SOURCE PASS |
| Deployment-generation resource-policy binding | APPLIED / SOURCE PASS |
| Signed release/provenance resource-policy binding | APPLIED / SOURCE PASS |
| Runtime resource status/deep command | APPLIED / SOURCE PASS |
| Post-migration resource attestation ordering bug | FIXED |
| C2 runtime certification | 39 GATES DEFINED / REAL TARGET REQUIRED |
| C4 operator rehearsal | 113 CHECKS DEFINED / REAL TARGET REQUIRED |
| C6 HA rehearsal | 25 CHECKS DEFINED / REAL 2+ NODE TARGET REQUIRED |
| Automatic PHP/OS resource mutation | DISABLED |
| Automatic disk cleanup | DISABLED |
| Real C1-C6 target evidence | REQUIRED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until N1.0 real PASS |

N1.0 remains TARGET CERTIFICATION. v4.1 proves source-level resource-policy identity and admission contracts only; it does not claim the real Laragon/production host has sufficient memory, disk, file descriptors, backup scratch space or multi-node capacity headroom.


## N1.0 Target Execution v4.2 — Runtime Policy Plane Convergence

| Item | Status |
|---|---|
| Secret-free effective policy fingerprint | APPLIED / SOURCE PASS |
| Concurrency/transfer/runtime effective policy binding | APPLIED / SOURCE PASS |
| Upgrade/update/release/supply-chain effective policy binding | APPLIED / SOURCE PASS |
| Dependency-lock + HA policy binding | APPLIED / SOURCE PASS |
| Machine-local/secret paths excluded from fingerprint | APPLIED / SOURCE PASS |
| Order-insensitive policy lists canonicalized | APPLIED / SOURCE PASS |
| Production fail-closed invariant checks | APPLIED / SOURCE PASS |
| Queue schema 12 policy-plane fence | APPLIED / SOURCE PASS |
| Wrong-policy queued job rejection | APPLIED / SOURCE PASS |
| Runtime-node policy fingerprint/status/deep digest | APPLIED / SOURCE PASS |
| HA exact policy-plane convergence | APPLIED / SOURCE PASS |
| Install/upgrade policy lineage | APPLIED / SOURCE PASS |
| Upgrade pre/post policy drift rejection | APPLIED / SOURCE PASS |
| Deployment-generation policy-plane binding | APPLIED / SOURCE PASS |
| Release/provenance/seal policy-plane binding | APPLIED / SOURCE PASS |
| Runtime policy status command | APPLIED / SOURCE PASS |
| Historical Laragon Inertia regression class | GUARDED — 122 Admin TS/TSX / 11 targets |
| C2 runtime certification | 40 GATES DEFINED / REAL TARGET REQUIRED |
| C4 operator rehearsal | 121 CHECKS DEFINED / REAL TARGET REQUIRED |
| C6 HA rehearsal | 27 CHECKS DEFINED / REAL 2+ NODE TARGET REQUIRED |
| Automatic effective-policy mutation | DISABLED |
| Real C1-C6 target evidence | REQUIRED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until N1.0 real PASS |

### Progress / power bars

Percentages below are milestone completion, not production-certification claims. N1.0 combined planning progress gives equal weight to source implementation and real target certification.

- Core N0.0–N0.34: **100%** `████████████████████`
- N1.0 source implementation/hardening: **100%** `████████████████████`
- N1.0 real C1–C6 certification: **0% certified chunks (0/6)** `░░░░░░░░░░░░░░░░░░░░`
- N1.0 combined planning progress: **50%** `██████████░░░░░░░░░░`
- N1.1 Admin UX / Design System: **0% — blocked** `░░░░░░░░░░░░░░░░░░░░`
- N1.2–N1.26: **0% — planned** `░░░░░░░░░░░░░░░░░░░░`
- N2.0 Stable Production: **0% — blocked** `░░░░░░░░░░░░░░░░░░░░`

N1.0 remains TARGET CERTIFICATION. The historical Laragon build proved target TypeScript execution was attempted, but the current rc.57 source still requires a fresh dependency-backed C1 rerun before any C1 percentage can become certified.

## N1.0 Target Execution v4.3 — Operational Process Plane / Process-Role Liveness

| Item | Status |
|---|---|
| Deterministic web/queue/scheduler process-role policy fingerprint | APPLIED / SOURCE PASS |
| Shared DB-clock-backed process role leases | APPLIED / SOURCE PASS |
| Web middleware role heartbeat | APPLIED / SOURCE PASS |
| Queue job-admission role heartbeat | APPLIED / SOURCE PASS |
| Idle queue-worker `Queue::looping` heartbeat | APPLIED / SOURCE PASS |
| Scheduler role heartbeat independent of scheduler leader | APPLIED / SOURCE PASS |
| HA web process quorum | APPLIED / SOURCE PASS |
| HA queue process quorum | APPLIED / SOURCE PASS |
| HA scheduler process quorum | APPLIED / SOURCE PASS |
| HA exact process-policy convergence | APPLIED / SOURCE PASS |
| Indefinite/unsafe queue blocking rejection for HA | APPLIED / SOURCE PASS |
| Queue schema 13 process-policy fence | APPLIED / SOURCE PASS |
| Wrong-process-policy queue rejection | APPLIED / SOURCE PASS |
| Install/upgrade process lineage | APPLIED / SOURCE PASS |
| Upgrade pre/post process-policy drift rejection | APPLIED / SOURCE PASS |
| Deployment-generation process-policy SHA binding | APPLIED / SOURCE PASS |
| Release/provenance/seal process-policy SHA binding | APPLIED / SOURCE PASS |
| Runtime process status/heartbeat commands | APPLIED / SOURCE PASS |
| Deployment/cutover/lineage process visibility | APPLIED / SOURCE PASS |
| Admin System Health process-policy visibility | APPLIED / SOURCE PASS |
| Production process defaults | APPLIED / SOURCE PASS |
| Historical Laragon Inertia regression class | GUARDED — fresh rc.58 C1 rerun required |
| C2 runtime certification | 41 GATES DEFINED / REAL TARGET REQUIRED |
| C4 operator rehearsal | 131 CHECKS DEFINED / REAL TARGET REQUIRED |
| C6 HA rehearsal | 31 CHECKS DEFINED / REAL 2+ NODE TARGET REQUIRED |
| Automatic OS process start/stop/restart | DISABLED |
| Real process-manager / LB / worker / scheduler evidence | REQUIRED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until N1.0 real PASS |

### Progress / power bars

Percentages are certified milestone completion, not a claim that production is ready. Combined planning progress gives equal weight to source engineering and the six real target certification chunks.

- Core N0.0–N0.34: **100%** `████████████████████`
- N1.0 source implementation/hardening: **100%** `████████████████████`
- C1 real target certification: **0%** `░░░░░░░░░░░░░░░░░░░░`
- C2 real target certification: **0%** `░░░░░░░░░░░░░░░░░░░░`
- C3 five-DB matrix certification: **0%** `░░░░░░░░░░░░░░░░░░░░`
- C4 real rehearsal certification: **0%** `░░░░░░░░░░░░░░░░░░░░`
- C5 browser/A11y/RTL/Vitals certification: **0%** `░░░░░░░░░░░░░░░░░░░░`
- C6 real HA/final release certification: **0%** `░░░░░░░░░░░░░░░░░░░░`
- N1.0 real C1–C6 certification: **0% certified chunks (0/6)** `░░░░░░░░░░░░░░░░░░░░`
- N1.0 combined planning progress: **50%** `██████████░░░░░░░░░░`
- N1.1 Admin UX / Design System: **0% — blocked** `░░░░░░░░░░░░░░░░░░░░`
- N1.2–N1.26: **0% — planned** `░░░░░░░░░░░░░░░░░░░░`
- N2.0 Stable Production: **0% — blocked** `░░░░░░░░░░░░░░░░░░░░`

N1.0 remains TARGET CERTIFICATION. v4.3 proves source-level process-role policy/liveness contracts only; real supervisors, load-balancer probes, scheduler execution, idle queue workers and multi-node quorums must still be observed on the target.


## N1.0 Target Execution v4.4 — Framework / Reviewed Dependency Reconciliation

| Item | Status |
|---|---|
| Laravel 13.x reviewed compatibility window >=13.24 <14 | APPLIED / SOURCE PASS |
| Composer framework constraint `^13.24` | APPLIED / SOURCE PASS |
| Reviewed lock + locked Laravel version attestation | APPLIED / SOURCE PASS |
| Running Laravel must equal reviewed lock | APPLIED / SOURCE PASS |
| Dependency-only generation drift diagnosis | APPLIED / SOURCE PASS |
| Maintenance-only dependency reconciliation | APPLIED / SOURCE PASS |
| Activation rotation after reconcile | APPLIED / SOURCE PASS |
| Queue restart after reconcile | APPLIED / SOURCE PASS |
| Atomic transition receipt | APPLIED / SOURCE PASS |
| Opaque deployment mismatch diagnostics | FIXED / ACTIONABLE |
| Laravel 14+ automatic compatibility | BLOCKED BY DESIGN |
| Lock hashes retained in deployment generation | RETAINED / SECURITY BOUNDARY |
| Framework policy signed in release/deployment materials | APPLIED / SOURCE PASS |
| C2 framework/dependency status | 43 GATES DEFINED / REAL TARGET REQUIRED |
| C4 dependency-update rehearsal | 141 CHECKS DEFINED / REAL TARGET REQUIRED |
| C6 framework/dependency convergence | 34 HA CHECKS DEFINED / REAL 2+ NODE TARGET REQUIRED |
| Human-readable critical compatibility/release path | ENFORCED / SOURCE PASS |
| Automatic dependency acceptance/update | DISABLED |
| Automatic traffic restore after reconcile | DISABLED |

N1.0 remains TARGET CERTIFICATION. A future Laravel 13.x minor/patch dependency update may be reviewed and reconciled without weakening deployment identity. Real dependency installation, C1 build/runtime evidence, C2-C6 operator evidence and signed production release remain mandatory.


## N1.0 Target Execution v4.5 — Tenant Seed Isolation & TypeScript Stabilization

| Item | Status |
|---|---|
| Installer stale TenantContext reset | APPLIED / SOURCE PASS |
| Scoped default-organization seeding | APPLIED / SOURCE PASS |
| CRM pipeline/stage FK-safe default seeding | APPLIED / SOURCE PASS |
| Helpdesk SLA FK-safe default seeding | APPLIED / SOURCE PASS |
| Newsletter list FK-safe default seeding | APPLIED / SOURCE PASS |
| Stale active tenant write fail-closed validation | APPLIED / SOURCE PASS |
| TenantContext scoped restoration | APPLIED / SOURCE PASS |
| Tenant regression Feature tests | 3 DEFINED / REAL TARGET REQUIRED |
| Historical Laragon TypeScript targets | 11 GUARDED |
| Human-readable high-density TypeScript targets | 8 REFACTORED / GUARDED |
| Inertia source contract | PASS |
| TypeScript syntax parser | SOURCE PASS / REAL TSC REQUIRED |
| C2 runtime certification | 44 GATES DEFINED / REAL TARGET REQUIRED |
| C4 operator rehearsal | 147 CHECKS DEFINED / REAL TARGET REQUIRED |
| C6 HA rehearsal | 34 CHECKS DEFINED / REAL 2+ NODE TARGET REQUIRED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until real N1.0 PASS |

N1.0 remains target certification. Source-level tenant and TypeScript contracts do not replace a fresh `migrate:fresh --seed`, Enterprise Feature test, reviewed-dependency `tsc --noEmit`, Vite build or the remaining C1-C6 target evidence.


## N1.0 Target Execution v4.6 — Tenant Execution Boundary

| Item | Status |
|---|---|
| TenantExecutionScope active-organization resolver | APPLIED / SOURCE PASS |
| Missing queue tenant rejection | APPLIED / SOURCE PASS |
| Deleted queue tenant rejection | APPLIED / SOURCE PASS |
| Suspended queue tenant rejection | APPLIED / SOURCE PASS |
| Workflow queue tenant scope | APPLIED / SOURCE PASS |
| Webhook queue tenant scope | APPLIED / SOURCE PASS |
| Newsletter queue tenant scope | APPLIED / SOURCE PASS |
| SEO crawl queue tenant scope | APPLIED / SOURCE PASS |
| Queue pre-job tenant clear | APPLIED / SOURCE PASS |
| Queue success/exception/idle tenant clear | APPLIED / SOURCE PASS |
| Scheduler start/end/failure tenant clear | APPLIED / SOURCE PASS |
| CRM/Helpdesk/Newsletter transactional defaults | APPLIED / SOURCE PASS |
| Dedicated C2 tenant execution gate | 45 TOTAL C2 GATES / REAL TARGET REQUIRED |
| C4 tenant execution drills | 156 TOTAL C4 CHECKS / REAL TARGET REQUIRED |
| Automatic fallback from stale job tenant to default tenant | DISABLED |
| C6 HA architecture | 34 CHECKS / UNCHANGED |
| Fresh dependency-backed `tsc --noEmit` | REAL TARGET REQUIRED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until N1.0 real PASS |


## N1.0 Target Execution v4.7 — Fresh-Install Dependency Trust Bootstrap

| Item | Status |
|---|---|
| Runtime dependency identity separated from human review state | APPLIED / SOURCE PASS |
| Missing review allowed only for fresh-install bootstrap | APPLIED / SOURCE PASS |
| Deterministic composer.lock required | APPLIED / SOURCE PASS |
| Deterministic package-lock.json required | APPLIED / SOURCE PASS |
| Running Laravel must equal composer.lock | APPLIED / SOURCE PASS |
| Installed Composer runtime must match composer.lock production packages | APPLIED / SOURCE PASS |
| package.json must match package-lock root declarations | APPLIED / SOURCE PASS |
| Corrupt/stale/invalid review evidence bootstrap fallback | BLOCKED BY DESIGN |
| Fresh-install bootstrap receipt | APPLIED / SOURCE PASS |
| Installer bootstrap trust lineage | APPLIED / SOURCE PASS |
| Runtime bootstrap identity accepted without review-only 503 | APPLIED / SOURCE PASS |
| Installer doctor dependency preflight | APPLIED / SOURCE PASS |
| Reviewed provenance sync command | APPLIED / SOURCE PASS |
| Review sync rejects broader deployment drift | APPLIED / SOURCE PASS |
| Review sync changes deployment generation | DISABLED |
| Automatic fabrication of human-reviewed attestation | DISABLED |
| C2 runtime certification | 45 GATES DEFINED / REAL TARGET REQUIRED |
| C4 operator rehearsal | 168 CHECKS DEFINED / REAL TARGET REQUIRED |
| C6 HA rehearsal | 34 CHECKS DEFINED / REAL 2+ NODE TARGET REQUIRED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until N1.0 real PASS |

### Progress / power bars

- Core N0.0–N0.34: **100%** `████████████████████`
- N1.0 source implementation/hardening: **100%** `████████████████████`
- v4.7 fresh-install dependency trust source work: **100%** `████████████████████`
- C1 real target certification: **0%** `░░░░░░░░░░░░░░░░░░░░`
- C2 real target certification: **0%** `░░░░░░░░░░░░░░░░░░░░`
- C3 five-DB matrix certification: **0%** `░░░░░░░░░░░░░░░░░░░░`
- C4 real rehearsal certification: **0%** `░░░░░░░░░░░░░░░░░░░░`
- C5 browser/A11y/RTL/Vitals certification: **0%** `░░░░░░░░░░░░░░░░░░░░`
- C6 real HA/final release certification: **0%** `░░░░░░░░░░░░░░░░░░░░`
- N1.0 real C1–C6 certification: **0% certified chunks (0/6)** `░░░░░░░░░░░░░░░░░░░░`
- N1.0 combined planning progress: **50%** `██████████░░░░░░░░░░`
- N1.1 Admin UX / Design System: **0% — blocked** `░░░░░░░░░░░░░░░░░░░░`
- N1.2–N1.26: **0% — planned** `░░░░░░░░░░░░░░░░░░░░`
- N2.0 Stable Production: **0% — blocked** `░░░░░░░░░░░░░░░░░░░░`

N1.0 remains TARGET CERTIFICATION. v4.7 removes the false fresh-install review blocker without converting unreviewed dependencies into reviewed evidence. Real C1 dependency review/install, C2 runtime execution, C3 database matrix, C4 168-check rehearsal, C5 browser evidence and C6 HA/release evidence remain mandatory.


## N1.0 Target Execution v4.8 — Crash-Safe Installation Commit Boundary

| Item | Status |
|---|---|
| Schema-2 sealed permanent installation lock | APPLIED / SOURCE PASS |
| Canonical installation-lock SHA-256 integrity | APPLIED / SOURCE PASS |
| Corrupt/tampered lock fail-closed | APPLIED / SOURCE PASS |
| Corrupt lock never reopens installer | APPLIED / SOURCE PASS |
| Legacy unsealed lock backward compatibility | APPLIED / SOURCE PASS |
| Legacy lock reseal on metadata update | APPLIED / SOURCE PASS |
| Read-only installation lock status command | APPLIED / SOURCE PASS |
| Bootstrap receipt staged until final attestations | APPLIED / SOURCE PASS |
| Orphan bootstrap receipt cleanup before retry | APPLIED / SOURCE PASS |
| Bootstrap receipt integrity validation before publish | APPLIED / SOURCE PASS |
| `installed.lock` durable commit point | APPLIED / SOURCE PASS |
| Post-commit backup/access cleanup non-fatal | APPLIED / SOURCE PASS |
| Post-commit progress/run telemetry non-fatal | APPLIED / SOURCE PASS |
| Invalid-lock HTTP 503 diagnostics | APPLIED / SOURCE PASS |
| C2 runtime certification | 49 GATES DEFINED / REAL TARGET REQUIRED |
| C4 operator rehearsal | 180 CHECKS DEFINED / REAL TARGET REQUIRED |
| C6 HA rehearsal | 34 CHECKS DEFINED / REAL 2+ NODE TARGET REQUIRED |
| Automatic corrupt-lock deletion/reopen | DISABLED |
| Real C1-C6 target evidence | REQUIRED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until N1.0 real PASS |

N1.0 remains TARGET CERTIFICATION. v4.8 proves source-level crash-safe installation commit semantics only; real fresh-install, crash/retry, lock-tamper and post-commit telemetry evidence still belongs to C2/C4 target execution.


## N1.0 Target Execution v4.9 — Installer Consent & Preflight Stabilization

| Item | Status |
|---|---|
| Dependency trust resolved before database mutation | APPLIED / SOURCE PASS |
| Installer protocol/version visible in stream | APPLIED / SOURCE PASS |
| Recoverable DB Resume option | APPLIED / SOURCE PASS |
| Recoverable DB Start-clean option | APPLIED / SOURCE PASS |
| Backup / explicit overwrite consent restored for reset | APPLIED / SOURCE PASS |
| Final Install CTA visibility bug | FIXED |
| Final terms → Install button gate | APPLIED / SOURCE PASS |
| Weak password consent | APPLIED / SOURCE PASS |
| Low/Medium password consent | APPLIED / SOURCE PASS |
| Strong password no-consent path | APPLIED / SOURCE PASS |
| Non-bypassable password safety floor | APPLIED / SOURCE PASS |
| DB/password consent lineage metadata | APPLIED / SOURCE PASS |
| C2 runtime certification | 51 GATES DEFINED / REAL TARGET REQUIRED |
| C4 operator rehearsal | 191 CHECKS DEFINED / REAL TARGET REQUIRED |
| C6 HA rehearsal | 34 CHECKS DEFINED / REAL TARGET REQUIRED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until N1.0 real PASS |

N1.0 remains TARGET CERTIFICATION. v4.9 closes source-level installer preflight/consent/navigation defects; actual browser installer execution, real backup/reset consent, password-risk consent and dependency-backed runtime evidence still require target observation.


## N1.0 Target Execution v5.0 — Installation Resume Provenance & Fast-Track Closure

| Item | Status |
|---|---|
| Exact installation resume provenance fingerprint | APPLIED / SOURCE PASS |
| Platform/protocol resume binding | APPLIED / SOURCE PASS |
| Migration manifest resume binding | APPLIED / SOURCE PASS |
| Core seeder manifest resume binding | APPLIED / SOURCE PASS |
| Composer/npm lock resume binding | APPLIED / SOURCE PASS |
| Changed-source partial install Resume rejection | APPLIED / SOURCE PASS |
| Legacy partial install Start-clean requirement | APPLIED / SOURCE PASS |
| Server-side stale Resume rejection | APPLIED / SOURCE PASS |
| Incompatible recovery UI reason/version/protocol | APPLIED / SOURCE PASS |
| Incompatible recovery auto-selects Start clean | APPLIED / SOURCE PASS |
| Backup/overwrite consent remains mandatory | APPLIED / SOURCE PASS |
| One-command safe target fast-track runner | APPLIED / SOURCE PASS |
| Fast-track lock-review automation | DISABLED |
| Fast-track destructive DB automation | DISABLED |
| C2 | 52 GATES DEFINED / REAL TARGET REQUIRED |
| C4 | 198 CHECKS DEFINED / REAL TARGET REQUIRED |
| C6 | 34 HA CHECKS / REAL 2+ NODE TARGET REQUIRED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until N1.0 real PASS |

Fastest real-target path after reviewed locks are ready: `scripts\n1-target-fast-track.bat --install-deps --operator="YOUR NAME"`. It advances only through gates that can be automated without weakening human review or destructive-operation controls.


## N1.0 Target Execution v5.1 — Target Progress Visibility & Historical TypeScript Closure

| Item | Status |
|---|---|
| Strict C1–C6 chunk truth | PRESERVED / 6 chunks |
| Granular exact-source target progress | APPLIED / 105 gates |
| C1 granular denominator | 14 |
| C2 granular denominator | 52 |
| C3 granular denominator | 5 |
| C4 granular denominator | 7 |
| C5 granular denominator | 7 |
| C6 granular denominator | 20 |
| Fast-track live progress output | APPLIED |
| C1→C6 post-stage progress checkpoints | APPLIED |
| Plan/dashboard granular snapshot | APPLIED |
| Historical TypeScript incident ledger | 76 errors / 11 files |
| Historical TypeScript source remediation | 76/76 / SOURCE PASS |
| Historical TypeScript real-target verification | 0/76 until C1 typecheck + Vite PASS |
| Automatic source→target promotion | DISABLED |
| C2 architecture | 52 gates / unchanged |
| C4 operator rehearsal | 198 checks / unchanged |
| C6 HA rehearsal | 34 checks / unchanged |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until N1.0 real PASS |

### Progress interpretation

- Source engineering/hardening: **100%** `████████████████████`
- Historical TypeScript source remediation: **100% (76/76)** `████████████████████`
- Historical TypeScript real-target verification in a clean source archive: **0% (0/76)** `░░░░░░░░░░░░░░░░░░░░`
- Strict N1.0 chunks in a clean source archive: **0/6** `░░░░░░░░░░░░░░░░░░░░`
- Granular N1.0 target gates in a clean source archive: **0/105** `░░░░░░░░░░░░░░░░░░░░`

The last two values are intentionally zero in a clean source package because target evidence is generated on the authoritative Laragon/browser/database/HA environments and is not bundled into the source archive. Unlike the previous display, a partial target run now moves the granular percentage immediately even when the enclosing C1–C6 chunk has not yet reached full PASS.


## N1.0 Target Execution v5.2 — Exact Source Activation & Stale Web-Process Guard

| Item | Status |
|---|---|
| Platform version | `1.0.0-rc.67` |
| Installer protocol | `v5.2` |
| Source generation | `n1-v5.2` |
| Executing Installer.php SHA verification | APPLIED / SOURCE PASS |
| Loaded Installer.php path verification | APPLIED / SOURCE PASS |
| Browser-visible source identity | APPLIED / SOURCE PASS |
| `/install/source-status` no-store endpoint | APPLIED / SOURCE PASS |
| Stale web source blocked before DB mutation | APPLIED / SOURCE PASS |
| `nexora:source:status --assert-current` | APPLIED / SOURCE PASS |
| `nexora:source:activate --assert-current` | APPLIED / SOURCE PASS |
| Windows source activation helper | APPLIED / SOURCE PASS |
| Automatic Laragon web-service restart | DISABLED / OPERATOR-CONTROLLED |
| Granular target denominator | **105 / UNCHANGED** |
| Strict C1–C6 denominator | **6 / UNCHANGED** |

### Current interpretation of the reported installer screenshot

- Installer provisioning flow: **approximately 98% / final commit blocker only** `███████████████████░`
- Completed functional stages visible in the run: database reset/recovery, environment write, migrations, core seed, Super Admin, runtime synchronization and final cleanup.
- Current blocker: permanent installation lock receives an exception string that is absent from current v5.1/v5.2 `Installer.php`; this is treated as stale executable source until the web process proves `rc.67 · v5.2 · n1-v5.2` plus the sealed Installer SHA.
- N1.0 source engineering: **100%** `████████████████████`
- N1.0 real certification remains separately evidence-driven; source activation does not fabricate target PASS.


## N1.0 Target Execution v5.3 — Critical Source Set Integrity & CLI/Web Activation Handshake

| Item | Status |
|---|---|
| Platform version | `1.0.0-rc.68` |
| Installer protocol | `v5.3` |
| Source generation | `n1-v5.3` |
| Critical installer source manifest | 14 FILES / SEALED |
| Partial installer deployment | FAIL-CLOSED |
| CLI source activation nonce | APPLIED / SOURCE PASS |
| Web source acknowledgement | APPLIED / SOURCE PASS |
| CLI↔web exact source-set verification | APPLIED / SOURCE PASS |
| Web acknowledgement helper | APPLIED / SOURCE PASS |
| Installation progress persistence | APPLIED / SOURCE PASS |
| Fast-track installation progress bar | APPLIED / SOURCE PASS |
| Granular target denominator | **105 — UNCHANGED** |
| Strict chunk denominator | **6 — UNCHANGED** |
| Source implementation | 100% / SOURCE CERTIFIED TARGET |
| Real target evidence | CONTINUES ON LARAGON |
| N1.1 | BLOCKED until N1.0 real PASS |

### Progress semantics

- Installation execution is shown independently (for example permanent-lock blocker = ~98%).
- N1.0 source engineering remains a separate 100% source-certified track.
- Real C1-C6 progress remains exact-source evidence over the existing 105 granular gates.
- Strict final certification remains 0–6 chunks.
- v5.3 does **not** add certification gates merely to increase source-hardening counts.


## N1.0 Target Execution v5.4 — Runtime Class Convergence & Secure Web Acknowledgement

| Item | Status |
|---|---|
| Platform / installer identity | `1.0.0-rc.69` / `v5.4` / `n1-v5.4` |
| Critical disk installer source set | **22 files / SOURCE PASS** |
| Loaded critical PHP runtime set | **20 classes / SOURCE PASS** |
| Old compiled critical class with new disk file | BLOCKED BY DESIGN |
| CLI activation receipt runtime-class binding | APPLIED / SOURCE PASS |
| One-time web acknowledgement token | APPLIED / SOURCE PASS |
| Public source-status detailed paths/hashes | REDACTED |
| Anonymous source-status acknowledgement mutation | DISABLED |
| Invalid web acknowledgement token | HTTP 403 / NO ACK |
| Successful web acknowledgement token reuse | DISABLED / SINGLE-USE |
| Installer progress sanitized blocker persistence | APPLIED / SOURCE PASS |
| Granular C1-C6 denominator | **105 / UNCHANGED** |
| Strict chunk denominator | **6 / UNCHANGED** |
| C2 architecture | **52 gates / unchanged** |
| C6 HA architecture | **34 checks / unchanged** |
| Real target evidence | REQUIRED |

### Current progress tracks

- Source engineering: **100%** `████████████████████`
- v5.4 runtime/source activation hardening: **100% source** `████████████████████`
- Critical disk source set: **22/22 source** `████████████████████`
- Critical loaded PHP generation sentinels: **20/20 source** `████████████████████`
- Historical TypeScript source remediation: **76/76 source** `████████████████████`
- Real granular target evidence in a clean source archive: **0/105** `░░░░░░░░░░░░░░░░░░░░`
- Strict target chunks in a clean source archive: **0/6** `░░░░░░░░░░░░░░░░░░░░`

The real target bars are intentionally not advanced by source-only diagnostics. On the authoritative Laragon target, source activation is considered converged only after disk `22/22`, loaded runtime `20/20`, and the secure one-time CLI/web acknowledgement all pass.


## N1.0 Target Execution v5.5 — Installer Host/Clock Preflight Stabilization

| Item | Status |
|---|---|
| Host/clock check moved before destructive DB/migrations | APPLIED / SOURCE PASS |
| Final-lock host/clock recheck | APPLIED / SOURCE PASS |
| Windows POSIX umask false blocker | FIXED |
| Installer-specific bounded DB clock skew | APPLIED / 60s DEFAULT / 300s HARD MAX |
| Strict C2/C6 clock skew policy | UNCHANGED / 5s DEFAULT |
| Database UTC clock anchor | STILL REQUIRED FOR INSTALL |
| Atomic rename / flock / secure random / temp writable | STILL REQUIRED FOR INSTALL |
| Same-request Intl locale/timezone normalization | APPLIED / SOURCE PASS |
| Exact host failure diagnostics | APPLIED / `nexora:runtime:host-status --installation` |
| Critical disk source set | 24 FILES |
| Loaded runtime generation set | 22 CLASSES |
| Granular target denominator | 105 / UNCHANGED |
| C2 runtime gates | 52 / UNCHANGED |
| C4 operator evidence architecture | 198 / UNCHANGED |
| C6 HA checks | 34 / UNCHANGED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until N1.0 real PASS |

This batch fixes the observed late host/clock installation blocker source-side. It does not claim the real Laragon host clock is within strict C2/C6 certification tolerance until target evidence is collected.


## N1.0 Target Execution v5.6 — Installer Runtime Readiness Preflight

| Item | Status |
|---|---|
| Seven-component installer runtime readiness | APPLIED / SOURCE PASS |
| Source generation readiness | APPLIED / SOURCE PASS |
| Dependency trust readiness | APPLIED / SOURCE PASS |
| Host/clock installation-safe readiness | APPLIED / SOURCE PASS |
| Resource/capacity installation-safe readiness | APPLIED / SOURCE PASS |
| Effective-policy installation-safe readiness | APPLIED / SOURCE PASS |
| Process-role installation-safe readiness | APPLIED / SOURCE PASS |
| Runtime-activation installation-safe readiness | APPLIED / SOURCE PASS |
| Readiness executes before reset/migrations | APPLIED / SOURCE PASS |
| Final lock reuses same readiness semantics | APPLIED / SOURCE PASS |
| Readiness failure remains cancellable/pre-mutation | APPLIED / SOURCE PASS |
| Exact aggregated readiness command | APPLIED / SOURCE PASS |
| Critical disk source set | 30 / 30 |
| Loaded runtime sentinels | 28 / 28 GUARDED |
| Strict C2 resource/policy/process certification | UNCHANGED |
| Strict C6 HA policy/quorum/capacity certification | UNCHANGED |
| Granular target denominator | 105 / UNCHANGED |
| C2 runtime gates | 52 / UNCHANGED |
| C4 operator architecture | 198 / UNCHANGED |
| C6 HA checks | 34 / UNCHANGED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until real N1.0 PASS |

The purpose of v5.6 is not to create more certification work. It moves the remaining resource/policy/process/activation first-install blockers out of the 98% commit stage and into one early, explainable readiness decision while preserving the stricter production certification track.


## N1.0 Target Execution v5.7 — Source-Bound Install Commit & Runtime Handoff

| Item | Status |
|---|---|
| Full source-tree SHA bound to uninstalled deployment generation | APPLIED / SOURCE PASS |
| Full deployment deep verification in installer readiness | APPLIED / SOURCE PASS |
| Installer readiness components | 8 / SOURCE PASS |
| Host/resource/policy/process strict status removed from normal request-admission blocker | APPLIED / SOURCE PASS |
| Exact host/resource/policy/process fingerprints still required | APPLIED / SOURCE PASS |
| Service/framework/dependency admission remains fail-closed | APPLIED / SOURCE PASS |
| Post-install deployment memo reset | APPLIED / SOURCE PASS |
| Post-install activation epoch adoption | APPLIED / SOURCE PASS |
| Post-install full source/deployment compatibility verification | APPLIED / SOURCE PASS |
| Sealed post-install handoff receipt | APPLIED / SOURCE PASS |
| `nexora:runtime:post-install-status --assert-ready` | APPLIED / SOURCE PASS |
| Critical disk source set | 34 FILES |
| Loaded runtime sentinels | 32 CLASSES |
| C1–C6 granular denominator | 105 / UNCHANGED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until real N1.0 PASS |

The purpose of v5.7 is to make installation 100% mean “the next web request resolves the same source/deployment/activation identity”, without turning strict production certification recommendations into false first-login 503 errors.


## N1.0 Target Execution v5.8 — Clock Semantics & Writable Temp Portability

| Item | Status |
|---|---|
| MySQL/MariaDB 5-hour timezone skew false positive | FIXED / SOURCE PASS |
| Timezone-safe database epoch query | APPLIED / SOURCE PASS |
| Installer app-local writable temp resolver | APPLIED / SOURCE PASS |
| Windows system temp fallback behavior | APPLIED / SOURCE PASS |
| Host installer probe uses resolved temp | APPLIED / SOURCE PASS |
| Resource installer probe uses same resolved temp | APPLIED / SOURCE PASS |
| Strict 5-second host clock certification | PRESERVED |
| Strict system-temp diagnostics | PRESERVED |
| Critical source set | 35/35 |
| Runtime generation sentinels | 33 classes |
| Target denominator | 105 / UNCHANGED |
| N1.0 | TARGET CERTIFICATION |

The reported ~17,999,982 ms skew is approximately +05:00 and matches the previous MySQL timezone-conversion defect. Real rc.73 target execution must confirm near-zero clock skew and a writable selected installation temp path before installer continuation.


## N1.0 Target Execution v5.9 — Exact Resume Provenance & Commit Snapshot Stability

| Item | Status |
|---|---|
| Full source-tree SHA bound to interrupted-install resume | APPLIED / SOURCE PASS |
| Critical source-manifest SHA bound to resume | APPLIED / SOURCE PASS |
| Resume provenance schema 2 | APPLIED / SOURCE PASS |
| Final dependency trust re-resolution before permanent lock | APPLIED / SOURCE PASS |
| Preflight/commit dependency fingerprint stability | APPLIED / SOURCE PASS |
| Preflight/commit source-tree/deployment stability | APPLIED / SOURCE PASS |
| Committed-but-unready handoff suppresses login redirect | APPLIED / SOURCE PASS |
| Sealed handoff receipt required for `--assert-ready` | APPLIED / SOURCE PASS |
| Explicit post-install reconcile command | APPLIED / SOURCE PASS |
| Dedicated runtime-handoff recovery page | APPLIED / SOURCE PASS |
| Runtime readiness + handoff visible installer stages | APPLIED / SOURCE PASS |
| Critical source set | 37 FILES / SOURCE PASS |
| Loaded runtime convergence | 34 CLASSES / SOURCE PASS |
| Granular real-target denominator | 105 UNCHANGED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until N1.0 real PASS |

The v5.9 batch closes source/runtime drift and post-commit recovery ambiguity. It does not convert source validation into real target evidence; C1-C6 still require exact-source Laragon/database/browser/operator/HA execution.


## N1.0 Target Execution v5.10 — Frontend Build Closure & Exact C1 Diagnostics

| Item | Status |
|---|---|
| Historical Laragon compiler baseline | 76 ERRORS / 11 FILES / LOCKED |
| Per-file historical distribution | 50,1,1,1,3,14,1,1,1,1,2 |
| Source remediation | 76/76 / SOURCE PASS |
| Real target compiler verification | PENDING EXACT TARGET RUN |
| Windows + standard TS diagnostic parser | APPLIED / SOURCE PASS |
| Historical-target recurrence detection | APPLIED / SOURCE PASS |
| Missing npm graph detection | APPLIED / SOURCE PASS |
| C1 typecheck diagnostic artifact | APPLIED / SOURCE PASS |
| C1 Vite-build diagnostic artifact | APPLIED / SOURCE PASS |
| C1 PASS diagnostic artifact revalidation | APPLIED / FAIL-CLOSED |
| First-blocker compiler details | APPLIED / SOURCE PASS |
| Frontend build doctor wrappers | BAT / PS1 / SH |
| C1 canonical certification gates | 14 / UNCHANGED |
| C1 setup actions | 2 / NOT GATES |
| Critical installer disk source | 37 / 37 |
| Loaded installer/runtime sentinels | 34 / 34 GUARDED |
| N1.0 granular denominator | 105 / UNCHANGED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED UNTIL REAL N1.0 PASS |

The v5.10 batch converts the historical build failure into exact target diagnostics rather than treating static TypeScript source cleanup as a compiler PASS. A real target run must still install/review the exact npm dependency graph and pass both `tsc --noEmit` and Vite before C1 can close.


## N1.0 Target Execution v5.11 — Transactional Dependency Lock Intake

| Item | Status |
|---|---|
| Missing deterministic root lockfiles identified as highest-value C1 blocker | CONFIRMED |
| Source host offline npm cache | EMPTY / cannot generate real lock |
| Source host Composer | UNAVAILABLE / cannot generate real lock |
| Lock refresh isolated from root project | APPLIED / SOURCE PASS |
| Composer + npm candidate pair required before publish | APPLIED / SOURCE PASS |
| Candidate Laravel range validation | APPLIED / SOURCE PASS |
| npm root manifest parity / integrity / unsafe-source validation | APPLIED / SOURCE PASS |
| Package-version diff dossier | APPLIED / SOURCE PASS |
| Explicit human reviewed promotion | APPLIED / SOURCE PASS |
| Root lock pair rollback on ordinary failure | APPLIED / SOURCE PASS |
| Durable pre-promotion backups | APPLIED / SOURCE PASS |
| Durable promotion journal | APPLIED / SOURCE PASS |
| Explicit crash recovery command | APPLIED / SOURCE PASS |
| Fast-track blocks incomplete promotion | APPLIED / SOURCE PASS |
| Root refresh failure tested on this host | PASS — no root mutation |
| Rollback helper probe | PASS |
| C1 target gates | 14 / UNCHANGED |
| C1–C6 granular denominator | 105 / UNCHANGED |
| N1.0 real target evidence | STILL REQUIRED |

The exact target lock path is now: `refresh-dependency-locks.bat --confirm=REFRESH` → inspect candidate dossier → `promote-reviewed-dependency-locks.bat --reviewer="NAME" --confirm=PROMOTE-REVIEWED` → `n1-target-fast-track.bat --install-deps --operator="NAME"`. Source certification still does not claim reviewed locks or C1 PASS.


## N1.0 Target Execution v5.12 — Reproducible Dependency Toolchain Binding

| Item | Status |
|---|---|
| Transactional root-lock protection | PRESERVED / SOURCE PASS |
| Candidate lock generation workspaces | 2 / ISOLATED |
| Exact A/B lock SHA reproducibility | REQUIRED / FAIL-CLOSED |
| PHP/Composer/Node/npm executable fingerprint | APPLIED / SOURCE PASS |
| Candidate toolchain fingerprint binding | APPLIED / SOURCE PASS |
| Promotion toolchain fingerprint recheck | APPLIED / SOURCE PASS |
| Reviewed-lock attestation toolchain binding | APPLIED / SOURCE PASS |
| Locked install root-lock immutability check | APPLIED / SOURCE PASS |
| Locked install toolchain stability check | APPLIED / SOURCE PASS |
| C1 certification gate count | 14 / UNCHANGED |
| C1–C6 granular denominator | 105 / UNCHANGED |
| N1.0 | TARGET CERTIFICATION |
| N1.1 | BLOCKED until real N1.0 PASS |

The authoritative Laragon target still has to provide Composer/network/cache access so a real reproducible lock pair can be generated. Source certification does not fabricate lockfiles or claim C1 PASS.


## PKG-1 — Usable Release + C1 Closure

| Acceptance item | Required result |
|---|---|
| Reviewed Composer/npm lock pair | PASS / exact human-reviewed hashes |
| C1 | 14/14 |
| TypeScript / Vitest / Vite | PASS / PASS / PASS |
| Exact CLI/web source convergence | PASS |
| Browser installer | 100% |
| Permanent installation lock | PASS |
| Committed runtime readiness | PASS |
| Post-install runtime handoff | PASS |
| Installer Super Admin / DB read-only smoke | PASS |
| Live `/login` → `/admin` smoke | PASS |
| Sealed PKG-1 closure receipt | PASS |

Current source implementation: **100% ready for target execution**. Real PKG-1 closure is not claimed until the exact authoritative target produces the sealed closure receipt. C1 remains 14 gates and total N1.0 remains 105 gates.

Canonical target command after deployment is `scripts\pkg1-usable-closure.bat --operator="REAL NAME" --base-url=http://nexora`. The same command is rerun after each explicit checkpoint. Human lock review uses `--promote-reviewed --reviewer="REAL NAME"`; the final live auth smoke reads `NEXORA_PKG1_SMOKE_PASSWORD` from the process environment only.


PKG-1 final live-login closure on Windows uses `scripts\pkg1-finalize-login-smoke.bat "REAL NAME" http://nexora`; PowerShell prompts for the Super Admin password with `SecureString`, exposes it only to the child process environment, and clears it after execution.

### PKG-1 Composer bootstrap enhancement

PKG-1 now self-bootstraps a verified local Composer when no system/Laragon Composer is available. This removes the manual Composer-install prerequisite while preserving fail-closed TLS/signature verification, dependency-toolchain fingerprinting and zero root-lock mutation before candidate review. PKG-1 target closure still requires real C1 14/14 and installer/login evidence.

### PKG-1 rc.79 — environment + build provenance closure

- `.env.production.example`: restored, secret-free, schema-13 production policy defaults.
- `.env.example`: restored with current session/queue/runtime safety keys.
- normal `npm run build`: provenance-wrapped; raw compiler chain remains `tsc --noEmit && vite build`.
- historical TypeScript remediation boundary: 76/76 diagnostics across 11/11 files source-remediated; target verification still requires dependency-backed C1.
- C1 denominator: 14 unchanged.
- N1.0 denominator: 105 unchanged.


### PKG-1 rc.80 — dependency candidate supply-chain admission

Source implementation is complete for candidate-stage trusted registry/source validation, npm integrity enforcement, Composer/npm vulnerability audits, double-workspace supply-chain fingerprint matching, pre-root-mutation promotion revalidation, and reviewed-lock provenance binding. No secret-bearing registry/auth value is persisted. This does not add gates: C1 remains 14 and total target evidence remains 105. Real candidate audit PASS still requires the authoritative target to have working Composer/npm registry connectivity.


### PKG-1 rc.81 — offline-safe fast resume

PKG-1 target execution now short-circuits verified final closure and reusable C1 before Composer/network work. `scripts\pkg1-status.bat --base-url=http://nexora` reports an exact next action. This is a usability/resume change only: C1 stays 14 and N1.0 stays 105.

## PKG-1 rc.82 / v5.17 operator fast path

The canonical Laragon command is now `scripts\pkg1-run.bat "REAL NAME" http://nexora`. It drives the existing resumable state machine automatically while preserving explicit human review, recovery, installer, and credential checkpoints. This changes no certification denominator: C1 remains 14 gates and N1.0 remains 105.

## PKG-1 rc.83 / v5.18 Windows launcher parser fix

The Laragon one-command launcher is now Windows PowerShell 5.1-safe: `pkg1-run.ps1` is ASCII-only/CRLF and `pkg1-run.bat` parses it with the installed Windows PowerShell parser before execution. This is a launcher compatibility fix only; C1 remains 14 gates and N1.0 remains 105.


## PKG-1 rc.84 / v5.19 PHP-first launcher

The canonical Laragon command remains `scripts\pkg1-run.bat "REAL NAME" http://nexora`, but the batch entrypoint now runs the PHP state-machine launcher directly. Primary PKG-1 execution no longer depends on Windows PowerShell parsing. A canonical BLOCKED result stops after one failed attempt with the exact blocker instead of looping. PowerShell remains only for the hidden-password final login smoke and is parser-guarded. C1 stays 14 and the total denominator stays 105.


## PKG-1 rc.85 / v5.20 Windows npm bridge

The Laragon blocker where Composer passed but npm was reported unavailable is fixed at the central command-runner boundary. Windows `npm.cmd` / `npx.cmd` are translated to `node.exe + npm-cli.js` / `npx-cli.js`; the executed npm payload is fingerprinted, and `pkg1-status` checks the full toolchain before candidate generation. C1 remains 14 and the total target denominator remains 105.


## PKG-1 rc.86 / v5.21 npm bundled-integrity coverage

The package-lock v3 validator now accepts only cryptographically covered `inBundle` children while continuing to block external packages without direct integrity. The exact Tailwind WASM six-child fixture passes; missing owner integrity, missing owner bundle membership, and external missing integrity fixtures fail closed. C1 remains 14 and total target gates remain 105.


## PKG-1 rc.87 / v5.22 semantic reproducibility + TS2589

Source implementation now distinguishes raw lock byte evidence from canonical dependency semantics for independent A/B generation and closes the four target-observed TS2589 errors in Automation/Documents without changing runtime payload validation. C1 remains 14; total remains 105.
