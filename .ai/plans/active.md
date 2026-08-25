# Active Plan — RUNTIME-CLOSURE-001

## Identity

- Parent stage: `RUNTIME-CLOSURE-001 — Installation + Runtime Closure`
- Registered development unit: `SYS-RUNTIME-IDENTITY`
- Release train: `builder-beta`
- Status: `BLOCKED` pending final real-target readiness + exact target-to-web `/login` evidence
- Real target: Windows + Laragon, `D:\laragon\www\nexora`
- Installed target release: `1.0.0-rc.93`
- Current canonical source baseline for this pass: `main@dffb238e655a1c474f4f7ce7e75c6eda004c0c32`
- Method: bounded existing-problem repair + DMAIC/control improvement; no roadmap expansion

## Governance boundary

The Research/Quality/Data/Reliability/Payment/System-Graph/Flow/AI-development planning programs do **not** widen this active runtime closure. Do not start `CORE-QA-001` or later stages until this stage is `TARGET_VERIFIED`.

This pass remains inside `SYS-RUNTIME-IDENTITY`. The Runtime Recovery / Closure Orchestrator is a control improvement for the already-active recovery workflow, not a new product/domain capability. It may coordinate only already-authorized runtime identity repair/readiness primitives and the already-existing CLI↔web `SourceActivationHandshake` proof surface.

The exact target-to-web hardening does not add a public route, external network destination, dependency, permission, secret source, migration, tenant capability, payment surface, product API, or product-AI behavior. It reuses the existing target-owned `/install/source-status` route, existing `SourceActivationHandshake`, and existing `nexora:source:status --require-web-ack` verification contract.

System Graph/Flow product contribution remains `NOT_APPLICABLE`: no new product runtime/package/data/security relationship model is introduced.

## Objective

Close the installed rc.93 post-install runtime identity mismatch without disguising it as an rc.94 upgrade; prove compatibility/readiness on the real target; prove that the HTTP origin being certified is served by the exact target rather than merely trusting `app.url`; make the safe recovery sequence deterministic; then advance to `CORE-QA-001` only after final target evidence passes.

## Real-target evidence received — 2026-08-25

Operator-supplied command output from `D:\laragon\www\nexora` establishes the following observed facts. This is real-target evidence supplied in-session; it is not replaced by CI/source claims.

### Repair dry-run

`npm run repair:rc93 -- --target="D:\laragon\www\nexora"`

Observed:

- `status=pass`
- `mode=dry-run`
- running + installed version `1.0.0-rc.93`
- only `activation`, `environment`, `process`, `service` mismatched
- `mutation_performed=false`

### Bounded repair apply

`npm run repair:rc93 -- --target="D:\laragon\www\nexora" --apply --confirm=REPAIR-RC93`

Observed:

- `status=pass`
- `mode=applied`
- repaired only the four allowed mismatches
- pre-repair sealed-lock SHA-256 `5db5fbf7f33a5d901146544463547800a114acad80bb62053d17c7e88e069d88`
- post-repair sealed-lock SHA-256 `1576c4212323c11dbb591a45a27988afa6e5c55a62982808af738e918828022a`
- protected backup path emitted under `storage/app/nexora/repair-backups/`
- repair receipt path emitted under `storage/app/nexora/runtime/repair-receipts/`
- `mutation_performed=true`

### Independent deep compatibility

`php artisan nexora:runtime:compatibility-status --deep`

Observed:

- `status=pass`
- `mismatches=[]`
- `runtime.compatible=true`
- `runtime.mode=installed-data-plane`
- environment/activation/service/process now compatible
- source/deployment/framework/data-plane/storage/host/resource/policy/dependency compatibility remained PASS
- `deployment_drift.status=pass`
- dependency runtime status PASS; reviewed dependency-lock attestation remains `missing` as a separate release/dependency-governance concern, not a runtime identity mismatch

### Post-install handoff

First readiness assertion observed:

- `status=receipt-refresh-required`
- `ready=false`
- `runtime_ready=true`
- `receipt_current=false`

This was expected after the sealed installation lock and activation fingerprint changed during the approved repair. The old handoff receipt still referenced the pre-repair identity.

