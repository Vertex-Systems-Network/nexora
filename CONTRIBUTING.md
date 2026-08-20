# Contributing to Nexora

## Before coding

- Read `ARCHITECTURE.md`.
- Read `docs/database.md` before schema work.
- Read `docs/admin-ui.md` before admin UI work.
- Add/modify tests with every behavior change.

## Pull-request quality gate

A change is not ready to merge unless:

- PHP formatting/static checks pass;
- TypeScript typecheck/lint pass;
- unit + feature + architecture tests pass;
- `migrate:fresh` passes;
- `migrate:fresh --seed` passes;
- production frontend build passes;
- no debug/temporary files remain;
- docs are updated when public behavior/contracts change.

## N0.5 package-security contribution rule

Code that receives an extension/theme/app package must route it through Nexora Sentinel quarantine and scanning. Do not add `include`, `require`, Composer lifecycle execution, extraction into runtime directories, or activation shortcuts from upload handlers. New scanner rules require a stable `NEX-*` rule id, severity, category, tests, and a remediation-oriented message.

## N0.33 tenant-safe development

Feature code must not query a tenant-owned root model with `withoutGlobalScopes()` or manually substitute a tenant identifier unless the code is an audited enterprise/global maintenance path. New tenant-owned models should use `BelongsToTenant`, and queue jobs that load tenant-owned roots must restore `TenantContext` before resolving related tenant data. Enterprise organization roles may restrict platform permissions but must never create a parallel privilege-escalation path.

## N0.34 distributed-runtime development

New scheduled cluster work must be leader-gated unless every node must execute it (for example node heartbeats). Do not implement process-local mutexes for cross-node correctness; use `DistributedLockContract` or an audited lease. New storage-dependent features should consume Nexora/Laravel storage abstractions rather than hard-coding a local filesystem path. Readiness checks must stay cheap and secret-free, while destructive restore logic must remain an explicit offline operator action rather than a web or scheduler side effect.


## Release-candidate gates

Before proposing platform changes during N1.0, run `php scripts/certify-release.php --source-only`. On a fully provisioned Laragon/server development host run `scripts\quality-check.bat` (or the PowerShell/shell equivalent). Do not bypass the isolated certification database or hand-edit a certification report to make production packaging pass.

## RC9 build-budget gate

After changing frontend dependencies, shared UI or bundling behavior, run `npm run build` and `php scripts/performance-build-verify.php`. Do not raise `config/nexora-performance.php` ceilings merely to silence a regression; record why the increase is justified. Production package inclusion/exclusion policy belongs in `config/nexora-release.php` rather than duplicated path lists in packaging scripts.
