# Nexora Runtime Recovery / Closure Orchestrator

## Purpose

`runtime-recovery-orchestrator.php` converts the existing manual runtime-repair sequence into one fail-closed operator workflow while preserving the required human approval before mutation.

It is an operator/control tool inside `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY`. It is not a general upgrade engine and does not authorize future roadmap work.

## Safety model

Dry-run is the default. Apply mode requires:

```text
--apply --confirm=RECOVER-RUNTIME
```

Apply mode is **single-writer per target**. Before creating or opening the apply lock or any recovery receipt, every `storage/app/nexora/runtime/recovery-orchestrator` path component is resolved independently and must remain the exact target-owned directory path. Symlink/junction or other filesystem redirection is rejected fail-closed, including redirection that would otherwise escape the explicit target. The orchestrator then acquires a non-blocking exclusive target lock before any apply-mode compatibility/recovery/reconcile work. A second concurrent apply attempt fails closed instead of racing sealed runtime identity, handoff receipts, source/web acknowledgement evidence, or recovery evidence generation.

After target validation and successful apply-lock acquisition, all **post-lock apply failures** also write a unique protected machine-readable failure outcome receipt with the accumulated steps, mutation state, and non-secret failure context. Argument, target-validation, confirmation, recovery-storage containment, and lock-acquisition failures remain receipt-free because apply ownership has not been established. If the protected receipt itself cannot be written or its directory no longer resolves safely inside the target, the result remains FAIL and exposes `evidence_write_status=fail` without masking the original failure.

**Windows-safe child process capture** keeps only child stdout on an anonymous pipe. Child stderr is captured in a unique transient regular file and read after the child closes, so a verbose failing command cannot fill an unread stderr pipe while the parent is waiting for stdout EOF. The transient stderr file is closed after capture and is not a target/runtime evidence artifact.

The orchestrator:

1. establishes an exact target-owned, non-redirected recovery-storage path and acquires the apply lock when mutation is authorized;
2. inspects the explicit target with `nexora:runtime:compatibility-status --deep`;
3. if compatibility already passes, it does not run an identity repair;
4. if the target is exactly `1.0.0-rc.93` and the only mismatches are `environment`, `activation`, `service`, `process`, it delegates to the version-pinned rc.93 repair adapter;
5. independently re-runs deep compatibility after any repair and requires both exit code `0` and the expected PASS payload;
6. runs `nexora:runtime:post-install-status --assert-ready` and binds readiness PASS to exit code `0`; the handoff implementation itself re-verifies source, deep deployment identity, runtime compatibility, activation identity and the current sealed handoff receipt;
7. automatically runs `nexora:runtime:post-install-reconcile --confirm=RECONCILE` only when the target reports the exact stale-receipt state: `receipt-refresh-required`, `runtime_ready=true`, and `receipt_current=false`;
8. re-runs the readiness assertion after reconciliation;
9. resolves the target application's own bootstrapped `config('app.url')`;
10. preflights that origin's existing `/install/source-status` contract with TLS verification enabled and redirects disabled;
11. issues a fresh target-local **one-time** `SourceActivationHandshake` challenge only after the preflight is reachable, sends the secret only in the in-process `X-Nexora-Activation-Token` request header, requires the web process to acknowledge the exact nonce, then independently runs local `nexora:source:status --require-web-ack` to prove that the acknowledgement belongs to the exact target source/runtime generation;
12. only after that exact target-to-web identity proof passes, performs verified HTTP(S) `GET /login` on the same target-owned origin;
13. writes a machine-readable apply-mode outcome receipt under the revalidated protected target runtime storage path using a unique per-run identifier so rapid sequential runs cannot overwrite previous evidence.

Arbitrary HTTP target overrides are intentionally unsupported. `config('app.url')` ownership alone is **not** treated as proof that the HTTP server belongs to the target directory: a different Nexora deployment could otherwise return `/login` HTTP 200. The fresh one-time CLI→web acknowledgement closes that false-PASS path.

The challenge token is secret, single-use, and **never written into the recovery receipt, logs, or public result payload**. The receipt may contain the non-secret challenge nonce and source/runtime fingerprints as provenance.

No new public endpoint or trust boundary is introduced by this control: it reuses the existing `SourceActivationHandshake`, `/install/source-status`, and `nexora:source:status --require-web-ack` mechanisms already used to prove CLI↔web source convergence.

## Forbidden behavior

The orchestrator does not:

- copy current source into the target;
- run `git pull` or `git checkout`;
- install or update Composer/npm dependencies;
- run migrations or seeders;
- change the target version;
- ignore unrelated compatibility mismatches;
- accept JSON PASS from a child command that exited non-zero;
- use a second anonymous child pipe for stderr that can deadlock under back-pressure;
- allow concurrent apply-mode writers for the same target;
- follow symlink/junction/filesystem redirection for recovery lock or receipt storage;
- reuse a timestamp-only receipt filename that can silently replace previous evidence;
- allow a post-lock apply failure to disappear without a protected outcome receipt when evidence storage is writable;
- treat `app.url` plus an arbitrary `/login` 200 as exact-target proof;
- run `/login` as authoritative evidence before the fresh target-to-web challenge is acknowledged and verified locally;
- persist the one-time acknowledgement token in recovery evidence;
- downgrade an explicit non-200 `/login` or source-status response to `BLOCKED`;
- accept an arbitrary alternate HTTP host for target certification;
- disable TLS verification or follow redirects merely to obtain PASS;
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

If the protected recovery-storage path resolves through a symlink/junction or outside the exact target, apply fails before lock ownership and writes nothing through the redirected path. If another apply-mode recovery already owns the valid target lock, the second run also fails closed. Do not delete, redirect, or bypass the lock to force progress; first establish the target filesystem state and whether another operator/process is still active.

The exact target-to-web challenge is issued only in apply mode, after final CLI readiness and a reachable source-status preflight. It updates the existing protected source-activation handshake evidence and consumes its one-time token through the web process. This is counted as target evidence mutation in the final recovery receipt.

If target-owned `config('app.url')` is missing/invalid, source-status cannot be reached with verified transport, the exact target web process rejects the one-time challenge, or its HTTPS connection cannot be verified by the PHP runtime, automated HTTP certification remains `BLOCKED`/`FAIL` as appropriate. Do not supply an alternate host or disable TLS merely to obtain green status; repair the target URL/web mapping/trust configuration or provide separate authorized evidence.

## Result semantics

- `status=pass` / exit `0`: deep compatibility, final readiness/current receipt, exact target-to-web one-time challenge proof, and `/login` HTTP 200 all passed.
- `status=blocked` / exit `2`: runtime compatibility/readiness passed but exact target-to-web and/or `/login` transport/TLS evidence could not be certified automatically. A source-status transport failure before challenge issuance does not mutate handshake evidence.
- `status=fail` / exit `1`: a target/recovery-storage/lock, recovery/compatibility/readiness invariant failed; the reachable web origin did not satisfy the expected source-status contract or rejected/mismatched the exact challenge; an explicit HTTP/configuration failure occurred; another apply run already owns the target lock; or required evidence could not be safely produced. Post-lock failures include their protected `evidence_receipt` when writable and always report `evidence_write_status` when the receipt-aware apply context is active. Do not bypass the failing control.

Project state is advanced only after the real target evidence is reviewed and canonical `.ai` state is updated.
