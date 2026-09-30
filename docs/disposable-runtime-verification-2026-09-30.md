# Disposable runtime verification — 2026-09-30

Base main: `84feada170f0529078984821f4a6706a830a1b1d`. Registered unit: SYS-RUNTIME-IDENTITY. Scope: disposable local SQLite installation and HTTP execution; no production acceptance or key rotation claim.

## Reproduced defect and fix

A fresh SQLite installation under `database/runtime-qa.sqlite` migrated, seeded and created the administrator, but refused permanent installation lock publication: source_tree_sha256 and deployment_generation changed after preflight. Runtime SQLite content was included in source attestation. An otherwise equivalent fresh installation using a database outside the attested source roots committed successfully.

The fix excludes only direct `database/*.sqlite` / `*.sqlite3` runtime data and SQLite -wal/-shm/-journal sidecars from packaged-source hashing. Migration/seeder/factory PHP and nested fixtures remain attested. Database integrity/recovery remains a separate data-plane responsibility. No install provenance assertion or authentication/runtime middleware is removed.

Regression: creation/writes of runtime database files do not change the source hash; modification of migration PHP still changes it. SourceAttestationTest: 2 tests, 10 assertions PASS. Unified source certification: SOURCE PASS before installation.

## Actual disposable execution

PHP 8.3.6 extracted into a task-owned toolchain; PHP memory/upload/input/execution settings meet installer requirements. Dependencies and build copied from the reconciled checkout. No paid hosting/provider was provisioned.

With the fix and a fresh database at `database/qa-fixed.sqlite`:

- Installer runs real migrations, seeding, administrator creation and lock commit: INSTALL_COMMITTED.
- Fresh HTTP runtime handoff completes.
- Installed lock/admin existence, active/verified/super-admin flags, database read and required routes: PASS.
- Post-install runtime readiness/current receipt: PASS.
- Public HTTP smoke with explicit base URL: PASS (not skipped).
- Actual cookie/CSRF login POST: 302 to configured `/admin`.
- Authenticated admin GET: 200.
- Existing pkg1-usable-smoke.php returns exit 0 / status pass.

In the separate unmodified-main fixture with an outside-source database, CLI source activation, token-authenticated web acknowledgement and CLI --require-web-ack all returned PASS. After optimize:clear/source activation, service identity mismatch was observed: login POST 503 and public root 503. That post-activation/recovery combination remains unresolved; it is not converted into acceptance. The later fresh fixed installation passes the baseline flow before reactivation. These are distinct scenarios.

## Remaining boundaries

Remote exact-head CI pending at preparation. Loopback HTTP is not TLS/production/browser/accessibility certification. Full installer UI interaction, all database families, real hosting, exposed-key invalidation and post-activation service convergence remain separate gates. Issue #74 stays open. Generated databases, environment files, passwords, cookies and activation tokens are excluded from this commit.

## Subsequent diagnosis and corrected-source verification

The prior service mismatch was traced to ephemeral workspace proxy-port drift, not source activation. Stable-environment re-execution found and fixed an independent shared-throttle authentication defect. Fresh activation/recovery/readiness and real login/admin now pass on the corrected candidate. See [stable-runtime-verification-2026-09-30.md](stable-runtime-verification-2026-09-30.md) for measured evidence and remaining scope. Earlier FAIL observations remain historical.
