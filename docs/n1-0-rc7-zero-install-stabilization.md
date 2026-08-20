# N1.0 RC7 — Zero-Install, Deployment & Recovery Stabilization

RC7 turns Nexora's browser-first deployment/installer path into an explicit release-candidate contract rather than a collection of historical behaviors.

## True-zero state

`scripts/setup-zero.bat`, `.ps1` and `.sh` now remove root/protected environment state, installer/deployment locks and journals, private bootstrap tools, `vendor`, `node_modules`, `public/build`, runtime module cache and backup/release staging state before the browser is opened. `scripts/zero-state-verify.php --strict-source` proves that the test is not being pre-seeded by a previous installation.

The canonical `/` entry point still owns deployment preparation. When Nexora is not installed and either Composer dependencies or the production frontend manifest are absent, `public/index.php` renders the framework-independent deployment bootstrap at the normal site URL. A production release with `vendor` + `public/build` skips that preparation and enters the Laravel installer directly.

## Interrupted deployment recovery

The standalone deployment bootstrap persists a run ID, heartbeat, step and process information while holding an OS file lock. RC7 adds lock-aware interrupted-state normalization: if state claims a task is active but the OS deployment lock is available, Nexora marks the stale run `interrupted`, archives it as `deployment-last-interrupted.json`, and allows a new deployment task. Completion state is persisted before the OS lock is released to close the old race window.

## Interrupted main-installer recovery

`InstallationRunControl` journals the target database using a one-way fingerprint; credentials/passwords are not written to the run journal. Once a run enters a protected schema-changing stage it is marked `protected_started`. A failed or genuinely stale run can be recovered only for the same database target and only within the configured recovery window.

If the same database contains a Nexora-owned partial installation, the installer preserves that partial schema and continues `migrate` + idempotent seeding instead of demanding a second destructive reset/backup cycle. A different database target never inherits that recovery state.

Active journals are considered interrupted only when their heartbeat is stale **and** the real installer mutex is free. This avoids marking a long-running protected operation interrupted while its worker still holds the lock.

## Completion lockout

`installed.lock` remains an atomic final mutation after migrations, seed, Super Admin creation, runtime synchronization and final cache cleanup. Once it exists, installer index/database/stream/store/cancel/status controls refuse further provisioning and hand the browser to login.

## Production package hygiene

Certified production package generation excludes installation/deployment locks, access keys, run journals, protected environment files, database backups, deployment/installation control directories, private toolchains and certification evidence. Customer releases therefore cannot inherit the build machine's installation state.
