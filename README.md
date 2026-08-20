**Current development candidate: Nexora `1.0.0-rc.94`, installer protocol `v5.29`.** Development closure now precedes final audit/certification.

# Nexora

Nexora is a secure modular Laravel application platform. The N0.x Core feature roadmap is complete through N0.34; N1.0 is the fail-closed release-candidate certification line. RC20 binds certification to the exact source tree, fixes PHPUnit certification-database isolation, makes five-primary-DB evidence mandatory in final mode, adds observed zero-install and existing-upgrade closure evidence, enforces database minimum versions, and independently re-validates the production ZIP.

## Stack

- Laravel 13 / PHP 8.3+
- Relational primary database abstraction; MySQL `root/root` remains the local zero-test default
- Inertia v3
- React 19 + strict TypeScript
- Vite 8 + Tailwind CSS 4
- Untitled UI behind the `@nexora/admin-ui` abstraction
- PHPUnit + Vitest
- `nikic/php-parser` AST security analysis
- PHP `ext-zip` for non-executing ZIP inspection


## N0.15 adds

- Amazon RDS / Aurora primary database presets
- MongoDB, Redis, DocumentDB, ElastiCache and DynamoDB connection catalog
- Admin → Data Connections foundation
- Premium React/installer select system and local flag assets
- Installer-created Super Admin is immediately verified
- Managed database backup aliases normalize to their native SQL driver

- database driver registry with runtime PDO availability checks
- MySQL, MariaDB, PostgreSQL, SQLite and SQL Server installer support
- driver-aware database creation, inspection, reset and Laravel runtime configuration
- native MySQL/MariaDB SQL backup and SQLite snapshot backup
- explicit no-backup destructive consent path when the user skips backup
- final installation run IDs, safe cancellation checkpoints and protected-stage lockout
- relaxed per-endpoint backup throttling to prevent false HTTP 429 failures

- fixes the Untitled input TypeScript truthiness error by normalizing the optional ReactNode before class composition
- uses the official Inertia v3 page-resolver/mounting path (`pages`, `withApp`, `strictMode`) instead of the legacy manual resolver integration
- app-wide Theme/Toast providers receive shared page props explicitly instead of calling `usePage()` outside the Inertia page context, preventing the post-install admin render crash
- Identity step password fields are aligned and include show/hide controls, live pattern checks, Low/Medium/Strong status, strength bar and live confirmation matching
- server and browser share the same strength policy; a valid Low/Medium password requires explicit user consent while the hard minimum pattern remains mandatory
- `Locale` presentation is renamed to `Language`; supported choices show human-readable language + country + flag metadata
- database credentials are collected only in the Laravel installer Database step
- non-empty databases are never wiped silently: create a protected SQL backup, download it, explicitly authorize reset, then the install stream empties the selected schema
- database backup and schema reset both expose progress in the wizard
- backup tokens are installer-session + database-fingerprint bound, expire, and cannot authorize reset until the download endpoint records the backup as downloaded
- zero-reset scripts remove backup/access-key runtime state for a genuine fresh installation

## N0.12 adds

- run-owned deployment cancellation with unique `run_id`, heartbeat and child-process state
- explicit server-side `cancel_stream` control instead of relying on browser connection aborts
- Windows process-tree termination for Composer/npm/Vite cancellation
- previous-worker recovery after refresh, with `deployment_status` polling until the lock is truly released
- deployment lock is released before inactive state is published, avoiding immediate-retry races
- zero-reset scripts remove deployment-control state/cancellation flags
- localization foundation from pre-Laravel bootstrap through Laravel/Inertia/React admin
- English, Urdu, Turkish, Arabic and Russian starter locales
- RTL direction support for Urdu/Arabic in bootstrap, installer and admin shell
- locale persistence through cookie/session and authenticated user preference
- reusable React `LanguageSwitcher` using the Nexora Lucide icon layer
- installer language selector uses supported locale metadata instead of arbitrary free text
- premium hidden-native release ZIP picker with Nexora dropzone, drag/drop, filename and file size
- localization/cancellation/file-picker regression tests and source guards

## N0.11 adds

- `.env.example` is a required packaged artifact and is regression-guarded
- Laravel can boot the deployment/installer flow even when a project-root `.env` does not yet exist
- project-root `.env` is preferred, with protected `storage/app/nexora/environment/.env` fallback when root ACLs are not writable
- an active-environment marker prevents a stale read-only root `.env` from overriding the protected fallback on later requests
- bootstrap APP_KEY persistence is temporary and removed after the real environment is committed
- environment mode is recorded in installation metadata without storing secrets
- real Nexora brand mark/logo and non-empty favicon/app icons
- `lucide-react` is the canonical React/admin icon library behind the existing Nexora Icon compatibility layer
- branded Lucide-compatible icons are also available in the pre-React Blade/bootstrap stages
- premium deployment and installation cards, controls, step navigation, status icons, progress surfaces and log console
- textual `FOUND / NOT FOUND / READY / MISSING` badges are replaced by accessible icon states where status is visual
- source/architecture guards for environment fallback, brand artifacts, favicon integrity and icon-library consistency

## N0.10 retained

- streamed browser deployment using newline-delimited JSON
- real Composer/npm/Vite stdout/stderr in the installer UI
- stage-based progress percentage instead of fake animated progress
- elapsed-time display and one-second server heartbeat
- explicit current stage and completed-stage count
- cancellation for active dependency/build commands
- fixed-command execution timeout
- single-deployment concurrency lock
- no full-page navigation while source dependencies/build are running
- automatic transition to `/install` only after `vendor/autoload.php` + production build verification
- observable final installation: preflight, DB, environment, migrations, seed, Super Admin, runtime, install lock and cleanup
- retry-capable failure state instead of an indefinite spinner
- source guards/tests for both deployment and final-install progress contracts

## Deployment foundations retained

- clean-domain deployment preparation at `/`
- Laravel runtime-directory auto-repair before framework boot
- deterministic `storage/framework/views` compiled-view path
- Windows/Laragon/system Composer and Node/npm discovery
- isolated Composer/npm environment fallback for Apache/FastCGI
- verified private Composer fallback
- checksum-verified private Node.js LTS fallback
- prebuilt production-release mode requiring no Composer/Node/npm on customer hosting
- verified prebuilt release ZIP upload/deploy
- no arbitrary shell command field

## Sentinel security foundation retained

- upload-first quarantine boundary
- SHA-256 package identity and tamper detection
- ZIP path traversal / ZIP-bomb / symlink / nested archive defenses
- mandatory `nexora.json` validation
- Composer + npm supply-chain checks
- PHP AST + tokenizer/static heuristics
- JavaScript/TypeScript/SVG/HTML/CSS security heuristics
- migration and protected-route policy checks
- capability declaration vs detected behavior comparison
- exact file/line findings and risk decisions

## MySQL local standard

```text
Development DB: nexora
Testing DB:     nexora_testing
Host:           127.0.0.1:3306
Username:       root
Password:       root
```

`nexora_testing` is the only database destructively refreshed by quality gates.

## Fresh Laragon / Windows zero test

Extract into a clean project directory and run:

```bat
scripts\setup-zero.bat
```

Then open only:

```text
https://nexora/
```

On the deployment preparation screen authorize the browser if required, then click **Prepare everything automatically**. No application database credentials are requested at this stage. When dependencies and assets verify successfully Nexora opens `/install`; enter MySQL `root / root` only in the Database step. If that database is non-empty, Nexora requires a protected backup download plus explicit reset consent before installation can continue.

After installation run:

```bat
scripts\quality-check.bat
```

## Existing project update

```bat
composer install
npm install
php scripts\create-mysql-database.php
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class="Database\Seeders\Core\NexoraCoreSeeder"
php artisan nexora:runtime:sync
php artisan nexora:runtime:cache
scripts\quality-check.bat
```

## Sentinel CLI

```bat
php artisan nexora:sentinel:scan path\to\package.zip
php artisan nexora:sentinel:scan path\to\package.zip --json
```

Read `ARCHITECTURE.md`, `SECURITY.md`, `docs/n0-13-installer-stabilization.md`, `docs/n0-11-premium-installer-env-resilience.md`, `docs/n0-10-observable-deployment.md`, `docs/n0-9-portable-toolchain.md`, `docs/n0-8-zero-cli-deployment.md`, `docs/n0-6-installer.md`, and `docs/zero-install.md` before extending the deployment lifecycle.

## N0.16 — UI library + Document Engine

N0.16 fixes installer database-driver identity so visible options always submit their registry key (`mysql`, `mariadb`, `pgsql`, etc.) rather than a grouped collection index. Installer controls now compose the Nexora Blade UI library and Admin feature controls/links resolve through `@nexora/admin-ui`.

The publishing foundation is active: **Nexora Document Engine** provides structured documents, typed block/type registries, immutable revision snapshots, permissions/capabilities and Admin document CRUD. Internal Blog/Article features and external packages such as Books or CV/Profile reuse this engine rather than introducing separate private content stores.

See `docs/n0-16-ui-library-document-engine.md` and `docs/n0-16-verification.md`.

## N0.17 — Premium Admin shell + Writer foundation

N0.17 fixes the Inertia `ButtonLink` TypeScript `size: never` collision, adds Light/Dark/System switching, persistent collapsible navigation with tooltips, human-readable data-service labels, and the first semantic Nexora Writer block editor over the universal Document Engine. See `docs/n0-17-admin-shell-writer.md` and `docs/NEXORA_PLAN_STATUS.md`.


## N0.19 — SEO Core

Nexora now includes a theme-independent SEO foundation: canonical metadata, robots/indexing controls, a central JSON-LD Schema Graph, `/sitemap.xml`, structured audit issues and internal-link suggestions. Themes and future extensions consume SEO output through public Nexora contracts rather than storing competing metadata.

Books, CV/Profile, LMS, Booking and Projects are explicitly outside the internal/base roadmap. They are planned as installable external Apps/Extensions/Themes using the same public Nexora contracts. See `docs/NEXORA_PLAN_STATUS.md`.

## N0.20 — Theme Engine

Nexora now includes a Sentinel-gated Theme Engine with a non-executable `nexora-safe-html` package format, immutable theme versions, private previews, atomic activation/rollback, design-token overrides and a built-in safe fallback theme. Public content and SEO semantics remain platform-owned so theme switches cannot destroy document or SEO data.

Admin workspace: `Admin → Themes` (`/admin/appearance/themes`).

## N0.21 — Nexora Studio

N0.21 adds the first visual builder foundation. Studio stores a validated visual tree instead of arbitrary executable markup, supports desktop/tablet/mobile style layers, allow-listed dynamic bindings, reusable components, local undo/redo and immutable server revisions. Published document-bound canvases render through the active Theme Engine while SEO/document semantics remain owned by their existing Core services.

See `docs/n0-21-studio.md` and `docs/NEXORA_PLAN_STATUS.md`.

## N0.22 — Blog & Article Publishing

N0.22 turns Nexora's existing Document, Writer, Editorial, SEO, Studio and Theme foundations into a first-party publishing workflow without introducing a second body-content or SEO store. Articles and blog posts use the canonical structured Document tree, while publishing metadata adds public authors, categories/topics/tags, series, scheduling, featured state, archives and related-content discovery.

Public publishing routes include `/blog`, `/blog/{slug}`, `/articles/{slug}`, taxonomy archives, series archives and public author profiles. Scheduled drafts are processed by `php artisan nexora:publishing:run`, which is registered with Laravel Scheduler and appends an immutable revision when a scheduled publication goes live.

Article-specific SEO defaults, Article/BlogPosting schema nodes and sitemap additions are contributed through Nexora SEO Core. Public rendering continues through the Theme Engine and uses a published Studio canvas when present, with the semantic Document renderer as the safe fallback.

## N0.25 — Media Library, Newsletter & Distribution

N0.25 adds the first-party asset and distribution layer without creating a second content system. Media Library assets can be reused by Writer, Blog/Article publishing and future extensions through stable media IDs and usage records. Newsletter campaigns can reuse published Nexora documents and are delivered through queue jobs using the configured Laravel mail transport. Public RSS is generated from the same canonical Article/Blog document tree.

