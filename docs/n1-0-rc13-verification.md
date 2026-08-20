# N1.0 RC13 Verification

RC13 source verification requires the existing RC1–RC12 contract stack plus the existing-install upgrade safety gate. Dependency-backed command discovery, actual in-place migration execution and backup verification remain target-environment evidence.

## Executed source gates

- `php scripts/upgrade-contract-verify.php` — PASS: 4 upgrade commands, 4 compatibility domains, 2 backup modes, automatic DB rollback disabled.
- `php scripts/source-guard.php --source-only` — PASS.
- `php scripts/certification-preflight.php --source-only` — PASS for platform `1.0.0-rc.13`.
- `php scripts/certify-release.php --source-only --no-package` — SOURCE PASS for `1.0.0-rc.13`.
- PHP syntax lint — 734 PHP files, 0 syntax errors.
- TypeScript/local import static scan — 124 TS/TSX files, 55 relative imports checked, 0 missing.
- Admin raw feature controls — 0.
- Admin native date/time inputs — 0.
- Migration `->after()` — 0.
- `phase_*` / `milestone_*` table creation — 0.
- Historical architecture tests freezing a prior `1.0.0-rc.*` version — 0. Only the current RC13 architecture test owns the mutable platform identity.

## Dependency-backed status on this execution host

Composer is unavailable and `vendor/autoload.php` is absent, so Laravel command discovery, migrations and PHPUnit were not claimed PASS here. `node_modules` is absent. An actual `npm run build` was attempted and stopped at `TS2688: Cannot find type definition file for 'vite/client'`, which is the expected dependency-absence failure rather than a source-level RC13 TypeScript regression.

## Target installation gates

On a disposable clone of an existing installation:

1. `composer install`
2. `npm install`
3. `npm run build`
4. `php artisan nexora:upgrade:status`
5. `php artisan nexora:upgrade:preflight`
6. create and verify source-version backup evidence
7. `php artisan nexora:upgrade:plan --backup=<id>` or `--external-backup-evidence=<file>`
8. `php artisan nexora:upgrade:apply --yes`
9. `scripts\target-diagnostics.bat --full`
10. `scripts\final-target-run.bat --status-only`

A source-only PASS does not certify a real in-place upgrade or close N1.0.
