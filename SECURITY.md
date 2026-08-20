# Nexora Security Baseline — N0.6

N0.5 establishes **Nexora Sentinel** as a pre-execution security gate. It does not claim that arbitrary third-party PHP is already sandboxed.

## Primary package rule

> No third-party package executes merely because it was uploaded, scanned or displayed in admin.

```text
Upload
  ↓
Quarantine
  ↓
Static inspection
  ↓
Risk decision
  ↓
ALLOW / REVIEW / BLOCK
```

N0.5 stops at the decision. A later block will own transactional activation.

## Quarantine guarantees

- Random UUID stored filename; original filename is metadata only.
- Quarantine is under non-public `storage/nexora/quarantine`.
- ZIP is inspected without extracting into the application.
- Upload SHA-256 becomes immutable baseline identity.
- Baseline mismatch on rescan hard-blocks.
- Digest mutation during scan hard-blocks.
- Quarantine deletion refuses to silently treat failed file removal as success.
- Scan failure is `block`, never implicit trust.

## Current Sentinel detection families

- path traversal and absolute paths
- filesystem path ambiguity / reserved Windows names
- duplicate normalized ZIP entries
- symlinks and encrypted/unreadable entries
- ZIP-bomb/resource-exhaustion heuristics
- nested archives and hidden executable payloads
- native binaries, OS scripts and PHAR
- secret/private-key files
- web-server override files
- directly executable public PHP
- malformed/missing `nexora.json`
- Composer lifecycle scripts/plugins/custom repositories
- PHP AST parse failure, dynamic execution, shell/process primitives and dynamic calls
- unsafe deserialization, raw network/socket use and privileged filesystem primitives
- obfuscation/large encoded payload indicators
- JavaScript dynamic execution, process/env/cookie/storage/network/DOM injection surfaces
- SVG/HTML script/event/javascript-URI/external-resource surfaces
- declared capability vs observed behaviour mismatch

## Human authorization and code authorization remain separate

```text
Human user → Roles / permissions → HTTP + domain authorization
Code runtime → Runtime identity → Capabilities → platform API
```

Sentinel uses package-declared capabilities as behavioural contracts; later brokers will enforce them at runtime.

## Current high-risk capability vocabulary

- `http.outbound`
- `filesystem.private`
- `filesystem.public`
- `secrets.read`
- `security.sentinel.scan`
- `security.findings.read`
- `security.quarantine.manage`

## Admin security

- Sentinel pages are permission protected.
- Scan/upload routes are rate limited.
- Rescan requires scan permission.
- Quarantine deletion requires dedicated critical permission.
- Scan/quarantine operations are audit logged and correlated with request IDs.
- Source excerpts are displayed as text, never executed.

## Still pending before third-party activation is considered production-safe

- signed publisher identities
- package signature/digest verification against trusted metadata
- SBOM generation/ingestion
- dependency advisory/malware feeds
- secure update metadata / rollback protection
- transactional staging + migration dry run + health check + rollback
- scoped network/storage/DB/secret brokers
- runtime circuit breaker and auto-quarantine
- trusted-native vs restricted vs isolated execution tiers
- container/process isolation for untrusted executable workloads

These are subsequent Sentinel/extension lifecycle blocks, not claims made by N0.5.


## N0.7 deployment/installer security boundary

- The main Laravel installation services never execute Composer, NPM, shell processes, or arbitrary operating-system commands.
- The standalone pre-Laravel deployment bootstrap may run only fixed Composer/NPM/build tasks, only before installation, and only when server process execution exists. Local development requests may be auto-authorized; remote source builds use a random file-backed deployment access key. Application database credentials are never used as the deployment-bootstrap secret and are collected only inside the Laravel installer Database step.
- There is no arbitrary command input and request data is never passed to `proc_open`.
- Customer release artifacts should ship prebuilt with `vendor` and `public/build`, eliminating package-manager/process requirements on shared hosting.
- Prebuilt release lock/build hashes are recorded in `nexora-release.json` and checked by the deployment bootstrap.
- UI-uploaded release ZIPs are path-normalized, reject traversal/symlink/environment-state entries, are staged before deployment, and are not extracted with blind `extractTo`.
- Installation uses file-backed session/cache before the database exists.
- Database passwords are never written to installer logs or the installation lock.
- Non-empty databases are blocked by default and require explicit operator acknowledgement.
- Forward migrations are used; the browser installer never calls `migrate:fresh`.
- Concurrent installation is blocked by an exclusive mutex.
- The installer becomes inaccessible after `installed.lock` is written.
- Runtime lock/mutex files are excluded from version control.

