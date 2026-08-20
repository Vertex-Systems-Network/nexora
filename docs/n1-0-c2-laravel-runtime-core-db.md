# N1.0-C2 — Laravel Runtime + Core Database Certification

C2 owns the dependency-backed Laravel/application runtime and the primary isolated certification database. It deliberately does **not** install dependencies, refresh/accept lockfiles, execute the five-database matrix, or collect browser/HA/backup operator evidence.

C2 requires a PASS from N1.0-C1 on the **same platform version, exact source-tree SHA-256, reviewed lockfiles, installed dependency graph, frontend build, dependency audit/provenance and asset-budget evidence**.

Primary Windows/Laragon command:

```bat
scripts\n1-c2-laravel-runtime-certify.bat
```

Optional isolated database selection uses non-secret command arguments plus environment credentials:

```bat
set NEXORA_CERT_DB_HOST=127.0.0.1
set NEXORA_CERT_DB_USERNAME=root
set NEXORA_CERT_DB_PASSWORD=root
scripts\n1-c2-laravel-runtime-certify.bat --db-connection=mysql --db-database=nexora_certification
```

The runner refuses destructive work against database names outside the dedicated `nexora_test*` / `nexora_certification*` namespace. SQLite must live under `storage/app/nexora/certification/`.

C2 certifies package discovery, application/routes/scheduler boot, isolated migrations + repeated seed + reset/rebuild, runtime synchronization/cache, exact PHPUnit DB binding, all Laravel/PHPUnit suites, Pint, runtime doctors and optimized-cache boot. C3 owns the strict MySQL/MariaDB/PostgreSQL/SQLite/SQL Server matrix.
