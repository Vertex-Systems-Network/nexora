# Nexora Testing Strategy

## Database

Backend tests are MySQL-first and use `nexora_testing`. Local quality runners prepare that database automatically with the configured local `root / root` credentials. Never point the test runner at `nexora` or a production database.

## Test classes

- `Unit/` — isolated domain/service behavior.
- `Feature/` — HTTP/auth/validation/authorization behavior.
- `Architecture/` — dependency and boundary enforcement.
- `Integration/` — module/service/database integration.
- `Security/` — capability bypass, authorization, dangerous-input behavior.
- `Compatibility/` — package/platform compatibility behavior.

## N0.4 runtime coverage

- dependency ordering
- missing dependency rejection
- circular dependency rejection
- version-constraint rejection
- runtime context restoration
- declared capability enforcement
- idempotent runtime-to-database synchronization
- permission-protected runtime admin routes
- audited runtime synchronization

## Mandatory CI checks

```text
MySQL test database preparation
PHP format/static checks
TypeScript typecheck
Unit tests
Feature tests
Architecture tests
Integration/security tests
migrate:fresh
migrate:fresh --seed
Nexora runtime sync/cache
Frontend component tests
Production build
```

Factories produce reusable test fixtures. Seeders are not a substitute for factories in isolated tests.


## N1.0 release certification

The cross-platform quality wrappers now delegate to `scripts/certify-release.php`. Full certification uses `nexora_certification` (or a `NEXORA_CERT_DB_*` override) and refuses unsafe destructive database names. Source-only certification is available with `php scripts/certify-release.php --source-only`. A strict relational engine matrix is available through `scripts/certify-database-matrix.php`; missing PDO drivers are not compatibility evidence.
