# N1.0-C3 — Strict Five-Database Portability Matrix

C3 certifies Nexora against **MySQL, MariaDB, PostgreSQL, SQLite and SQL Server** on the exact dependency-backed source that already passed C2. It is fail-closed behind C2 evidence and does not install dependencies or collect C4-C6 operator evidence.

## Run

```bat
scripts\n1-c3-database-matrix-certify.bat
```

Required PHP PDO extensions are `pdo_mysql` (MySQL/MariaDB), `pdo_pgsql`, `pdo_sqlite`, and `pdo_sqlsrv`. Configure each server with `NEXORA_CERT_<DRIVER>_HOST`, `_PORT`, `_USERNAME`, and `_PASSWORD` as needed. Certification databases use dedicated `nexora_certification_*` names.

For every driver C3 runs database preparation/version checks, fresh migration + seed, repeated seeding, the Compatibility suite, Commerce/CRM/Automation/Enterprise/Studio high-risk flows, concurrency certification, full migration reset/rebuild and Compatibility again. Any one failed or unavailable driver blocks C3.

The canonical `storage/app/nexora/certification/database-matrix.json` remains schema 2 for final-closure compatibility, but C3 adds exact C2/reviewed-lock artifact hashes so old matrix evidence cannot be reused after source or dependency drift.