Key operational routes and commands:

```text
Admin → Media Library
Admin → Newsletter & Distribution
GET /feed.xml
GET /media/{asset-uuid}
php artisan nexora:distribution:run
```

Public media delivery does not require `storage:link`; Nexora streams approved public media through its controlled route. Active-content formats such as SVG/HTML/PHP are intentionally excluded from this initial public media allow-list.


## N0.27 — Automation, Workflow Engine & Webhooks

N0.27 adds the `nexora.automation` Core module. Stable domain events are published through `AutomationEventBusContract`; active workflows evaluate server-side conditions and queue ordered action adapters. Workflow definitions cannot execute arbitrary PHP, JavaScript, SQL or shell expressions.

Initial triggers cover Document lifecycle, Media uploads, Newsletter subscriptions, zero-result public search, verified inbound webhooks and authorized manual runs. Initial actions create Admin notifications, queue signed outbound webhooks or append Audit Trail events. Workflow runs and individual steps persist attempts, outputs and failures; successful steps remain checkpointed when a later action retries.

Inbound and outbound webhook secrets use encrypted model casts. Outbound messages are HMAC-SHA256 signed, timestamped and idempotent; redirects are not followed and production delivery rejects private/reserved destinations. Inbound endpoints validate the same signature format, enforce a five-minute replay window, limit JSON payloads to 1 MB and retain endpoint-scoped idempotent receipts.

## N0.26 — Search, Content Analytics & SEO Crawler

N0.26 adds first-party content discovery and diagnostics on top of the existing Document, Media, Publishing, SEO and Theme systems. Nexora now maintains a rebuildable search projection for structured content/media metadata, exposes public published-content search, records privacy-aware content/search demand, aggregates daily metrics, and can crawl the configured Nexora public host for concrete technical/content SEO observations. The crawler intentionally reports evidence and severity instead of a synthetic SEO score.

Useful commands:

```bash
php artisan nexora:search:reindex
php artisan nexora:analytics:aggregate
php artisan nexora:seo:crawl --limit=250
```


## N0.28 supply-chain security

N0.28 adds trusted publisher public keys, deterministic artifact content digests, Ed25519 detached-signature verification, CycloneDX SBOM inventory, provenance attestations, explicit execution trust profiles, hardened Media uploads, standardized Admin HTTP error presentation and scheduler regression protection. See `docs/n0-28-supply-chain-security.md`.


## N0.30 — Commerce + Billing Foundation

N0.30 adds a provider-neutral Commerce Core for products/prices, currencies, explicit tax rules, customers, orders, invoices, payment transactions, refunds, subscriptions and billing events. Money is stored as integer minor units. Payment gateways are not bundled into Core: verified extensions implement `PaymentProviderContract` and register adapters through `PaymentProviderRegistry`, while gateway secrets stay outside Core.

## N0.29 — Extensions Lifecycle, Forge SDK & Marketplace

N0.29 adds the `nexora.extensions` Core module above Sentinel/Supply Chain. Verified extension, app, integration and Studio-pack artifacts can be installed as immutable versions, assigned explicit runtime capability grants, dependency-checked, enabled/disabled, guardedly rolled back and uninstalled while preserving lifecycle history. Marketplace catalogs can discover and stage packages, but remote bytes always return to quarantine/Sentinel before installation.

Developer commands:

```bash
php artisan nexora:make:extension vendor.package --name="Package Name"
php artisan nexora:extension:list
php artisan nexora:extension:list --json
```

N0.29 also standardizes shared Admin behavior: DataTable headers/pagination remain visible while scrolling, Select no longer inherits generic action-button press animation, and Date/DateTime/Time inputs use the shared React Aria-backed Nexora UI primitives.

## N0.31 CRM Foundation

Nexora now includes a provider-neutral CRM foundation for Organizations, Contacts, Leads, Opportunities, Pipelines, Activities, Notes, relationship timelines, typed custom-field foundations and explicit Commerce customer linking. Sales transitions are transaction-safe and Automation-aware, while email/calendar provider integrations remain extension boundaries.

## N0.32 Membership + Helpdesk Foundations

N0.32 introduces provider-neutral Membership and Helpdesk domains. Membership adds plans, typed entitlements, direct/Commerce-linked membership lifecycle records, central protected-resource access evaluation, and hourly expiry maintenance. Helpdesk adds tickets, requester identity links, public replies, internal notes, assignments, priorities, SLA targets, ticket history, and five-minute SLA breach refresh. LMS, Booking and Projects remain external installable package families.

## N0.33 — Enterprise Tenancy

N0.33 adds organization tenancy, tenant-scoped settings/data, verified domains, invitations, two-key enterprise authorization, SSO adapter contracts, SCIM provisioning foundation, organization switching, reason-bound impersonation and enterprise audit/governance. Existing data is migrated into a default organization so upgrading from N0.32 does not orphan historical content.

## N0.34 — Cloud / HA / Distributed Runtime

N0.34 adds a single-node-compatible distributed-runtime foundation: stable node identity and heartbeats, drain/readiness state, database-backed scheduler leadership, shared cache-lock and object-storage contracts, runtime topology warnings, operational metrics, protected checksum-sealed database backups and offline restore planning. Public `/health/live` and `/health/ready` probes support load balancers without exposing configuration secrets. Nexora reports HA readiness conservatively and does not claim a local-disk/sync-queue deployment is horizontally safe.

The N0.x Core feature roadmap is complete through N0.34. **N1.0 Target Execution v5.12 is now in progress** at platform version `1.0.0-rc.77`; C1–C6 implementation is source-certified and real target/operator evidence remains pending. Use `scripts\quality-check.bat` on Windows or `php scripts/certify-release.php --source-only` for dependency-free source certification. N1.1 remains blocked until the N1.0 dependency/browser/operator evidence is green.

### N1.0 RC9 performance/package certification

After dependencies are installed, `scripts\quality-check.bat` now verifies the production Vite manifest and asset budgets, optimized Laravel boot, and the centralized production ZIP policy. Set `NEXORA_CERT_BASE_URL=https://your-test-host` to include live HTTP latency/header/cache checks. Production packaging is blocked until the exact RC version has both a certification PASS and build-assets PASS report.


### N1.0 RC13 upgrade safety certification

Existing installations must run `php artisan nexora:upgrade:preflight`, create an expiring backup-bound plan with `nexora:upgrade:plan`, and only then run `nexora:upgrade:apply --yes`. Downgrades, incompatible enabled extensions/themes, unverified backups and stale target-version plans are blocked. A protected-stage failure remains in maintenance mode for verified backup restoration; Nexora does not automatically run destructive migration rollback/reset commands.

### N1.0 RC14 environment/config drift safety

Run `php artisan nexora:environment:doctor --production` before and after rebuilding Laravel production caches. Installed deployments have one authoritative root/protected environment source; an explicit marker whose file disappears fails closed instead of silently switching credentials. Installer environment writes remove stale `bootstrap/cache/config.php`, runtime `env()` calls outside `config/*.php` are certification failures, and `.env.production.example` provides a secret-free production baseline. Upgrade preflight includes the same environment doctor.

### N1.0 RC11 final target runner + RC12 diagnostics

For a Laragon diagnostic bundle that keeps running long enough to capture the real blocker:

```bat
scripts\target-diagnostics.bat --install-deps --full
```

The bundle is written under `storage/app/nexora/target-diagnostics/`. It does not dump `.env` and redacts password/token/cookie-shaped values.

Use `scripts\final-target-run.bat --install-deps` on Windows for the automated target pass. Use `scripts\final-target-run.bat --status-only` to generate fail-closed closure status. After real browser, HTTP, backup/restore and multi-node HA evidence is recorded, run `scripts\final-target-run.bat --final`; only this mode may seal the certified production package.


### N1.0 RC15 dependency reproducibility / supply-chain safety

RC15 makes lockfiles part of the certification boundary. Final target runs require `composer.lock` and `package-lock.json`, install only the locked graph (`composer install` + `npm ci`), verify the PHP/Composer/Node/npm toolchain ranges, run Composer/npm vulnerability audits, and bind the audit evidence to the lockfile hashes before a production ZIP can be sealed. Lockfile generation is an explicit maintainer action (`scripts\refresh-dependency-locks.bat`), never an automatic certification side effect.


### N1.0 RC16 filesystem / path portability

RC16 adds `php artisan nexora:filesystem:doctor` plus source-level filesystem contracts. Critical Nexora state uses centralized atomic publication, repository/package paths are certified for Windows/Linux case and naming behavior, and theme/extension archives with case-insensitive collisions or symbolic-link entries are rejected before publication.

### N1.0 RC17 large-file / transfer safety

RC17 adds `php artisan nexora:transfer:doctor` and a shared `TransferSafety` boundary for protected staging, bounded chunked copies, disk-capacity preflight, checksum/byte verification and atomic publication. Media uploads verify the stored object after streaming; marketplace downloads are size-bounded; Theme/Extension ZIPs enforce entry/count/expanded-size/compression-ratio budgets and stream entries instead of loading arbitrary package files into PHP memory. Runtime and installer database backups are written/verified as streams with partial-write cleanup, and protected transfer staging is excluded from production artifacts.


### N1.0 RC18 runtime limits / queue safety

RC18 adds `php artisan nexora:runtime:doctor`, a 64 MiB default application request ceiling, explicit trusted proxy configuration, queue retry-after/timeout alignment, fail-closed queue timeout policy, long-lived worker tenant cleanup/graceful memory restart, and cooperative SEO crawl cancellation. Full target certification still requires reviewed lockfiles plus real Laravel/Vite/browser/restore/HA evidence.


### N1.0 RC20 final closure integrity

RC20 closes release-integrity gaps discovered by the total audit: exact-source SHA-256 attestation from certification through packaging, PHPUnit certification DB binding checks, strict five-family DB matrix in final mode, observed zero-install and existing-install upgrade rehearsals, server minimum-version enforcement, and independent production ZIP re-validation. RC19 concurrency protections remain intact.


### N1.0 RC21 Laragon frontend type contracts

RC21 is driven by the real Laragon `npm run build` failure inventory. It locks Inertia v3 form-data compatibility, non-chainable `transform()` semantics, RequestPayload-safe router helpers, recursive Writer form payloads and shared navigation component contracts into source certification. RC20 exact-source/final-closure integrity remains in force; N1.0 is not DONE until the updated dependency-backed Laragon build and remaining operator evidence are green.


### N1.0 RC23 Target bootstrap / resume

Run `scripts\target-environment-bootstrap.bat` first on Windows/Laragon. It diagnoses the active PHP/php.ini, required PHP extensions, Composer/Node/npm policy and reviewed lockfile presence without downloading tools or resolving unlocked dependency graphs. Use `scripts\target-runtime-run.bat --install-deps` for the fail-fast target gate, then `--resume-latest` after fixing a blocker. Resume reuse is limited to exact source/lock/dependency fingerprints. Use `php scripts/target-runtime-evidence-verify.php --input=<bundle> --require-pass --seal` to validate and seal exact target evidence.

### N1.0 RC22 Target runtime closure runner

Run `scripts\target-runtime-run.bat --install-deps` on Laragon after reviewing/committing `composer.lock` and `package-lock.json`. The runner fails at the first required target blocker, records redacted step logs, and validates the real frontend build plus Laravel boot/doctors. Use `--full` only when ready to delegate migrations/seeding/PHPUnit to the isolated certification database. N1.1 remains blocked until the complete N1.0 closure ledger is green.


## N1.0 target certification

On Windows/Laragon use `scripts\target-certification-orchestrator.bat --install-deps` for readiness and `scripts\target-certification-orchestrator.bat --full` after reviewed locks/dependencies are present. `--final` is fail-closed and requires `--full`.

## N1.0 certification chunks

N1.0 closure now executes in large chunks rather than micro-RC operator steps. C1 is implemented but still requires dependency-backed target PASS. **N1.0-C2 — Laravel Runtime + Core Database Certification** is now code-ready and fail-closed behind exact C1 PASS evidence.

On the trusted Laragon target, after explicitly reviewing dependency locks, run:

```bat
scripts\n1-c1-dependency-certify.bat --install-deps
```

