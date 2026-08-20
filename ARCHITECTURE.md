# Nexora Architecture Constitution — N0.5

## Dependency direction

```text
Laravel Framework
      ↓
Nexora Kernel
      ↓
Nexora Contracts
      ↓
Registries + Runtime Capabilities
      ↓
First-party Modules
      ↓
Sentinel Gate
      ↓
Future Apps / Extensions / Themes / Integrations
```

Third-party code must not depend on private core implementation classes.

## Package lifecycle boundary

Future installable code follows this order:

```text
Receive archive
    ↓
Quarantine outside public/runtime directories
    ↓
Sentinel static inspection
    ↓
Manifest + capability review
    ↓
ALLOW / REVIEW / BLOCK
    ↓
Only a future activation transaction may stage approved code
```

N0.5 intentionally has no “upload then immediately include/require” path.

## Runtime phases

```text
Explicit configured core module classes
        ↓
Typed manifests
        ↓
Dependency + version validation
        ↓
Deterministic boot order
        ↓
REGISTER all modules
        ↓
BOOT all modules
```

No request-time filesystem module discovery is allowed.

## Authorization is two different systems

```text
Human user → Roles → Permissions → HTTP/domain authorization
Executing code → Runtime identity → Declared capabilities → Capability-aware platform API
```

Human permissions never grant code capabilities, and code capabilities never grant admin permissions.

## Non-negotiable rules

1. Core is never edited by third-party packages.
2. Public extension points are contracts/capabilities/registries.
3. Nothing third-party executes before Sentinel and the future activation transaction.
4. Uploaded packages live in quarantine, outside web/runtime paths.
5. Security scan failure is fail-closed.
6. Package upload digest is an immutable baseline; drift is tampering.
7. Heavy package scanning occurs at install/update/publish time, not per HTTP request.
8. Runtime module discovery is deterministic and compiled/configured.
9. Released migrations are immutable; schema changes receive new migrations.
10. Tables use domain names, never `phase_*` or `milestone_*`.
11. Admin feature code consumes `@nexora/admin-ui` only.
12. Backend authorization is authoritative; UI visibility is not security.
13. Core modules do not reach directly into Eloquent/DB facades.
14. MySQL is the primary development/test database; destructive quality tests use only `nexora_testing`.
15. A package source that cannot be completely inspected within policy limits cannot activate.
16. Scanner resource limits are part of the security boundary.
17. Every feature must pass the Definition of Done.

## Admin UI dependency boundary

```text
Untitled UI source-owned components
          ↓
resources/js/admin/ui
          ↓
@nexora/admin-ui
          ↓
Nexora admin features/modules
```

## Current security scanner layers

```text
ZIP metadata/path inspection
        +
Nexora manifest validation
        +
Composer/npm supply-chain policy
        +
Migration + protected-route policy
        +
Secret scanning
        +
PHP AST
        +
PHP tokenizer/obfuscation heuristics
        +
JS/TS static heuristics
        +
SVG/HTML/CSS execution/network heuristics
        +
Capability behaviour comparison
        ↓
Risk Engine
```

## Runtime persistence

Code/config is authoritative for core boot. Database runtime tables are synchronized metadata/history. Sentinel database records preserve package quarantine identity, scans and line-level findings.

## N0.7 deployment + installation boundary

Clean deployments have three layers:

```text
Pre-Laravel Runtime Repair
  ├─ storage/framework/views
  ├─ sessions + cache/data
  ├─ logs
  └─ bootstrap/cache
            ↓
Standalone Deployment Bootstrap
  ├─ prebuilt release verification (preferred)
  └─ optional fixed Composer/NPM/build tasks when server capability exists
            ↓
Main Nexora Installation Wizard
  ├─ requirement preflight
  ├─ MySQL connection / optional create
  ├─ environment configuration
  ├─ forward migrations
  ├─ deterministic core seed
  ├─ first Super Admin
  ├─ runtime sync/cache
  └─ installed.lock
```

Customer/shared-hosting releases are prebuilt on trusted CI and include `vendor` plus `public/build`, so the customer server needs no Composer or Node toolchain. A source distribution can fall back to browser-assisted server build only when the host exposes process execution and the required executables; the standalone bootstrap exposes a fixed command allow-list and no arbitrary command input. The Laravel installer itself stays shell/process-free.

While `installed.lock` is absent, session and cache use file drivers so the installer does not require the application database merely to render. Normal application routes redirect into deployment/install flow; once the lock exists, installer endpoints redirect away.

## N0.10 observable deployment contract

Long-running deployment and installation operations must not block the browser behind an opaque spinner. Source-build tasks expose an NDJSON progress stream with stage state, stage-based percentage, elapsed time, stdout/stderr and heartbeats. The browser uses a streaming `fetch()` request and can cancel active dependency/build commands. A single deployment lock prevents concurrent Composer/npm operations.

The main Laravel installer follows the same observability principle for preflight, database, environment, migrations, seed, Super Admin, runtime, installation lock and cleanup. Progress values represent completed architectural stages; Nexora does not fabricate per-package percentages when an underlying package manager cannot report deterministic completion.


## N0.11 resilient installation environment contract

The browser installer must never require the project root to be writable merely to render. Nexora boots with a temporary protected installation environment and chooses an explicit persistence target only when the operator commits configuration. The preferred target remains the project-root `.env`; when host ACLs prevent that write, the installer persists the same configuration at `storage/app/nexora/environment/.env`. An `active` marker records `root` or `fallback` so a stale/read-only root file can never silently override the selected protected environment on later HTTP or Artisan requests.

Both `public/index.php` and `artisan` load the same installer-environment bootstrap before Laravel, keeping HTTP and CLI configuration resolution deterministic. `.env.example` is a required distribution artifact, not an optional local file. Protected environment state, bootstrap keys and active markers are runtime state and must never be included in production release archives.

### Premium installer UI contract

The deployment bootstrap and Laravel installer are first-class Nexora product surfaces, not diagnostic scaffolding. They use the Nexora brand mark/logo, real favicon/app icons, consistent design tokens, premium spacing and hierarchy, accessible icon-based state language and observable progress. React/admin icons resolve through the Nexora `Icon` compatibility layer backed by `lucide-react`; pre-React bootstrap and Blade installer stages use self-contained Lucide-compatible SVG geometry so dependency installation is not a prerequisite for branded UI.


## N0.12 deployment recovery + localization contract

Deployment work is owned by an explicit run identity rather than by a browser connection. Each long-running bootstrap task writes a run ID, owner/child process identity, current step and heartbeat into protected runtime state. Cancellation targets that run, the active process observes a cancellation flag, and the OS lock is released before the state is reported inactive. A subsequent browser can therefore stop a previous worker and safely retry without deleting a lock file manually.

Localization is a platform contract, not a page-level feature. The framework-independent bootstrap, Laravel installer/auth layer and Inertia/React admin share the same supported locale metadata (`code`, native name, direction). Locale resolution is user preference → session → cookie → configured default. UI layout uses logical/RTL-aware positioning, while logs/code remain LTR. Feature modules must add namespaced translations rather than hard-coded multilingual branches.

The production release uploader is a Nexora design-system surface: the native file input remains only as the browser transport while a branded accessible dropzone owns the visible interaction.


## N0.13 installation data flow

```text
/  Deployment preparation
   ├─ system/tool detection
   ├─ fixed dependency/build tasks
   └─ NO application DB credentials
             ↓
/install  Laravel installer
   ├─ readiness
   ├─ Database credentials + connection test
   │    ├─ empty → continue
   │    └─ non-empty → protected backup → user download → explicit reset consent
   ├─ Identity + default Language + Super Admin password intelligence
   ├─ Review
   └─ streamed provisioning
        ├─ DB revalidation
        ├─ backup/download revalidation
        ├─ schema reset when authorized
        ├─ environment
        ├─ migrations + core seed
        ├─ Super Admin
        ├─ runtime
        └─ install lock
```

The deployment bootstrap and application installer are deliberately separate trust stages. Build authorization must never reuse database credentials. The installer is the only stage allowed to know the target application database password.

The React runtime uses the Inertia v3 Vite-managed page resolver. Feature pages live under `resources/js/admin/pages`, while app-wide providers are wrapped using `withApp`; this avoids mixing legacy manual page resolution with the v3 plugin.

## N0.14 database driver architecture

Installer database logic is no longer MySQL-specific. `DatabaseDriverRegistry` describes the supported Laravel database engines and server availability; `DatabaseProvisioner` owns driver-specific connectivity, creation, inspection, reset, environment mapping, and runtime connection configuration. UI code consumes driver metadata instead of hard-coding MySQL fields.

Destructive installation has two explicit authorization paths: verified Nexora backup or explicit no-backup consent. Final installer cancellation is server-owned and available only before protected schema-changing stages.


## N0.15 data connection architecture

Nexora distinguishes the primary relational schema store from auxiliary data services. `DatabaseDriverRegistry` owns installable primary SQL/managed presets. `ConnectionCatalog` and `nx_data_connections` own optional MongoDB, Redis and AWS service connections. Modules must consume future connection contracts/handles instead of accessing encrypted credentials directly. AWS RDS/Aurora presets normalize to their compatible Laravel driver.

## N0.16 UI library + Document Engine contract

Feature code does not own native interactive-control styling or behavior. Laravel installer views compose `x-ui.*` controls; React/Admin pages import controls and navigation primitives from `@nexora/admin-ui`. Library internals are the only place where native browser controls are authored. This keeps interaction states, spacing, focus behavior, accessibility, icons and future Untitled UI upgrades centralized.

Database display grouping is presentation-only. A primary database option is identified solely by `DatabaseDriverRegistry[*].key`; controllers generate options from that explicit key and browser state reconstructs its lookup from the same property. Collection indexes are never persisted or submitted as driver identities.

`nexora.documents` is the universal semantic publishing substrate. Documents store a versioned structured block tree, not arbitrary editor HTML. `DocumentTypeRegistry` and `BlockRegistry` are extension points and reject duplicate identifiers to prevent silent package conflicts. `DocumentContentValidator` accepts only registered block types and normalizes stable block IDs/version/data/children. Current document state lives in `nx_documents`; successful writes append revision snapshots to `nx_document_revisions`.

## N0.17 Admin shell + Writer contract

