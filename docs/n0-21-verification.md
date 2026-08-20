# N0.21 Verification

Required source gates:

- PHP syntax across `app`, `config`, `database`, `routes`, `tests`.
- TypeScript parser/import graph.
- `scripts/source-guard.php --source-only`.
- No raw Admin interactive controls outside `@nexora/admin-ui`.
- Studio module/capabilities registered in `config/nexora.php`.
- Portable forward-only Studio migration.
- Studio tree validator rejects unknown element types and undeclared bindings.
- Studio revisions are immutable.
- Public document renderer falls back to Document Engine if no published Studio canvas exists.

Dependency-backed gates remain mandatory on the target development/CI machine:

```bash
composer install
npm install
scripts/quality-check.bat
```

The quality runner remains the source of truth for Laravel migrations, tests, TypeScript, Vitest and production Vite compilation.
