# N1.0 RC17 Verification

## Source gates

RC17 must pass:

- `php scripts/transfer-contract-verify.php`
- `php scripts/filesystem-contract-verify.php`
- `php scripts/dependency-contract-verify.php`
- `php scripts/source-guard.php --source-only`
- `php scripts/certification-preflight.php --source-only`
- `php scripts/certify-release.php --source-only`
- complete PHP syntax lint
- internal TypeScript/import checks
- Admin UI raw-control/native-date guards
- migration portability guards
- strict zero-state verification
- final ZIP integrity verification

Expected source contract properties include zero unbounded archive extraction paths and zero whole-backup memory-load paths on the covered transfer surfaces.

## Dependency-backed target gates

After reviewed Composer/npm lockfiles exist and dependencies are installed from those lockfiles:

```bat
composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress
npm ci --no-audit --no-fund
php scripts\dependency-contract-verify.php --strict-locks
php scripts\transfer-contract-verify.php
php artisan nexora:filesystem:doctor
php artisan nexora:transfer:doctor
npm run build
scripts\target-diagnostics.bat --full
scripts\final-target-run.bat --status-only
```

This source package cannot claim dependency-backed PASS when reviewed lockfiles, Composer/vendor, node_modules, Laravel tests or the real Vite build are unavailable. Browser/accessibility/RTL, disposable restore and independent multi-node HA evidence remain separate fail-closed N1.0 closure domains.
