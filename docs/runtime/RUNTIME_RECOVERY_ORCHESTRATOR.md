# Nexora Runtime Recovery / Closure Orchestrator

## Purpose

`runtime-recovery-orchestrator.php` converts the existing manual runtime-repair sequence into one fail-closed operator workflow while preserving the required human approval before mutation.

It is an operator/control tool inside `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY`. It is not a general upgrade engine and does not authorize future roadmap work.

## Safety model

Dry-run is the default. Apply mode requires:

```text
--apply --confirm=RECOVER-RUNTIME
```

Before any apply lock or child runtime command is started, the explicit target and the required runtime entrypoints (`artisan`, `vendor/autoload.php`, and `bootstrap/app.php`) are resolved component-by-component. Every parent directory and final file must resolve to its exact lexical path inside the explicit target. A symlink, Windows junction/reparse point, or other parent-directory redirect that would make `vendor/autoload.php` or `bootstrap/app.php` load code from outside the target is rejected before child execution and before apply-lock ownership.

That initial containment check is **not** treated as a durable trust decision. Immediately before every later target-owned PHP child (`artisan` commands and inline Laravel bootstraps), the same runtime-entrypoint containment check is repeated. If another process replaces `vendor`, `bootstrap`, or a required file with a symlink/junction/reparse redirect after an earlier PASS, the next child is refused before execution. The CI behavioral contract proves this by letting the first compatibility child return PASS while replacing `vendor` with an outside symlink, then verifying that the following readiness child never executes and the post-lock failure is sealed.

The version-pinned rc.93 repair adapter enforces the same rule when invoked directly: its required runtime files must resolve inside the exact target, the installed-state lock must remain a contained regular file, and the protected `repair-backups` and `runtime/repair-receipts` directories are created/resolved component-by-component before repair writes. The generic `AtomicFileWriter` is not treated as a parent-directory trust boundary.

Apply mode is **single-writer per target**. Before creating or opening the apply lock or any recovery receipt, every `storage/app/nexora/runtime/recovery-orchestrator` path component is resolved independently and must remain the exact target-owned directory path. Symlink/junction or other filesystem redirection is rejected fail-closed, including redirection that would otherwise escape the explicit target. The orchestrator then acquires a non-blocking exclusive target lock before any apply-mode compatibility/recovery/reconcile work. A second concurrent apply attempt fails closed instead of racing sealed runtime identity, handoff receipts, source/web acknowledgement evidence, or recovery evidence generation.

After target validation and successful apply-lock acquisition, all **post-lock apply failures** also write a unique protected machine-readable failure outcome receipt with the accumulated steps, mutation state, and non-secret failure context. Argument, target-validation, confirmation, recovery-storage containment, and lock-acquisition failures remain receipt-free because apply ownership has not been established. If the protected receipt itself cannot be written or its directory no longer resolves safely inside the target, the result remains FAIL and exposes `evidence_write_status=fail` without masking the original failure.

Mutation evidence is conservative rather than optimistic. Before the orchestrator starts an authorized mutating child such as the rc.93 repair adapter or post-install receipt reconciliation, it records `mutation_attempted=true` and `mutation_may_have_occurred=true`. Only a definitive successful child result clears that uncertainty; confirmed mutations additionally set `mutation_performed=true`. Therefore a child that changes target state and then exits non-zero cannot leave behind a failure receipt that falsely claims a clean no-mutation outcome. CI behaviorally exercises this with a disposable reconcile child that writes a target marker before exiting `9`.

**Windows-safe child process capture** keeps only child stdout on an anonymous pipe. Child stderr is captured in a unique transient regular file and read after the child closes, so a verbose failing command cannot fill an unread stderr pipe while the parent is waiting for stdout EOF. The transient stderr file is closed after capture and is not a target/runtime evidence artifact.

The orchestrator:

