# N1.0 — Release Candidate Certification

N1.0 is a stabilization gate. It does not add a new product domain. The current RC line converts the accumulated N0.x platform into a repeatable, fail-closed certification process. RC22 adds a fail-fast target-runtime closure gate after RC21 stabilized the real Laragon TypeScript error inventory; RC20 exact-source attestation, DB isolation, strict final DB matrix and production artifact re-validation remain intact.

## Platform identity

The RC platform version is `1.0.0-rc.29`. The built-in theme and Forge scaffolds use a compatibility window that includes N0.34 packages through the 1.x line while RC validation is underway.

## One certification engine

Run the full automated gate from Windows CMD:

```bat
scripts\quality-check.bat
```

PowerShell:

```powershell
scripts\quality-check.ps1
```

Linux/macOS:

```bash
scripts/quality-check.sh
```

All wrappers delegate to `scripts/certify-release.php`; they no longer maintain separate command lists.

Source-only validation, which does not require Composer/npm dependencies:

```bash
php scripts/certify-release.php --source-only
```

A full certification run uses an isolated destructive database named `nexora_certification` by default. Override it only with the `NEXORA_CERT_DB_*` environment variables. The runner refuses unsafe database names so a normal `nexora`/production database cannot accidentally receive `migrate:fresh`.

## Automated required gates

A full run checks, in order:

1. RC source/runtime preflight.
2. Core module dependency graph verification.
3. Laravel middleware/route/scheduler/job/provider contract verification.
4. frontend/Inertia runtime contract verification.
5. Nexora Source Guard.
6. Composer manifest validation.
7. Laravel package discovery.
8. framework cache clear.
9. route registry boot.
10. scheduler registry boot (including callback naming/overlap errors).
11. isolated certification database preparation.
12. `migrate:fresh`.
13. `migrate:fresh --seed`.
14. Nexora runtime sync/cache.
15. complete Laravel/PHPUnit suite.
16. optional strict relational database matrix when `NEXORA_CERT_DB_MATRIX` is configured.
17. Laravel Pint.
18. strict TypeScript typecheck.
19. frontend component/unit tests.
20. production Vite build.
21. Laravel production optimize smoke and cleanup.
22. optional HTTP liveness/readiness/login smoke when `NEXORA_CERT_BASE_URL` is configured.
23. certified production-release packaging.

The report is persisted under `storage/app/nexora/certification/` and contains each gate's exit code, duration, and output tail. Production packaging refuses to create a release unless the report certifies the exact platform version being packaged.

## Database matrix

After Composer dependencies are installed, a strict migration/seed/Compatibility-suite matrix can be run with:

```bash
php scripts/certify-database-matrix.php --drivers=mysql,sqlite,pgsql,sqlsrv --strict
```

Credentials use `NEXORA_CERT_<DRIVER>_HOST`, `_PORT`, `_USERNAME`, and `_PASSWORD`. Missing PDO drivers fail a strict matrix instead of being silently interpreted as compatibility evidence.

## True zero installation

`scripts/setup-zero.*` now removes the root `.env` and deliberately does not copy `.env.example` back before the browser flow. `scripts/zero-state-verify.php` confirms that installer/deployment locks and a root `.env` are absent. This certifies the actual clean-ZIP bootstrap path rather than a pre-seeded Laravel environment.

For Laragon the target browser URL remains:

```text
https://nexora/
```

## Production packaging

`scripts/build-production-release.php` reads the current version from `config/nexora.php`; no release filename is hard-coded. It requires:

- Composer dependencies and `composer.lock`;
- npm dependencies recorded in `package-lock.json`;
- `public/build/manifest.json`;
- a matching `certification-pass` report.

It reopens the generated ZIP, verifies required entries, writes `nexora-release.json`, and emits SHA-256 evidence.

## RC stabilization defects fixed by whole-tree certification

The first N1.0 pass caught release/test drift that feature-specific milestone checks did not:

- the production release builder was still hard-coded to platform `0.26.0`;
- several N0.28–N0.34 Architecture tests froze the top-level platform version at `0.33.0`/`0.34.0`;
- the zero-install scripts pre-created `.env`, so they were not actually testing the documented no-`.env` bootstrap path;
- Windows/PowerShell/Linux quality runners duplicated gate lists and could diverge.

RC1 removed those four classes of drift and made Source Guard reject their return. RC2 additionally fixes the Enterprise module dependency typo (`nexora.identity` → `nexora.identity-access ^0.5`), adds a dependency-free 24-module graph verifier before Laravel package discovery, restores clean-ZIP runtime directory markers, and removes mutable top-level version assertions from historical milestone tests. RC3 fixes the runtime heartbeat middleware pipeline signature and the first target-machine TypeScript/Inertia build-error sweep. RC4 generalizes that lesson into `scripts/laravel-runtime-contract-verify.php`, so every local middleware, route alias, scheduled entry point, queued job and service provider is source-validated before Laravel package/route/schedule boot.

## Manual/operator evidence still required

Automated certification is not the same as final stable certification. Before Nexora can move from N1.0 CERTIFYING to a stable release, evidence is still required for:

- clean browser installation on supported Windows/Laragon and server targets;
- WCAG 2.2 AA browser/manual audit;
- responsive + RTL browser matrix;
- backup/restore rehearsal using disposable data;
- multi-node queue/scheduler/HA rehearsal;
- supported relational database matrix on actual engines;
- production performance budgets and regression baselines.

N1.1 must not begin as a release claim until these N1.0 gates are green or explicitly waived with documented evidence.

RC5 adds database contract certification across migration ordering/rollback coverage, foreign-key targets, identifier portability, tenant-model/table parity, deterministic seeders, repeated seeding, and full migration reset/rebuild round-trips.


RC6 adds authentication/session security certification: successful login, registration and SSO rotate sessions; password reset/change revokes persistent credentials and database sessions; password-reset requests avoid account-enumeration responses; Admin entry enforces the enterprise-role restriction key; tenant route bindings receive an explicit IDOR guard; SCIM suspension is organization-local rather than global-user destructive; tenant-owned form references use tenant-aware validation rules; and the CSRF-independent SSO/SCIM/webhook protocols are source-certified before Laravel boot.


## RC15 dependency reproducibility

Production certification is lockfile-driven. `composer.lock` and `package-lock.json` must exist and match the manifests; final installs use Composer install and `npm ci` only. Dependency audits are recorded for the exact lock hashes and are required by the production release builder. Source-only checks may remain green with missing lockfiles only as an explicit **pending dependency evidence** state; they do not constitute a dependency-backed PASS.


## RC16 filesystem / path portability

RC16 makes Windows Laragon and Linux filesystem behavior part of the release boundary. `scripts/filesystem-contract-verify.php` checks repository case-fold collisions, Windows-invalid names, PSR-4 filename/class casing, local App import casing and hard-coded path separators. `AtomicFileWriter` centralizes crash-aware state publication with destination-local temporary files, flush/fsync and Windows-safe replacement. `nexora:filesystem:doctor` probes required runtime paths. Theme/Extension ZIP installers reject case-insensitive duplicate entries and symbolic-link entries before extraction.

## RC17 large-file / transfer safety

RC17 makes large/untrusted byte transfer an explicit release boundary. `scripts/transfer-contract-verify.php` rejects full-backup memory loads and unbounded archive extraction patterns. `TransferSafety` provides protected staging, chunked copy loops that handle partial `fwrite()` results, capacity preflight, checksum/size verification and atomic publication. Media uploads validate the stored object after streaming; Marketplace downloads enforce progress/Content-Length budgets; Theme and Extension packages enforce entry-count, expanded-size, per-entry and compression-ratio limits before streamed extraction. Runtime database backups and installer MySQL/SQLite backup artifacts are staged, flushed/verified and atomically published. `php artisan nexora:transfer:doctor` probes the configured transfer root and bounded-copy path in dependency-backed target certification.


## RC18 runtime limits / queue safety

RC18 introduces a single runtime policy for HTTP body ceilings, explicit trusted proxies, PHP minimum capacity and queue timeout/retry relationships. Every first-party queue job has explicit attempts, timeout and timeout-failure behavior; retrying jobs have explicit backoff. Queue workers clear tenant context between jobs and can request graceful restart when memory crosses the configured threshold. SEO crawl cancellation is cooperative and persisted. `php artisan nexora:runtime:doctor` is the dependency-backed operator check.


## RC19 concurrency / idempotency safety

RC19 introduces `ConcurrencyGuard` with bounded transaction attempts, a portable row-backed transaction mutex, idempotency convergence after unique-key races, stale-aware queue delivery claims, transaction-time optimistic locking for Documents/Studio and serialized scheduled article publishing. Target certification runs `php artisan nexora:concurrency:doctor`. External SMTP/HTTP effects remain explicitly at-least-once; provider-independent exactly-once delivery is not claimed.


## RC20 final closure integrity

RC20 binds every automated and operator evidence stage to the exact source-tree SHA-256, removes PHPUnit DB `force=true` overrides that could escape the isolated certification database, requires all five primary database families in final mode, adds zero-install and existing-upgrade rehearsal evidence as closure domains, enforces minimum database server versions, and independently validates the sealed production ZIP and sidecar before N1.0 can be DONE.


## RC21 target frontend typecheck

RC21 guards the target build regressions reported by Laragon: recursively serializable Inertia `useForm` data, `RequestPayload` router payloads, non-chainable `transform()` semantics, Writer nested form values and the shared navigation component API. `scripts/inertia-frontend-contract-verify.php` is a dependency-free early gate; the real `npm run build` on the reviewed locked dependency graph remains mandatory before N1.0 can close.


## RC22 target runtime closure runner

RC22 adds `scripts/target-runtime-run.php` as the fail-fast target counterpart to the keep-going RC12 diagnostics collector. It requires reviewed Composer/npm lockfiles, can install only those locked graphs, runs the real frontend type/test/build/budget gates, boots Laravel package/routes/scheduler and runtime doctors, and records the first required blocker with redacted logs. `--full` delegates destructive migration/seed/PHPUnit work to the isolated certification engine; no destructive migration command exists in the RC22 runner itself. A target-readiness PASS does not close N1.0; the remaining operator evidence and independently reverified production package remain mandatory.