After C1 passes on the same exact source/locks, run `scripts\n1-c2-laravel-runtime-certify.bat`. C2 owns Laravel package discovery, routes/scheduler boot, isolated migration/seed round-trips, PHPUnit, Pint, runtime doctors and optimized boot. See `docs/n1-0-c2-laravel-runtime-core-db.md`.


### N1.0-C3 strict five-database matrix

After exact-source C2 passes, run `scripts\n1-c3-database-matrix-certify.bat`. C3 requires MySQL, MariaDB, PostgreSQL, SQLite and SQL Server to all pass migrations, compatibility tests and selected high-risk flows. See `docs/n1-0-c3-five-database-matrix.md`.

## N1.0-C4 operational recovery certification

`N1.0-C4` consolidates fresh-install/recovery, existing-install upgrade rehearsal, and disposable-target backup/restore evidence behind `scripts/n1-c4-operations-certify.*`. Generate a fail-closed operator kit with `php scripts/n1-c4-evidence-prepare.php --operator="REAL OPERATOR"`.

## N1.0-C5 browser, accessibility, RTL and performance certification

`N1.0-C5` consolidates browser/responsive/theme/RTL, accessibility, HTTP/security/latency, production asset budgets and observed Web Vitals behind `scripts/n1-c5-browser-performance-certify.*`. The runner requires exact-source C2 PASS first and never installs dependencies or owns C3/C4/C6 work. Generate fail-closed browser/Web Vitals evidence with `php scripts/n1-c5-evidence-prepare.php --operator="REAL OPERATOR"`.


## N1.0-C6 multi-node HA and final release closure

`N1.0-C6` is the final N1.0 code-side certification chunk. It revalidates exact-source C1-C5 PASS evidence, requires real HA readiness/rehearsal on the target topology, imports two-or-more-node operator evidence, seals all five operator domains, runs the existing final target certification in `--final` mode, requires all 11 closure domains to pass, independently verifies the production ZIP, and only then writes the C6/N1.0 DONE manifest.

Generate a fail-closed HA kit with `php scripts/n1-c6-evidence-prepare.php --operator="REAL OPERATOR"`, then run `scripts\n1-c6-final-certify.bat --base-url=https://TARGET --evidence=<KIT-DIR>` after C1-C5 target evidence is genuinely PASS. C6 never accepts dependency locks automatically and does not directly execute destructive database commands.


## N1.0 Target Execution Pack

C1-C6 code-side certification chunks are orchestrated on real targets by `scripts/n1-target-execution.php` and its BAT/PowerShell/sh wrappers. The pack is fail-closed and does not constitute a C7 feature chunk.

Target Execution Pack v2 adds trusted Laragon Composer discovery and a review-gated lock refresh path. Use `scripts\n1-target-execution.bat --refresh-locks --confirm-refresh=REFRESH` only when lockfiles must be generated/refreshed. The command uses Composer `--no-install` plus npm `--package-lock-only`, writes `storage/app/nexora/dependency-intake/lock-refresh.md`, and stops for human review. It never accepts locks. After reviewing the exact diff, explicitly run `scripts\dependency-lock-review.bat --accept --reviewer="REAL NAME" --confirm=REVIEWED`, then continue with `--install-deps`.


### N1.0 target support capsule
Every `scripts\n1-target-execution.bat` run writes a ZIP-independent, redacted `storage/app/nexora/n1-target-execution/latest-support.json`. Use this single file for target troubleshooting when Composer/PHP extensions/locks/build/runtime gates block. It does not dump `.env`; secret-shaped values and local project/home paths are redacted. Regenerate from the latest run with `php scripts\n1-target-support-capsule.php`.


## N1.0 Target Execution Pack v2.2

The target executor now combines three operational handoffs: source-bound PHP restart verification after safe Laragon extension remediation, explicit reviewed-lock acceptance/continuation from the latest lock-refresh handoff, and exact-evidence C1-C3 resume. `--refresh-locks` still cannot be combined with review/install, and stale chunk evidence is never reused.


## N1.0 Target Execution v2.3 — Maximum Closure Batch

The remaining code-side closure safeguards are consolidated at `1.0.0-rc.38`. `scripts\n1-target-next-action.bat` is a read-only state planner that reports the exact next safe command without accepting locks or fabricating evidence. C5 now requires browser, Web Vitals and HTTP evidence to refer to one normalized target URL; C6 additionally requires the same HTTPS target for HA evidence. Evidence freshness is centrally configured in `config/nexora-certification-evidence.php`. Upgrade rehearsal evidence must use a concrete older source version inside `config/nexora-upgrade.php`'s supported source window. Production packaging freezes all source/evidence/lock/build inputs and discards the artifact if any input changes during archive creation; independent artifact verification rechecks policy and current-host evidence hashes. N1.0 remains open until real C1-C6 target/operator evidence passes.


## N1.0 Target Execution v2.4 — Session Integrity & Final Release Seal

The current target-certification flow now uses a single exact-source/reviewed-lock certification session for C4-C6 operator evidence, rejects concurrent master target runs, enforces bounded future-clock skew and session freshness, and produces a sanitized certification evidence bundle plus external release seal alongside the production ZIP. The existing eleven-domain closure count is unchanged: `production_package` now represents the independently verified production ZIP + evidence bundle + release seal as one sealed release domain. Real Laragon/browser/HA observations remain mandatory.


## N1.0 Target Execution v2.5 — Signed Release Trust & Offline Verification

The current `1.0.0-rc.40` source freezes the certified PHP/Composer/Node/npm toolchain into C1 evidence, carries that binding through C2-C6 and the certification session, and upgrades the final release to a detached RSA-signed release seal. Runtime signing keys are excluded from source and production archives. Final delivery consists of the production ZIP, sanitized certification evidence ZIP, release seal JSON, detached signature and public key. `scripts/release-offline-verify.php` can independently verify those artifacts without access to the certification host; v2.6 additionally requires an out-of-band signer identity (`--expected-public-key-sha256=<sha256>` or `--trust-anchor=<trusted-anchor.json>`) so a bundled public key cannot authenticate itself. Production packaging also rejects unsafe ZIP paths, symlinks, case-colliding entries and archive-bomb style budgets. Real target/operator/browser/HA evidence remains mandatory and N1.1 stays blocked until N1.0 is genuinely PASS.


## N1.0 Target Execution v2.6 — Signer Identity & Production Supply Chain

`1.0.0-rc.41` closes the remaining release-identity and production-dependency boundary. A runtime trust anchor pins a signer `key_id` and public-key SHA-256; final certification refuses revoked/unregistered keys. Portable verification is strict by default and requires an out-of-band expected public-key SHA-256 or trust-anchor JSON, because a public key bundled beside an artifact proves integrity but not identity. C1 emits a CycloneDX 1.5 SBOM from reviewed Composer/npm locks. Final packaging builds a separate Composer `--no-dev --no-scripts` production vendor stage instead of shipping the certification host's development vendor tree, prepares release provenance, and embeds a per-entry SHA-256 content manifest. The signed final seal binds the trust anchor, SBOM, production dependency stage and provenance. Real target/browser/operator/HA evidence remains mandatory before N1.0 can close.

For strict offline verification, pin signer identity outside the release bundle:

```bat
php scripts\release-offline-verify.php ^
  --production=nexora-<version>-production.zip ^
  --evidence=nexora-<version>-certification-evidence.zip ^
  --seal=nexora-<version>-release-seal.json ^
  --signature=nexora-<version>-release-seal.sig ^
  --public-key=nexora-<version>-release-public.pem ^
  --trust-anchor=trusted-nexora-release-anchor.json
```

Alternatively use `--expected-public-key-sha256=<64-hex-sha256>` and optionally `--expected-key-id=<key-id>`. The trust-anchor/fingerprint must come from a channel independent of the release bundle.



## N1.0 Target Execution v2.7 — Trusted Update Admission & Anti-Rollback

`1.0.0-rc.42` closes the post-signing update boundary. Existing installations keep an independent recipient trust anchor under persistent storage; importing never silently overwrites it, rotation is explicit and archived, and revocation blocks future admissions. A signed production/evidence/seal/signature/public-key set must pass the strict offline verifier against that recipient anchor before Nexora writes a short-lived admission receipt. The release is then staged into an empty directory and the extracted exact source digest must match the signed release. `nexora:upgrade:plan` and `nexora:upgrade:apply` both require that exact receipt, source digest, trust-anchor hash and monotonic source→target version. The sealed plan binds the admission receipt hash, so swapping an admitted release after planning is rejected. Successful upgrades persist the new release-seal hash and signer identity into installation lineage metadata and clear the one-time admission receipt. Downgrades and same-version reinstalls are blocked by default.

Recipient trust bootstrap/rotation is explicit:

```bat
scripts\trusted-update-trust-anchor.bat --import=D:\trusted\nexora-release-anchor.json
scripts\trusted-update-trust-anchor.bat --import=D:\trusted\new-anchor.json --rotate --confirm=ROTATE
```

Before replacing source files, admit and stage the signed release:

```bat
scripts\trusted-update-admit.bat --production=<production.zip> --evidence=<evidence.zip> --seal=<seal.json> --signature=<seal.sig> --public-key=<release-public.pem>
scripts\trusted-update-stage.bat --production=<production.zip> --destination=D:\staging\nexora-update
```

Only the verified staged tree should replace application source while `.env` and persistent storage are preserved. Real target/browser/HA evidence remains mandatory for N1.0.


### Signed certification candidate for C4 ordering

C4 upgrade rehearsal occurs before C6 can produce the final production release, so Nexora does not weaken production update trust to break that ordering cycle. `scripts/trusted-update-candidate.*` creates a short-lived signed **certification-candidate** bound to the exact source tree and reviewed dependency locks. A disposable prior installation can admit it with `scripts/trusted-update-admit-candidate.*`. Runtime acceptance requires the explicit `NEXORA_CERTIFICATION_UPGRADE_REHEARSAL=1` switch, a non-production `local/testing/certification` environment, and an isolated database whose name starts with `nexora_test` or `nexora_cert`. Production environments cannot use this candidate path and still require the complete signed production/evidence/seal/signature/public-key set.


## N1.0 Target Execution v2.8 — Crash-Safe Update Recovery & Trust Continuity

`1.0.0-rc.43` hardens the already signed/admitted update path rather than adding a new product domain. Upgrade apply now writes an integrity-sealed transaction journal before maintenance mode and checkpoints maintenance, migration, runtime-sync/cache and installation-metadata stages. A protected-stage failure leaves a `recovery_required` journal and maintenance mode in place; Nexora never attempts blind down-migrations. `nexora:upgrade:recovery-status` is intentionally read-only and surfaces the verified backup reference/checksum and recovery instruction, while `nexora:upgrade:lineage` exports non-secret installed release lineage for audit. Upgrade CLI commands are explicitly registered by `NexoraServiceProvider`.

Recipient update trust rotation is now a verifiable chain: persisted anchors carry a monotonic rotation sequence and previous-anchor hash, archived anchors link to their replacement key, admission binds the current lineage head/depth, and `trusted-update-trust-anchor --lineage` detects missing, cyclic or inconsistent history. Signed update staging writes persistent stage records; extraction/source-digest failures are marked `quarantined` rather than silently reused. Cleanup is explicit, TTL-gated, confirmation-gated and restricted to the configured managed staging root.


## N1.0 Target Execution v2.9 — Restore Readiness, Maintenance Ownership & Health-Gated Upgrade

`1.0.0-rc.44` hardens the protected upgrade transaction without claiming a target PASS. Upgrade plans now require both a verified source-version backup and a guarded restore-readiness plan. External backup evidence is schema-2, freshness-bounded, database-fingerprint bound, and must prove a non-destructive restore plan. Runtime backups are passed through `BackupRestoreRehearsalService` before an upgrade plan can become ready.

Upgrade apply now owns maintenance mode through an integrity-sealed maintenance lease and refuses to take over a pre-existing maintenance state. The lease is revalidated at protected boundaries. After migrations/runtime sync/cache, two post-upgrade health gates validate database connectivity, route registry, writable runtime directories and installation/source version state before traffic is restored. Installation lineage records backup, database fingerprint, restore-readiness and health hashes. Post-traffic bookkeeping is separated from the destructive transaction boundary so archival/cleanup warnings do not falsely relabel an already-live successful upgrade as a rollback incident.