1. validates the explicit target's required runtime entrypoints component-by-component and rejects parent-directory/file redirection before any child runtime code can execute;
2. establishes an exact target-owned, non-redirected recovery-storage path and acquires the apply lock when mutation is authorized;
3. **revalidates those exact target runtime entrypoints immediately before every target-owned PHP child**, preventing a post-validation filesystem swap from becoming a later exact-target execution;
4. inspects the explicit target with `nexora:runtime:compatibility-status --deep`;
5. if compatibility already passes, it does not run an identity repair;
6. if the target is exactly `1.0.0-rc.93` and the only mismatches are `environment`, `activation`, `service`, `process`, it delegates to the version-pinned rc.93 repair adapter, whose own runtime/lock/backup/receipt paths are independently target-contained;
7. marks mutating repair/reconcile operations as attempted before child execution so abnormal exits preserve mutation uncertainty;
8. independently re-runs deep compatibility after any repair and requires both exit code `0` and the expected PASS payload;
9. runs `nexora:runtime:post-install-status --assert-ready` and binds readiness PASS to exit code `0`; the handoff implementation itself re-verifies source, deep deployment identity, runtime compatibility, activation identity and the current sealed handoff receipt;
10. automatically runs `nexora:runtime:post-install-reconcile --confirm=RECONCILE` only when the readiness child returns the command's expected not-ready exit code `1` **and** the payload is the exact stale-receipt state: `receipt-refresh-required`, `ready=false`, `runtime_ready=true`, and `receipt_current=false`. Stale-shaped JSON from any abnormal child exit is a hard failure and cannot authorize reconciliation;
11. re-runs the readiness assertion after reconciliation;
12. resolves the target application's own bootstrapped `config('app.url')`;
13. preflights that origin's existing `/install/source-status` contract with TLS verification enabled and redirects disabled;
14. issues a fresh target-local **one-time** `SourceActivationHandshake` challenge only after the preflight is reachable, sends the secret only in the in-process `X-Nexora-Activation-Token` request header, requires the web process to acknowledge the exact nonce, then independently runs local `nexora:source:status --require-web-ack` to prove that the acknowledgement belongs to the exact target source/runtime generation;
15. only after that exact target-to-web identity proof passes, performs verified HTTP(S) `GET /login` on the same target-owned origin;
16. writes a machine-readable apply-mode outcome receipt under the revalidated protected target runtime storage path using a unique per-run identifier so rapid sequential runs cannot overwrite previous evidence.

Arbitrary HTTP target overrides are intentionally unsupported. `config('app.url')` ownership alone is **not** treated as proof that the HTTP server belongs to the target directory: a different Nexora deployment could otherwise return `/login` HTTP 200. The fresh one-time CLI→web acknowledgement closes that false-PASS path.

The challenge token is secret, single-use, and **never written into the recovery receipt, logs, or public result payload**. The receipt may contain the non-secret challenge nonce and source/runtime fingerprints as provenance.

No new public endpoint or trust boundary is introduced by this control: it reuses the existing `SourceActivationHandshake`, `/install/source-status`, and `nexora:source:status --require-web-ack` mechanisms already used to prove CLI↔web source convergence.

## Forbidden behavior

The recovery controls do not:

- copy current source into the target;
- run `git pull` or `git checkout`;
- install or update Composer/npm dependencies;
- run migrations or seeders;
- change the target version;
- trust a regular final `vendor/autoload.php` or `bootstrap/app.php` when any parent directory resolves through a symlink/junction/reparse point or outside the explicit target;
- treat one earlier target-runtime containment PASS as sufficient for later child executions after the filesystem may have changed;
- allow the direct rc.93 adapter to read an installed lock or write repair backup/receipt evidence through redirected/outside target paths;
- ignore unrelated compatibility mismatches;
- accept JSON PASS from a child command that exited non-zero;
- authorize stale-receipt reconciliation from payload shape alone or from an abnormal readiness child exit;
- report `mutation_performed=false` as if it proved no mutation when an authorized mutating child was attempted but failed without a definitive outcome;
- use a second anonymous child pipe for stderr that can deadlock under back-pressure;
- allow concurrent apply-mode writers for the same target;
- follow symlink/junction/filesystem redirection for recovery lock or receipt storage;
- reuse a timestamp-only recovery-orchestrator receipt filename that can silently replace previous evidence;
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