Admin React surfaces may only consume interactive controls from `@nexora/admin-ui`. Icon-only actions must expose accessible tooltips through `IconButton` / `IconLink`. The desktop sidebar may collapse without changing navigation semantics; labels remain available via tooltip and screen-reader labels. Appearance switching is an individual UI preference layered over the platform default theme tokens.

`Nexora Writer` consumes `Nexora Document Engine`; it does not create a separate content store. Writer blocks are serialized to the canonical `{version, blocks[]}` tree and pass through `DocumentContentValidator` before persistence. Internal Blog/Editorial/SEO features and external packages such as Books or CV/Profile must continue to use this document contract instead of introducing parallel private rich-text storage.


## SEO Core boundary

SEO persistence is independent of themes. `SeoRepositoryContract` owns canonical resource metadata, `SeoManagerContract` exposes resolved metadata/schema payloads to future public renderers, and `SchemaGraph` prevents silent duplicate-type conflicts. Public themes may render SEO output but may not become the source of truth for canonical URLs, robots rules or structured data.

External product families (Books, CV/Profile, LMS, Booking, Projects) may register adapters through public SEO, Document, Theme and Extension contracts. They must not receive private Core dependencies merely because they are first-party packages.

## N0.21 Studio boundary

`Nexora Studio` is a composition layer, not a second content database and not a theme-code editor. Documents continue to own semantic content, SEO Core owns discoverability metadata, Theme Engine owns the outer public template, and Studio owns a validated visual tree that can bind to approved data sources. Studio nodes are rendered by server-owned renderers; packages cannot inject arbitrary JS/HTML through a canvas payload.

## N0.22 Publishing boundary

Blog and Article publishing is an orchestration layer over existing Nexora services, not a parallel CMS. `article` and `blog_post` are Document Engine types; their body, autosave and revision history remain owned by Documents/Editorial. Publishing metadata owns bylines, taxonomy, series, scheduling and featured state. SEO Core remains the owner of canonical URLs, robots, Schema Graph and sitemap policy, while Themes/Studio remain presentation layers.

Public author profiles are publishing identities and may exist without login accounts. Taxonomy terms and series are reusable domain records. Scheduled publication must increment the Document lock version and append a permanent revision so concurrency and history guarantees remain intact.

## N0.25 Media & Distribution boundary

Nexora Media owns uploaded asset identity, metadata, variants, folders, collections and usage references. Documents, Publishing, Studio and external packages reference media by stable asset ID rather than duplicating files or writing storage paths directly.

Distribution remains downstream of canonical content. RSS reads published Documents. Newsletter campaigns may reference a published Document, but they do not own or copy its editorial/SEO state. Distribution adapters register through a dedicated registry and future external adapters must use public capabilities rather than direct network execution.

Security rules:

- Public media uploads use server-detected MIME types and a conservative allow-list.
- Active-content/public-executable file types are blocked by default.
- Stored filenames are generated by Nexora; client paths/extensions are not trusted.
- Original SHA-256 identity is recorded before the asset enters the library.
- Image variants are generated only inside bounded pixel/format limits.
- Permanent media deletion is blocked while usage references exist.
- Newsletter recipients are consented subscribers, independent from application user identities.
- Unsubscribe links open a confirmation screen; GET requests never mutate subscription state.

## N0.26 Search / Analytics / Crawler boundary

Search, analytics and crawling form the `nexora.discovery` Core module. Search stores rebuildable projections and never becomes the source of truth for Documents or Media. Content analytics stores privacy-aware first-party event/aggregate data and does not persist raw visitor IP addresses. The SEO crawler consumes canonical Sitemap/Theme/SEO output over the configured same-host public surface and records evidence-based findings; it does not own SEO metadata and does not generate a synthetic SEO score.

Search indexing is event-driven through model observers with an explicit full rebuild command. Crawler execution is same-host constrained and queue/CLI driven so normal public requests never perform crawl work.

## N0.27 Automation / Workflow boundary

`nexora.automation` is an orchestration layer over stable Nexora events and action adapters. Core feature modules emit event payloads through `AutomationEventBusContract`; they do not call workflow controllers or know which workflows exist. The workflow engine stores trigger context, evaluates allow-listed condition operators, creates queue-backed runs and checkpoints each action step independently.

Workflow definitions are data, not executable source code. The base runtime intentionally exposes no arbitrary PHP/JavaScript/SQL/shell action and no expression interpreter. New trigger or action families must be registered as explicit adapters with capability boundaries. Outbound Webhook delivery is a registered action rather than an arbitrary HTTP-request primitive exposed to workflow JSON.

Inbound Webhook endpoints are integration event sources, not authenticated Admin sessions. Their route is excluded from browser request-forgery verification and instead requires Nexora's timestamped HMAC protocol, endpoint state, replay-window validation and idempotent receipts. Webhook secrets are encrypted at rest and normal Admin listings never return them.

## N0.29 extension lifecycle boundary

Extensions, apps, integrations and Studio packs are versioned packages managed above Sentinel/Supply Chain. Installation requires a linked Sentinel `ALLOW` artifact, activation requires dependency and explicit capability-grant checks, and policy-gated PHP/migrations require the supply-chain sandbox policy to allow execution. Marketplace catalogs can discover/stage packages but cannot install or execute remote artifacts directly. Shared Admin table/select/date-time behavior belongs to `@nexora/admin-ui`, not feature pages.

## N0.30 Commerce boundary

Commerce Core is provider-neutral. Products, prices, currencies, explicit tax rules, customers, orders, invoices, payment/refund/subscription records and billing events are canonical Nexora data. Payment gateway integrations implement the public `PaymentProviderContract` from verified extensions and register through `PaymentProviderRegistry`; Core does not import a gateway SDK or store provider private keys.

All billable amounts are persisted as integer minor units. Orders snapshot commercial line-item values so catalog edits cannot retroactively rewrite historical transactions.

## N0.31 CRM architecture

CRM is a first-party relationship/sales domain but remains separated from billing identity and communication providers. Organizations, contacts, leads and opportunities own CRM state; Commerce customers remain billing records and are connected only through explicit CRM-Commerce links. Pipeline transitions and lead conversion use services with transactional history/timeline recording instead of controller-side field mutation. Email/calendar integrations are provider contracts for verified Extensions rather than provider SDKs embedded in Core.

## N0.32 Membership + Helpdesk architecture

Membership and Helpdesk are independent Core modules. Membership owns authorization-like entitlement state for business content but does not replace Identity permissions or Commerce billing records. `MembershipAccessContract` is the only public-content gate used by the Theme document renderer; resources without an active policy remain unchanged.

Helpdesk owns support-domain state while requester identities remain referenced from Identity, CRM, or Commerce rather than copied/merged. Ticket mutations pass through a transaction-safe manager that appends events and emits Automation triggers. SLA evaluation is centralized in `HelpdeskSlaService`; N0.32 uses elapsed-time deadlines and leaves business-calendar logic behind a future adapter boundary.

## N0.33 Enterprise tenancy architecture

Nexora Enterprise introduces organization tenancy without replacing existing domain concepts such as CRM Organizations. Tenant identity is stored as `tenant_id`; CRM companies continue to use their own `organization_id` relation. `TenantContext` resolves the active enterprise organization and tenant-scoped models opt into the centralized `BelongsToTenant` global scope. Existing records are backfilled to the default organization during the forward-only N0.33 migration so upgrades do not create an orphaned global dataset.

Authorization uses two independent keys. Platform RBAC determines the maximum set of actions a human account may perform, while the current enterprise role can only further restrict that set inside an organization. Enterprise roles therefore cannot elevate a user into Sentinel, Marketplace, Billing or other platform permissions that the user's platform role does not already possess.

Enterprise identity is adapter-driven. OIDC and SAML integrations implement `EnterpriseIdentityProviderContract`; SCIM uses hash-only bearer tokens scoped to an organization. Core contains no vendor-specific Okta, Entra, Auth0, Google Workspace or other identity SDK. Queue-backed domain work restores the originating tenant context before resolving tenant-scoped records.

## N0.34 Cloud / HA / Distributed Runtime architecture

N0.34 keeps Nexora valid on a single shared-hosting/Laragon node while introducing contracts and coordination needed for horizontal deployments. `NodeIdentity` provides a stable runtime-node key, `NodeManager` records heartbeats and drain/maintenance state, and public readiness fails closed with HTTP 503 when a node should be removed from load balancing. Drain mode is cooperative: it does not terminate in-flight PHP requests or kill workers.

Scheduled cluster maintenance uses a database-backed `scheduler-leader` lease. Every node continues to heartbeat, but publishing, distribution, analytics, crawler, automation, membership, Helpdesk and runtime-maintenance schedules run only while the node holds the leader lease. Critical extension/runtime sections consume `DistributedLockContract`, which is backed by Laravel atomic cache locks and can therefore move from local cache to Redis or another supported shared lock store without changing feature code.

Storage follows the same boundary. `ObjectStorageContract` wraps the configured Laravel filesystem disk. Local storage is a valid single-node choice, while S3-compatible/shared object storage can be configured for multi-node deployments. `RuntimeTopology` reports queue/cache/session/storage choices and warns when node-local or synchronous drivers make a deployment unsuitable for HA; it never infers HA merely because more than one node row exists.

Operational state is intentionally bounded. Runtime metrics record database/cache latency, queue backlog, failed jobs and process memory with retention pruning. Protected database backups reuse Nexora's existing in-app MySQL/MariaDB/SQLite snapshot strategies, are copied into protected runtime storage and sealed with SHA-256. `RestorePlanner` verifies the artifact and produces an explicit offline sequence with a one-time confirmation; web requests and scheduled jobs never execute an unattended destructive database restore. Cloud-vendor-specific queue/storage/monitoring products remain configuration or extension adapters rather than private Core dependencies.


## N1.0 Release Candidate certification boundary

N1.0 does not introduce another product domain. Current platform version `1.0.0-rc.88` is source-certified through the cross-platform certification/target-execution runners; dependency/browser/operator/HA PASS still requires real target evidence. RC20 adds exact-source SHA-256 attestation, PHPUnit certification-database binding, a mandatory five-primary-DB final matrix, observed zero-install and existing-upgrade evidence, database minimum-version enforcement, and independent production artifact re-validation. Production ZIP generation is blocked unless exact-version + exact-source automated and operator evidence all pass.