Recovery remains operator controlled. `nexora:upgrade:recovery-record` writes an integrity-bound decision (`restore_verified_backup`, `retry_pre_migration`, or `manual_investigation`) and never runs a restore or migration rollback. `retry_pre_migration` is rejected after data mutation may have begun. `nexora:upgrade:maintenance-lease --release --confirm=RELEASE` can clear only a stale lease while traffic is already live and no running/recovery transaction exists; it never changes maintenance mode. C4 upgrade evidence now additionally requires restore-readiness, maintenance-takeover rejection, both health gates, recovery-status drill and a recorded operator decision.


## N1.0 Target Execution v3.0 — Distributed Upgrade Coordination & Migration Convergence

`1.0.0-rc.45` extends the protected upgrade transaction across a real cluster without claiming target certification. Upgrade planning seals an exact compatibility assessment hash and a pre-migration ledger hash. Apply refuses to continue if extension/theme/environment/pending-migration state or the migration ledger changes after planning. After `migrate`, every source migration must be present exactly once and all previously applied migrations must remain present before runtime synchronization continues.

Multi-node upgrades use the database-backed `platform-upgrade` runtime lease in addition to the local filesystem lock. Fresh peer nodes must be explicitly moved to `draining` or `maintenance`; Nexora never drains peers automatically. Draining/maintenance web nodes return HTTP 503, queue workers request a graceful quit on the next loop, and such nodes cannot acquire scheduler leadership. Multi-node maintenance additionally requires Laravel's shared `cache` maintenance driver and an approved shared cache store.

Operator flow is explicit: run `php artisan nexora:upgrade:cluster-status`, then on each peer run `php artisan nexora:upgrade:node-status draining --confirm=SET`; a drained scheduler owner can explicitly run `php artisan nexora:upgrade:scheduler-lease --release --confirm=RELEASE`. Once scheduler leadership is local/expired, create a fresh upgrade plan and apply. A protected-stage failure marks the distributed upgrade lease `recovery_required`; it remains a fail-closed barrier until local recovery state is resolved and an operator explicitly runs `php artisan nexora:upgrade:cluster-lock --release --confirm=RELEASE`. `cluster-status` also reports post-upgrade node-version/status convergence.


## N1.0 Target Execution v3.1 — Runtime Quiescence & Mixed-Version Fencing

`1.0.0-rc.46` closes the remaining mutation-race boundary around distributed upgrades. Web requests, queue jobs and scheduled tasks register short-lived `nx_runtime_leases` activity records while they are executing. Drained peers reject new web traffic, queue workers stop before new work, scheduler leadership is version/status fenced, and upgrade planning rejects peer activity or non-zero configured queue backlog. The upgrade owner marks itself maintenance and waits for zero current-node web/queue/scheduler activity before `artisan down` and before any migration begins.

Runtime readiness is also source-version aware. A node whose code version differs from the installed platform version returns HTTP 503, queue workers quit/refuse new incompatible jobs, scheduler leadership is denied, and manual `node-status active` is blocked until version, maintenance, upgrade lease and quiescence checks pass. This prevents an old or prematurely deployed node from rejoining traffic after schema convergence.

Pending first-party migrations are scanned from the `up()` method only. Destructive/contract-breaking operations such as table/column drops, renames, column `change()` calls or raw destructive ALTER/TRUNCATE SQL fail the default upgrade policy. The resulting migration-safety SHA is sealed into the upgrade plan, transaction journal and installation lineage. There is no automatic destructive-migration approval.

Operator inspection:

```bat
php artisan nexora:upgrade:quiescence
php artisan nexora:upgrade:quiescence --wait
php artisan nexora:upgrade:cluster-status
```

C4 real rehearsal must now prove zero in-flight activity, zero configured queue backlog, current-node quiescence, mixed-version fencing, old-node reactivation rejection and migration-safety plan binding before it may pass.


## N1.0 Target Execution v3.2 — Atomic Cutover Barrier & Exact Queue Fencing

`1.0.0-rc.47` closes the admission race between ordinary runtime work and the distributed `platform-upgrade` lease. Web, queue and scheduler activity now acquire their activity lease in the same database transaction that locks and inspects the upgrade-barrier row. Therefore either an activity lease is committed before the upgrade barrier and the upgrader waits for it to quiesce, or the barrier wins and the new activity is rejected. A `recovery_required` barrier remains closed even after its ordinary TTL until an explicit safe operator release.

Queue payload compatibility is now fail-closed: newly created payloads use schema 2, legacy payloads without Nexora metadata are rejected, and an old payload from another Nexora platform version is rejected even when it shares the same major version. This matches the existing requirement that configured queues be empty before schema mutation. Web admission failure returns HTTP 503 with `X-Nexora-Cutover-Barrier: active`; scheduled-task activity admission no longer swallows a barrier refusal. `php artisan nexora:upgrade:cutover-status` reports the barrier, live activity, queue backlog and queue-payload policy without mutating runtime state.

The Laragon build log from the prior branch exposed an Inertia React v3 migration cluster across 11 files. The v3.1 source already contains those repairs; v3.2 converts them into permanent source contracts covering serializable `useForm` payloads, `RequestPayload`, non-chained `transform()`, recursive writer data, typed enterprise SSO forms and horizontal `ButtonLink` navigation. Dependency-backed `tsc`/Vite PASS is still a real-target C1 requirement.


## N1.0 Target Execution v3.3 — Deployment Generation & Client/Cache/Session Fencing

`1.0.0-rc.48` closes the same-version/different-build gap left after exact platform-version fencing. Nexora now derives a deterministic deployment generation from the platform version, certified source tree, frontend manifest, reviewed Composer/npm locks, runtime/upgrade policy hashes, and the session-schema epoch. Production release manifests, signed update admission, upgrade plans, runtime-node heartbeats, queue payloads and installed release lineage bind to that exact generation. A node advertising the correct version but a different generation is incompatible.

Queue payload schema 3 requires both the exact platform version and exact deployment generation. Inertia's asset version is derived from the certified frontend manifest/generation, same-origin raw admin JSON requests carry `X-Nexora-Deployment-Generation`, stale clients receive a fail-closed 409/reload fence, and runtime sessions carry an explicit session-schema epoch. Cache prefixes are generation namespaced so old/new release caches do not silently share keys.

`php artisan nexora:runtime:deployment-status --deep` performs explicit source/material integrity verification without hashing the entire source tree on every request. C4 now requires 54 observed upgrade/cutover/deployment/environment/key-rotation checks, and C6 requires 15 HA observations including deployment-generation, deep-integrity, cache-namespace and session-schema consistency across nodes. These remain real-target evidence requirements; source certification does not convert them into production PASS.


## N1.0 Target Execution v3.4 — Runtime Environment Identity & APP_KEY Continuity

`1.0.0-rc.49` closes the same-artifact/different-runtime-configuration gap. Nexora derives a non-secret runtime environment fingerprint from the active APP_KEY fingerprint, encryption cipher, canonical application origin, database driver family, session backend/cookie/security contract, cache backend/prefix, queue connection, filesystem/object-storage choice and shared maintenance store. Runtime nodes advertise this fingerprint; HA readiness, upgrade planning, cluster convergence, installed metadata and queue payload schema 4 require exact agreement. Raw APP_KEY/previous-key values are never written to status output or release metadata.

APP_KEY changes are explicit maintenance-mode operations. `php artisan nexora:runtime:key-rotation --record --operator="NAME" --confirm=ROTATE` requires the installed active key to be present in `APP_PREVIOUS_KEYS`, records only hashes, and authorizes the temporary environment mismatch while shared maintenance remains active. Every fresh node must then advertise the new environment fingerprint while drained/maintenance. `--commit --confirm=COMMIT` updates installation lineage only after convergence and does not leave maintenance mode or mutate secret key material. Abort removes only the authorization receipt; configuration rollback remains an operator responsibility.

`php artisan nexora:runtime:environment-status` and `nexora:runtime:deployment-status` expose non-secret compatibility state. C4 requires real observations for environment drift, schema-4 queue rejection and key-rotation continuity; C6 requires runtime-environment fingerprint consistency across independent nodes. Source certification does not convert these target observations into PASS.


## N1.0 Target Execution v3.5 — Runtime Activation, Framework Cache & Process Fencing

`1.0.0-rc.50` closes the stale framework-cache/long-running-process gap that remains even when platform version, deployment generation and runtime environment match. Each installed runtime carries an integrity-protected activation epoch and activation fingerprint derived from deployment generation, framework cache snapshots and the PHP runtime family. Queue payload schema 5 binds the exact activation epoch/fingerprint; a worker that booted before an intentional activation rotation becomes incompatible and requests graceful termination before processing new work.

Upgrade apply seals the source activation state, rejects drift after planning, rotates the activation epoch while maintenance protection is active, commits the resulting cache fingerprint into installation lineage, signals `queue:restart`, and requires cluster activation convergence before traffic restoration. `php artisan nexora:runtime:activation-status --deep` validates deployment materials plus framework-cache/activation state. OPCache policy is observed rather than silently mutated: when timestamp validation is disabled, real C4 evidence must include PHP worker-pool restart proof. Runtime activation state is excluded from source/customer packages and strict source-zero certification.

The final production release input freeze now revalidates both `upgrade_policy_sha256` and `activation_policy_sha256`; this also fixes a pre-existing builder asymmetry where the upgrade-policy hash was frozen before packaging but omitted from post-ZIP input revalidation. Automatic PHP-FPM restart, automatic traffic restoration and destructive database rollback remain disabled.


## N1.0 Target Execution v3.6 — PHP Runtime Engine & Extension Convergence

`1.0.0-rc.51` adds a machine/runtime compatibility axis beyond deployment generation, runtime environment and activation state. `RuntimeEngineIdentity` deterministically fingerprints the exact PHP patch level, Zend version, compatibility extension set/versions, PDO driver set, OpenSSL/Sodium/ICU capability versions and a small compatibility-safe INI profile. SAPI-specific process details are reported separately so legitimate FPM → CLI queue handoff is not rejected merely because the process SAPI differs.

Queue payload schema 6 now carries `runtime_engine_fingerprint`; jobs created under a different PHP/extension/PDO profile are rejected before job execution. Node heartbeats advertise the same engine fingerprint and supporting digests, HA readiness requires exact engine convergence, and upgrade planning seals the target engine fingerprint so a PHP patch/extension change between plan and apply forces a new plan. Initial installation and successful upgrade lineage persist the engine fingerprint, PHP version, extension-profile SHA-256 and PDO-driver-set SHA-256.

Operators can run `php artisan nexora:runtime:engine-status --deep` for non-secret engine materials and process diagnostics. C2 now includes that command as a required runtime gate. C4 requires 69 real upgrade/recovery observations including exact PHP patch/extension/PDO convergence and wrong-engine queue rejection; C6 requires 17 HA checks including runtime-engine consistency. Source certification does not claim those target observations have occurred.


## N1.0 Target Execution v3.7 — Database Data Plane & Structural Schema Attestation

`1.0.0-rc.52` closes the database data-plane boundary that migration-ledger names alone cannot cover. `DatabaseDataPlaneIdentity` fingerprints the logical database identity, normalized server version, compatibility-sensitive session profile and a deterministic structural schema snapshot across MySQL, MariaDB, PostgreSQL, SQLite and SQL Server. The structural snapshot covers tables, columns, indexes, foreign keys and, when enabled, views.

Runtime queue payload schema 7 now carries `runtime_database_fingerprint`; nodes advertise the same fingerprint and HA/readiness rejects cross-node database data-plane drift. Upgrade planning seals both the live data-plane identity and structural schema fingerprint. Apply refuses manual schema/session/server drift that occurred after planning, and post-migration convergence requires a fresh structural schema attestation before runtime synchronization can complete. Runtime/external backups are bound to the exact pre-upgrade data-plane and structural schema.