`php artisan nexora:runtime:post-install-reconcile --confirm=RECONCILE` then returned `status=pass` and a new receipt bound to the repaired installation lock SHA-256.

**Still required before stage advancement:** a fresh `post-install-status --assert-ready` result proving `status=pass`, `ready=true`, `runtime_ready=true`, `receipt_current=true`, followed by exact target-to-web proof and real `/login` evidence.

## Root-cause / control findings

The underlying defect was stale post-install runtime identity sealing. The first source-side gap was that the referenced rc.93 repair pack was not committed/discoverable; PR #26 closed that gap.

A second operator-experience/control gap was then observed: after explicit mutation approval, the operator still had to manually chain compatibility, readiness, stale-receipt reconciliation, another readiness check and `/login` validation. The architecture correctly separated trust steps, but deterministic post-approval orchestration was missing.

A later adversarial pass found two additional reliability/evidence risks in the new orchestration layer: concurrent apply-mode writers could race the same sealed runtime target, and timestamp-only receipt names could overwrite prior evidence when multiple runs completed within the same second. Both are now part of the required control surface rather than accepted residual risk.

A further adversarial pass found a **wrong-host false-PASS** surface: bootstrapping the target and reading its own `config('app.url')` prevents arbitrary operator URL override, but it does not prove that the server currently answering that URL is the same target directory. A stale/misconfigured `app.url` could point to another reachable Nexora deployment whose `/login` returns HTTP 200. Therefore `app.url + /login 200` is no longer sufficient acceptance evidence.

The durable control improvement is a single Runtime Recovery / Closure Orchestrator that preserves human approval for mutation while automating deterministic verification/reconciliation, serializing apply-mode writers, preserving unique evidence, and proving a fresh target-local CLI→web nonce/source/runtime handshake before `/login` can become authoritative.

## Runtime Recovery / Closure Orchestrator contract

Canonical implementation target: `scripts/runtime-recovery-orchestrator.php`, exposed as `npm run runtime:recover`.

Dry-run default:

```bat
npm run runtime:recover -- --target="D:\laragon\www\nexora"
```

Apply requires explicit mutation authority:

```bat
npm run runtime:recover -- --target="D:\laragon\www\nexora" --apply --confirm=RECOVER-RUNTIME
```

Required behavior:

1. in apply mode acquire a non-blocking exclusive lock bound to the explicit target before recovery/reconcile/evidence work; concurrent apply attempts fail closed;
2. inspect deep target compatibility first;
3. if already compatible, do not run identity repair;
4. if target is exactly rc.93 and mismatches are only the four approved planes, delegate to the version-pinned rc.93 adapter;
5. independently re-run deep compatibility after repair and require both child exit code `0` and the expected PASS payload;
6. assert post-install readiness and bind PASS to child exit code `0`; `RuntimePostInstallHandoff::verifyCurrent()` independently rechecks source integrity, deep deployment identity, compatibility and activation identity;
7. automatically reconcile **only** `receipt-refresh-required` with `runtime_ready=true` and `receipt_current=false`;
8. re-assert readiness after reconciliation;
9. resolve only the target application's own bootstrapped `config('app.url')` and reject credentials/query/fragment as an HTTP base;
10. preflight the existing target-owned `/install/source-status` route with TLS verification enabled and redirects disabled **before** issuing challenge state, so pure transport failure does not mutate the handshake;
11. issue a fresh target-local one-time challenge through the existing `SourceActivationHandshake` only after source integrity is PASS;
12. send the bearer token only in-process through `X-Nexora-Activation-Token`; never copy it to logs, receipts, steps or public output;
13. require HTTP 200 + `X-Nexora-Source-Ack: acknowledged` + matching acknowledgement nonce, then independently run local `nexora:source:status --require-web-ack` with exit code `0` and matching source/runtime fingerprints;
14. only after exact target-to-web proof passes, perform verified `GET /login` on the same target-owned origin without redirects or TLS bypass;
15. write a machine-readable apply-mode recovery outcome receipt under protected target runtime storage using a unique per-run identifier so rapid runs cannot overwrite historical evidence;
16. return PASS only when compatibility, final readiness/current receipt, exact target-to-web proof and `/login` HTTP 200 all pass.