### RC7 zero-install recovery

The source and production package paths share an explicit zero-install contract. Source packages can bootstrap Composer/Node/build at the canonical URL without `.env`; production packages skip dependency preparation when certified artifacts are already present. Deployment state is reconciled against the OS lock, main-installer protected-stage recovery is bound to a hashed database target, and `installed.lock` remains the atomic final mutation that permanently closes installer provisioning controls.


### RC8 browser / UX / accessibility / RTL

RC8 adds source contracts and component-level tests for keyboard focus, input error relationships, dialogs, live regions, DataTable semantics and logical RTL utilities. It also defines a version-bound operator browser-evidence matrix. Source contracts do not claim full WCAG certification; assistive-technology and observed-browser evidence remain required before stable release.

### N1.0 RC9 performance and production-artifact boundary

RC9 introduces a fail-closed boundary between a source-clean RC and a shippable production artifact. Runtime HTTP responses pass through `ApplyPerformanceHeaders`; sensitive/authenticated/control-plane traffic is never intermediary-cacheable, while content-hashed `/build/*` assets are served by the web server with immutable caching and Brotli/gzip readiness. Vite build output must pass manifest/hash/size/leak checks before production packaging. `config/nexora-release.php` is the single production ZIP policy, and the builder reopens the ZIP to verify required and forbidden entries before writing its SHA-256.


### N1.0 RC10 final evidence boundary

RC10 separates application primitives from infrastructure proof. `HaReadinessService` may report that configured shared runtime prerequisites and observed nodes satisfy the strict in-app HA contract, but stable release still requires independent-node operator evidence. Backup validation likewise proves artifact integrity and guarded planning, not a successful disaster-recovery restore. The final evidence aggregator combines browser, HTTP/build, backup/restore and multi-node HA evidence and production packaging requires its exact-version SHA-256-sealed PASS report.


### N1.0 RC11 final target closure boundary

RC11 does not add a product feature. It adds a fail-closed target-environment harness around the existing RC1–RC10 gates. `scripts/final-target-run.php` supports dependency installation, automated prepare runs, final evidence-required runs and status-only inspection. `closure-status.json` is the explicit release ledger across automated certification, Vite asset budgets, target HTTP evidence, browser evidence, disposable restore evidence, multi-node HA evidence, final evidence aggregation and production package sealing. N1.0 cannot be marked DONE while any closure domain is pending or failed.


### N1.0 RC13 existing-install upgrade boundary

RC13 treats an existing installation upgrade as a protected operational transaction, not as a file-copy shortcut. `InstallationState` preserves the original installation identity/time while allowing atomic version provenance updates after a successful upgrade. The upgrade compatibility service rejects downgrades and unsupported source versions, audits pending core migrations, validates enabled extension/active theme Nexora constraints, and identifies forward-only extension schema rollback barriers. A ready upgrade plan requires verified source-version backup evidence and expires before execution. Apply enters maintenance mode, runs forward migrations and runtime synchronization, updates installation provenance only after those stages succeed, then returns traffic. Protected-stage failures intentionally remain in maintenance mode and must recover from the verified backup rather than automatic blind database rollback. Runtime upgrade journals are excluded from production artifacts.

### N1.0 RC12 target diagnostics boundary

RC12 does not add a product feature or weaken the RC11 closure ledger. `scripts/target-diagnostics.php` is a failure-capture harness for real target environments such as Laragon. It runs dependency-free contracts first, records toolchain availability, optionally captures Composer/npm installation, then records Laravel package discovery/routes/scheduler and frontend type/test/build output when dependencies exist. `--full` delegates to the existing isolated certification database runner. Logs are written per step, environment secrets are not dumped, credential-shaped values are redacted, and the diagnostic bundle is useful even when one or more commands fail. Source-only diagnostics are never accepted as final N1.0 evidence.


### Dependency reproducibility boundary

N1.0 RC15 treats dependency resolution as release input, not build-time mutable state. Source manifests describe allowed ranges; committed Composer/npm lockfiles define the exact graph. Certification installs from those lockfiles only, validates toolchain ranges, records security-audit evidence, and production packaging seals lockfile/audit/policy hashes. Missing lockfiles are a hard blocker for dependency-backed or production certification.


### RC16 filesystem portability boundary

Nexora treats filesystem semantics as a cross-platform contract. Durable state is published through `AtomicFileWriter` using destination-local temporary files and flush/fsync where available. Windows replacement semantics never degrade to silent partial in-place state writes. `PortablePath` rejects traversal, absolute/drive paths and Windows-reserved/nonportable components for package-relative paths. Source certification checks case-fold collisions and PSR-4/import casing so code that works only on a case-insensitive workstation cannot become a release artifact.

### RC17 large-file / transfer safety boundary

Nexora treats untrusted or potentially large byte movement as a bounded transfer contract. `TransferSafety` stages temporary data under protected Nexora storage, copies streams in fixed-size chunks with partial-write handling, checks available capacity where the filesystem exposes it, verifies byte counts/SHA-256 where required and publishes files atomically. Media, Marketplace, Theme/Extension archives and database-backup surfaces use explicit size/expansion budgets. ZIP extraction rejects over-budget entry counts, expanded bytes, per-entry sizes and suspicious compression ratios before publication; backup verification uses streams rather than whole-artifact memory loads. Disk-space checks are preventive signals only—the write path itself remains responsible for cleanup and fail-closed behavior if storage fills mid-transfer.


### RC18 runtime limits / queue safety boundary

`config/nexora-runtime.php` defines request, proxy, PHP-capacity and queue-worker policy. Early request middleware configures only explicit trusted proxies and rejects oversized/malformed Content-Length values. `RuntimeLimitsDoctor` validates PHP input/upload/runtime ceilings and ensures queue `retry_after` remains greater than the longest first-party job timeout plus a safety margin. Queue lifecycle hooks clear tenant context between jobs and request graceful worker restart at the configured memory threshold. SEO crawl cancellation is cooperative and persisted so a worker exits between URL operations instead of being force-killed during a write.


### RC19 concurrency and duplicate-execution boundary

Critical state transitions use `ConcurrencyGuard` for bounded deadlock/serialization retries. `nx_concurrency_mutexes` supplies a portable transaction-row mutex where an invariant has no natural row to lock. Money aggregates, document/studio optimistic locks, automation event fan-out, campaign dispatch and background delivery claims are serialized at the database boundary. Unique constraints remain authoritative for idempotency races. External SMTP/HTTP calls use stable identifiers and stale-claim recovery but remain at-least-once; the Core does not assert cross-provider exactly-once semantics.


### RC20 final closure integrity boundary

RC20 does not add a product domain. It converts the release gate from version-only evidence to exact-source evidence, prevents PHPUnit from overriding the certification database, requires MySQL/MariaDB/PostgreSQL/SQLite/SQL Server matrix evidence in final mode, adds zero-install and existing-upgrade rehearsals as first-class closure domains, enforces minimum database server versions, and reopens/revalidates the sealed production ZIP before N1.0 can close.


### RC21 frontend type-contract boundary

RC21 does not add a product domain. The real Laragon TypeScript failure set is converted into a source contract: Inertia form data must be recursively serializable, router payloads must satisfy `RequestPayload`, `transform()` cannot be chained because Inertia v3 returns void, and shared navigation components must be used according to their declared API. The dependency-backed `tsc --noEmit && vite build` target run remains the authoritative build evidence.


### RC23 target bootstrap, resume and evidence boundary

RC23 keeps dependency acquisition outside automatic Nexora bootstrap code. The target bootstrap may identify active PHP/php.ini, missing required extensions, Composer/Node/npm incompatibility and missing reviewed lockfiles, but it must not download executables or resolve an unlocked graph. Target-runtime resume is fingerprint-bound to the exact platform/source tree, both lockfiles and installed dependency manifests; only selected heavy dependency/frontend PASS steps may be reused, while Laravel boot and runtime doctors rerun. Target evidence bundles are untrusted input and must pass path traversal checks, exact-version/source/lock binding and per-step log validation before sealing.

### RC22 target runtime closure boundary

RC22 separates troubleshooting from release gating. `target-diagnostics.php` is allowed to continue collecting failures; `target-runtime-run.php` is fail-fast by default and owns the first-blocker target evidence. It may install only reviewed lockfile graphs and may not issue destructive database migration commands itself. Full destructive certification remains centralized in `certify-release.php` against the isolated certification database. Source-contract PASS, target-readiness PASS and final N1.0 closure are three distinct states and must never be conflated.


## N1.0-C3 database portability boundary

C3 is gated by exact C2 evidence and reviewed dependency locks. It owns only the strict five-database compatibility matrix and cannot install dependencies or collect zero-install/browser/backup/HA evidence. The matrix artifact is bound to source, C2 and lock hashes.

### N1.0-C4 operational recovery boundary

C4 is downstream of exact-source C2 evidence. It seals three independent operator-observed domains—zero-install/recovery, existing-install upgrade, and backup/disposable restore—against the current source and reviewed dependency graph. It intentionally excludes C3 database portability, C5 browser/performance, and C6 HA.

### N1.0-C5 browser/accessibility/performance boundary

C5 is an evidence boundary after C2, not a dependency/runtime bootstrap layer. It requires Chrome/Edge/Firefox across 360/768/1440, LTR/RTL and light/dark (36 rows), real assistive-technology observation, strict target HTTP/security/latency evidence, exact production build budgets, and repeated observed Web Vitals. C5 evidence binds the current source tree, C2 PASS, reviewed Composer/npm locks, browser evidence, Web Vitals, HTTP report and build-assets report. C5 does not run the five-database matrix, operational recovery rehearsals or HA certification.


### N1.0-C6 multi-node HA/final release boundary

C6 is the final orchestration boundary after C1-C5. Final evidence now permanently requires exact-source C1-C5 PASS manifests, so the older final-target harness cannot bypass chunk certification. C6 requires `nexora:ha:status`, `nexora:ha:rehearse`, and fresh evidence from at least two independent active nodes covering shared cache/session/object storage, async queue distribution, single scheduler leadership/failover, node/worker drain, node failure recovery and version consistency. It then seals the unified five-domain operator intake, executes final exact-version certification and packaging, verifies all 11 closure domains, independently reopens the production ZIP, and writes a final C6 manifest only when `n1_0_done=true`.