## N0.10 streamed deployment safety

Observable deployment does not broaden the command surface. Browser requests select only fixed allow-listed tasks; request input is never concatenated into an arbitrary shell command. The streaming layer inherits the normalized Nexora process environment, retains command timeouts, strips terminal control sequences before browser display, enforces a single deployment lock and terminates an active child process when the browser cancels/disconnects.

The final Laravel installation stream never executes Composer/npm. It exposes only the existing controlled provisioning stages and never emits database passwords, APP_KEY values or other stored secrets in progress events.


## N0.11 environment persistence and installer-brand security

A missing or read-only project-root `.env` is no longer treated as an unrecoverable bootstrap error. Before installation Nexora stores only temporary bootstrap state under protected application storage. Final environment values are written atomically to the root `.env` when possible, otherwise to the protected fallback under `storage/app/nexora/environment`. The selected location is recorded by a non-secret active marker and the temporary bootstrap key is removed after the committed environment contains the application key.

The fallback directory, active marker, temporary bootstrap key and environment file are excluded from source/release artifacts. Installation metadata records only the environment mode, never the database password or APP_KEY. This design avoids requesting broad project-root write permissions solely to install Nexora while maintaining deterministic configuration selection across HTTP and CLI processes.

Brand assets and icon libraries are also integrity-gated: the source guard requires non-empty Nexora brand/favicon artifacts, requires `lucide-react` as the canonical React icon dependency, and prevents the retired icon dependency from re-entering the package.


## N0.12 deployment cancellation + locale security

Deployment cancellation is scoped to a cryptographically random run ID and requires the already-authorized bootstrap session/CSRF token. The browser cannot supply arbitrary process IDs or commands. On Windows, Nexora obtains the child PID from `proc_get_status` and uses a fixed numeric `taskkill` command solely to terminate that owned process tree. Cancellation flags, worker state and lock metadata live under protected runtime storage and are excluded from release artifacts.

Locale changes accept only codes defined in the Nexora localization configuration. The pre-Laravel switcher uses an allow-listed catalog; the Laravel locale endpoint validates against the same supported set and is rate limited. Locale cookies contain only an allow-listed locale code and never credentials. RTL direction is derived from trusted locale metadata, not request-provided HTML attributes.

The premium release picker changes presentation only. It does not bypass archive validation, release-manifest verification, hash checks or staging rules.


## N0.13 installer stabilization + existing-database safety

The source-build bootstrap no longer collects database host/user/password values. Database configuration starts only after the Laravel installer is running. A non-empty selected database is fail-closed: Nexora first creates a SQL backup in protected storage, binds its metadata to the installer session and database fingerprint, records when the protected download endpoint is used, then requires an additional explicit destructive-reset consent. The install transaction revalidates all of those conditions before dropping any table or view. If backup creation, download verification or consent is missing, the schema reset is blocked.

The Super Admin password keeps a hard minimum policy (12+ characters with lowercase, uppercase, number and symbol). The separate Low/Medium/Strong score is advisory intelligence on top of that minimum. Low or Medium choices require explicit consent on both client and server; predictable patterns cannot be rated Strong. Password confirmation is validated live in the UI and still enforced by Laravel's confirmed rule server-side.

The React entry point uses the Inertia v3 Vite integration instead of mixing the v3 plugin with the legacy resolver callback. This removes a class of build/runtime divergence where the frontend could compile incorrectly or fall into the global admin error boundary immediately after installation.

## N0.14 database reset and cancellation safety

Existing database contents are never reset merely because a connection test succeeded. The user must explicitly choose the reset path and either verify a downloaded Nexora backup or consent to irreversible reset without a Nexora backup and type the database name exactly. The server revalidates this choice.

Installation cancellation is deliberately restricted to safe checkpoints. Once database reset, environment mutation, or schema migrations begin, Nexora disables cancellation rather than risk a partially mutated schema. Cancellation state is file-backed, session-bound, and updated under filesystem locks.


## N0.15 data connection security

Auxiliary data-service secrets are stored through Laravel's encrypted cast in `nx_data_connections.secret_payload`. Admin routes are permission-gated and tests/audits never intentionally echo stored secrets. Installer-created Super Admin email verification is explicit, and login recovery only verifies the user whose ID matches the signed installation-state owner metadata and who still holds the Super Admin role.