If source-status or `/login` cannot be certified because of local TLS trust/web-server reachability, return `BLOCKED` where no explicit HTTP/configuration rejection exists, preserve successful runtime/readiness evidence, and do not mislabel target verification as complete. Explicit non-200/source-contract/nonce/fingerprint failures are `FAIL`, not `BLOCKED`.

## Guardrails

- Do not overwrite the live rc.93 installation with rc.94 merely to repair identity.
- Repair and upgrade remain separate operations.
- No `git pull`, source copy, Composer/npm install/update, migration or seed behavior inside recovery orchestration.
- The rc.93 adapter remains pinned to exactly `1.0.0-rc.93` and only the four allowed identity mismatches.
- Any unrelated/immutable mismatch fails closed.
- Apply mode requires an exact confirmation token.
- Apply mode is single-writer per target; lock contention is FAIL, never an invitation to delete/bypass the lock.
- A child JSON PASS is never accepted when that child exited non-zero.
- The rc.93 adapter preserves protected backup + rollback-on-non-convergence behavior.
- Receipt reconciliation is allowed only after runtime compatibility is PASS and the exact stale-receipt state is observed.
- Recovery outcome receipts use unique identifiers; prior evidence is never silently replaced by a same-second run.
- `app.url` is only the candidate target-owned origin, **not** sufficient proof of exact target web identity.
- `/login` is skipped as non-authoritative until the fresh target-local one-time web acknowledgement and local CLI re-verification both PASS.
- The one-time web acknowledgement token is secret, single-use and never persisted in the recovery receipt/log/public result.
- TLS peer/name verification remains enabled for both source-status and `/login`; redirects remain disabled.
- Explicit HTTP/source-contract/nonce/fingerprint mismatch is FAIL; only transport/TLS inability with no authoritative HTTP result is BLOCKED.
- Orchestrator receipts are evidence artifacts, not authority to edit canonical project state by themselves.
- Source/CI PASS never substitutes for real target/browser evidence.

## Research / quality method

This is an existing defect/control improvement, not a new product feature. Market/VOC research is `NOT_APPLICABLE`.

DMAIC/control intent:

- **Define:** excessive manual sequencing and weak HTTP target binding create operator error and false-certification risk.
- **Measure:** the observed repair required separate repair, compatibility, readiness, reconcile and follow-up readiness/browser actions; an `app.url`-only smoke could not distinguish a stale URL pointing at another deployment.
- **Analyze:** primitives were fail-closed but lacked a post-approval coordinator; later audits found concurrent-writer, evidence-collision and wrong-host HTTP identity risks.
- **Improve:** add one bounded orchestrator over existing trusted primitives with per-target apply serialization, unique evidence identities, and reuse of the existing one-time CLI↔web source acknowledgement trust contract.
- **Control:** regression contract, exact confirmation, mismatch/version gates, child-exit binding, single-writer lock, unique receipts, exact target-to-web challenge proof, TLS/redirect controls, independent local re-verification, machine-readable evidence and exact-head CI/review.

CTQs:

- dry-run before mutation;
- explicit mutation authorization;
- one apply writer per target;
- preserve immutable source/deployment/trust planes;
- no hidden upgrade/dependency/migration work;
- automatic reconcile only for the exact stale-receipt state;
- independent compatibility + readiness verification including process exit status;
- exact target-to-web identity proof before login smoke;
- one-time challenge secret never written to evidence;
- verified TLS and no redirects for HTTP proof;
- unique/non-overwriting evidence output;
- fail/blocked instead of false PASS.

## Architecture / data / authorization

