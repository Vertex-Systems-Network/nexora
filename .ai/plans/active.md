# Active Plan — RUNTIME-CLOSURE-001

## Identity

- Parent stage: `RUNTIME-CLOSURE-001 — Installation + Runtime Closure`
- Registered development unit: `SYS-RUNTIME-IDENTITY`
- Release train: `builder-beta`
- Status: `BLOCKED` pending real-target execution
- Current repair-tooling source base: `main@7abf7a8dfded06b21f1e179c1635146d6a1fcf1b`
- Repair-tooling branch: `fix/rc93-post-install-identity-repair-pack`
- Real target: Windows + Laragon, `D:\laragon\www\nexora`
- Installed target release: `1.0.0-rc.93`
- Method: bounded existing-problem repair; DMAIC/control evidence applies to the missing repair-artifact/tooling gap

## Phase 5/6/7 governance note

The Research/Quality/Data/Reliability/Payment/System-Graph/Flow/**AI-development orchestration** planning expansions do **not** widen this active runtime repair. Do not pull future stages into this blocker.

System Graph/Flow product contribution remains `NOT_APPLICABLE`: no new package/runtime/data/security relationship model is being introduced. Phase 7 automation is also not treated as implemented. Current procedural safeguards remain mandatory: exact target/path/version preflight, bounded mutation scope, no hidden upgrade, no self-authored target PASS, preserved evidence, exact-head review and no weakening of readiness/compatibility controls.

## Objective

Close the installed rc.93 post-install runtime identity mismatch without disguising it as an rc.94 upgrade, prove compatibility/readiness on the real target, then advance to `CORE-QA-001`.

## Current evidence

Observed matching planes from the real rc.93 target:

- platform version;
- source generation;
- deployment/source;
- database data plane;
- storage;
- host;
- resources;
- policy;
- Laravel framework;
- runtime dependencies.

Observed stale post-install fingerprints:

- environment;
- activation;
- service;
- process.

### Tooling-gap finding

The legacy handoff said a prepared external rc.93 Post-Install Identity Repair Pack existed, but the repository did not contain a discoverable executable artifact. That was a real zero-skip execution gap: the plan required a critical repair mechanism that a future agent/operator could not deterministically recover from source.

The source-side corrective work for that gap is now represented by:

- `scripts/rc93-post-install-identity-repair.php` — self-contained external repair executable;
- `scripts/rc93-post-install-identity-repair.ps1` — Windows/PowerShell wrapper;
- `tests/Unit/Certification/Rc93PostInstallIdentityRepairPackTest.php` — regression/control contract;
- `docs/runtime/RC93_POST_INSTALL_IDENTITY_REPAIR.md` — operator procedure.

This source work does **not** change the target status. `RUNTIME-CLOSURE-001` remains BLOCKED until real-target execution passes.

## Guardrails

- Do not overwrite the live rc.93 installation with rc.94 merely to repair these fingerprints.
- Repair and upgrade are separate operations.
- The repair tool is pinned to exactly `1.0.0-rc.93`.
- It may repair only `environment`, `activation`, `service`, `process` mismatches.
- Any other mismatch fails closed before mutation.
- It boots the target's own autoloader/application; it does not copy current source into the target.
- Apply mode requires an explicit confirmation token, a verified sealed-lock backup and rollback on non-convergence.
- A repair receipt is not target certification evidence.

## Research / CTQ

This is an existing known defect, not a new product feature. Market/VOC research is `NOT_APPLICABLE`.

CTQs:

- repair only the permitted stale identity state;
- preserve immutable source/deployment/trust planes;
- deterministic dry-run before mutation;
- verified sealed-lock backup before apply;
- automatic rollback if convergence fails;
- compatibility returns zero mismatches;
- post-install readiness passes;
- `/login` becomes reachable;
- no source/version upgrade is hidden inside repair;
- repeated blocker classes receive durable regression/control protection.

## Architecture / data / authorization

- Architecture: no product redesign; preserve runtime identity/trust model.
- DataFlow: runtime identity evidence only; no new product/customer data flow.
- System Graph/Flow: `NOT_APPLICABLE` to this bounded repair.
- Migrations: none.
- Permissions/auth policy: unchanged.
- Packages/API/product-AI/payment: out of scope.
- Runtime mutation authority: only target `InstallationState::updateMetadata()` plus target `AtomicFileWriter` backup/rollback semantics.

## Security / FMEA

Risk: `critical` because installed runtime identity evidence is mutated.

Failure modes/controls:

- wrong target/version → exact rc.93 preflight reject;
- missing/changed rc.93 API contract → method/class preflight reject before mutation;
- invalid sealed lock → reject;
- source/deployment drift → reject;
- immutable/unrelated mismatch → reject;
- service/process identity unhealthy → reject;
- accidental apply → default dry-run + exact confirmation token;
- repair mixed with upgrade → forbidden; no source-copy/update path exists in the pack;
- partial convergence → atomically restore pre-repair sealed lock and verify original SHA-256;
- false PASS from source/static/AI-authored evidence → forbidden; real target commands remain required;
- readiness/compatibility guard relaxed to obtain PASS → forbidden.

## Performance / reliability / cost

No product performance change is intended. Reliability requirement is deterministic repair/rollback and exact target evidence. The deep service probe may perform bounded non-destructive cache/queue/network checks already owned by the target runtime identity provider.

## Execution chunks

### A0 — source repair-tooling closure

- [x] Identify that the previously referenced external repair pack was not committed/discoverable.
- [x] Add a version-pinned, dry-run-first external repair executable.
- [x] Add explicit Windows wrapper and operator documentation.
- [x] Add regression/control coverage for version pin, allowed mismatch set, confirmation, backup/rollback and forbidden upgrade/shell paths.
- [ ] Require exact-head source certification PASS and merge before using the pack as canonical source tooling.

### A — safe rc.93 repair on the real target

- [ ] Confirm live target path/version.
- [ ] Run dry-run: `php scripts/rc93-post-install-identity-repair.php --target="D:\laragon\www\nexora"` from the canonical source checkout.
- [ ] Require source/deployment/lock/API prechecks and only the four permitted mismatches.
- [ ] If dry-run passes, apply with `--apply --confirm=REPAIR-RC93`.
- [ ] Require verified protected backup before mutation.
- [ ] Require automatic rollback if convergence fails.

### B — compatibility evidence

Run from `D:\laragon\www\nexora`:

- [ ] `php artisan nexora:runtime:compatibility-status --deep`
- [ ] Require `status=pass`.
- [ ] Require `mismatches=[]`.
- [ ] Require `compatible=true`.
- [ ] Require `mode=installed-data-plane`.

### C — readiness

- [ ] `php artisan nexora:runtime:post-install-status --assert-ready`
- [ ] Record exact target result.

### D — product handoff

- [ ] Open `/login` on the real target.
- [ ] Confirm no runtime/tenant/bootstrap failure.
- [ ] Update `SYS-RUNTIME-IDENTITY` evidence/status.
- [ ] Update state/handoff/ledger.
- [ ] Advance only when target evidence passes.

## Tests / control

- Existing compatibility/readiness contracts remain authoritative.
- `Rc93PostInstallIdentityRepairPackTest` is source regression evidence for the missing repair-artifact class; it is not runtime proof.
- Static/source CI never implies browser/target PASS.
- Author-written receipts/evidence cannot substitute for the target command/browser evidence.

## Rollback / recovery

Apply mode must preserve the exact pre-repair sealed installation lock under protected target storage. If post-write compatibility is not `compatible=true`, `mismatches=[]`, `mode=installed-data-plane`, the pack restores the original lock atomically and verifies the original SHA-256. rc.94 overwrite is neither repair nor rollback.

## Definition of Done

Advance only when:

1. repair-tool source has exact-head certification and is canonical/merged;
2. real rc.93 target is repaired through the approved bounded path;
3. deep compatibility PASS, zero mismatches, installed-data-plane mode;
4. readiness assertion PASS;
5. `/login` is reachable without the current blocker;
6. state/handoff/registry/ledger evidence is updated;
7. no immutable trust plane or validation rule was relaxed/re-written to force PASS.

## Next stage

`CORE-QA-001 — Super Admin + Core Application Functional QA`.

After Core QA:

`AI-GOV-AUTOMATION-100 → RESEARCH-DISCOVERY-100 → QUALITY-GOVERNANCE-100 → ADMIN-UX-CLOSURE-001 → SECURITY-BASELINE-200 → ARCH-BOUNDARY-100`.

Later Builder Beta reaches `SYSTEM-GRAPH-100` only after declared dependencies are complete; later Pro reaches `FLOW-INTELLIGENCE-200` only after its dependency graph is satisfied.
