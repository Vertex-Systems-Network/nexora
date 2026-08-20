# N1.0 rc.75 — Frontend Build Closure & Exact C1 Diagnostics

## Historical target baseline

The authoritative Laragon build incident contained 76 TypeScript diagnostics across 11 Admin TS/TSX files. The source-side remediation ledger preserves the exact per-file distribution and known error-code families.

## Truth model

- **Source remediated** means known unsafe source patterns are absent.
- **Diagnostic clean** means a particular compiler/build output contains no TypeScript diagnostics.
- **C1 target verified** requires the canonical C1 runner to pass its dependency-backed `typecheck` and `vite-build` gates for the exact source and reviewed locks.

The build doctor never promotes C1.

## Target commands

```text
php scripts/n1-c1-frontend-build-doctor.php --run --write --json
php scripts/n1-c1-dependency-certify.php
```

When dependency installation is explicitly intended after reviewed-lock verification:

```text
php scripts/n1-c1-dependency-certify.php --install-deps
```

## Denominator

C1 remains 14 certification gates. N1.0 remains 105 granular target gates. Diagnostics and dependency setup actions are supporting evidence/actions, not new gates.