## Media and newsletter controls (N0.25)

Media Library does not expose arbitrary uploaded bytes directly from the project public directory. Uploads are MIME-inspected, renamed, checksummed and stored through the configured media disk, while public delivery is mediated by a controlled route with content type, immutable caching and `nosniff` headers. SVG/HTML/PHP and other active-content types are not in the base public allow-list.

Newsletter data stores consent source/time and a non-guessable unsubscribe token. Public unsubscribe links require a confirmation POST, reducing accidental unsubscribes by automated link scanners. Campaign delivery is queue-based and records per-recipient success/failure state.

## N0.26 discovery intelligence controls

- Analytics never persists raw visitor IP addresses; HMAC-derived visitor identifiers rotate daily.
- GPC (`Sec-GPC: 1`) and DNT (`DNT: 1`) suppress first-party analytics recording.
- Public search result pages are `noindex,follow` and search query length is bounded.
- SEO crawler requests are restricted to the configured `APP_URL` host and exclude Admin/auth/installer/media/newsletter utility surfaces.
- Crawl output is treated as evidence/diagnostics; no arbitrary remote URL input or open-proxy behavior is exposed through Admin routes.
- Search projections are rebuildable and cannot replace canonical Document/Media/SEO records.

## N0.27 automation and Webhook controls

- Workflow conditions use an allow-listed dotted-path evaluator; no user-provided executable expressions are evaluated.
- Workflow actions execute through registered adapters and queue jobs with per-step checkpoints, preventing already-successful steps from repeating during a later retry.
- Newly generated inbound/outbound signing secrets are shown once to the authorized Admin and stored through encrypted model casts.
- Outbound Webhooks use HMAC-SHA256 over `timestamp.raw_body`, include a delivery UUID and idempotency key, do not follow redirects, and enforce timeouts/retry ceilings.
- Production outbound destinations require HTTPS; localhost, embedded URL credentials, literal private/reserved addresses and DNS results resolving to private/reserved A/AAAA records are blocked.
- Inbound Webhooks require timestamp + HMAC verification, reject timestamps outside five minutes, limit bodies to 1 MB JSON, and deduplicate endpoint-scoped idempotency keys.
- Inbound secret rotation keeps the previous secret for only a 15-minute grace period. Source addresses may be allow-listed, while retained receipts store a keyed source hash rather than raw source IP.
- Raw automation events and inbound receipts have configurable retention and a scheduled prune command.

## N0.29 extension and Marketplace security

Marketplace package discovery is not a trust decision. Remote artifacts are downloaded without redirects, checked against catalog SHA-256 metadata when present, then moved into protected Sentinel quarantine. Trusted-only sources require an active publisher key identity before staging. Installation is blocked unless Sentinel returned `ALLOW`, package version/content immutability is enforced, capabilities require explicit grants, and forward-only extension migrations require the N0.28 execution policy. Nexora never stores publisher private signing keys.

## N0.30 payment and billing safety

Nexora Core ships with no payment gateway credentials or embedded gateway SDK. Payment adapters are extension-provided and remain subject to Sentinel, supply-chain trust and extension capability grants. Core provider configuration stores non-secret preferences and secret references only. Payment/refund services expose idempotency foundations and immutable provider references; amounts are integer minor units rather than floating-point billing values.

## N0.31 CRM safety boundaries

CRM opportunity monetary values are integer minor units. Lead conversion and opportunity stage movement use database transactions and row locks where state races could produce conflicting sales history. CRM-to-Commerce linking is explicit and does not merge or overwrite Commerce customer/order/invoice identity. Communication providers are extension-provided through `CrmActivityProviderContract`; Core stores no Gmail/Outlook OAuth secrets or provider SDK implementation.

## N0.32 Membership + Helpdesk security boundaries

Protected content is denied unless the current authenticated user satisfies the active Membership policy. Membership policy evaluation does not grant Admin permissions and cannot override Identity role/permission checks. Commerce subscriptions are synchronized only when a Membership plan explicitly maps to a Commerce price.

Helpdesk internal notes are persisted with an explicit internal flag and are not treated as requester-facing replies. Ticket requester links are foreign-key references to Identity/CRM/Commerce records rather than duplicated credentials or billing secrets. No external email/helpdesk provider SDK or provider secret is added to Core.

## N0.33 enterprise tenancy and identity controls

