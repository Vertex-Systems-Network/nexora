# Active Plan — RUNTIME-CLOSURE-001

## Identity

- Parent stage: `RUNTIME-CLOSURE-001 — Installation + Runtime Closure`
- Registered development unit: `SYS-RUNTIME-IDENTITY`
- Release train: `builder-beta`
- Status: `BLOCKED` pending real-target execution
- Source baseline reference: `main@f854c50c0f7687fc87fdfab01b49562392af4ef4`
- Real target: Windows + Laragon, `D:\laragon\www\nexora`
- Method: bounded existing-problem repair; use DMAIC/control evidence if new root-cause work is required

## Phase 5 governance note

The Research/Quality/Data/Reliability/Payment planning expansion does **not** change or widen this active runtime repair. Do not pull future stages into this blocker. If this runtime defect requires new source implementation, use evidence-based DMAIC and add a durable Control/regression guard; otherwise execute the already prepared bounded repair path only.

## Objective

Close the installed rc.93 post-install runtime identity mismatch without disguising it as an rc.94 upgrade, prove compatibility/readiness on the real target, then advance to `CORE-QA-001`.

## Current evidence imported from legacy handoff

Observed matching planes:

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

## Guardrail

Do not overwrite the live rc.93 installation with rc.94 merely to repair these fingerprints. Repair and upgrade are separate operations.

## Research / CTQ

This is an existing known defect, not a new feature. New market/VOC research is `NOT_APPLICABLE` to the bounded repair.

CTQs:

- repair only the permitted stale identity state;
- preserve immutable identity/trust planes;
- compatibility returns zero mismatches;
- post-install readiness passes;
- `/login` becomes reachable;
- no source/version upgrade is hidden inside repair;
- a repeated root-cause source defect, if found, receives regression/control protection.

## Dependencies / preconditions

- `AI-GOV-001` source control-plane work exists on the AI branch.
- Confirm target path/version before mutation.
- Prepared external rc.93 repair pack preserves immutable planes and rolls back permitted mutations on convergence failure.

## Architecture / data / authorization

- Architecture: no redesign expected; preserve runtime identity/trust model.
- DataFlow: runtime identity evidence only; no new product/customer data flow.
- Migrations: not a migration stage.
- Permissions: no auth policy change.
- Packages/API/AI/payment: out of scope.

## Security / FMEA

Risk: `critical` because installed runtime identity evidence is mutated.

Failure modes/controls:

- wrong target/version → preflight reject;
- immutable plane rewrite → forbidden;
- repair mixed with upgrade → forbidden;
- partial convergence → rollback permitted mutation and remain blocked;
- false PASS from source/static evidence → forbidden; real target commands required.

If new source behavior is introduced, update threat/FMEA/regression evidence before commit.

## Performance / reliability / cost

No product performance feature change is intended. Reliability requirement is deterministic repair/rollback and exact target evidence. Any material runtime regression discovered by source changes must be measured before closure.

## Execution chunks

### A — safe rc.93 repair

- [ ] Confirm live target path/version.
- [ ] Run prepared rc.93 Post-Install Identity Repair Pack.
- [ ] Require immutable-plane prechecks.
- [ ] Require rollback if convergence fails.

### B — compatibility evidence

- [ ] Run `php artisan nexora:runtime:compatibility-status --deep`.
- [ ] Require `status=pass`.
- [ ] Require `mismatches=[]`.
- [ ] Require `compatible=true`.
- [ ] Require `mode=installed-data-plane`.

### C — readiness

- [ ] Run `php artisan nexora:runtime:post-install-status --assert-ready`.
- [ ] Record exact result.

### D — product handoff

- [ ] Open `/login` on real target.
- [ ] Confirm no runtime/tenant/bootstrap failure.
- [ ] Update `SYS-RUNTIME-IDENTITY` evidence/status.
- [ ] Update state/handoff.
- [ ] Advance only when target evidence passes.

## Tests / control

- Existing compatibility/readiness contracts remain required.
- Any source fix for this blocker class must add a regression/control guard.
- Static/source checks never imply browser/target PASS.

## Rollback / recovery

Repair must roll back permitted fingerprint changes if convergence fails. rc.94 overwrite is not repair or rollback.

## Definition of Done

Advance only when:

1. real rc.93 target repaired through approved bounded path;
2. deep compatibility PASS, zero mismatches, installed-data-plane mode;
3. readiness assertion PASS;
4. `/login` reachable without current blocker;
5. state/handoff/registry evidence updated;
6. no immutable trust plane relaxed/re-written to force PASS;
7. any newly discovered recurring source defect has Control/regression protection.

## Next stage

`CORE-QA-001 — Super Admin + Core Application Functional QA`.

After Core QA, canonical governance sequence is:

`AI-GOV-AUTOMATION-100 → RESEARCH-DISCOVERY-100 → QUALITY-GOVERNANCE-100 → ADMIN-UX-CLOSURE-001 → SECURITY-BASELINE-200 → ARCH-BOUNDARY-100`.