- Architecture: no product redesign; orchestration remains an external/operator control over existing runtime commands and the existing source-activation handshake trust primitive.
- DataFlow: installation/runtime evidence, protected local recovery receipts, and a single-use local source-activation bearer token sent only to the target-owned origin; no customer/product data flow.
- Secret handling: acknowledgement token originates from existing `SourceActivationHandshake`, remains in process memory/request header, is consumed once by the web process, and is excluded from orchestrator evidence.
- Network: no new external destination; source-status and `/login` use the same bootstrapped `app.url` origin with TLS verification and redirects disabled.
- System Graph/Flow: `NOT_APPLICABLE` to this bounded control improvement.
- Migrations: none.
- Permissions/auth policy: unchanged.
- Packages/API/product-AI/payment: out of scope.
- Runtime mutation authority: existing target `InstallationState` bounded repair + existing post-install receipt reconciliation + existing protected `SourceActivationHandshake` evidence only.

## Security / FMEA

Risk remains `critical` because recovery may mutate sealed runtime identity/evidence and certify a live web origin.

Failure modes and controls:

- wrong target/version → explicit target + target file/version checks;
- source/deployment drift → deep compatibility/readiness fail closed;
- unrelated mismatch → no approved adapter, fail closed;
- accidental mutation → dry-run default + `RECOVER-RUNTIME` confirmation;
- concurrent apply writers race sealed state/receipts/handshake → non-blocking exclusive target lock; second writer fails closed;
- rapid sequential runs overwrite timestamp-only evidence → unique random receipt identifier in every final filename;
- shell injection → child commands are fixed argument arrays with shell bypass; target path/token are not interpolated into a shell command;
- child emits PASS JSON but exits non-zero → PASS predicates also require exit code `0`;
- hidden dependency/upgrade/migration → explicitly absent and regression-guarded;
- stale receipt reconciled when runtime unhealthy → forbidden; requires exact receipt-refresh state with runtime ready;
- repair claims its own success → independent deep compatibility runs after repair;
- stale receipt claims readiness → readiness re-runs after reconcile and itself verifies source/deployment/compatibility/activation;
- stale/misconfigured `app.url` points at another reachable Nexora deployment → `/login` cannot run authoritatively until that web process consumes a fresh target-local one-time token and the exact target CLI verifies the same nonce/source/runtime fingerprints;
- token leaks into evidence/logs → token is retained only in process memory/request header, removed before public step creation, and excluded from receipts/results;
- source-status transport unavailable → preflight returns BLOCKED before challenge issuance, avoiding unnecessary handshake mutation;
- wrong host exposes source-status but cannot consume target token → explicit acknowledgement rejection/mismatch is FAIL;
- insecure local HTTPS shortcut → TLS peer/name verification stays enabled;
- redirects mask a different destination → redirects disabled for both source-status and `/login`;
- exact-target `/login` explicit non-200 → FAIL, never downgraded to BLOCKED;
- transport/TLS unavailable after valid runtime readiness → BLOCKED without rolling back an independently compatible runtime solely for transport evidence;
- false canonical advancement → state remains BLOCKED until independent review and real target evidence satisfy DoD.

## Performance / reliability / cost

No product performance change is intended. Apply orchestration executes a bounded number of local subprocesses, one source-status preflight GET, one one-time source-status acknowledgement GET, one local source-status verification, and one `/login` GET only after exact web identity proof succeeds. No retry loop is introduced. Reliability is deterministic stop-on-failure, single-writer apply serialization, non-overwriting evidence, exact web-target challenge binding, and preservation of existing repair backup/rollback semantics.

## Execution chunks

### A0 — source repair-tooling closure

