# DEV-5 Real Database Target Matrix

This document is the operator runbook for turning Nexora primary SQL portability from **SOURCE DONE** into **TARGET VERIFIED** evidence.

The matrix runner is intentionally destructive only inside disposable databases/files whose names begin with `nexora_matrix_`. It refuses non-empty databases, never drops database containers, never rewrites `.env`, and removes only the objects it created during the compatibility test.

## 1. Discover available engines

From the development checkout with Composer dependencies installed:

```bat
php scripts\database-target-matrix.php --list
```

The command reports every Nexora primary/managed SQL driver, its logical Laravel driver, PDO availability and the environment-variable prefix used by the matrix.

## 2. SQLite baseline

SQLite needs no network service:

```bat
php scripts\database-target-matrix.php --drivers=sqlite --evidence
```

Expected result: `PASSED`, test exit code `0`, and cleanup `SQLite matrix file removed`.

## 3. MySQL / MariaDB

Use a dedicated disposable database. The name must begin with `nexora_matrix_`.

MySQL example:

```bat
set NEXORA_MATRIX_MYSQL_HOST=127.0.0.1
set NEXORA_MATRIX_MYSQL_PORT=3306
set NEXORA_MATRIX_MYSQL_DATABASE=nexora_matrix_mysql
set NEXORA_MATRIX_MYSQL_USERNAME=root
set NEXORA_MATRIX_MYSQL_PASSWORD=
php scripts\database-target-matrix.php --drivers=mysql --evidence
```

MariaDB example:

```bat
set NEXORA_MATRIX_MARIADB_HOST=127.0.0.1
set NEXORA_MATRIX_MARIADB_PORT=3306
set NEXORA_MATRIX_MARIADB_DATABASE=nexora_matrix_mariadb
set NEXORA_MATRIX_MARIADB_USERNAME=root
set NEXORA_MATRIX_MARIADB_PASSWORD=
php scripts\database-target-matrix.php --drivers=mariadb --evidence
```

## 4. PostgreSQL

```bat
set NEXORA_MATRIX_PGSQL_HOST=127.0.0.1
set NEXORA_MATRIX_PGSQL_PORT=5432
set NEXORA_MATRIX_PGSQL_DATABASE=nexora_matrix_pgsql
set NEXORA_MATRIX_PGSQL_USERNAME=postgres
set NEXORA_MATRIX_PGSQL_PASSWORD=YOUR_TEST_PASSWORD
php scripts\database-target-matrix.php --drivers=pgsql --evidence
```

## 5. Microsoft SQL Server

The PHP runtime must have the SQL Server PDO driver available.

```bat
set NEXORA_MATRIX_SQLSRV_HOST=127.0.0.1
set NEXORA_MATRIX_SQLSRV_PORT=1433
set NEXORA_MATRIX_SQLSRV_DATABASE=nexora_matrix_sqlsrv
set NEXORA_MATRIX_SQLSRV_USERNAME=sa
set NEXORA_MATRIX_SQLSRV_PASSWORD=YOUR_TEST_PASSWORD
php scripts\database-target-matrix.php --drivers=sqlsrv --evidence
```

## 6. Multiple engines in one run

When all required disposable services are configured:

```bat
php scripts\database-target-matrix.php --drivers=sqlite,mysql,mariadb,pgsql,sqlsrv --evidence
```

The overall result is PASS only when every selected engine passes its connection/version check, `DatabaseRoundTripCompatibilityTest`, and cleanup.

## 7. Managed SQL variants

Managed aliases reuse the compatible Laravel/PDO driver but keep provider policy separate. Database creation is disabled for managed services, so create the empty `nexora_matrix_*` database beforehand.

Supported managed keys:

```text
aws_rds_mysql
aws_rds_mariadb
aws_rds_pgsql
aws_rds_sqlsrv
aws_aurora_mysql
aws_aurora_pgsql
```

Example for RDS PostgreSQL:

```bat
set NEXORA_MATRIX_AWS_RDS_PGSQL_HOST=YOUR_RDS_ENDPOINT
set NEXORA_MATRIX_AWS_RDS_PGSQL_PORT=5432
set NEXORA_MATRIX_AWS_RDS_PGSQL_DATABASE=nexora_matrix_rds_pgsql
set NEXORA_MATRIX_AWS_RDS_PGSQL_USERNAME=YOUR_TEST_USER
set NEXORA_MATRIX_AWS_RDS_PGSQL_PASSWORD=YOUR_TEST_PASSWORD
php scripts\database-target-matrix.php --drivers=aws_rds_pgsql --evidence
```

Use only a disposable test database. Do not point the matrix at an application, staging, shared, customer or production database.

## 8. Evidence artifact

`--evidence` writes:

```text
storage/app/nexora/qa/database-target-matrix.json
```

The durable evidence file contains only:

- evidence schema/status/scope/timestamp
- Nexora platform version and source generation
- PHP runtime version
- selected driver keys
- per-driver logical driver/status
- database server version
- object count before the test
- PHPUnit exit code
- cleanup result

It intentionally excludes hostnames, usernames, passwords, connection profiles and verbose diagnostic output.

The regular console/`--json` output remains the place for transient troubleshooting detail.

## 9. TARGET VERIFIED rule

Do not mark an engine TARGET VERIFIED merely because source contracts or configuration tests pass. TARGET VERIFIED requires a real matrix run on that engine with:

```text
status: passed
test_exit_code: 0
cleanup: successful
```

and the evidence must identify the same Nexora source generation being evaluated.

Final reviewed dependency-lock and C1-C6 release certification remain separate DEV-6 work.