## N1.0 Target Execution Pack

C1-C6 code-side certification chunks are orchestrated on real targets by `scripts/n1-target-execution.php` and its BAT/PowerShell/sh wrappers. The pack is fail-closed and does not constitute a C7 feature chunk. The v2 dependency-preparation boundary centralizes trusted Composer discovery (PATH first, then executable Laragon `bin/composer` candidates including `composer.phar`) and lock refresh. Lock refresh may resolve metadata only after explicit `REFRESH` confirmation, never installs the dependency graph, never accepts locks, and always returns control for human diff review before C1 can install dependencies.


## N1.0 Target Support Capsule
The target execution layer emits a bounded, redacted, ZIP-independent JSON support capsule for first-blocker handoff. This is diagnostic evidence only and is excluded from production/source-zero state.


## N1.0 Target Execution Pack v2.2

The target executor now combines three operational handoffs: source-bound PHP restart verification after safe Laragon extension remediation, explicit reviewed-lock acceptance/continuation from the latest lock-refresh handoff, and exact-evidence C1-C3 resume. `--refresh-locks` still cannot be combined with review/install, and stale chunk evidence is never reused.


## N1.0 Target Execution v2.3 — Maximum Closure Batch

The remaining code-side closure safeguards are consolidated at `1.0.0-rc.38`. `scripts\n1-target-next-action.bat` is a read-only state planner that reports the exact next safe command without accepting locks or fabricating evidence. C5 now requires browser, Web Vitals and HTTP evidence to refer to one normalized target URL; C6 additionally requires the same HTTPS target for HA evidence. Evidence freshness is centrally configured in `config/nexora-certification-evidence.php`. Upgrade rehearsal evidence must use a concrete older source version inside `config/nexora-upgrade.php`'s supported source window. Production packaging freezes all source/evidence/lock/build inputs and discards the artifact if any input changes during archive creation; independent artifact verification rechecks policy and current-host evidence hashes. N1.0 remains open until real C1-C6 target/operator evidence passes.


## N1.0 Target Execution v2.4 — Session Integrity & Final Release Seal

The current target-certification flow now uses a single exact-source/reviewed-lock certification session for C4-C6 operator evidence, rejects concurrent master target runs, enforces bounded future-clock skew and session freshness, and produces a sanitized certification evidence bundle plus external release seal alongside the production ZIP. The existing eleven-domain closure count is unchanged: `production_package` now represents the independently verified production ZIP + evidence bundle + release seal as one sealed release domain. Real Laragon/browser/HA observations remain mandatory.


## N1.0 Target Execution v2.5 — Signed Release Trust

`1.0.0-rc.40` extends the certification constitution with a source/lock-bound certified toolchain fingerprint and a detached RSA signature over the external release seal. Signing private keys are runtime-only and are forbidden from source-zero and production archives. The signed seal binds the production ZIP, sanitized certification evidence bundle, exact source tree, certification session, certified toolchain, reviewed Composer/npm locks and final evidence. A portable offline verifier validates the signature, both ZIP archives, archive hygiene, evidence index and final PASS without relying on the original certification host. Certification sessions are finalized only after the signed release artifacts are independently revalidated, preventing new operator evidence from being appended to a finalized release cycle.


## N1.0 Target Execution v2.6 — Signer Identity & Supply-Chain Boundary

The release signature is no longer treated as signer identity by itself. The target operator explicitly registers a runtime trust anchor for the release key and may revoke it; C6 refuses an unanchored or revoked signer. Offline verification requires an independent expected public-key SHA-256 or exported trust-anchor file. C1 produces deterministic CycloneDX dependency inventory from reviewed lockfiles. Production packaging creates a Composer no-dev/no-scripts runtime stage, records its tree digest, prepares exact-source/session/toolchain provenance and embeds a per-entry content manifest. These new records are hashed into the external signed release seal.


## N1.0 Target Execution v2.7 — Trusted Update Boundary

Signed release creation and signed release consumption are separate trust domains. The release builder owns its runtime signing key and signing trust anchor; an installed Nexora instance owns a distinct recipient update trust anchor in persistent storage. Recipient trust is imported out-of-band, never copied from the release bundle itself, and rotation/revocation are explicit auditable operations. A candidate update must pass strict offline signature/content/evidence verification against the recipient anchor before an admission receipt is written. The admitted production ZIP may then be extracted only into an empty staging directory, where the exact source attestation is recomputed and matched to the signed release. Upgrade planning and apply both revalidate that admission, bind the receipt hash into the expiring upgrade plan, block downgrade/same-version replay by policy, and persist release-seal/signer lineage only after successful migrations/runtime sync. This keeps cryptographic artifact trust ahead of destructive upgrade work.


### Signed certification candidate for C4 ordering

C4 upgrade rehearsal occurs before C6 can produce the final production release, so Nexora does not weaken production update trust to break that ordering cycle. `scripts/trusted-update-candidate.*` creates a short-lived signed **certification-candidate** bound to the exact source tree and reviewed dependency locks. A disposable prior installation can admit it with `scripts/trusted-update-admit-candidate.*`. Runtime acceptance requires the explicit `NEXORA_CERTIFICATION_UPGRADE_REHEARSAL=1` switch, a non-production `local/testing/certification` environment, and an isolated database whose name starts with `nexora_test` or `nexora_cert`. Production environments cannot use this candidate path and still require the complete signed production/evidence/seal/signature/public-key set.


## N1.0 Target Execution v2.8 — Crash-Safe Update Recovery

The trusted-update pipeline now has a second persistence boundary after admission: an atomic upgrade transaction journal records the exact upgrade id, source/target versions, trusted-admission hash and verified backup identity before maintenance mode. Each destructive/operational boundary is checkpointed. Failures move the journal to `recovery_required`; no automatic database rollback or destructive recovery path exists. The recovery command is read-only, so the only supported protected-stage recovery remains restoration of the verified source-version backup.

Recipient signer rotations form a hash-linked trust lineage. Update admission revalidates the lineage and binds its head/depth into the one-time receipt. Staging records are separate from source payloads and failed/partial stages are quarantined by record. Cleanup can only delete TTL-expired targets inside `managed_staging_root`, requires a matching record and explicit `--confirm=CLEAN`.


## N1.0 Target Execution v2.9 — Protected Upgrade Operational Boundary

The v2.9 upgrade boundary treats backup existence, restore readiness, maintenance ownership and post-migration health as separate invariants. A backup cannot make a plan ready unless its checksum is verified and a guarded restore plan is independently established. External evidence is time-bounded and binds a database fingerprint; runtime backup records are validated through the same restore-planning service used by disaster-recovery rehearsal.

Maintenance mode is owned by an integrity-sealed lease tied to the upgrade id. A pre-existing maintenance state is not silently adopted. Migrations/runtime synchronization run only after the lease is acquired, and traffic is restored only after pre-metadata and post-metadata health checks pass. Once traffic has been restored, remaining plan/admission/journal archival is post-commit bookkeeping and may produce operator warnings but cannot trigger automatic destructive recovery.

Recovery is deliberately decision-oriented rather than action-oriented. The system records a tamper-evident operator decision and surfaces verified backup/restore-readiness/maintenance state, but performs no automatic down-migration or backup restore.


## N1.0 Target Execution v3.0 — Distributed Upgrade Coordination

The v3.0 upgrade boundary adds a cluster-wide database lease and migration-ledger convergence to the local protected transaction. The local file lock remains useful against same-host concurrency, while `nx_runtime_leases/platform-upgrade` prevents independent runtime nodes from starting the same schema transition concurrently. Peer draining is explicit and node-local: a peer marked `draining` or `maintenance` stops advertising readiness, returns 503 for web traffic, asks queue workers to quit at loop boundaries, and cannot become scheduler leader. No coordinator code silently changes peer state.

For distributed targets, Laravel maintenance mode must use the shared `cache` driver backed by an approved shared cache store. Planning seals the full compatibility assessment and the migration ledger; apply revalidates both immediately before the distributed lease and migrations. Post-migration convergence rejects duplicates, missing previously-applied migrations and any source migration still pending. A protected failure marks the distributed lease `recovery_required` so a second node cannot silently take over after lease expiry; release remains an explicit operator action after recovery has been resolved.


## N1.0 Target Execution v3.1 — Quiescence Before Schema Mutation

The distributed upgrade lease alone prevents two upgrade coordinators, but it does not prove that ordinary work has stopped. v3.1 therefore models active web requests, queue jobs and scheduler tasks as expiring runtime-activity leases. Peer drain is valid only when peers are non-active and have no live activity leases; the configured shared queues must report zero backlog. The owner changes its own node status to maintenance, preventing new web/queue/scheduler work, then waits for all existing activity leases to clear before it enters Laravel maintenance mode and starts migrations.

`RuntimeVersionGuard` establishes a second independent fence: serving web traffic, starting queue work, acquiring scheduler leadership or manually reactivating a node is not allowed when the local code version differs from the installed platform version. This is intentionally stricter than merely checking cluster node rows and protects the window between source deployment and installation-metadata commit.

`UpgradeMigrationSafety` parses only migration `up()` bodies and rejects destructive operations under the default policy. Its deterministic fingerprint is sealed alongside the migration ledger. The migration ledger answers whether expected migrations ran exactly once; migration safety answers whether the pending forward transition was contract-preserving before it was allowed to run.


## N1.0 Target Execution v3.2 — Atomic Runtime Admission Barrier

Runtime quiescence alone leaves a small admission race if a request/job/task passes readiness just before the upgrader acquires its cutover lease. v3.2 removes that gap by making runtime-activity admission and the `platform-upgrade` barrier share the same `nx_runtime_leases` row lock. Activity admission locks the barrier row before it can publish its own lease. The upgrader acquires that same barrier before changing the owner node to maintenance and waiting for existing activity to drain. This creates a deterministic cutover boundary without automatically draining peers.

The barrier is also recovery-sticky: `recovery_required` prevents new activity even if the normal lease expiry time passes. Queue payload metadata is mandatory and exact-version fenced with payload schema 2; same-major compatibility is intentionally insufficient around schema changes. Web and scheduler admission are fail-closed while the barrier is active. The frontend source gate separately preserves the Inertia v3 typing repairs previously observed by target `tsc`; source contracts are not a substitute for a real dependency-backed build.