- [x] Missing/discoverability gap identified.
- [x] Version-pinned dry-run-first rc.93 repair executable + Windows wrapper added.
- [x] Regression/control coverage added.
- [x] Exact-head source certification PASS and canonical merge complete (PR #26).

### A1 — real rc.93 repair

- [x] Live target/version confirmed.
- [x] Dry-run PASS with only four allowed mismatches.
- [x] Apply PASS with protected backup + receipt.
- [x] Independent deep compatibility PASS with zero mismatches and installed-data-plane mode.

### A2 — deterministic recovery orchestration control

- [x] Manual post-approval orchestration gap identified.
- [x] Add `scripts/runtime-recovery-orchestrator.php`.
- [x] Add single `runtime:recover` npm operator entrypoint.
- [x] Add regression contract + operator documentation.
- [x] Self-audit closed child-exit/HTTP-status/arbitrary-host-override false-green loopholes.
- [x] Second adversarial pass closed concurrent apply-writer and same-second receipt-overwrite risks.
- [x] Third adversarial pass closed `app.url`-points-to-another-deployment false-PASS by requiring fresh target-local CLI→web challenge acknowledgement before `/login`.
- [ ] Exact-head source certification PASS on the final hardened head.
- [ ] Independent exact-head review for the critical recovery-control change.
- [ ] Merge canonical implementation.

### B — compatibility evidence

- [x] `status=pass`.
- [x] `mismatches=[]`.
- [x] `compatible=true`.
- [x] `mode=installed-data-plane`.

### C — readiness

- [x] Initial readiness correctly detected stale receipt with `runtime_ready=true`.
- [x] Guarded receipt reconciliation PASS.
- [ ] Fresh readiness assertion must prove `status=pass`, `ready=true`, `runtime_ready=true`, `receipt_current=true`, `errors=[]`.

### D — product handoff

- [ ] Fresh exact target-to-web one-time challenge acknowledgement PASS.
- [ ] `/login` HTTP/browser evidence PASS on that exact proven origin without runtime/tenant/bootstrap failure.
- [ ] Update `SYS-RUNTIME-IDENTITY` evidence/status.
- [ ] Update canonical state/handoff/ledger.
- [ ] Advance only when target evidence passes.

## Tests / control

- Existing compatibility/readiness contracts remain authoritative.
- `Rc93PostInstallIdentityRepairPackTest` remains source regression evidence for the bounded repair adapter.
- `RuntimeRecoveryOrchestratorTest` guards confirmation, sequencing, child-exit binding, per-target apply serialization, unique receipt identity, reconcile conditions, target-owned URL selection, one-time exact target-to-web challenge ordering/secret handling, TLS/redirect behavior, HTTP PASS/FAIL/BLOCKED semantics, forbidden upgrade/dependency/migration behavior and npm entrypoint.
- `runtime-recovery-orchestrator-contract-verify.php` is a required exact-head CI gate and independently checks the same fail-closed source invariants without relying on the PHPUnit suite being executed by release certification.
- Source CI/static tests are not target proof.
- High/critical change requires exact-head independent review in addition to authoring tests/CI.

## Rollback / recovery

The rc.93 adapter continues to preserve and restore the exact pre-repair sealed installation lock if its own post-write compatibility does not converge. The orchestrator does not roll back a runtime that is independently compatible merely because later receipt or HTTP evidence is incomplete; those cases remain recoverable/verifiable without reverting valid runtime identity.

A source-identity challenge is only issued after a reachable source-status preflight. A successfully issued challenge may remain pending if transport fails between issuance and acknowledgement; it is bounded by the existing handshake TTL and may be superseded by a later authorized challenge. It is evidence state, not domain/customer data. The one-time token is not placed in the orchestrator receipt.

Apply-mode process termination releases the OS file lock; the lock file itself is only a synchronization anchor and must not be interpreted as proof that a process remains active.

## Definition of Done

Advance only when:

1. bounded repair + orchestration source has exact-head certification and required independent review;
2. real rc.93 target repair evidence is accepted;
3. deep compatibility PASS, zero mismatches, installed-data-plane mode;
4. final readiness assertion PASS with current receipt;
5. fresh target-local CLI→web challenge proof binds the configured origin to the exact target;
6. `/login` returns HTTP 200 on that same exact proven origin without runtime/tenant/bootstrap blocker;
7. state/handoff/registry/ledger evidence is updated;
8. no immutable trust plane or validation/security rule was relaxed to force PASS.

## Next stage

`CORE-QA-001 — Super Admin + Core Application Functional QA`.

Do not begin it before the current Definition of Done is satisfied.

After Core QA, canonical sequence remains:

`AI-GOV-AUTOMATION-100 → RESEARCH-DISCOVERY-100 → QUALITY-GOVERNANCE-100 → ADMIN-UX-CLOSURE-001 → SECURITY-BASELINE-200 → ARCH-BOUNDARY-100`.