C2 now has 35 ordered gates including database baseline/rebuild structural equality and deep data-plane status. C3 performs the same fresh-vs-rebuild schema equality inside every supported database family. C4 defines 78 real upgrade/recovery observations including manual schema drift rejection, backup/schema binding, wrong-database queue rejection and deep DB status. C6 defines 18 HA checks including runtime database data-plane consistency. These remain real-target requirements; source certification alone does not mark them PASS.


## N1.0 Target Execution v3.8 — Persistent Storage Data Plane & Shared Recovery

`1.0.0-rc.53` closes the persistent/shared-storage boundary across media, object storage and database-backup artifacts. `RuntimeStorageDataPlaneIdentity` derives a non-secret fingerprint from the configured disk roles, backend driver, logical locator/bucket/endpoint/root identity, storage namespace and optional cluster identity, and can perform guarded write/read/delete probes without persisting probe payloads. Local public media additionally requires `public/storage` to resolve to `storage/app/public`; the fresh installer prepares this link non-destructively and refuses to overwrite a conflicting path.

Queue payload schema 8 adds the exact runtime storage fingerprint to the existing platform/deployment/environment/activation/engine/database fences. Runtime nodes advertise the same storage identity and HA readiness requires exact cross-node storage convergence plus a shared backup-storage candidate. Database backups no longer hard-code the local disk: `NEXORA_BACKUP_STORAGE_DISK` selects the backup backend, the backup manifest binds the storage data-plane and backup-disk profile, and restore planning requires an explicit secure external-copy + SHA-256 re-verification step when the source backup disk is not shared.

Upgrade planning seals the exact storage data-plane and rejects storage drift before migrations. Install/upgrade lineage stores the role disks and deep-probe hash. C2 now defines 36 source gates including deep storage status, C4 defines 88 real upgrade/recovery observations, and C6 defines 20 HA observations including storage-data-plane consistency and shared backup storage. These remain real-target requirements; source certification alone does not mark them PASS.


## N1.0 Target Execution v3.9 — Service/Network Data Plane & Approved Outbound Broker

`1.0.0-rc.54` closes the cache/session/queue/mail/TLS/proxy service boundary that driver names alone cannot prove. `RuntimeServiceDataPlaneIdentity` produces a non-secret fingerprint across cache, session, queue, Redis, mail transport, proxy, CA-bundle and trusted-proxy profiles and can perform guarded cache round-trip, Redis PING, queue visibility and mail-DNS probes. Runtime nodes advertise the same identity, HA requires cross-node convergence, installation/upgrade lineage persists it, and upgrade planning/apply rejects service-profile drift before or during schema transition.

Outbound first-party HTTP is centralized behind `ApprovedHttpClient` + `NetworkDestinationPolicy`. External traffic is fail-closed around HTTPS, approved ports, DNS resolution, private/reserved-address rejection and cURL DNS pinning; embedded URL credentials are rejected and the SEO crawler is same-origin only. Webhooks, marketplace catalog/package retrieval and the crawler no longer directly use the Laravel HTTP facade. Queue payload schema 9 adds the exact service-data-plane fingerprint to existing version/deployment/environment/activation/engine/database/storage fences.

C2 now defines 37 source gates including deep service status, C4 defines 98 real upgrade/recovery observations, and C6 defines 21 HA observations including runtime service-data-plane convergence. These are target requirements: source certification does not claim real Redis/mail/DNS/TLS/proxy or multi-node service probes have passed.


## N1.0 Target Execution v4.0 — Host / Platform / Clock Data Plane

`1.0.0-rc.55` closes the host-clock boundary left after service/network identity. Runtime compatibility now includes a SAPI-neutral host fingerprint across OS family, machine architecture, runtime timezone, application/fallback locale, Intl default locale and the signed host policy. Deep target probes verify shared database-clock skew, configured umask, writable temporary storage, atomic rename, advisory file locking and cryptographic random capability without changing host configuration.

Distributed runtime leases, node freshness windows, APP_KEY rotation receipts and upgrade expiry decisions use the shared primary-database clock rather than each node's wall clock. Queue payload schema 10 carries the exact host fingerprint plus a generated timestamp; jobs with a missing or policy-exceeding future timestamp are rejected. Runtime nodes advertise host identity, C6 requires host-profile convergence plus bounded DB-clock skew, and upgrade planning/apply binds the deep host attestation before migrations.

C2 now defines 38 source gates including `nexora:runtime:host-status --deep`, C4 defines 105 real upgrade/recovery observations, and C6 defines 23 HA observations. Nexora does not mutate NTP, system timezone or host locale automatically. Source certification proves these contracts only; real host clocks, filesystems and multi-node convergence remain target evidence.


## N1.0 Target Execution v4.1 — Runtime Resource / Capacity Envelope

`1.0.0-rc.56` closes the live-capacity admission gap left after host/clock convergence. Nexora now derives a deterministic resource-policy fingerprint from the certified runtime-limit policy and configured minimum memory, filesystem, transfer, backup-staging, queue-worker and open-file headroom. The fingerprint represents policy, not momentary free space; live capacity is measured separately by bounded deep probes so normal disk/memory fluctuation does not create a new deployment generation on every request.

`php artisan nexora:runtime:resource-status --deep --assert-installed` checks the existing PHP runtime-limit doctor plus process memory headroom, temporary/storage/transfer/bootstrap/backup-staging free space, queue-worker memory headroom and POSIX open-file limits where observable. Upgrade planning/apply and runtime backup admission fail closed when required capacity is unavailable. Fresh runtime-node heartbeats publish a deep resource probe digest/status; HA readiness requires an exact resource-policy fingerprint and `pass` capacity status from every fresh active node.

Queue payload schema 11 carries the exact resource-policy fingerprint in addition to the existing version/generation/environment/activation/engine/database/storage/service/host metadata. Jobs created under another capacity policy are rejected, while transient free-space values are intentionally not embedded in queue compatibility metadata. C2 defines 39 ordered gates, C4 defines 113 real operator observations and C6 defines 25 HA observations. Source certification proves these contracts only; production memory/disk/file-descriptor headroom still requires real target evidence. Automatic resource-limit mutation, disk cleanup and host tuning remain disabled.


## N1.0 Target Execution v4.2 — Runtime Policy Plane Convergence

`1.0.0-rc.57` closes a configuration-drift gap that source hashes and specialized data-plane fingerprints cannot fully cover: two nodes can run the same source but receive different environment overrides for concurrency TTLs, transfer limits, runtime/deployment fences, upgrade safety, update trust, release/supply-chain trust or dependency-lock policy. `RuntimePolicyPlaneIdentity` therefore derives a secret-free effective-policy fingerprint from behavior-changing values only; machine-local paths, private/public key paths and trust-state file locations are intentionally excluded. Order-insensitive allow-lists are canonicalized before hashing.

Queue payload schema 12 adds the exact effective policy fingerprint to the existing version/deployment/environment/activation/engine/database/storage/service/host/resource fences. Runtime nodes publish policy fingerprint/status/deep digest, HA requires exact policy-plane convergence and PASS policy status, installation/upgrade lineage persists the fingerprint, and upgrade planning/apply rejects effective-policy drift before and after migrations. Deployment generation, production release inputs, provenance and the final release seal bind `config/nexora-policy-runtime.php`.

Deep policy status proves fail-closed production invariants for HTTP transport, cache/session/client generation fences, upgrade backup/quiescence/migration safety, update signature/monotonic source trust, release archive hygiene, SBOM/provenance/no-dev supply-chain rules, deterministic dependency-lock policy, explicit at-least-once external effect semantics, media-upload versus HTTP-body ceiling and multi-node HA minimums. `php artisan nexora:runtime:policy-status --deep --assert-installed` is the real-target operator command. C2 now defines 40 ordered gates, C4 defines 121 observations and C6 defines 27 HA checks. No automatic policy mutation is performed.

## N1.0 Target Execution v4.3 — Operational Process Plane / Process-Role Liveness

`1.0.0-rc.58` closes a process-availability gap left by node-level heartbeat alone. A runtime node can remain fresh in the database while a required web, queue or scheduler process role is absent. `RuntimeProcessPlane` therefore publishes short DB-clock-backed leases for the three operational roles and keeps the deterministic process-role policy fingerprint separate from volatile live lease state. Web requests renew the web role, queue execution plus the queue loop renew queue liveness even while workers are idle, and every scheduler invocation renews the scheduler role independently of scheduler-leader election.

HA readiness now requires configured web/queue/scheduler role quorums plus exact process-policy convergence. Queue payload schema 13 binds the process-role policy fingerprint to the existing version/deployment/environment/activation/engine/database/storage/service/host/resource/effective-policy chain. Upgrade planning/apply and installation lineage bind the same process policy, production release inputs/provenance/seals bind `config/nexora-process-runtime.php`, and operator visibility is available through `php artisan nexora:runtime:process-status --assert-installed --assert-live`, deployment status, cutover status and Admin System Health.

For HA, unsafe indefinite queue blocking is rejected when it would prevent bounded idle-worker heartbeat observation. Nexora deliberately does **not** start, stop, restart or supervise OS processes automatically; the real target must prove its service manager, load-balancer health probes, scheduler and queue workers keep the required role leases fresh. C2 defines 41 ordered gates, C4 defines 131 real upgrade/recovery/process observations and C6 defines 31 HA observations. Source certification does not substitute for those target observations.


## N1.0 Target Execution v4.4 — Framework / Reviewed Dependency Reconciliation

`1.0.0-rc.59` fixes the reviewed dependency-update transition that could previously surface as `This Nexora runtime node does not match the installed platform deployment identity.` Dependency lock hashes remain part of deployment generation by design: changing `composer.lock` or `package-lock.json` must produce a new generation. The fix is an explicit maintenance-only transition, not a weaker identity check.

Nexora now supports reviewed Laravel **13.24.0 or newer within the 13.x major line** and keeps the Composer constraint at `^13.24`. A future 13.26/13.27/etc. lock is therefore allowed after review, while Laravel 14+ remains blocked until a separate major-version compatibility review. The running Laravel framework version must exactly match the reviewed `composer.lock` before reconciliation is allowed.

Safe installed-node dependency refresh:

1. Put the node in maintenance mode with `php artisan down`.
2. Refresh the candidate locks with `php scripts/dependency-lock-refresh.php --confirm=REFRESH`.
3. Review the lock diff and framework/package changes.
4. Accept only the reviewed locks with `php scripts/dependency-lock-review.php --accept --reviewer="REAL NAME" --confirm=REVIEWED --require-refresh-handoff`.
5. Install the exact reviewed Composer/npm locks using the existing C1 dependency workflow.
6. Run `php artisan nexora:runtime:dependency-status`.
7. Run `php artisan nexora:runtime:compatibility-status --deep`.
8. If and only if the drift is classified as dependency-only, run `php artisan nexora:runtime:dependency-reconcile --operator="REAL NAME" --confirm=RECONCILE`.
9. Re-run compatibility and target checks.
10. Only the operator may restore traffic with `php artisan up`.

Dependency reconciliation clears framework caches, commits the new generation, rotates activation state, signals queue restart and writes an auditable transition receipt. It never removes lock hashes from generation and never restores traffic automatically. The critical compatibility/release path is also protected by a human-readable-code contract that rejects generated-looking multi-statement chaining and excessive line lengths in the v4.4 files.


## N1.0 Target Execution v4.5 — Tenant Seed Isolation & TypeScript Stabilization

`1.0.0-rc.60` closes a real installer/tenancy defect where a long-lived installer request could retain an Enterprise organization in `TenantContext` across schema recreation. After migrations created a new default organization UUID, tenant-scoped seed writes could still inherit the stale UUID and fail with MySQL FK 1452 when CRM, Helpdesk or Newsletter defaults were inserted.

`TenantContext` now has explicit `clear()` and scoped `runWith()` lifecycle methods. The installer clears stale tenant state after migrations and before `db:seed`. `NexoraCoreSeeder` clears ambient tenant state, resolves the freshly-created default organization, then seeds CRM pipeline/stages, Helpdesk SLA policies and the default Newsletter list inside an explicit scoped tenant context. `BelongsToTenant` validates explicit/active tenant IDs before writes and fails with a descriptive domain exception instead of reaching a raw foreign-key failure. Regression tests cover stale-context schema replacement, deleted active organizations and scoped-context restoration.