## N1.0 Target Execution v3.3 — Exact Deployment Generation

Nexora distinguishes a release version from a concrete deployment generation. The generation is a SHA-256 identity over version, exact certified source digest, frontend manifest, reviewed dependency locks, runtime and upgrade policy hashes, and the session-schema epoch. Signed production release metadata is authoritative, while admitted-update and installed-metadata modes recompute the local material set and refuse a declared generation that no longer matches local policies, locks, frontend assets or session compatibility.

This identity is carried through runtime-node metadata, queue payload schema 3, update admission, upgrade planning, installation lineage and cluster convergence. Exact-version nodes with a different generation are fenced. Web clients use the same identity through Inertia asset-versioning and `X-Nexora-Deployment-Generation`; raw same-origin JSON requests with a missing or stale generation are rejected before application logic. Cache namespaces are release-generation scoped and sessions are guarded by an explicit schema epoch.

Fast request fencing intentionally does not compute a complete source attestation on every request. Deep integrity is an explicit operator/certification action via `nexora:runtime:deployment-status --deep`, and real C4/C6 evidence must observe it on the target topology.


## N1.0 Target Execution v3.4 — Runtime Environment Compatibility

Deployment generation identifies immutable code/build material; v3.4 adds a separate installation-specific runtime environment fingerprint. The fingerprint intentionally contains no raw secrets, but includes a one-way APP_KEY fingerprint plus session/cookie, cache, queue, filesystem, database-driver and maintenance-store compatibility material. Runtime version readiness requires version + deployment generation + runtime environment compatibility. Node heartbeats and cluster convergence carry the same environment fingerprint, and queue payload schema 4 rejects jobs created under another runtime environment even when platform version/build generation match.

Key rotation is a two-phase operator workflow under maintenance mode. The old installed key fingerprint must appear in Laravel `APP_PREVIOUS_KEYS`; an integrity-sealed short-lived receipt authorizes the new environment only while maintenance remains active. Commit requires fresh nodes to advertise the new fingerprint in non-active states, updates installation lineage, archives the receipt and performs no secret mutation or traffic-state change. This lets Laravel previous-key decryption preserve a controlled continuity window without allowing silent APP_KEY drift.


## N1.0 Target Execution v3.5 — Runtime Activation Boundary

Deployment generation identifies immutable release material and the runtime-environment fingerprint identifies installation-specific configuration. v3.5 adds a third compatibility axis: runtime activation. An integrity-protected activation epoch plus deterministic framework-cache snapshot identifies the currently activated bootstrap/config/routes/runtime-cache state. Long-running processes capture the epoch at boot; queue schema 5 requires exact version, deployment generation, environment fingerprint, activation epoch and activation fingerprint, so a stale worker cannot silently execute jobs after cache/source activation changes.

The activation transaction is upgrade-aware. The plan seals the source activation fingerprint, apply rejects activation drift, maintenance-protected upgrade work rotates the epoch, installation metadata records the new activation/cache hashes, queue workers receive a restart signal, and cluster convergence requires every active target node to report the same target activation identity. OPCache is not automatically reset by application code; when timestamp validation cannot safely reload code, operator evidence must prove the PHP worker pool was restarted before traffic restoration.


## N1.0 Target Execution v3.6 — Runtime Engine Compatibility

Runtime compatibility now has four independently observed axes: immutable deployment generation, installation-specific runtime environment, activated cache/process epoch, and the PHP runtime-engine fingerprint. The engine fingerprint is intentionally SAPI-independent and covers PHP patch/Zend identity, selected extension versions, PDO drivers and compatibility-safe INI material; process SAPI/binary/memory/opcache details remain a separate diagnostic profile. This lets FPM-created work execute on a legitimate CLI worker while still rejecting a node or queue worker running a materially different PHP engine.

Schema-6 queue metadata binds the engine fingerprint in addition to version/generation/environment/activation. Runtime nodes publish `runtime_engine_fingerprint`, `php_version`, `extension_profile_sha256`, and `pdo_drivers_sha256`. HA readiness and upgrade-cluster convergence require that identity to match. Upgrade planning snapshots the current engine and apply refuses engine drift before schema mutation. Release artifacts sign the engine policy rather than a machine-specific engine fingerprint; the actual fingerprint is derived and persisted on the target installation.


## N1.0 Target Execution v3.7 — Database Data Plane & Structural Schema Attestation

The runtime compatibility chain now includes a database data-plane identity in addition to deployment generation, environment, activation and PHP engine identity. Lightweight runtime identity binds driver, hashed logical database identity, normalized server version and compatibility-sensitive session settings. Deep certification adds deterministic structural schema metadata covering tables, columns, indexes, foreign keys and optional views.

Queue schema 7 binds jobs to the exact runtime database data plane. Node heartbeat/HA evidence binds every serving node to the same data-plane fingerprint. Upgrade planning seals the pre-upgrade structural schema and data-plane profile; apply rejects drift before migration, and post-migration attestation must converge before runtime sync/traffic restoration. Runtime and external backup evidence is also bound to the source database data plane and schema, preventing a checksum-valid backup for the wrong schema/data plane from satisfying upgrade safety.

C2 and C3 certification compare fresh and rebuild schema fingerprints rather than treating successful migration command exit codes as sufficient proof. Real database execution remains required on the target drivers; source-only certification proves the contracts/runners but not the live schemas.


## N1.0 Target Execution v3.8 — Persistent Storage Data Plane

The v3.8 boundary treats persistent storage as a compatibility surface rather than a passive filesystem detail. Media, generic object storage and runtime backup disks are fingerprinted through non-secret backend identities. S3-family profiles bind bucket, region, endpoint host, root and path-style behavior; local profiles bind the configured root locator; FTP/SFTP profiles bind host/port/root without exposing credentials. Deep certification writes a random probe, verifies read-after-write, deletes it, and verifies deletion on each distinct role disk.

Runtime queue schema 8 and runtime-node metadata include the storage data-plane fingerprint. HA readiness rejects nodes that agree on version/deployment/environment/activation/engine/database but disagree on persistent storage, and separately requires a shared backup-storage candidate for HA recovery. Runtime database backups use the configured backup disk rather than an implicit node-local disk. Backup manifests and upgrade evidence bind both the aggregate storage identity and exact backup-disk profile. Non-shared backup media is never treated as cluster-portable: restore planning inserts an operator-controlled secure copy plus post-copy SHA-256 verification before a disposable restore can proceed.

Fresh installation prepares the conventional local public-media link only when the configured media disk is the standard `storage/app/public` local target. Existing conflicting `public/storage` paths are never overwritten automatically. Upgrade planning and apply bind the exact storage identity, post-migration deep storage probes must pass before traffic restoration, and installation lineage records the exact role disks/storage fingerprints.


## N1.0 Target Execution v3.9 — Service/Network Data Plane

The v3.9 compatibility boundary treats cache/session/queue/mail/TLS/proxy endpoints as a runtime data plane. The identity deliberately excludes credentials while binding endpoint/connection topology, Redis logical databases/cluster behavior, mail transport location, proxy host/port policy, CA-bundle content and trusted-proxy configuration. Deep probes are bounded and non-destructive: cache write/read/delete, Redis PING when relevant, queue-size visibility, mail DNS and optional TCP reachability.

All known first-party outbound HTTP routes through `ApprovedHttpClient`, which obtains a validated `NetworkDestinationPolicy` decision before sending. External destinations require public resolved addresses, configured HTTPS/ports and DNS pinning; redirects are disabled, embedded credentials are rejected, and same-origin crawling cannot escape its configured origin. Queue schema 9 and runtime-node metadata carry the exact service fingerprint; HA, install, upgrade and deployment-generation policy bind the same service/network contract. C2/C4/C6 require real deep service/network observations before N1.0 can close.


## N1.0 Target Execution v4.0 — Host / Platform / Clock Data Plane

The v4.0 compatibility boundary adds a non-secret host identity over OS family, machine architecture, timezone/locale normalization and host policy while deliberately keeping SAPI/process-specific details outside the cross-node compatibility hash. A deep host probe measures primary-database clock skew and verifies temporary-directory writes, same-directory atomic rename, advisory `flock`, secure randomness and allowed umask behavior.

Distributed coordination no longer trusts independent application wall clocks for lease expiry/freshness. `RuntimeLeaseManager`, node heartbeat freshness, cluster upgrade fences and key-rotation windows are anchored to the primary database clock. Queue schema 10 adds exact host identity and a generated timestamp with future-skew rejection. C2/C4/C6 require real host, clock and convergence evidence; the platform never auto-adjusts NTP, OS timezone or locale.


## N1.0 Target Execution v4.1 — Runtime Resource / Capacity Envelope

The v4.1 boundary separates a deterministic resource-policy identity from live capacity observations. `RuntimeResourceEnvelopeIdentity` fingerprints the signed resource policy and configured minima while a deep probe measures current PHP/process memory headroom, temporary/storage/transfer/bootstrap/backup-staging free space, queue-worker memory reserve and open-file soft limits. This avoids treating normal capacity fluctuation as immutable deployment identity while still blocking operations that need guaranteed headroom.

Upgrade plans seal the resource-policy fingerprint plus the current deep-probe digest and refuse policy drift or insufficient capacity before migrations and again after the protected migration phase. Runtime database backup admission also checks the local protected backup-staging path before producing a snapshot; existing size-aware transfer capacity checks remain in force after actual snapshot size is known. Runtime node heartbeats publish a deep resource status/digest, and HA readiness requires both resource-policy convergence and `pass` capacity status across fresh active nodes. Queue schema 11 binds the policy fingerprint but deliberately does not bind volatile free-byte counters.

C2 requires `nexora:runtime:resource-status --deep`; C4 includes low-memory, low-disk, backup-staging and resource-policy drift drills; C6 requires real node-level resource fingerprints, deep-probe digests and minimum-capacity observations. Nexora never auto-edits PHP limits, deletes data to create headroom or mutates OS resource limits as part of certification.


## N1.0 Target Execution v4.2 — Runtime Policy Plane Convergence

