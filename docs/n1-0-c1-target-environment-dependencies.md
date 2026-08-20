# N1.0-C1 — Target Environment + Dependencies

C1 is the first large N1.0 closure chunk. It consolidates target PHP/Composer prerequisites, reviewed dependency locks, locked installation, dependency integrity/audit, TypeScript, frontend tests, Vite production build and asset budgets.

`composer install` runs with `--no-scripts` in C1 so application/Laravel runtime boot belongs to C2 rather than contaminating the dependency boundary. C1 never refreshes or accepts lockfiles automatically.

Windows/Laragon entry point:

```bat
scripts\n1-c1-dependency-certify.bat --install-deps
```

If the prerequisite report says matching Laragon DLLs exist, remediation is an explicit separate action:

```bat
scripts\n1-c1-dependency-certify.bat --apply-extensions
```

After Laragon restarts, rerun `--install-deps`. C1 is PASS only after reviewed locks, locked installs, installed-graph verification, typecheck, Vitest, Vite build, provenance, dependency audit and asset budgets are all green.