The historical Laragon dependency-backed build error surface remains guarded across all 11 reported Admin TypeScript files. Eight high-density targets were additionally rewritten into human-readable TypeScript/TSX during this batch while preserving Inertia-safe form/payload/submit patterns. Source syntax parsing and Inertia contracts do not replace a dependency-backed `tsc --noEmit`; a fresh rc.60 target run is still mandatory.

C2 now defines 44 ordered runtime gates including the Enterprise tenant-seed regression test. C4 defines 147 real install/upgrade observations including stale-tenant reset, default-organization re-resolution and CRM/Helpdesk/Newsletter FK/idempotency checks.


## N1.0 Target Execution v4.6 — Tenant Execution Boundary

`1.0.0-rc.61` extends the v4.5 stale-seed repair into a long-running execution invariant. Tenant-aware queue roots no longer assign ambient `TenantContext` directly. Each job resolves its owning organization outside the ambient tenant scope, rejects missing/deleted/suspended organizations, then runs the actual workload through `TenantExecutionScope::runRequired()`, which restores the previous context in `finally`. Queue lifecycle hooks clear tenant context before a job and after success, exception and idle-loop reuse; scheduler lifecycle hooks clear tenant state before task execution and after finished/background/failed paths.

The default CRM pipeline, Helpdesk SLA policies and Newsletter list are now seeded inside one tenant-scoped database transaction. This prevents a partial default-tenant data set if one of those seed blocks fails. C2 defines 45 ordered gates with a dedicated tenant-execution regression gate; C4 defines 156 real operational observations including stale/deleted/suspended queue tenants, queue/scheduler context cleanup, transactional defaults and cross-tenant bleed rejection. C6 remains 34 checks because this boundary has no independent per-node configuration to converge.

The clean source host still cannot claim dependency-backed TypeScript success because npm dependency installation timed out and no reviewed `node_modules` graph exists. Existing source parser and historical 11-file Inertia regression guards remain mandatory, while a fresh real-target `tsc --noEmit` is still required.


## N1.0 Target Execution v4.7 — Fresh-Install Dependency Trust Bootstrap

`1.0.0-rc.62` fixes the installation-lock failure where a clean source package reached 98% and then refused to write permanent installation state solely because `storage/app/nexora/dependency-intake/reviewed-locks.json` was absent. Clean source archives intentionally exclude operator review evidence, so fresh-install runtime identity and formal human review are now modeled as separate trust layers rather than one boolean.

`ReviewedDependencyState` reports deterministic runtime identity independently from formal review state. `FreshInstallDependencyTrust` allows a missing review only for the initial install bootstrap and only when `composer.lock` and `package-lock.json` are valid, the running Laravel version matches the Composer lock, production Composer packages match `vendor/composer/installed.json`, and package.json dependency declarations match the package-lock root. A present-but-corrupt, stale or invalid review file remains fail-closed and can never trigger bootstrap fallback. The installer writes a runtime-local bootstrap receipt and records `dependency_trust_mode=bootstrap-verified`; runtime compatibility uses the deterministic dependency fingerprint so the freshly installed node does not immediately enter a generic deployment-identity 503.

Formal review is still required for C1/C6/release closure. Once reviewed-lock attestation exists, `php artisan nexora:runtime:dependency-review-sync --operator="REAL OPERATOR" --confirm=SYNC` promotes the installation provenance to `reviewed` without changing deployment generation. The sync refuses broader source/deployment drift. `nexora:install:doctor` now reports fresh-install dependency trust before database mutation, so missing locks/runtime mismatches are discovered before the installation-lock stage. C4 defines 168 target observations including bootstrap receipt, stale-review rejection, exact runtime-lock matching, review sync and retrying a previously blocked installation lock.


## N1.0 Target Execution v4.8 — Crash-Safe Installation Commit Boundary

`1.0.0-rc.63` closes the durable-installation commit boundary. New installations publish a schema-2 `installed.lock` with a canonical SHA-256 integrity seal. A corrupt/tampered lock keeps the installer closed and returns fail-closed diagnostics instead of silently reopening destructive setup controls. Existing legacy unsealed locks remain readable for backward compatibility and are resealed automatically on the next metadata update. `php artisan nexora:install:lock-status --assert-valid` provides a read-only operator check.

Fresh-install dependency bootstrap receipts are now prepared in memory, published only after every final runtime attestation passes, and orphan receipts from an interrupted pre-commit install are removed before the next attempt. `installed.lock` is the durable commit point; backup-token cleanup, deployment-access cleanup, progress transport and run-control bookkeeping after that point are best-effort and cannot convert a committed installation into a reported failure. HTTP middleware returns a 503 fail-closed response for an invalid lock while preserving installer lockout.

C2 now defines 49 ordered runtime gates, including four dedicated install-lock/receipt regression gates. C4 defines 180 real operator observations with sealed-lock, tamper, legacy compatibility, staged-receipt, crash/retry and post-commit telemetry drills. C6 remains at 34 HA checks because installation commit integrity is not a topology metric. Real target execution remains mandatory before N1.0 can close.


## N1.0 Target Execution v4.9 — Installer Consent & Preflight Stabilization

`1.0.0-rc.64` closes four operator-facing installation regressions. Deterministic dependency trust is resolved during installation preflight, before any database mutation; the selected trust mode and installer protocol are emitted in the stream so a stale deployment cannot masquerade as current code until the final 98% lock stage. Recoverable interrupted databases now expose an explicit **Resume** or **Discard and start clean** choice. The destructive branch restores the protected-backup and explicit no-backup/database-name consent controls instead of silently hiding them during recovery mode.

The final **Install** CTA now uses the HTML `hidden` state rather than the legacy `.is-hidden { display:none!important }` class that overrode JavaScript visibility. Password handling separates a non-bypassable hard safety floor (10+ characters and at least three character classes) from the recommended Strong pattern. Weak, Low and Medium passwords above that floor require explicit risk consent; Strong passwords do not. The permanent installation metadata records the selected database action/protection mode and password-strength consent without storing the password. C2 now defines 51 real-target gates and C4 defines 191 operator observations.


## N1.0 Target Execution v5.0 — Installation Resume Provenance & Fast-Track Closure

`1.0.0-rc.65` prevents interrupted-install recovery from crossing installer generations. Each installation run now records an exact non-secret resume fingerprint covering the platform version, installer protocol, installer source manifest, migration manifest, Core seeder manifest and deterministic Composer/npm lock hashes. A partial schema can be resumed only when that fingerprint exactly matches the currently deployed installer. Legacy runs without provenance and runs from changed source/migrations/seeders/dependency locks are shown as interrupted but non-resumable; the installer forces the Start clean path and preserves the existing backup / explicit overwrite-consent boundary.

The target execution workflow also gains `scripts\n1-target-fast-track.bat` (plus PowerShell/sh wrappers). It reuses valid evidence and drives the maximum safe automated C1→C3/session/operator-kit path in one command, while refusing to auto-review locks, refresh locks, reset databases, generate signing trust, or fabricate operator/browser/HA evidence. `n1-target-next-action` and the target plan now expose C1–C6 completion percentage and a powerbar-friendly progress payload. C2 defines 52 real target gates, C4 defines 198 operator checks and C6 remains 34 HA checks.


## N1.0 Target Execution v5.1 — Target Progress Visibility & Historical TypeScript Closure

`1.0.0-rc.66` fixes the roadmap visibility problem without weakening certification semantics. C1–C6 remain strict chunk-level PASS/FAIL boundaries, but Nexora now also computes exact-source granular gate progress from the canonical target-runner evidence. The current runners define 105 certification gates: C1 14, C2 52, C3 5, C4 7, C5 7 and C6 20. `n1-target-fast-track` prints both strict chunk status and granular gate progress, while `n1-target-execution` emits a fresh progress checkpoint after every C1–C6 stage. `n1-target-plan` exposes the same granular snapshot for dashboards/reporting.

The historical Laragon compiler incident is now represented by a permanent remediation ledger instead of a generic source-contract claim. The observed build contained 76 TypeScript errors across 11 files. v5.1 verifies that the corresponding current-source failure families are removed and reports `76/76 source remediated`; it will report `76/76 real target verified` only after exact-source C1 evidence contains successful `typecheck` and `vite-build` gates. Source remediation can never auto-promote to target verification.

This means a clean source archive can honestly show `0/105` real target gates because it intentionally contains no real Laragon evidence, while still showing completed source remediation. After a real target run, partial progress moves immediately—for example C1 can display `4/14` even when the whole C1 chunk is still pending.


## N1.0 Target Execution v5.2 — Exact Source Activation & Stale Web-Process Guard

`1.0.0-rc.67` closes the deployment ambiguity exposed by an installer target that continued to emit the historical reviewed-dependency final-lock exception even though the exact string no longer existed in current `Installer.php`. The installer now has three explicit source-activation coordinates: protocol `v5.2`, source generation `n1-v5.2`, and the sealed SHA-256 of the executing `Installer.php`. `/install` renders those values before the user can touch the database, `/install/source-status` exposes the running web-process identity with no-store headers, and installation fails before the database stage when the executing class/path/hash does not match the package.

`php artisan nexora:source:status --assert-current` inspects the current PHP process. `php artisan nexora:source:activate --assert-current` clears Laravel caches, records a CLI activation receipt and reports whether a Laragon web-service reload may still be required. `scripts\n1-source-activate.bat` wraps the same flow for Windows. This source-activation layer does **not** add new C1–C6 denominator gates; the granular target denominator remains 105 so progress does not move backward simply because diagnostics became stronger.


## N1.0 Target Execution v5.3 — Critical Source Set Integrity & CLI/Web Activation Handshake

`1.0.0-rc.68` strengthens v5.2 from a single-file Installer.php identity check into a sealed 14-file critical installer source set. `bootstrap/nexora-source-manifest.json` records SHA-256 hashes for the installer, controller, dependency-trust services, installation state, source-identity/handshake services, source commands, installer Blade view, web routes and Nexora service provider. The manifest itself is sealed in `config/installer.php`. A partial deployment therefore fails before database mutation even when the new Installer.php was copied successfully but another installer-path file remained stale.

Source activation now has an explicit CLI-to-web handshake. `nexora:source:activate` issues a short-lived sealed nonce tied to the exact critical-source-set fingerprint. After Laragon/PHP reload, `/install/source-status` acknowledges that same nonce from the web process. `php artisan nexora:source:status --require-web-ack` proves both PHP execution contexts have converged to the same platform/protocol/generation/source set. `scripts\n1-source-web-ack.bat [base-url]` automates the HTTP acknowledgement and verification.

Installation progress is now persisted separately at `storage/app/nexora/n1-target-execution/installation-progress.json` and rendered above C1-C6 fast-track progress. A failed permanent-lock stage is therefore visible as installation ~98% while target certification remains independently measured. The N1.0 granular target denominator remains 105 and strict chunk denominator remains 6; v5.3 adds diagnostic certainty without moving the goalposts.


## N1.0 Target Execution v5.4 — Runtime Class Convergence & Secure Web Acknowledgement

`1.0.0-rc.69` closes the remaining stale-OPcache ambiguity left after v5.3. The critical installer disk manifest expands from 14 to 22 files, covering installation run/resume control, database provisioning/backup/driver registry, environment writing, system requirements and password policy in addition to the existing installer/controller/dependency/source-activation surface. Twenty critical PHP classes now expose the exact `n1-v5.4` runtime generation sentinel; `SourceActivationIdentity` reflects the **loaded class constants**, not only current disk hashes, and requires `20/20` runtime convergence before installation can proceed.

The web acknowledgement is now protected by a one-time 256-bit activation token. `GET /install/source-status` without the token is read-only and redacted: it exposes version/protocol/generation plus disk/runtime convergence counts, but no absolute paths, source fingerprints, file hashes, runtime-class result map or activation nonce. A valid `X-Nexora-Activation-Token` may acknowledge the exact CLI activation receipt once; the local token file is deleted immediately after success. `scripts\n1-source-web-ack.bat` and the shell equivalent retrieve the token locally through `nexora:source:status --web-token`, submit it in the header and then require the CLI/web acknowledgement to converge.

