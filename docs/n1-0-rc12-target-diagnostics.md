# N1.0 RC12 — Target Diagnostics & Evidence Capture

RC12 is a stabilization aid for the final target-environment run. It does not introduce a new product feature and it does not mark N1.0 complete.

## Goal

Capture the first real Laragon/production-like integration failures without losing context after the first command exits. The diagnostics bundle records source contracts, toolchain versions, Composer/npm install output when requested, Laravel bootstrap commands when `vendor/` exists, frontend type/test/build output when `node_modules/` exists, optional full isolated certification, and the final closure ledger.

## Windows

```bat
scripts\target-diagnostics.bat --install-deps --full
```

For a non-destructive capture that does not run the isolated migration/test certification database:

```bat
scripts\target-diagnostics.bat --install-deps
```

## Output

Each run creates `storage/app/nexora/target-diagnostics/<run-id>/` containing `environment.json`, `summary.json`, `summary.md`, and per-step stdout/stderr logs. If PHP ext-zip is available, a `Nexora_Target_Diagnostics_*.zip` bundle is created beside the run directories.

The collector does not dump `.env` or ambient environment variables. Password/token/cookie/API-key-shaped values in captured command output are replaced with `[REDACTED]`.

## Safety

`--full` delegates to `scripts/certify-release.php --no-package --keep-going`, so the existing certification database naming and protected SQLite-path safeguards remain authoritative. Diagnostics do not make production packaging eligible and do not convert PENDING/SKIP closure evidence into PASS.
