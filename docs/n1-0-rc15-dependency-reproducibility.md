# N1.0 RC15 — Dependency Reproducibility & Supply-Chain Certification

RC15 closes mutable dependency-resolution gaps before N1.0 can ship.

## Contracts

- `config/nexora-dependencies.php` defines certified PHP/Composer/Node/npm ranges and deterministic install/audit commands.
- `scripts/dependency-contract-verify.php` validates manifests, package-manager metadata, release policy and lockfile shape. Source mode reports absent lockfiles as pending; `--strict-locks` makes them mandatory.
- `scripts/dependency-runtime-verify.php` checks the actual target toolchain and requires both lockfiles.
- `scripts/dependency-provenance.php` parses both lockfiles into an exact-version provenance record and requires npm integrity metadata for resolved registry packages.
- `scripts/dependency-audit.php` runs Composer and npm vulnerability audits and writes exact-version, exact-lock-hash evidence.
- final target dependency installation uses `composer install` and `npm ci` only. It never falls back to resolving an unlocked npm graph.
- `scripts/refresh-dependency-locks.bat` / `.ps1` / `.sh` are explicit maintainer-only workflows for intentional lock refreshes and is not called by certification.
- production packaging requires the audit evidence to match both current lockfile hashes and seals those hashes plus the dependency policy into the release manifest.

## Current limitation

The RC15 source artifact does not fabricate lockfiles. The current execution host cannot resolve Composer/npm dependencies, so lockfiles remain pending until generated/reviewed on the trusted Laragon/maintainer environment. Therefore dependency-backed/full certification remains BLOCKED here by design.
