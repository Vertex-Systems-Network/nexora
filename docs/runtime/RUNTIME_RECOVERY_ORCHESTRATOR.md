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
4. independently re-runs deep compatibility after any repair and requires both exit code `0` and the expected PASS payload;
5. runs `nexora:runtime:post-install-status --assert-ready` and binds readiness PASS to exit code `0`;
6. automatically runs `nexora:runtime:post-install-reconcile --confirm=RECONCILE` only when the target reports the exact stale-receipt state: `receipt-refresh-required`, `runtime_ready=true`, and `receipt_current=false`;
7. re-runs the readiness assertion after reconciliation;
8. resolves the target application's own bootstrapped `config('app.url')` and performs a verified HTTP(S) `GET /login` without disabling TLS verification or following redirects;
9. writes a machine-readable apply-mode outcome receipt under protected target runtime storage.

Arbitrary HTTP target overrides are intentionally unsupported. The orchestrator cannot certify a different host merely because that host returns `/login` HTTP 200.

## Forbidden behavior

The orchestrator does not:

- copy current source into the target;
- run `git pull` or `git checkout`;
- install or update Composer/npm dependencies;
- run migrations or seeders;
- change the target version;
- ignore unrelated compatibility mismatches;
- accept JSON PASS from a child command that exited non-zero;
- downgrade an explicit non-200 `/login` result to `BLOCKED`;
- accept an arbitrary alternate HTTP host for target certification;
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

If target-owned `config('app.url')` is missing/invalid or its HTTPS connection cannot be verified by the PHP runtime, automated HTTP certification remains `BLOCKED`/`FAIL` as appropriate. Do not supply an alternate host or disable TLS merely to obtain green status; repair the target URL/trust configuration or provide separate authorized browser evidence.

## Result semantics

- `status=pass` / exit `0`: deep compatibility, final readiness/current receipt, and target-owned `/login` HTTP 200 all passed.
- `status=blocked` / exit `2`: runtime compatibility/readiness passed but the target-owned HTTP smoke could not be certified automatically, for example because of local TLS trust or web-server reachability.
- `status=fail` / exit `1`: a recovery/compatibility/readiness invariant failed, an explicit HTTP/configuration failure occurred, or required evidence could not be safely produced. Do not bypass the failing control.

Project state is advanced only after the real target evidence is reviewed and canonical `.ai` state is updated.
