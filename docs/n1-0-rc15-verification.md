# N1.0 RC15 Verification Results

Platform: `1.0.0-rc.15`

## Source gates executed

- Unified `php scripts/certify-release.php --source-only`: **PASS**.
- RC preflight source mode: **PASS**.
- Module graph: **PASS — 24 modules**.
- Laravel runtime contracts: **PASS — middleware 10/11, aliases 2, scheduled commands 11, callbacks 2, queue jobs 4, providers 2**.
- Database contracts: **PASS — 24 migrations, 135 tables, 75 foreign targets, 51/51 tenant table/model roots**.
- Zero-install, browser/UX/RTL, performance/packaging, HA/final evidence, final closure, target diagnostics, upgrade safety, environment, security and frontend contracts: **PASS**.
- Dependency reproducibility source contract: **PASS with explicit pending lock evidence**. `composer.lock` and `package-lock.json` are absent in this source checkout, so strict/full dependency certification is blocked.
- Source Guard: **PASS**.
- PHP syntax: **748 PHP files, 0 syntax errors**.
- TypeScript/TSX/config parse check using available TypeScript parser 5.8.3: **124 files, 0 parse diagnostics**.
- Local/alias TypeScript imports: **442 checked, 0 missing**.
- Admin raw feature controls outside shared UI: **0**.
- Admin native date/time inputs outside shared UI: **0**.
- Migration `->after()`: **0**.
- `phase_*` / `milestone_*` migration tables: **0**.
- Historical Architecture tests freezing mutable prior RC version/status: **0**.

## RC15 dependency state

RC15 deliberately does not invent lockfiles. The RC14 source input did not contain `composer.lock` or `package-lock.json`, and this execution host could not complete external dependency resolution. The new strict gate therefore correctly fails:

- `php scripts/dependency-contract-verify.php --strict-locks`: **FAIL as designed — both lockfiles missing**.
- full `certification-preflight.php`: **FAIL as designed — both lockfiles missing; PHP mbstring and zip are also unavailable on this host**.
- `dependency-runtime-verify.php`: **FAIL as designed — both lockfiles missing and Composer executable unavailable**.

Observed toolchain on this host: PHP `8.4.23`, Node `v22.16.0`, npm `10.9.2`. Composer is unavailable. PHP `mbstring` and `zip` are unavailable. Therefore Composer install, Laravel dependency-backed tests, npm `ci`, Vite production build, Composer/npm vulnerability audit and production package generation are **not claimed PASS**.

## Release-integrity changes verified

- final target certification has no unlocked `npm install` fallback; it uses `npm ci` only.
- browser/source bootstraps require both reviewed lockfiles before dependency installation.
- direct dependency manifests reject wildcard/latest/dev/git/URL-style unconstrained package inputs (extension platform requirements remain allowed as `ext-* = *`).
- `package.json` declares Node/npm engines and pins `packageManager` to `npm@10.9.2`.
- full certification requires lockfile integrity, target toolchain compatibility, locked dependency provenance, Composer/npm vulnerability audit, and then the existing Laravel/frontend gates.
- production packaging requires dependency audit and provenance reports whose lock SHA-256 values match the exact `composer.lock` and `package-lock.json` being shipped.
- the production release manifest seals dependency policy, audit/provenance evidence and both lockfile hashes.

## Required target action

On a trusted Laragon/maintainer checkout, intentionally generate/review the initial lockfiles with `scripts\\refresh-dependency-locks.bat`, then commit/preserve both lockfiles. After that, use locked installs (`composer install ...` and `npm ci`) and rerun the final target certification. N1.0 remains CERTIFYING until those dependency-backed and operator evidence gates are actually green.
