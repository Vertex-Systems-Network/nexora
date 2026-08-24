# Active Plan — RUNTIME-CLOSURE-001

## Identity

- Parent stage: `RUNTIME-CLOSURE-001 — Installation + Runtime Closure`
- Registered development unit: `SYS-RUNTIME-IDENTITY`
- Release train: `builder-beta`
- Status: `BLOCKED` pending final real-target readiness + `/login` evidence
- Real target: Windows + Laragon, `D:\laragon\www\nexora`
- Installed target release: `1.0.0-rc.93`
- Current canonical source baseline for this pass: `main@dffb238e655a1c474f4f7ce7e75c6eda004c0c32`
- Method: bounded existing-problem repair + DMAIC/control improvement; no roadmap expansion

## Governance boundary

The Research/Quality/Data/Reliability/Payment/System-Graph/Flow/AI-development planning programs do **not** widen this active runtime closure. Do not start `CORE-QA-001` or later stages until this stage is `TARGET_VERIFIED`.

This pass remains inside `SYS-RUNTIME-IDENTITY`. The Runtime Recovery / Closure Orchestrator is a control improvement for the already-active recovery workflow, not a new product/domain capability. It may coordinate only already-authorized runtime identity repair/readiness primitives.

System Graph/Flow product contribution remains `NOT_APPLICABLE`: no new product runtime/package/data/security relationship model is introduced. No dependency, migration, permission, tenant, payment, public API or product-AI scope is added.

## Objective

Close the installed rc.93 post-install runtime identity mismatch without disguising it as an rc.94 upgrade; prove compatibility/readiness on the real target; make the safe recovery sequence deterministic so operators do not manually chain internal commands; then advance to `CORE-QA-001` only after final target evidence passes.

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

**Still required before stage advancement:** a fresh `post-install-status --assert-ready` result proving `status=pass`, `ready=true`, `runtime_ready=true`, `receipt_current=true`, followed by real `/login` evidence.

## Root-cause / control finding

The underlying defect was stale post-install runtime identity sealing. The first source-side gap was that the referenced rc.93 repair pack was not committed/discoverable; PR #26 closed that gap.

A second operator-experience/control gap was then observed: after explicit mutation approval, the operator still had to manually chain compatibility, readiness, stale-receipt reconciliation, another readiness check and `/login` validation. The architecture correctly separated trust steps, but deterministic post-approval orchestration was missing.

The durable control improvement is a single Runtime Recovery / Closure Orchestrator that preserves human approval for mutation while automating deterministic verification/reconciliation steps.

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

1. inspect deep target compatibility first;
2. if already compatible, do not run identity repair;
3. if target is exactly rc.93 and mismatches are only the four approved planes, delegate to the version-pinned rc.93 adapter;
4. independently re-run deep compatibility after repair and require both child exit code `0` and the expected PASS payload;
5. assert post-install readiness and bind PASS to child exit code `0`;
6. automatically reconcile **only** `receipt-refresh-required` with `runtime_ready=true` and `receipt_current=false`;
7. re-assert readiness after reconciliation;
8. resolve only the target application's own bootstrapped `config('app.url')`, then perform verified `GET /login` without disabling TLS verification or following redirects; arbitrary alternate HTTP hosts are forbidden;
9. write a machine-readable apply-mode recovery outcome receipt under protected target runtime storage;
10. return PASS only when compatibility, final readiness/current receipt and target-owned `/login` HTTP 200 all pass.

If target-owned HTTP smoke cannot be certified because of local TLS trust/web-server reachability, return `BLOCKED`, preserve successful runtime/readiness evidence, and do not mislabel target verification as complete. If `/login` returns an explicit non-200/configuration failure, return `FAIL`, not `BLOCKED`.

## Guardrails

- Do not overwrite the live rc.93 installation with rc.94 merely to repair identity.
- Repair and upgrade remain separate operations.
- No `git pull`, source copy, Composer/npm install/update, migration or seed behavior inside recovery orchestration.
- The rc.93 adapter remains pinned to exactly `1.0.0-rc.93` and only the four allowed identity mismatches.
- Any unrelated/immutable mismatch fails closed.
- Apply mode requires an exact confirmation token.
- A child JSON PASS is never accepted when that child exited non-zero.
- The rc.93 adapter preserves protected backup + rollback-on-non-convergence behavior.
- Receipt reconciliation is allowed only after runtime compatibility is PASS and the exact stale-receipt state is observed.
- The final HTTP smoke is target-bound to the target application's own bootstrapped `app.url`; arbitrary URL overrides are not supported.
- TLS verification may not be disabled merely to obtain `/login` PASS.
- Explicit HTTP non-200 is FAIL; only transport/TLS inability with no HTTP result is BLOCKED.
- Orchestrator receipts are evidence artifacts, not authority to edit canonical project state by themselves.
- Source/CI PASS never substitutes for real target/browser evidence.

## Research / quality method

This is an existing defect/control improvement, not a new product feature. Market/VOC research is `NOT_APPLICABLE`.

DMAIC/control intent:

