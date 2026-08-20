# N0.4 Verification

## Source gates required

- PHP syntax clean.
- TypeScript/TSX syntax clean.
- JSON manifests valid.
- No `phase_*` or `milestone_*` database tables.
- Core module classes do not import Eloquent models or DB/Schema facades.
- Feature pages import UI primitives only through `@nexora/admin-ui`.
- No `.env`, credentials dump, vendor directory, node_modules or build output is shipped.

## Dependency-backed gates

On the developer/CI machine:

### Windows CMD / Laragon

```bat
scripts\quality-check.bat
```

### PowerShell

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\quality-check.ps1
```

### Git Bash / Linux / macOS

```bash
bash ./scripts/quality-check.sh
```

All runners use the isolated MySQL database `nexora_testing` with local credentials `root / root` unless you intentionally change the scripts/configuration. They must never run `migrate:fresh` against the development database `nexora`.

The suite covers MySQL database creation, fresh migration, fresh migration + seed, runtime sync/cache, backend tests, Pint, strict TypeScript, frontend tests and production build.
