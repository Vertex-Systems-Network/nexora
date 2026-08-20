# N0.5 verification notes

## Verified in artifact build environment
- PHP source syntax lint across app/config/database/routes/scripts/tests
- TypeScript/TSX parser diagnostics: no syntax diagnostics
- local TypeScript alias import graph
- Laravel 13 `bootstrap_path()` source guard
- phase/milestone DB naming guard
- direct Untitled feature import guard
- native browser `confirm()` feature guard
- package JSON syntax
- Sentinel migration/route/npm/CSS/secret scanner unit coverage added
- ZIP artifact integrity

## Dependency-backed gates not claimed in artifact environment
The artifact environment did not have Composer dependencies, Node dependencies, PHP `ext-zip`, or MySQL PDO available. Therefore this artifact does not falsely claim that migrations, Laravel tests, Sentinel ZIP tests, strict TypeScript semantics or the production Vite build were executed here.

Run `scripts\setup-zero.bat` on a clean Laragon copy, or `scripts\quality-check.bat` on an existing copy. The first failing gate should be treated as the source of truth.