Tenant-scoped models use a centralized organization context plus Eloquent global scope, and new records are stamped with the current `tenant_id`. Upgrade migrations backfill legacy records to the default organization before tenant isolation becomes authoritative. Organization roles are a restriction layer over platform RBAC and cannot elevate users beyond their global permissions.

Verified custom domains require a one-time DNS TXT proof stored only as a SHA-256 digest. If the PHP runtime cannot perform DNS TXT queries, Nexora returns a validation error instead of a fatal exception. SSO provider secrets use encrypted casts, SCIM bearer tokens are stored only as hashes, invitation tokens are stored only as hashes, and impersonation sessions record actor, target, reason, tenant and lifecycle events. Impersonation never permits an ordinary administrator to impersonate a Super Admin and always exposes a visible stop-impersonation control while active.

## N0.34 distributed-runtime and recovery controls

Cloud readiness is conservative. A node in draining or maintenance state returns HTTP 503 from readiness while liveness remains minimal, allowing a load balancer to stop new traffic without Nexora force-killing in-flight work. Scheduler leadership is lease-based, not process-local, and shared critical sections consume Laravel atomic cache locks through `DistributedLockContract`. HA topology warnings explicitly identify sync queues, node-local cache/session drivers and local object storage instead of treating them as distributed-safe.

Runtime database backups remain in protected storage, are SHA-256 sealed, and are verified before a restore plan can be issued. Installer-temporary backup artifacts are removed after the runtime copy is persisted. Restore planning is intentionally non-destructive: it generates an offline drain/maintenance/restore/recovery sequence and one-time confirmation, but no public/Admin request performs automatic destructive restore. Health endpoints expose status only and do not return database credentials, storage secrets or other configuration values.


## N1.0 RC6 authentication, session and tenant authorization controls

Successful password login, self-registration, SSO and impersonation privilege switches rotate the authenticated session identifier and CSRF token. Logout invalidates the current session. Password changes rotate remember credentials, revoke other database sessions and rotate the current session; password resets rotate remember credentials and revoke all database sessions for the account. Failed-login audits retain a normalized email hash rather than the submitted address, and forgot-password responses do not expose whether an account exists.

Admin entry uses two authorization keys: platform `admin.access` plus the current enterprise-role restriction. Route-bound tenant models receive an explicit `tenant_id` assertion before Admin controllers run, and tenant-owned foreign IDs use `TenantExists` / `TenantMemberExists` validation instead of global raw `exists` checks. This provides defense in depth against cross-tenant IDOR and cross-tenant relationship injection.

SSO state is hash-compared and expires after a bounded interval; the resolved account and enterprise membership must both be active. SCIM bearer tokens remain hash-only at rest and may suspend only the membership in the token's organization rather than globally suspending the shared user identity. Inbound webhooks remain CSRF-exempt only because they independently require timestamp freshness, HMAC signature validation, payload limits, optional source-IP allowlists and idempotency controls.

## RC9 production response and artifact security

N1.0 RC9 adds baseline response hardening (`nosniff`, referrer, frame and permissions policies), HTTPS HSTS support, and explicit `no-store` for Admin/Auth/Installer/SSO/SCIM/webhook/health/authenticated/error responses. Production ZIP generation is fail-closed: it requires exact-version certification plus a passing build-asset report, and rejects `.env`, `public/hot`, tests, node_modules, logs, runtime caches/sessions and installer/deployment recovery state after reopening the archive.


## RC10 recovery and HA evidence

Nexora never treats a generated restore plan as proof of successful recovery. Final certification requires restoration to a disposable target, checksum validation, migration/health/login/data checks and confirmation that production was not overwritten. HA certification likewise requires at least two independent runtime nodes and observed shared-state, queue, scheduler, drain and failover behavior. Evidence templates are fail-closed and placeholder values cannot pass the verifiers.

## N1.0 RC17 large-file and transfer safety

Untrusted or potentially large byte transfers must use bounded Nexora transfer policy rather than whole-file PHP memory loads. Media and package staging verify size/integrity around publication; Marketplace downloads use protected temporary storage and configured maximums; Theme/Extension ZIPs enforce entry-count, expanded-size, per-entry and compression-ratio limits in addition to traversal/symlink/case rules. Database backup artifacts are staged and checksum-verified before publication/download. Free-space checks are advisory only: any partial/failed write remains fatal and unpublished/partial destinations must be cleaned. Transfer temporary state is protected runtime data and is never a production-release payload.