The v4.2 policy plane is intentionally separate from file-policy hashes. File hashes answer whether source policy files changed; the effective policy fingerprint answers whether environment-resolved behavior actually matches across processes and nodes. It includes non-secret concurrency, transfer, runtime/deployment, upgrade, update-trust, release-trust, supply-chain, dependency and HA policy values while excluding machine-local paths and secret material. Semantically unordered allow-lists are sorted before hashing.

Production deep checks are fail-closed: insecure HTTP is rejected, runtime deployment fences remain enabled, upgrades require backup/restore/migration-ledger/quiescence safety, update admission remains signed/monotonic/exact-source, release archives reject unsafe entries and require an external identity anchor, supply-chain evidence remains SBOM/provenance/content-manifest/no-dev/no-scripts protected, and certification cannot proceed unlocked. Queue schema 12 and runtime node metadata carry the policy fingerprint; HA, install, upgrade, deployment generation and release sealing bind the same policy plane.

## N1.0 Target Execution v4.3 — Operational Process Plane / Process-Role Liveness

The process plane distinguishes **node identity**, **process-policy identity**, and **live role evidence**. Node heartbeat proves the runtime host is participating; the deterministic process fingerprint proves every node agrees on lease TTLs, role minima, queue blocking rules and schema policy; DB-clock-backed role leases prove that web, queue and scheduler execution paths are actually alive. Volatile lease state is intentionally excluded from deployment generation and queue identity so ordinary heartbeat expiry never mutates immutable release identity.

Web middleware renews a `web` role lease under the current node key. Queue workers renew `queue` on job admission and in `Queue::looping`, with bounded throttling so an idle worker remains observable. The scheduler runs a non-leader-gated process heartbeat every minute; scheduler leader election remains a separate single-owner coordination contract. Role leases are stored in the existing runtime lease table and expire against the shared primary-database clock.

HA readiness requires configured role quorums and exact process-policy convergence. Queue schema 13 rejects payloads created under another process policy. Upgrade planning/apply reattests process policy before and after migrations, while installation/update lineage, deployment-generation materials, production release manifests, provenance and release seals bind the process-policy SHA. Nexora never auto-spawns or kills system processes; process-manager configuration and actual multi-node role liveness remain operator/target evidence.


## N1.0 Target Execution v4.4 — Framework / Reviewed Dependency Reconciliation

A reviewed dependency refresh is an immutable deployment transition, not an in-place compatibility exception. `composer.lock` and `package-lock.json` stay inside the deployment-generation material set. When a reviewed Laravel 13.x lock changes, `RuntimeDeploymentIdentity` can diagnose the local candidate generation without throwing the old opaque installed-generation exception, while production-manifest and trusted-update validation remain strict.

`FrameworkCompatibility` permits Laravel >=13.24.0 and <14.0.0. `ReviewedDependencyState` binds the exact reviewed lock hashes and the locked `laravel/framework` version. `DependencyDeploymentReconciler` is maintenance-only and requires the running framework to already equal the reviewed lock. It accepts only dependency-only generation drift with the same Nexora source tree, frontend manifest and session schema. On success it commits the new dependency lineage, clears caches, rotates activation, signals queue restart and writes an atomic audit receipt without bringing the site out of maintenance mode.

Runtime compatibility diagnostics now expose explicit mismatch dimensions instead of a generic deployment-identity 503. Node heartbeat and HA readiness additionally converge on running Laravel version, reviewed dependency fingerprint and review status. C2, C4 and C6 include real dependency-update observations before N1.0 can close.


## N1.0 Target Execution v4.5 — Tenant Seed Isolation

Tenant context is request/process state, not durable tenancy truth. Installer migrations may recreate the enterprise organization table and therefore invalidate an organization object cached earlier in the same PHP request. v4.5 explicitly clears that state before seeding, re-resolves the current default organization from the migrated database, and executes tenant-owned seed blocks with `TenantContext::runWith()`. The context is restored in `finally`, preventing cross-task leakage.

Tenant-scoped model creation validates an explicit or active tenant ID against `nx_enterprise_organizations` before insertion. If an active context points at a deleted/replaced organization, Nexora fails closed with an actionable runtime exception rather than silently falling back to another tenant or surfacing a raw FK error. No-context console seeding may use the current default organization only.

The frontend stabilization boundary retains the 11 historical Laragon compiler targets as permanent Inertia regression inputs. Source-only parser/contracts are evidence of source shape, not a substitute for the real reviewed-dependency TypeScript compiler stage.


## N1.0 Target Execution v4.6 — Tenant Execution Boundary

Tenant identity is now treated as an execution boundary, not mutable ambient worker state. `TenantExecutionScope` resolves the persisted organization at execution time, requires an active organization and delegates to `TenantContext::runWith()` so previous context is restored under success or exception. Tenant-aware queue roots resolve only the root record's `tenant_id` without the tenant global scope, then re-enter normal tenant-scoped querying inside the verified scope. Missing, deleted and suspended organizations fail before job side effects.

Queue and scheduler lifecycle hooks clear `TenantContext` on every reusable process boundary. This protects long-running workers from cross-job tenant leakage even when a previous job fails. The core default CRM/Helpdesk/Newsletter tenant seed block is one database transaction under a freshly resolved default organization, preserving v4.5's stale-schema reset while adding all-or-nothing default data creation.


## N1.0 Target Execution v4.7 — Fresh-Install Dependency Trust Bootstrap

The v4.7 dependency boundary separates **runtime dependency identity** from **operator review provenance**. Runtime identity is derived from deterministic manifest/lock hashes and the locked Laravel version. Formal review remains an independent attestation used by certification, HA and release closure. Missing review evidence is therefore not equivalent to missing dependency identity.

For an uninstalled node only, `FreshInstallDependencyTrust` may create a runtime-local bootstrap receipt when deterministic locks are present, the installed Composer production package set matches composer.lock, the running Laravel version matches the lock and package.json matches package-lock root metadata. If a review file exists but is unreadable, stale or invalid, bootstrap fallback is forbidden. Installer metadata records the bootstrap trust mode and exact dependency fingerprint. `RuntimeVersionGuard` consumes the runtime identity status rather than the human-review status, preventing a freshly installed bootstrap-verified node from being quarantined solely because review evidence is intentionally absent from the clean source archive.

After formal lock review, `DependencyReviewSynchronizer` promotes installation provenance to reviewed only when the reviewed fingerprint exactly equals the installed fingerprint and no deployment-generation drift exists. This metadata-only synchronization does not rotate deployment generation. The existing dependency reconciliation workflow remains mandatory when lock hashes actually changed.


## N1.0 Target Execution v4.8 — Crash-Safe Installation Commit Boundary

The v4.8 installation boundary distinguishes **provisioning work**, **pre-commit provenance**, the **durable installation commit**, and **post-commit housekeeping**. `InstallationState` writes schema-2 sealed metadata through the central atomic file writer. The canonical payload hash is stored as `_lock_sha256`; the installer remains closed whenever the lock file exists, including when its integrity validation fails. This is intentionally fail-closed so a torn or manually edited lock can never reopen destructive installation routes against an already-provisioned database. Legacy unsealed locks remain readable and are resealed by the next metadata update.

Fresh-install dependency trust no longer publishes its bootstrap receipt during early validation. The receipt is constructed in memory, all host/resource/policy/process/data-plane attestations must pass, then the receipt is integrity-checked and published immediately before the atomic installed lock. An interrupted attempt can leave only a non-authoritative orphan receipt; the next installer attempt removes that orphan before provisioning. Once the sealed installed lock is published, cleanup/progress/run-control operations are non-authoritative best-effort work. Controller recovery therefore treats a valid committed lock as success even if secondary telemetry fails afterward.


## N1.0 Target Execution v4.9 — Installer Consent & Preflight Stabilization

The v4.9 installer resolves `FreshInstallDependencyTrust` before entering the database stage. Missing human review evidence may still produce the existing bootstrap-verified runtime identity when exact locks/runtime match, while corrupt/stale review or lock/runtime mismatches fail before schema work. The same preflight snapshot is carried through final metadata generation; the final lock stage does not rediscover dependency trust after migrations/seeding.

Existing databases have an explicit action state. A recoverable interrupted Nexora schema defaults to `resume`, preserving tables. The operator may instead select `reset`; that branch is treated identically to any other destructive existing-database install and requires either a verified/downloaded Nexora backup or explicit no-backup consent plus exact database-name confirmation. Installation lineage records the selected action and protection mode.

The installer navigation is a state machine: final CTA visibility uses the DOM `hidden` property, review terms gate the Install button, database consent gates the database step, and password risk consent gates the identity step. Password policy keeps an absolute hard floor while allowing Weak/Low/Medium values above that floor only with explicit operator consent.


## N1.0 Target Execution v5.0 — Installation Resume Provenance & Fast-Track Closure

`InstallationResumeIdentity` separates database-target identity from installer-generation identity. Installation run-control records both: the database fingerprint answers “is this the same database?” while the resume fingerprint answers “is this the exact installer/migration/seeder/dependency generation that created the protected partial schema?”. Resume requires both. A database can therefore remain recognized as an interrupted Nexora installation while exact-source Resume is disabled, forcing the explicit Start clean + backup/overwrite-consent path rather than mixing installation generations.

The resume fingerprint is intentionally non-secret and deterministic: platform version, installer protocol, selected installer source hashes, migration and Core-seeder manifests, plus Composer/npm lock hashes. It does not include database passwords or session secrets. Server-side enforcement rejects an explicit stale Resume even if a client is outdated. The browser API exposes compatibility, reason, prior version/protocol, and automatically selects the destructive-consent branch when Resume is incompatible.

`n1-target-fast-track` is a certification ergonomics layer, not a safety bypass. It delegates to the existing target-execution runner with `--resume-latest`, optionally installs exact reviewed dependencies when explicitly requested, prepares operator kits when an operator is supplied, and can consume all C4/C5/C6 evidence paths together. It will not refresh/review locks, mutate database state, create signing trust, or synthesize evidence.


## N1.0 Target Execution v5.1 — Progress Evidence Model

Nexora now exposes two independent progress layers. **Strict chunk certification** remains the final C1–C6 truth and is never inferred from source changes. **Granular gate progress** counts exact-source PASS/reused-PASS step IDs from the canonical C1–C6 evidence files, rejecting evidence whose platform version or source-tree SHA differs from the running source. The current denominator is 105 target gates (14/52/5/7/7/20).

