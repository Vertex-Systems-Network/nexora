# N1.0 RC14 Verification

## Source certification

- Platform: `1.0.0-rc.14`
- `php scripts/certification-preflight.php --source-only` — PASS.
- `php scripts/environment-contract-verify.php` — PASS: 0 runtime `env()` calls outside config, 47 production-template keys, two authoritative environment locations.
- `php scripts/source-guard.php --source-only` — PASS.
- `php scripts/certify-release.php --source-only --no-package` — SOURCE PASS across RC1–RC14 source gates.
- PHP syntax — 741 PHP files checked, 0 syntax errors.
- Local TypeScript imports — 55 checked, 0 missing.
- Admin raw feature controls — 0 files.
- Admin native date/time inputs — 0 files.
- Migrations — 24; `->after()` 0; `phase_*` / `milestone_*` table creation 0.

## Environment/config hardening verified in source

- installed active-marker source fails closed when explicitly selected environment file is unreadable;
- CLI `artisan` and browser entrypoint both stop on `NEXORA_INSTALL_BOOTSTRAP_ERROR`;
- installer environment writes invalidate stale `bootstrap/cache/config.php`;
- `nexora:environment:doctor` is registered by source and included in cached-boot/full target certification;
- RC13 upgrade compatibility now includes environment/config drift assessment;
- production release manifest seals the environment policy hash and states that real environment files are never packaged;
- `.env.production.example` is secret-free and HTTPS/session-safe by default.

## Dependency-backed status

Composer and `vendor/` are unavailable on this execution host. Node 22.16.0 and npm 10.9.2 are available, but `node_modules/` is absent. A real `npm run build` was attempted and stopped at TypeScript error TS2688 because `vite/client` is unavailable without installed npm dependencies. Therefore Laravel package discovery, migrations, PHP tests, real Vite build, cached `nexora:environment:doctor`, browser evidence, restore evidence and HA evidence are **not claimed PASS** here.

Run on Laragon:

```bat
composer install
npm install
php artisan optimize:clear
php artisan nexora:environment:doctor --production
npm run build
scripts\target-diagnostics.bat --full
scripts\final-target-run.bat --status-only
```
