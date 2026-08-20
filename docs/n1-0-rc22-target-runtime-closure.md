# N1.0 RC22 — Target Runtime Closure Runner

RC22 does not add a product domain. It adds the fail-fast target-runtime gate needed after RC21 converted the real Laragon TypeScript failures into source contracts.

`target-diagnostics.php` remains a keep-going troubleshooting collector. `target-runtime-run.php` is intentionally different: it stops at the first required release blocker unless `--keep-going` is explicitly requested, preserves redacted per-step logs, and never treats source-only checks as dependency-backed evidence.

## Commands

Readiness against dependencies already installed from reviewed locks:

```bat
scripts\target-runtime-run.bat
```

Install exact reviewed locks first, then run readiness:

```bat
scripts\target-runtime-run.bat --install-deps
```

After readiness is green, run isolated destructive certification:

```bat
scripts\target-runtime-run.bat --full
```

Or install reviewed dependencies and run the isolated certification in one invocation:

```bat
scripts\target-runtime-run.bat --install-deps --full
```

`--full` delegates migrations/seeding/tests to `scripts/certify-release.php --no-package`. The target runtime runner itself contains no `migrate:fresh`/`migrate:reset` commands and therefore does not perform destructive migration work against the ambient project database.

## Ordered target gate

1. dependency-free source preflight;
2. RC21 Inertia frontend source contract;
3. Composer/Node/npm availability;
4. reviewed `composer.lock` + `package-lock.json` presence and strict dependency policy;
5. optional `composer install` + `npm ci` from those exact locks;
6. real TypeScript typecheck, Vitest, Vite production build and RC9 build budgets;
7. Laravel package discovery, cache clear, app boot, route registry and scheduler registry;
8. database/environment/filesystem/transfer/runtime/concurrency doctors;
9. optional full isolated certification database migrations/seeding/PHPUnit/frontend run;
10. current N1.0 closure ledger.

## Evidence

Runs are stored under `storage/app/nexora/target-runtime/<run-id>/` with `environment.json`, `summary.json`, `summary.md`, and redacted step stdout/stderr logs. When PHP `ext-zip` is available a single diagnostic ZIP is created under `storage/app/nexora/target-runtime/`.

The collector does not dump `.env` or ambient environment variables. Token/password/cookie/API-key-shaped command output is redacted.

N1.0 remains open after a target-readiness PASS. Full automated certification plus the strict five-database matrix, zero-install/recovery, existing-install upgrade, browser/A11y/RTL, target HTTP/performance, backup/restore, real multi-node HA, final evidence and independently reverified production package are still required.
