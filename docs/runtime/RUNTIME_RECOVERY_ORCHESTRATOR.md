# Nexora Runtime Recovery / Closure Orchestrator

## Purpose

`runtime-recovery-orchestrator.php` converts the existing manual runtime-repair sequence into one fail-closed operator workflow while preserving the required human approval before mutation.

It is an operator/control tool inside `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY`. It is not a general upgrade engine and does not authorize future roadmap work.

## Safety model

Dry-run is the default. Apply mode requires:

```text
--apply --confirm=RECOVER-RUNTIME
```

The orchestrator:

1. inspects the explicit target with `nexora:runtime:compatibility-status --deep`;
2. if compatibility already passes, it does not run an identity repair;
3. if the target is exactly `1.0.0-rc.93` and the only mismatches are `environment`, `activation`, `service`, `process`, it delegates to the version-pinned rc.93 repair adapter;
4. independently re-runs deep compatibility after any repair;
5. runs `nexora:runtime:post-install-status --assert-ready`;
6. automatically runs `nexora:runtime:post-install-reconcile --confirm=RECONCILE` only when the target reports `receipt-refresh-required`, `runtime_ready=true`, and `receipt_current=false`;
7. re-runs the readiness assertion;
8. resolves `app.url` from the target (or accepts `--base-url`) and performs a verified HTTP(S) `GET /login` without disabling TLS verification or following redirects;
9. writes a machine-readable apply-mode receipt under protected target runtime storage.

## Forbidden behavior

The orchestrator does not:

- copy current source into the target;
- run `git pull` or `git checkout`;
- install or update Composer/npm dependencies;
- run migrations or seeders;
- change the target version;
- ignore unrelated compatibility mismatches;
- disable TLS verification;
- treat its own receipt as canonical project-state promotion.

## Commands

Dry-run:

```bat
npm run runtime:recover -- --target="D:\laragon\www\nexora"
```

Authorized closure attempt:

```bat
npm run runtime:recover -- --target="D:\laragon\www\nexora" --apply --confirm=RECOVER-RUNTIME
```

If target `app.url` cannot be resolved automatically:

```bat
npm run runtime:recover -- --target="D:\laragon\www\nexora" --apply --confirm=RECOVER-RUNTIME --base-url=https://nexora
```

## Result semantics

- `status=pass`: deep compatibility, final readiness/current receipt, and `/login` HTTP 200 all passed.
- `status=blocked`: runtime compatibility/readiness passed but the final HTTP smoke could not be certified automatically (for example local TLS trust or web-server reachability).
- `status=fail`: a recovery/compatibility/readiness invariant failed. Do not bypass the failing control.

Project state is advanced only after the real target evidence is reviewed and canonical `.ai` state is updated.