Installation progress also persists the sanitized failure summary. A run that fails at the permanent lock can therefore render `INSTALLATION 98% · FAILED · stage=lock` followed by the exact redacted blocker instead of only a stage name. v5.4 deliberately does **not** add C1-C6 gates: the granular target denominator remains 105 and strict chunk denominator remains 6.


## N1.0 Target Execution v5.5 — Installer Host/Clock Preflight Stabilization

`1.0.0-rc.70` fixes the late installation-lock failure where the full production/HA host profile was treated as a prerequisite for writing the first permanent install lock. The installer now runs a dedicated **installation-safe host/clock attestation before destructive reset/migrations**, then re-runs the same bounded safety profile immediately before commit. Strict C2/C6 host certification remains a separate, stronger target requirement.

Windows no longer fails the host profile solely because a POSIX `umask` does not map meaningfully to the platform. POSIX umask remains enforced on applicable operating systems. The installer clock-skew limit is independently bounded (default 60 seconds, hard maximum 300 seconds) while strict target certification remains at the existing 5-second default. Database clock-anchor availability, monotonic clock support, writable temp storage, atomic rename, advisory locking and secure randomness remain installation blockers. Exact failed checks and measured skew are surfaced through `php artisan nexora:runtime:host-status --installation`.

The installer also normalizes timezone/Intl locale inside the same long-lived request after writing environment values, preventing the final host profile from comparing the new application locale against stale process-global locale state. Host attestation source is now part of the critical source set: 24 sealed files and 22 loaded runtime-generation sentinels. The granular target denominator remains 105.


## N1.0 Target Execution v5.6 — Installer Runtime Readiness Preflight

`1.0.0-rc.71` generalizes the v5.5 host/clock repair across every remaining runtime attestation that could first fail at the 98% permanent-lock stage. The installer now evaluates a seven-component installation-readiness profile — exact source activation, deterministic dependency trust, host/clock safety, resource headroom, effective runtime policy, process-role policy and runtime-activation writeability/integrity — immediately after the selected database connection is verified and **before any destructive reset, migration or seeding**. The same readiness contract is re-evaluated immediately before the sealed installation commit.

Installation-safe resource thresholds are deliberately bounded below the stricter upgrade/HA capacity envelope: they prove that the current process has enough memory and writable free space to install safely without pretending that production capacity certification has passed. Installation-safe policy checks preserve queue schema, dependency-lock, external-effect and upload/body-limit invariants while release-signing, supply-chain, HTTPS and HA-only policy checks remain strict C2/C6 requirements. Installation-safe process checks enforce lease/throttle, queue schema and blocking-liveness safety without requiring live HA role quorums. Runtime activation readiness validates existing epoch integrity, secure randomness and activation-path writeability before schema mutation.

`php artisan nexora:runtime:install-readiness --json --assert-ready` now returns the exact blocker across all seven components in one command. A failed readiness stage remains cancellable/pre-mutation and therefore cannot create a false protected-schema resume state. Critical source convergence expands from 24 to 30 disk files and from 22 to 28 loaded runtime class sentinels. The granular target denominator remains exactly 105.


## N1.0 Target Execution v5.7 — Source-Bound Install Commit & Runtime Handoff

`1.0.0-rc.72` closes the commit-to-first-request gap. The uninstalled deployment identity now includes the complete Nexora source-tree SHA-256 instead of a source-null fallback, and installation readiness performs a full deployment deep verification in addition to the critical source-set check. The sealed installation lock therefore records an exact full-source digest and a deployment generation derived from that digest.

Normal runtime request admission now separates identity compatibility from strict C2/C6 certification for host, resource, policy and process planes. Exact fingerprints remain mandatory, while stricter production/HA health recommendations no longer quarantine an otherwise correctly installed node or block the first Super Admin login. Service, framework and dependency runtime failures remain fail-closed.

After `installed.lock` is committed, Nexora forgets the pre-install memoized deployment identity, adopts the current activation epoch, verifies the full source tree, runs `RuntimeVersionGuard`, and writes a sealed post-install handoff receipt before reporting 100%. `php artisan nexora:runtime:post-install-status --assert-ready` reproduces the same next-request compatibility check. Installer readiness now reports eight components. The C1–C6 granular denominator remains 105.


## N1.0 Target Execution v5.8 — Clock Semantics & Writable Temp Portability

`1.0.0-rc.73` fixes a real Windows/Laragon installation-readiness failure where MySQL/MariaDB clock skew could be reported at almost exactly the local UTC offset. `UNIX_TIMESTAMP(UTC_TIMESTAMP(6))` is unsafe when the database session timezone is non-UTC because the UTC datetime is fed back into a session-timezone conversion. Nexora now uses `UNIX_TIMESTAMP(CURRENT_TIMESTAMP(6))`, preserving epoch semantics across session timezones while keeping the strict 5-second C2/C6 clock policy unchanged.

Installer filesystem safety no longer hard-depends on `sys_get_temp_dir()`. `RuntimeWritableTempDirectory` prefers an optional configured installation temp path, then app-local `storage/framework/nexora-temp`, then `storage/app/nexora/tmp`, and finally PHP system temp. Every candidate is verified with an actual write probe. Host installation probes and installer resource-capacity checks share this resolved path; strict production host/resource diagnostics can still observe the PHP system temp independently. The readiness JSON now exposes the selected temp path, fallback source and candidate failures.

The critical source set is 35 files and the loaded runtime-generation set is 33 classes. Real target denominator remains 105.


## N1.0 Target Execution v5.9 — Exact Resume Provenance & Commit Snapshot Stability

`1.0.0-rc.74` strengthens interrupted-install recovery and the permanent commit boundary without changing the 105-gate target denominator. Resume provenance is now schema 2 and binds the complete source-tree SHA-256, source file count, critical source-manifest SHA-256/file count, source generation, migrations, core seeders and both dependency locks. A protected partial schema can therefore resume only under the exact full source generation that created it; same-version partial source changes require Start clean rather than mixing installer provenance.

The installer also re-resolves deterministic dependency trust immediately before permanent lock construction and compares preflight versus commit fingerprints, manifests, lock hashes, Laravel lock/runtime versions and trust mode. Full source activation, critical source set, runtime-class fingerprint, full source-tree SHA and deployment generation are likewise compared across the preflight/commit boundary. Any drift aborts before `installed.lock` publication.

Post-commit runtime handoff is no longer treated as generic bookkeeping. A committed installation whose runtime handoff or sealed handoff receipt is not current does not redirect directly to login. The installer exposes a dedicated runtime-handoff recovery page and the explicit `nexora:runtime:post-install-reconcile --confirm=RECONCILE` command. Progress now renders runtime-readiness and handoff as first-class stages.


## N1.0 Target Execution v5.10 — Frontend Build Closure & Exact C1 Diagnostics

`1.0.0-rc.75` turns the historical Laragon TypeScript incident into an exact, machine-readable C1 regression boundary without changing the 105-gate target denominator. The recorded target baseline remains **76 compiler diagnostics across 11 Admin TS/TSX files** with the original per-file distribution `50,1,1,1,3,14,1,1,1,1,2`. Source remediation remains distinct from target verification: static source contracts can prove the known unsafe patterns are gone, but only dependency-backed `tsc --noEmit` plus the Vite build can mark the baseline target-verified.

`scripts/n1-c1-frontend-build-doctor.php` parses both Windows and standard TypeScript diagnostic formats, normalizes target paths, groups diagnostics by file/code, identifies recurrence inside the 11 historical targets, detects missing dependency-graph failures, and can either inspect an existing build log or run the current target typecheck/build. It is diagnostic-only and never promotes C1. The canonical `n1-c1-dependency-certify.php` remains authoritative.

The C1 runner was refactored into human-readable ordered logic and now writes `typecheck.diagnostics.json` and `vite-build.diagnostics.json` alongside the raw step logs. A frontend first blocker therefore reports error count, affected file count, historical-target recurrence, first compiler diagnostic and whether the reviewed npm graph is missing. The canonical C1 denominator remains 14 gates; setup actions (`composer-install`, `npm-ci`) and diagnostic artifacts do not move the goalposts. Critical installer source remains 37 files / 34 loaded runtime classes because this batch hardens compiler/C1 evidence rather than the installer runtime surface.

C1 PASS verification also re-hashes both frontend diagnostic artifacts and requires the same platform/source/step identity plus `compiler_clean=true`, zero compiler diagnostics and zero historical-target diagnostics. Missing, edited or stale diagnostic evidence invalidates C1 PASS.


## N1.0 Target Execution v5.11 — Transactional Dependency Lock Intake

`1.0.0-rc.76` targets the highest-value remaining C1 blocker: the source package intentionally has no fabricated `composer.lock` or `package-lock.json`, while C1 requires reviewed deterministic lockfiles before locked install, provenance, SBOM, TypeScript, Vitest and Vite evidence can pass. The prior refresh path could update root `composer.lock` before npm refresh completed, leaving a partially refreshed pair if the second package manager failed.

v5.11 replaces that with a two-phase reviewed intake. `scripts\refresh-dependency-locks.bat --confirm=REFRESH` resolves both lockfiles inside an isolated staging workspace under `storage/app/nexora/dependency-intake`, validates Laravel range, npm root-manifest parity, npm integrity metadata and unsafe source schemes, records version diffs and publishes review candidates only when the pair is valid. Root lockfiles are never touched by refresh.

After human diff review, `scripts\promote-reviewed-dependency-locks.bat --reviewer="NAME" --confirm=PROMOTE-REVIEWED` re-verifies source/manifest/candidate hashes, writes durable pre-promotion backups, promotes the pair, runs strict root dependency contracts, creates the existing reviewed-lock attestation, verifies it, and rolls both locks plus dependency-intake evidence back on any ordinary failure. A durable promotion journal records `prepared → composer-promoted → pair-promoted → strict-validated → review-attested → complete`; an interrupted process is recovered explicitly with `scripts\recover-dependency-lock-promotion.bat --confirm=ROLLBACK`. Fast-track refuses to run while an incomplete promotion journal exists.

The C1 gate count remains 14 and the total N1.0 target denominator remains exactly 105. No lockfile or reviewed evidence is fabricated by source certification.


## N1.0 Target Execution v5.12 — Reproducible Dependency Toolchain Binding

`1.0.0-rc.77` hardens the v5.11 transactional lock-intake workflow so reviewed dependency locks are not merely valid, but reproducible under one exact package-manager toolchain. Candidate refresh now generates the Composer/npm lock pair twice in independent isolated workspaces. Candidate publication is blocked unless both `composer.lock` and `package-lock.json` SHA-256 values match exactly across both generations.

The candidate dossier binds PHP, Composer, Node and npm versions plus executable SHA-256 fingerprints into one dependency-toolchain fingerprint. Promotion is refused if the active toolchain differs from the candidate toolchain; operators must regenerate and re-review on the new toolchain instead of silently carrying a candidate across package-manager drift. The reviewed-lock attestation seals that same toolchain fingerprint.

C1 also snapshots reviewed root lock hashes and the dependency-toolchain fingerprint immediately before `composer install` / `npm ci` and compares them again after installation. Any lock mutation or package-manager drift blocks C1. These are stronger setup/evidence invariants, not new certification gates: C1 remains 14 gates and the N1.0 granular denominator remains 105.


## PKG-1 — Usable Release + C1 Closure

`1.0.0-rc.79` changes execution strategy from open-ended source hardening to a single resumable usability chunk. `scripts/pkg1-usable-closure.*` owns the complete PKG-1 state machine: reproducible dependency candidate generation, explicit human review/promotion, locked C1 dependency installation, the 14 C1 gates, exact source/web activation, browser installer completion, committed runtime readiness, post-install handoff, and non-destructive live Super Admin login/admin smoke.

Clean packages intentionally ship neither reviewed lockfiles nor dependency directories. The runner therefore stops at explicit `waiting-review`, `waiting-source-restart`, `waiting-install`, or `waiting-auth-smoke` checkpoints instead of inventing evidence. Existing `.env` and APP_KEY are never overwritten; a clean package creates `.env` from `.env.example` only after C1 dependencies are installed and generates an APP_KEY only when missing. Fresh selected-database readiness remains inside the browser installer; CLI readiness is rechecked after the permanent lock commits.

