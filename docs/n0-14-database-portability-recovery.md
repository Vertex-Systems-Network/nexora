# Nexora N0.14 — Database Portability & Installer Recovery

N0.14 removes the MySQL-only installer assumption and adds a database-driver registry for Laravel's five first-party relational/database drivers: MySQL, MariaDB, PostgreSQL, SQLite, and SQL Server.

## Driver behavior

- Driver availability is detected from the running PHP/PDO environment.
- Network database drivers expose host, port, database, username, and password fields.
- SQLite uses a database-file path and hides network-only fields.
- Database creation is driver-aware.
- Runtime Laravel connection configuration and generated environment values follow the selected driver.
- Core migrations avoid MySQL-only `after()` column-position modifiers.

## Existing database protection

When existing objects are detected, the installer offers two explicit paths:

1. Recommended protected backup + download + reset authorization.
2. Continue without a Nexora backup after a destructive-data warning and exact database-name confirmation.

MySQL/MariaDB have a native PHP streaming SQL backup. SQLite uses an atomic snapshot. PostgreSQL and SQL Server installation remain supported even when a portable in-browser backup tool is unavailable; the wizard then clearly recommends an external backup and exposes the explicit no-backup consent path.

## Cancellation

The final Laravel installation stream now owns a unique run ID and file-backed control state. The browser can request cancellation while installation is at a safe checkpoint. Once schema-changing work starts, cancellation is disabled intentionally until the protected operation finishes.

## Rate limiting

The nested installer throttle was removed and database backup streaming now uses a practical per-endpoint allowance. This avoids false HTTP 429 failures during normal backup retries while retaining abuse protection.
