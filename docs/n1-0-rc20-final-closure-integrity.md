# N1.0 RC20 — Final Closure Integrity

RC20 is a release-integrity hardening pass discovered by the total N1.0 audit. It does not add a product domain.

## Closed audit findings

1. **PHPUnit certification DB isolation** — `phpunit.xml` no longer force-overrides the DB selected by `scripts/certify-release.php`; target and DB-matrix tests assert the exact selected connection/database.
2. **Dependency policy alignment** — Composer PHP and npm engine constraints match `config/nexora-dependencies.php` before lockfiles are generated.
3. **Exact-source certification** — a deterministic source-tree SHA-256 is captured and rechecked before PASS, final evidence and production packaging.
4. **Closure ledger completeness** — observed zero-install/recovery and existing-install upgrade rehearsals are mandatory final domains.
5. **Five-family DB final matrix** — final certification requires MySQL, MariaDB, PostgreSQL, SQLite and SQL Server, plus selected high-risk feature flows on every driver.
6. **Database minimum versions** — installer/runtime checks reject database servers below the certified minimum and expose `nexora:database:doctor`.
7. **Independent production artifact verification** — the sealed ZIP is reopened and checked for required/forbidden entries, release-manifest hashes, source digest and SHA-256 sidecar.
8. **CI baseline** — source contracts run on Windows and Linux; a locked SQLite dependency-backed job activates when reviewed lockfiles exist.

## Final closure domains

RC20 closure status has 11 domains: automated certification, build assets, target HTTP, strict DB matrix, zero-install, upgrade rehearsal, browser/a11y/RTL, backup/restore, multi-node HA, final evidence aggregation and the independently validated production package.

N1.0 remains CERTIFYING until all 11 domains are green on the exact source tree.