PKG-1 closes only when a sealed `storage/app/nexora/pkg1/closure.json` binds the exact source SHA, C1 evidence, reviewed lock pair, build assets, permanent installation lock, post-install handoff and live login/admin smoke. `php scripts/pkg1-closure-evidence-verify.php` independently re-verifies that receipt. C1 remains 14 gates and the N1.0 denominator remains 105.


PKG-1 final live-login closure on Windows uses `scripts\pkg1-finalize-login-smoke.bat "REAL NAME" http://nexora`; PowerShell prompts for the Super Admin password with `SecureString`, exposes it only to the child process environment, and clears it after execution.

## PKG-1 verified Composer self-bootstrap

PKG-1 no longer requires a system-wide Composer installation before the first clean-source run. The closure runner first prefers an operator-selected Composer on `PATH`; when none is available it executes `scripts/composer-bootstrap.php`, which follows Composer's official programmatic-installer pattern: fetch the current official installer signature, fetch the installer over TLS, verify the installer SHA-384, then install Composer 2 locally under `storage/app/nexora/tools/composer/composer.phar`. The local PHAR is never bundled in the source ZIP and its exact version and SHA-256 enter the existing dependency-toolchain fingerprint.

The bootstrap remains fail-closed: TLS peer verification is never disabled, an invalid installer signature is rejected, a Composer version outside the certified `>=2.7 <3.0` range is rejected, and DNS/TLS/download failures stop PKG-1 before any root dependency lock mutation. Pre-vendor PKG-1 dependency scripts also avoid optional `mbstring` calls so bootstrap can reach deterministic dependency installation on minimal PHP hosts.

### PKG-1 offline Composer handoff
If the target cannot reach Composer over DNS/TLS and no system/Laragon Composer is available, supply a trusted local Composer PHAR explicitly. Nexora requires an exact SHA-256 and copies the verified PHAR into its private runtime-tool directory before executing it.

Windows example:

```bat
set NEXORA_COMPOSER_PHAR=C:\path\to\composer.phar
set NEXORA_COMPOSER_PHAR_SHA256=<64-character-sha256>
scripts\pkg1-usable-closure.bat --operator="YOUR NAME" --base-url=http://nexora
```

The external PHAR is never accepted without the exact SHA-256, and dependency lockfiles remain untouched when verification fails.

## PKG-1 rc.79 — production environment + build provenance closure

`1.0.0-rc.79` / installer protocol `v5.14` restores the release environment templates that the clean PKG-1 archive must carry. `.env.production.example` is secret-free and HTTPS/session-safe by default, pins queue payload schema 13, and exposes fail-closed defaults for cutover, activation, database/storage/service/host/resource/policy/process runtime planes. `.env.example` carries the same current runtime safety keys for a clean Laragon bootstrap without embedding credentials.

Normal `npm run build` is now provenance-wrapped. `scripts/pkg1-build.php` emits `NEXORA_BUILD_IDENTITY`, binds the exact source tree, reviewed lock pair, TypeScript/Vite configuration and all 11 historical TypeScript remediation files, then executes the unchanged compiler chain through `npm run build:raw` (`tsc --noEmit && vite build`). A successful build writes `storage/app/nexora/certification/pkg1-build-input.json`; C1 and the final PKG-1 closure both reject stale or drifted build identity. C1 remains 14 gates and total N1.0 remains 105 gates.


## PKG-1 rc.80 — candidate supply-chain admission

`1.0.0-rc.80` / protocol `v5.15` extends the existing v5.11-v5.12 dependency intake boundary without adding certification gates. Reproducible Composer/npm candidate locks now undergo candidate-stage provenance and vulnerability admission before they may be published for human review. Composer dist/source URLs must use HTTPS and resolve only through the configured trusted source-host allowlist; npm `resolved` URLs must use the official npm registry host and every non-link package must carry integrity metadata. Embedded URL credentials are rejected and raw audit stderr is never persisted.

Each isolated refresh workspace runs `composer audit --locked --no-interaction --format=json` and `npm audit --package-lock-only --audit-level=high --json`. Both workspaces must pass and produce the same deterministic candidate supply-chain fingerprint in addition to identical lock hashes. Promotion re-stages the candidate and reruns the supply-chain checks before the first root lock mutation. Reviewed-lock attestation then binds both the deterministic provenance fingerprint and the candidate supply-chain fingerprint. The existing release SBOM/provenance system remains unchanged; this boundary prevents a vulnerable or origin-drifted candidate from reaching it. C1 remains 14 gates and N1.0 remains 105 gates.


## PKG-1 rc.81 — offline-safe fast resume

`1.0.0-rc.81` / protocol `v5.16` makes PKG-1 target execution state-aware. Stale or corrupt unpromoted candidates are quarantined before any Composer/network work, and a valid unpromoted candidate can stop at human `WAITING-REVIEW` without network access. A valid sealed PKG-1 closure is verified before any Composer or registry access and returns immediate 100% PASS. If exact-source C1 14/14 evidence is reusable, dependency/bootstrap/review stages are skipped and execution resumes directly at environment/source activation. `scripts/pkg1-status.*` is a read-only target doctor that reports the current phase, progress and an explicit `NEXT_ACTION` / `NEXT_COMMAND` without forcing network access. Human reviewed-lock promotion remains explicit and C1 remains 14 gates; total N1.0 remains 105.

## PKG-1 single-command interactive launcher (rc.82)

Windows/Laragon operators can now drive the complete PKG-1 usability closure with one command:

```bat
scripts\pkg1-run.bat "REAL NAME" http://nexora
```

The launcher continuously reads the authoritative `pkg1-status.php --json` state and automatically executes only safe/resumable stages. It does **not** bypass trust boundaries: dependency promotion still requires the reviewer to inspect the refresh dossier plus both candidate lockfiles and type the exact `PROMOTE-REVIEWED` confirmation; incomplete promotion recovery requires the explicit `ROLLBACK` confirmation; the browser installer remains operator-completed; and the final Super Admin login uses the existing hidden-password PowerShell flow.

The launcher opens `/install` when required, waits for web/PHP reload at the source-convergence checkpoint, reuses C1/closure fast-resume state, and verifies the sealed PKG-1 receipt before returning terminal success. `scripts\pkg1-status.bat` remains the non-mutating diagnostic view.

## PKG-1 rc.83 - Windows PowerShell 5.1 parser compatibility

`1.0.0-rc.83` / protocol `v5.18` fixes the Laragon launcher parse failure seen on Windows PowerShell 5.1. `scripts/pkg1-run.ps1` is now deliberately ASCII-only and CRLF-normalized so Windows PowerShell cannot reinterpret UTF-8 multi-byte punctuation as smart quote tokens. `scripts/pkg1-run.bat` first invokes `System.Management.Automation.Language.Parser.ParseFile()` using the same Windows PowerShell engine and refuses to execute the launcher if any parser error exists. The final auth smoke is invoked directly in the current PowerShell process instead of spawning a nested parser process. Human review, installer, recovery, and credential boundaries remain unchanged.


## PKG-1 rc.84 - PHP-first Laragon launcher

The canonical `scripts\pkg1-run.bat "REAL NAME" http://nexora` entrypoint no longer executes a PowerShell state-machine launcher. The batch file now invokes `php scripts/pkg1-run.php` directly, so normal dependency, C1, source, installer and resume flow is independent of Windows PowerShell encoding/parser behavior. The PHP launcher consumes the existing status/closure contracts, preserves explicit `PROMOTE-REVIEWED` and `ROLLBACK` confirmations, opens the browser installer, stops after the first exact BLOCKED result instead of retrying a failing step repeatedly, and re-verifies sealed closure evidence before terminal success. PowerShell remains only at the hidden-password login smoke boundary; that small ASCII-only finalizer is parser-checked immediately before execution.


## PKG-1 rc.85 - Windows npm command bridge

Laragon/Windows exposes npm and npx primarily through `.cmd` launchers. The dependency intake path intentionally uses `proc_open(..., bypass_shell=true)` for argument integrity, but Windows cannot execute a `.cmd` file directly through CreateProcess. rc.85 normalizes npm/npx commands to `node.exe + node_modules/npm/bin/npm-cli.js` (or `npx-cli.js`) before execution. Candidate lock generation, npm audit, support diagnostics and dependency-toolchain probes therefore use the same shell-independent execution boundary.

The dependency-toolchain fingerprint now binds the npm CLI JS payload actually executed (with Node fingerprinted separately) and records `execution_mode=node-cli` on Windows. `pkg1-status` validates Composer + Node + npm together before reporting `READY_CANDIDATE_GENERATION`; otherwise it returns `BLOCKED_TOOLCHAIN` with the exact errors. npm remains pinned to the package policy (`>=10 <11`, packageManager `npm@10.9.2`). C1 remains 14 gates and N1.0 remains 105.


## PKG-1 rc.86 - npm bundled-integrity coverage

`1.0.0-rc.86` / protocol `v5.21` corrects package-lock v3 integrity admission for npm `inBundle` children such as the dependencies nested under `@tailwindcss/oxide-wasm32-wasi`. An `inBundle` child may omit its own `resolved` and `integrity` only when an ancestor registry package explicitly lists the direct bundled package and that owner has both a resolved registry URL and SRI integrity. External registry packages still require direct integrity, and fake/unlisted/uncovered bundle entries remain blocked. Candidate provenance, human review, and final dependency provenance all share this same coverage model. C1 remains 14 and N1.0 remains 105.


## PKG-1 rc.87 — semantic lock reproducibility + TS2589 closure

`1.0.0-rc.87` / protocol `v5.22` addresses the two blockers observed on the Laragon target after rc.86. Independent lock workspaces now record raw SHA-256 hashes but decide reproducibility from canonical JSON semantics: associative key ordering and Composer package-list ordering are normalized while actual package names/versions/integrity/source data remain digest-bound. If canonical semantics differ, PKG-1 still blocks and reports A/B dependency version differences. The promoted candidate remains the exact workspace-A raw lock pair and its raw hashes are sealed by review/promotion.

The same release also removes four observed TypeScript `TS2589` instantiation-depth failures. Automation workflow configuration now uses a finite scalar record instead of recursively nesting Inertia `FormDataConvertible`; the document writer keeps its recursive `DocumentContent` payload opaque at the Inertia form generic boundary and restores the concrete type at BlockEditor/statistics consumers. Runtime payload shape and server validation are unchanged. C1 remains 14 and N1.0 remains 105.



## rc.89 development closure Batch A

This development-first package closes installer UX and auxiliary connection gaps before final certification: the installer now loads the shared enhanced select behavior, supports light/dark/system appearance, reports cancellation outcomes, explains HTTP 429 cooldowns, and can configure/test MongoDB-compatible, Redis-compatible and DynamoDB auxiliary services. Selected auxiliary services are persisted after migrations and are enabled only after a successful runtime test. Generated runtime/certification artifacts are excluded from the clean development package.

## rc.88 development-first closure

The default installer is now a four-step general-purpose wizard: Requirements -> Database -> Application & Super Admin -> Review & Install. Existing-database backup/reset and interrupted-install recovery are shown only when database inspection requires them; source/runtime diagnostics are secondary rather than dominating the normal path. The installer remains driver-based and is not Laragon-specific.

Development work is now separated from final release audit. Run `scripts\development-readiness.bat --full` during development to check PHP, Composer, Node/npm, Laravel bootstrap/routes when dependencies exist, TypeScript `--noEmit`, and the raw Vite build. This command never promotes dependency locks or grants release certification. Final PKG/C1 supply-chain audit remains a later closure phase.

### rc.94 post-install identity stabilization

Fresh installation now treats environment, activation, service and process fingerprints as a one-time post-commit transition. The sealed install remains fail-closed for source, generation, engine, database, storage, host, resource, policy, framework and dependency identity. After `installed.lock` changes deployment mode, Nexora recomputes only the allowed volatile planes, reseals the lock, verifies full compatibility, and then records the post-install handoff receipt.