- **Define:** excessive manual sequencing after approved runtime repair creates operator error/repetition risk.
- **Measure:** the observed repair required separate repair, compatibility, readiness, reconcile and follow-up readiness/browser actions.
- **Analyze:** primitives were correctly fail-closed but lacked a post-approval coordinator.
- **Improve:** add one bounded orchestrator over existing trusted primitives.
- **Control:** regression contract, exact confirmation, mismatch/version gates, child-exit binding, target-owned HTTP identity, independent re-verification, machine-readable receipts and exact-head CI/review.

CTQs:

- dry-run before mutation;
- explicit mutation authorization;
- preserve immutable source/deployment/trust planes;
- no hidden upgrade/dependency/migration work;
- automatic reconcile only for the exact stale-receipt state;
- independent compatibility + readiness verification including process exit status;
- target-owned HTTP endpoint only;
- verified TLS for HTTP smoke;
- deterministic evidence output;
- fail/blocked instead of false PASS.

## Architecture / data / authorization

- Architecture: no product redesign; orchestration remains an external/operator control over existing runtime commands.
- DataFlow: only installation/runtime evidence and protected local recovery receipts; no customer/product data flow.
- System Graph/Flow: `NOT_APPLICABLE` to this bounded control improvement.
- Migrations: none.
- Permissions/auth policy: unchanged.
- Packages/API/product-AI/payment: out of scope.
- Runtime mutation authority: existing target `InstallationState` bounded repair + existing post-install receipt reconciliation only.

## Security / FMEA

Risk remains `critical` because recovery may mutate sealed runtime identity evidence.

Failure modes and controls:

- wrong target/version → explicit target + target file/version checks;
- source/deployment drift → existing repair adapter fails closed;
- unrelated mismatch → no approved adapter, fail closed;
- accidental mutation → dry-run default + `RECOVER-RUNTIME` confirmation;
- shell injection → child commands are fixed argument arrays with shell bypass; target path is not interpolated into shell text;
- child emits PASS JSON but exits non-zero → PASS predicate also requires exit code `0`;
- hidden dependency/upgrade/migration → explicitly absent and regression-guarded;
- stale receipt reconciled when runtime unhealthy → forbidden; requires exact receipt-refresh state with runtime ready;
- repair claims its own success → independent compatibility command runs after repair;
- stale receipt claims readiness → readiness is re-run after reconcile;
- arbitrary external host returns `/login` 200 → impossible through supported interface because smoke URL is derived only from target-owned bootstrapped `config('app.url')`;
- insecure local HTTPS shortcut → TLS peer/name verification stays enabled;
- target-owned `/login` explicit non-200 → FAIL, never downgraded to BLOCKED;
- transport/TLS unavailable after valid repair → BLOCKED without rolling back an independently compatible runtime solely for transport evidence;
- false canonical advancement → state remains BLOCKED until reviewed target evidence satisfies DoD.

## Performance / reliability / cost

No product performance change is intended. Operator orchestration executes a bounded number of local subprocesses and one target-owned HTTP GET. No retry loop is introduced. Reliability is deterministic stop-on-failure plus preservation of existing repair backup/rollback semantics.

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
- [x] Self-audit closed child-exit/HTTP-status/alternate-host false-green loopholes before merge.
- [ ] Exact-head source certification PASS.
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

- [ ] `/login` HTTP/browser evidence PASS without runtime/tenant/bootstrap failure.
- [ ] Update `SYS-RUNTIME-IDENTITY` evidence/status.
- [ ] Update canonical state/handoff/ledger.
- [ ] Advance only when target evidence passes.

## Tests / control

- Existing compatibility/readiness contracts remain authoritative.
- `Rc93PostInstallIdentityRepairPackTest` remains source regression evidence for the bounded repair adapter.
- `RuntimeRecoveryOrchestratorTest` guards orchestration confirmation, sequencing, child-exit binding, reconcile conditions, target-owned URL binding, TLS verification, HTTP PASS/FAIL/BLOCKED semantics, forbidden upgrade/dependency/migration behavior and npm entrypoint.
- Source CI/static tests are not target proof.
- High/critical change requires exact-head independent review in addition to authoring tests/CI.

## Rollback / recovery

The rc.93 adapter continues to preserve and restore the exact pre-repair sealed installation lock if its own post-write compatibility does not converge. The orchestrator does not roll back a runtime that is independently compatible merely because later receipt or HTTP evidence is incomplete; those cases remain recoverable/verifiable without reverting valid runtime identity.

## Definition of Done

Advance only when:

1. bounded repair + orchestration source has exact-head certification and required independent review;
2. real rc.93 target repair evidence is accepted;
3. deep compatibility PASS, zero mismatches, installed-data-plane mode;
4. final readiness assertion PASS with current receipt;
5. target-owned `/login` is reachable without the blocker;
6. state/handoff/registry/ledger evidence is updated;
7. no immutable trust plane or validation/security rule was relaxed to force PASS.

## Next stage

`CORE-QA-001 — Super Admin + Core Application Functional QA`.

Do not begin it before the current Definition of Done is satisfied.

After Core QA, canonical sequence remains:

`AI-GOV-AUTOMATION-100 → RESEARCH-DISCOVERY-100 → QUALITY-GOVERNANCE-100 → ADMIN-UX-CLOSURE-001 → SECURITY-BASELINE-200 → ARCH-BOUNDARY-100`.