Historical TypeScript remediation is a third diagnostic ledger: it records the 76 errors observed across 11 Laragon files and checks the exact current-source failure patterns. This proves source remediation only. Real-target verification remains zero until C1 independently records successful dependency-backed `typecheck` and `vite-build` steps for the exact source identity.


## N1.0 Target Execution v5.2 — Exact Source Activation Boundary

Source deployment is now an explicit pre-database invariant. `SourceActivationIdentity` reflects the loaded `Installer` class, verifies that its real path is the project `Installer.php`, compares protocol/source-generation constants against `config/installer.php`, and validates the loaded file SHA-256 against the package-sealed SHA. The web SAPI and OPcache timestamp settings are exposed as diagnostics because CLI cache cleanup cannot prove that a long-running Apache/Nginx/PHP process dropped an older class generation.

The installer UI shows the executing version/protocol/generation and disables forward navigation when the web source is stale. The stream start event repeats the source generation and Installer SHA so screenshots/logs carry enough provenance to identify the exact executable generation. Source activation is a precondition rather than a new certification denominator, preserving the v5.1 105-gate granular progress model.


## N1.0 Target Execution v5.3 — Critical Source Set & Process Handshake

Source activation is now a two-dimensional invariant: **source-set integrity** and **process convergence**. `SourceSetIntegrity` verifies a sealed manifest covering 14 critical installation-path files, preventing mixed-generation deployments where only Installer.php is current. `SourceActivationIdentity` combines protocol, generation, loaded Installer path/hash, and critical-source-set fingerprint into a fail-closed pre-database identity.

`SourceActivationHandshake` issues an atomic, short-lived CLI activation nonce tied to the critical source set. The web `/install/source-status` endpoint acknowledges that exact nonce after Laragon/PHP reload; a sealed web acknowledgement records web SAPI and source fingerprint. CLI verification with `--require-web-ack` therefore proves CLI and web processes execute the same source set, rather than assuming CLI OPcache cleanup affected a separate web process.

Installation completion progress is deliberately separate from N1.0 certification evidence. `n1-installation-progress.php` derives the latest installer-control state and maps the permanent-lock stage to 98% until the sealed installed.lock commit exists. Fast-track renders that bar before the existing granular 105-gate target bar. No new C1-C6 denominator was introduced.


## N1.0 Target Execution v5.4 — Runtime Class Convergence & Secure Web Ack

The source-activation boundary now has three independent layers: **sealed disk source**, **loaded PHP class generation**, and **CLI-to-web process acknowledgement**. The sealed disk manifest covers 22 critical installer-path files. Twenty PHP classes carry `RUNTIME_SOURCE_GENERATION=n1-v5.4`; the identity service reflects those constants from the classes actually loaded by the current PHP process and hashes the runtime-class map. A disk-current file with an older compiled OPcache class therefore fails convergence even when `hash_file()` sees the new file on disk.

The CLI activation receipt binds its nonce to both the disk source-set fingerprint and the loaded-runtime-class fingerprint/count. The web acknowledgement requires a one-time local activation token whose SHA-256 is sealed into the CLI receipt. Anonymous source-status requests are redacted and cannot write acknowledgement state. After a successful acknowledgement the token is deleted, making the acknowledgement single-use for that activation generation.

Installer-run failure state now records only a sanitized/truncated failure summary and stage. Target progress can expose the exact blocker without persisting credential values. These mechanisms are diagnostic/precondition hardening and do not expand the established 105 real target gates.


## N1.0 Target Execution v5.5 — Installer Host/Clock Preflight Stabilization

Host/clock validation now has two explicit trust levels. `RuntimeHostClockIdentity::installationAttestation()` is the first-install safety boundary: it requires a usable database UTC clock anchor, a bounded host/database clock skew, monotonic clock support, writable temporary storage, atomic rename, advisory locking and cryptographic randomness. `RuntimeHostClockIdentity::current(true)` remains the strict production/HA certification boundary used by C2/C6. A strict-profile warning therefore cannot silently reopen or weaken target certification simply because installation is permitted to commit.

The Windows platform does not treat POSIX umask as an applicable blocking property. On POSIX platforms, configured umask policy remains enforced. Installer-specific clock tolerance is configuration-bound and cannot exceed five minutes; the default is 60 seconds. Strict target clock skew remains independently configured at the existing 5-second default. The host/clock preflight is executed before any destructive database reset or migration and rechecked immediately before the sealed installation commit.


## N1.0 Target Execution v5.6 — Installer Runtime Readiness Preflight

Installation admission is now a separate runtime profile from final target certification. `RuntimeInstallationReadiness` aggregates source generation, dependency identity, host/clock, resource, policy, process and activation checks into one fail-closed installer-safe decision. It executes only after the requested database connection has been proved usable, because the host/clock profile anchors time against the selected database, but before any destructive database action. The final installation-lock path re-runs the same profile so preflight and commit cannot drift to different policy semantics.

`RuntimeResourceEnvelopeIdentity::installationAttestation()` requires bounded PHP memory headroom plus writable/free temp, storage and bootstrap capacity; the full deep resource envelope remains mandatory for upgrade/HA certification. `RuntimePolicyPlaneIdentity::installationAttestation()` enforces core runtime invariants without treating release-signing, external supply-chain anchors, production transport or HA convergence as first-install prerequisites. `RuntimeProcessPlane::installationAttestation()` proves process-policy, lease/throttle, queue schema and blocking-liveness safety without requiring live HA quorum. `RuntimeActivationIdentity::installationAttestation()` proves activation epoch integrity/writeability and secure randomness before first commit.

The `runtime-readiness` progress stage is explicitly cancellable and pre-mutation. Failure at that stage therefore does not set `protected_started` and cannot cause later recovery logic to misclassify an untouched database as an interrupted protected installation. Strict C2/C6 semantics remain unchanged.


## N1.0 Target Execution v5.7 — Source-Bound Install Commit & Runtime Handoff

The deployment generation used by installation is now source-bound from the first uninstalled request. `RuntimeDeploymentIdentity` computes the full source-tree attestation for source-fallback mode, so the generation stored in the permanent installation lock can be reproduced by the first installed request. `RuntimeInstallationReadiness` includes deployment deep verification as an eighth installer-safe component.

Runtime admission and strict certification are deliberately separate concerns. Host, resource, policy and process planes require exact installed/current fingerprints for request admission, but their strict production/HA status is evaluated by C2/C6 rather than converting a safe first installation into a 503. Service health, framework compatibility and dependency runtime identity remain fail-closed request-admission conditions.

`RuntimePostInstallHandoff` executes immediately after the sealed lock commit, clears the pre-install deployment memo, adopts the activation epoch, verifies full-source/deployment integrity and compatibility, and records an integrity-sealed runtime handoff receipt. A failure after the commit is reported as a committed-install warning rather than reopening or rolling back the installer.


## N1.0 Target Execution v5.8 — Clock Semantics & Writable Temp Portability

Database-clock identity is now explicitly epoch-based rather than datetime-string based. MySQL/MariaDB use `UNIX_TIMESTAMP(CURRENT_TIMESTAMP(6))`, avoiding a session-timezone double interpretation of `UTC_TIMESTAMP()`. A skew close to a 15-minute timezone increment is surfaced as a diagnostic signature, but the corrected query is the primary fix.

Installation filesystem probes resolve a writable application temp directory independently from strict system-temp certification. This makes Windows service-account installs portable when `C:\Windows\Temp` is unavailable while preserving strict production observations. The host atomic-rename/flock/entropy probes and the installation resource-capacity probe consume the same resolved directory.


## N1.0 Target Execution v5.9 — Exact Resume Provenance & Commit Snapshot Stability

The installation recovery boundary now uses schema-2 resume provenance. The fingerprint includes the complete source attestation and the sealed critical-source manifest in addition to migrations, core seeders and dependency locks. This makes protected-schema resume an exact-source operation instead of merely a same-protocol operation.

Permanent installation commit uses two snapshots: an early pre-mutation readiness snapshot and a final pre-lock snapshot. Dependency runtime identity is freshly re-resolved at commit time, including installed Composer package matching, and compared against the preflight state. Source activation fingerprint, critical source-set fingerprint, loaded runtime-class fingerprint, source-tree SHA and deployment generation must also remain identical. The permanent lock is not published when either source or dependency provenance changes during the long-lived installer request.

After the durable lock is written, runtime handoff has its own recovery state. Runtime convergence plus a current integrity-sealed handoff receipt are required for handoff readiness. A failed handoff is represented as `committed-runtime-pending`, with login redirect suppressed until an explicit reconciliation records a current receipt.


## N1.0 Target Execution v5.10 — Frontend Build Closure & Exact C1 Diagnostics

C1 compiler diagnostics now have an explicit two-layer truth model. `n1-historical-typescript-remediation.php` is a source-regression ledger for the known 76-error/11-file incident; it cannot certify the target. `n1-frontend-build-diagnostics.php` parses actual TypeScript process output and binds diagnostics to the current C1 run. `n1-c1-dependency-certify.php` remains the only component allowed to promote C1 through the ordered typecheck and Vite-build gates.

The historical baseline records per-file error counts and error-code families, allowing a later target run to distinguish a clean source from a reappearance inside the original failure surface. Diagnostic JSON is integrity-bound into the C1 run summary but is not a new certification gate. C1 remains 14 gates and the N1.0 granular denominator remains 105.
 C1 evidence verification independently revalidates the diagnostic hashes, source-tree identity and compiler-clean state before accepting a PASS report.


## N1.0 Target Execution v5.11 — Transactional Dependency Lock Intake

Dependency lock generation is now separated from lock promotion. The refresh phase operates only in a generated staging workspace and produces a candidate pair plus source/manifest/hash/diff dossier. This makes package-manager resolution failure non-destructive: a Composer success followed by npm failure cannot mutate one root lockfile while leaving the other stale.

Promotion is an explicit human-reviewed transaction. Candidate source-tree, manifest and lock hashes are revalidated before mutation. Existing `composer.lock`, `package-lock.json`, refresh handoff and reviewed-lock attestation are backed up durably before the first root write. Promotion then advances through a persistent journal and must pass strict dependency contracts plus reviewed-lock attestation verification. Ordinary errors roll back immediately; process/service crashes leave a recoverable journal and durable backup set. New refresh, promotion and target fast-track execution fail closed while an incomplete journal exists.

