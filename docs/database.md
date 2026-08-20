# Database Conventions

## Primary development database

Nexora development is MySQL-first:

```text
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nexora
DB_USERNAME=root
DB_PASSWORD=root
```

These are local Laragon defaults for this project, not production credentials.

Quality/test automation uses a separate database:

```text
nexora_testing
```

The quality scripts create it automatically and destructive test migrations must never target `nexora`.

## Naming

Use stable domain names. Forbidden examples:

```text
phase_1_*
phase_n_*
milestone_*
temp_*
```

Core platform tables use the `nx_` prefix where collision avoidance is valuable, including:

```text
nx_settings
nx_modules
nx_module_versions
nx_module_dependencies
nx_module_capabilities
nx_capabilities
nx_audit_logs
```

## Migration rules

1. A migration that has shipped/shared is never edited.
2. Changes are introduced through a new migration.
3. `migrate:fresh` and `migrate:fresh --seed` must pass against MySQL in CI.
4. Foreign keys/indexes are reviewed explicitly.
5. Destructive migrations require an explicit upgrade/rollback strategy.
6. Extension tables must have clear ownership and may not silently alter unrelated core/module tables.
7. Test automation must use an isolated database name.

## Seeds

- `Core/` contains mandatory deterministic platform seed data.
- `Demo/` contains optional development/demo data.
- Demo seeders must never be required for production correctness.
- Core seeding synchronizes the deterministic Nexora runtime metadata.