If a required target runtime path such as `vendor/autoload.php` reaches an outside location through a parent symlink/junction, apply fails during target validation: no target `artisan` child is run, no apply lock is acquired, and no recovery receipt is created. The CI behavioral contract exercises this with a disposable redirected `vendor` parent.

Containment is also rechecked between target children. The CI TOCTOU regression starts with a valid target, allows the compatibility child to replace `vendor` with an outside symlink while returning PASS, and then requires the orchestrator to stop **before** the readiness child executes. Because the apply lock is already owned at that point, the failure is written as protected post-lock evidence while mutation flags remain false for the orchestrator itself.

The same redirected-parent case is behaviorally exercised against the standalone rc.93 adapter: the outside `autoload.php` must not execute. Static contracts additionally require target containment for the adapter's installed lock, repair backup directory, and repair receipt directory.

If the protected recovery-storage path resolves through a symlink/junction or outside the exact target, apply fails before lock ownership and writes nothing through the redirected path. If another apply-mode recovery already owns the valid target lock, the second run also fails closed. Do not delete, redirect, or bypass the lock to force progress; first establish the target filesystem state and whether another operator/process is still active.

When a mutating child is attempted, interpret the three mutation fields together: `mutation_attempted` says a mutating operation was started, `mutation_performed` means mutation is positively confirmed, and `mutation_may_have_occurred` flags an unresolved partial-mutation possibility after abnormal child failure. A failure with `mutation_attempted=true`, `mutation_performed=false`, `mutation_may_have_occurred=true` requires target inspection before retry; it is not evidence that the target was untouched.

The exact target-to-web challenge is issued only in apply mode, after final CLI readiness and a reachable source-status preflight. It updates the existing protected source-activation handshake evidence and consumes its one-time token through the web process. This is counted as target evidence mutation in the final recovery receipt.

If target-owned `config('app.url')` is missing/invalid, source-status cannot be reached with verified transport, the exact target web process rejects the one-time challenge, or its HTTPS connection cannot be verified by the PHP runtime, automated HTTP certification remains `BLOCKED`/`FAIL` as appropriate. Do not supply an alternate host or disable TLS merely to obtain green status; repair the target URL/web mapping/trust configuration or provide separate authorized evidence.

## Result semantics

- `status=pass` / exit `0`: deep compatibility, final readiness/current receipt, exact target-to-web one-time challenge proof, and `/login` HTTP 200 all passed.
- `status=blocked` / exit `2`: runtime compatibility/readiness passed but exact target-to-web and/or `/login` transport/TLS evidence could not be certified automatically. A source-status transport failure before challenge issuance does not mutate handshake evidence.
- `status=fail` / exit `1`: an initial or per-child target runtime-file containment check, rc.93 repair path-containment, recovery-storage/lock, recovery/compatibility/readiness invariant failed, including an unexpected readiness child exit even when its JSON resembles the stale-receipt state; the reachable web origin did not satisfy the expected source-status contract or rejected/mismatched the exact challenge; an explicit HTTP/configuration failure occurred; another apply run already owns the target lock; a mutating child may have partially changed target state before failing; or required evidence could not be safely produced. Post-lock failures include their protected `evidence_receipt` when writable and report `mutation_attempted`, `mutation_performed`, `mutation_may_have_occurred`, plus `evidence_write_status` when the receipt-aware apply context is active. Do not bypass the failing control.

Project state is advanced only after the real target evidence is reviewed and canonical `.ai` state is updated.