This hardening changes no C1–C6 denominator. It turns the missing-lock C1 blocker into a bounded operator workflow while preserving the rule that source certification never manufactures dependency review provenance.


## N1.0 Target Execution v5.12 — Reproducible Dependency Toolchain Binding

Dependency lock review now has a reproducibility dimension in addition to transactional safety. Two isolated refresh workspaces receive the same manifests, existing locks, source identity and package-manager toolchain. Both independently generate candidate lockfiles; the pair is eligible for human review only when both lock SHA-256 values are identical.

The dependency toolchain is fingerprinted from PHP, Composer, Node and npm versions and executable hashes under the existing certified version policy. Candidate metadata, promotion journal/handoff and reviewed-lock attestation bind this fingerprint. A toolchain change invalidates the candidate/review lineage rather than being treated as an equivalent environment.

C1 locked installation records the reviewed lock pair and toolchain fingerprint before dependency installation and compares them after `composer install --no-scripts` and `npm ci`. Installation is rejected if either lockfile or toolchain identity changed. This preserves the existing 14 C1 gates while making their dependency evidence reproducible and immutable.


## PKG-1 — Resumable Usable Release Boundary

PKG-1 is a target-execution state machine rather than another source-only certification phase. Dependency candidate/review work is framework-independent and runs before Artisan so a clean source ZIP can start without `vendor`. After reviewed locks are promoted, C1 installs the exact Composer/npm graphs and proves the 14 existing dependency/frontend gates. Only then does PKG-1 bootstrap a missing `.env`/APP_KEY, establish CLI→web source convergence, and hand control to the browser installer.

The browser installer remains authoritative for fresh selected-database runtime readiness because only it owns the selected database connection before environment commit. After `installed.lock` exists, PKG-1 re-verifies readiness against the committed configuration, then validates post-install source/deployment/activation handoff. The final smoke is read-only except for the real authentication/session flow: it verifies the installer Super Admin, database readability, public health/login behavior and a real `/login` → `/admin` request using a password supplied only through `NEXORA_PKG1_SMOKE_PASSWORD`. The password is never written into evidence.

Final PKG-1 evidence is sealed and source-bound. A modified C1 report, reviewed lock, build asset report, installation lock, handoff receipt or usable-smoke report invalidates `pkg1-closure-evidence-verify.php`.

## PKG-1 verified Composer bootstrap boundary

The clean-package dependency stage may execute before Laravel/vendor dependencies exist. Composer discovery therefore has a framework-independent bootstrap boundary. `target-composer.php` prefers `PATH`, then the verified Nexora-local PHAR, then Laragon Composer candidates. `composer-bootstrap.php` writes the local PHAR only under runtime storage, verifies the current official installer using its SHA-384 signature before execution, constrains the result to the certified Composer major range, and emits a runtime bootstrap attestation. The local tool remains outside source/release archives and is subsequently bound by the v5.12 dependency-toolchain fingerprint. Failure at this boundary leaves both root lockfiles untouched.

## PKG-1 rc.79 — production environment and build-input identity

The clean source archive contains two non-secret environment templates but never a real `.env`. The production template is a contract artifact: it preserves secure Laravel session defaults and Nexora's schema-13 runtime fencing across cutover, deployment generation, activation, engine, database, storage, service, host, resource, policy and process planes. Runtime values may still be overridden by the operator's real environment, while the shipped template remains fail-closed and credential-free.

Frontend build evidence has a separate source/lock/config identity. Before invoking TypeScript and Vite, the build wrapper hashes the current source attestation plus Composer/npm manifests and locks, `tsconfig.json`, `vite.config.ts`, and the 11 files from the historical 76-error ledger. The same identity is recomputed after build. C1 accepts the build only when the wrapper report is PASS, pre/post identity is unchanged, and the report still matches the current target source and reviewed locks.


## v5.15 dependency candidate supply-chain boundary

The dependency intake pipeline now has an explicit supply-chain admission layer between reproducible lock generation and human promotion. Lock provenance is deterministic and source-bound: Composer dist/source hosts, npm resolved hosts, npm integrity coverage, and both lock SHA-256 values produce a provenance fingerprint. Network vulnerability checks are separate runtime evidence and must PASS in both isolated candidate workspaces. Their raw output is not retained; only exit codes and output digests are stored.

Promotion does not trust the refresh dossier blindly. It creates a private candidate audit workspace, copies the current manifests plus reviewed candidate locks, recomputes provenance, reruns Composer/npm audits, compares the resulting supply-chain fingerprint, and only then permits transactional root lock replacement. Reviewed-lock attestation seals the provenance and supply-chain fingerprints so later lock/source-origin drift invalidates dependency trust. This strengthens PKG-1 while keeping C1=14 and the N1.0 denominator at 105.


## v5.16 offline-safe PKG-1 resume boundary

PKG-1 now evaluates terminal and reusable evidence before dependency/network work. The candidate quarantine boundary also runs before Composer discovery: stale/corrupt unpromoted candidate directories are moved under runtime `stale-candidates/`, while valid exact-source candidates can remain at the human review boundary offline. A sealed closure receipt is terminal; a reusable exact-source C1 receipt is the dependency/network boundary. This prevents an already-built or already-installed target from regressing to a Composer/DNS requirement during ordinary resume/status operations. The read-only `pkg1-status` doctor derives the next operator action from the journal, candidate/review state, C1 evidence, source/web acknowledgement, installation lock and post-install state without mutating target evidence.

## PKG-1 operator state-machine launcher (v5.17)

`pkg1-run.ps1` is a Windows/Laragon operator shell around the existing immutable PKG-1 state machine, not a second certification implementation. It consumes `pkg1-status.php --json` and dispatches the existing canonical scripts. Safe states (`READY_COMPOSER_BOOTSTRAP`, `READY_CANDIDATE_GENERATION`, `STALE_CANDIDATE`, `READY_C1`, `READY_SOURCE_RESUME`, `WAITING_POST_INSTALL`) can advance automatically. Human-bound states cannot: `WAITING_REVIEW` requires explicit dossier/lock review and the literal `PROMOTE-REVIEWED` confirmation; `WAITING_RECOVERY` requires `ROLLBACK`; `WAITING_INSTALL` opens the browser installer and waits; `WAITING_AUTH_SMOKE` delegates to the SecureString login finalizer. `COMPLETE` is accepted only after `pkg1-closure-evidence-verify.php` passes again.

## v5.18 Windows PowerShell parser-safe operator boundary

The Windows PKG-1 launcher is an ASCII-only source artifact and the `.bat` entrypoint performs an authoritative local `System.Management.Automation.Language.Parser.ParseFile()` check before `-File` execution. This closes an encoding-dependent Windows PowerShell 5.1 failure mode where UTF-8 punctuation without a BOM can be decoded through the active ANSI codepage and accidentally become smart quotation tokens. The source-side `pkg1-powershell-contract-verify.php` additionally checks ASCII purity and lexical delimiter/string balance, while the Windows parser remains the final runtime parser authority.


## v5.19 PHP-first operator execution boundary

The Windows/Laragon entrypoint now terminates the primary PowerShell dependency: `pkg1-run.bat` dispatches directly to `pkg1-run.php`. The PHP launcher is the thin interactive driver over `pkg1-status.php`, `pkg1-usable-closure.php`, recovery, installer and closure verification. It stops immediately when canonical closure evidence reports `blocked`, preventing retry storms against a persistent DNS/toolchain failure. PowerShell is retained only for SecureString password capture during the final live authentication smoke; both the PHP launcher and manual finalizer batch parser-check that finalizer before execution.


## v5.20 Windows npm executable bridge

Windows npm/npx command wrappers are batch launchers rather than PE executables. The framework-independent target command runner resolves `node.exe`, resolves the npm/npx launcher directory, locates the corresponding npm CLI JS payload and executes it with Node using an argv array and `bypass_shell=true`. This preserves command argument boundaries without depending on cmd.exe quoting. Dependency-toolchain evidence fingerprints the executed npm CLI payload while Node remains separately fingerprinted. The bridge is central to candidate lock generation and candidate npm audit, and PKG-1 status now refuses to advertise candidate readiness until the full dependency toolchain passes.


## v5.21 npm bundled-integrity coverage

Package-lock v3 integrity admission distinguishes independently fetched registry packages from npm bundled children. Direct packages continue to require their own SRI integrity. Entries marked `inBundle: true` can inherit integrity only from a verifiable ancestor bundle owner: the owner must have a registry `resolved` URL and SRI `integrity`, and its `bundleDependencies`/`bundledDependencies` must explicitly contain the direct bundled package. This prevents false rejection of legitimate Tailwind WASM bundles without weakening external package integrity requirements. The same resolver is reused by candidate validation, candidate supply-chain provenance, reviewed-lock validation, and final dependency provenance.


## v5.22 semantic lock and TypeScript depth boundaries

Dependency reproducibility distinguishes representation from graph semantics. Both isolated workspaces retain raw lock SHA-256 evidence. Canonical semantic digests recursively sort object keys and sort Composer `packages`/`packages-dev` by package identity; they do not discard versions, source URLs, SRI, dependency metadata or other semantic fields. A/B semantic mismatch is fatal and emits dependency version diffs. A semantic match with different raw bytes is permitted with a warning, after which workspace A's exact raw lockfiles remain the only promotable payload and are sealed by raw SHA during review/promotion.

For frontend typing, recursive JSON models remain strongly typed at their domain/component boundary but are not recursively expanded through Inertia's conditional `FormDataType`. Automation uses finite workflow scalar configuration, and Documents uses a deliberate opaque form field for the recursive writer tree with explicit `DocumentContent` casts at typed consumers. This prevents TypeScript's instantiation-depth limit from obscuring real type errors.


## v5.23 development-first installer boundary

The installer presents a small default interaction surface while preserving advanced recovery and data-safety controls behind conditionally visible sections. Installation UI is independent of Laragon; Laragon is one supported local environment, not an architectural dependency. `development-readiness.php` is a non-certifying development health check and is intentionally separate from dependency review/promotion and final C1-C6 certification.
