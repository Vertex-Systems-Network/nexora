# N1.0 RC24 — Target Prerequisite / Lockfile Intake Certification

RC24 remains an N1.0 operational closure pass. It does not start N1.1 or introduce a new product domain.

## Purpose

RC23 can diagnose missing target prerequisites and resume exact-fingerprint runs. RC24 closes the remaining operator gap between “a lockfile exists” and “this exact dependency graph was deliberately reviewed on a trusted maintainer machine.”

- `scripts/target-prerequisite-intake.*` reports the active PHP binary, loaded/scanned `php.ini`, extension directory, Laragon detection, missing PHP extensions, Composer/Node/npm readiness and lock-review next actions.
- `scripts/dependency-lock-review.*` validates Composer/npm lock structure, npm root manifest parity, npm integrity metadata and `composer validate --strict --check-lock` when Composer is available.
- Lock acceptance is explicit: `--accept --reviewer=<name> --confirm=REVIEWED` writes a protected attestation bound to four SHA-256 hashes: `composer.json`, `package.json`, `composer.lock`, and `package-lock.json`.
- Target runtime and full certification verify that attestation before deterministic dependency installation/certification. If either manifest or lock changes, the attestation is invalid and must be reviewed again.
- Intake/review evidence is runtime-local, excluded from customer release ZIPs, and removed by strict zero-state preparation.

RC24 does not auto-edit `php.ini`, auto-download Composer/PHP/Node, fabricate lockfiles, or silently accept dependency updates.
