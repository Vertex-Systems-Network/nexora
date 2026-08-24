# Active Plan — RUNTIME-CLOSURE-001

## Identity

- Parent stage: `RUNTIME-CLOSURE-001 — Installation + Runtime Closure`
- Registered development unit: `SYS-RUNTIME-IDENTITY`
- Release train: `builder-beta`
- Status: `BLOCKED` pending real-target execution
- Source baseline reference: `main@f854c50c0f7687fc87fdfab01b49562392af4ef4`
- Real target: Windows + Laragon, `D:\laragon\www\nexora`

## Objective

Close the installed rc.93 post-install runtime identity mismatch without disguising it as an rc.94 upgrade, prove compatibility/readiness on the real target, then advance to `CORE-QA-001`.

## Current evidence imported from the legacy handoff

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

Do not overwrite the live rc.93 installation with rc.94 merely to repair these four fingerprints. Repair and upgrade are separate operations.

## Dependencies / preconditions

- `AI-GOV-001` source control-plane work exists on the AI branch.
- Current installed target identity/version/path must be confirmed before mutation.
- The prepared external rc.93 repair pack must preserve immutable identity planes and roll back permitted mutations on convergence failure.

## Architecture / data / authorization impact

- Architecture: no redesign expected; preserve runtime identity/trust model.
- Data/migrations: not a migration stage.
- Human permissions: not changing auth policy in this stage.
- Runtime capabilities/packages: no extension/theme capability change.
- API/AI: no product AI/API surface change.

## Security / threat model

Risk class: `critical` because this unit mutates installed runtime identity evidence.

Threat focus:

- do not rewrite immutable planes to manufacture PASS;
- do not mix repair with source/version upgrade;
- reject target/version mismatch;
- preserve rollback when convergence fails;
- preserve audit/evidence of what was changed.

The detailed historical runtime repair design remains the authoritative implementation evidence for this already-prepared repair path. If new source behavior is introduced while fixing the target, update/create an explicit threat-model note and regression protection before committing that source change.

## Privacy / UI / API / AI

- Privacy: no personal-data behavior change.
- UI: only `/login` reachability smoke after runtime closure; full auth UX belongs to `CORE-QA-001`.
- API/SDK/theme/Studio/extension/AI surfaces: out of scope except verifying runtime closure does not break platform prerequisites.

## Execution chunks

### RUNTIME-CLOSURE-001-A — safe rc.93 repair

- [ ] Confirm live target path and installed version before mutation.
- [ ] Run prepared external rc.93 Post-Install Identity Repair Pack.
- [ ] Require immutable identity-plane checks before permitted fingerprint mutation.
- [ ] Require rollback if convergence fails.

### RUNTIME-CLOSURE-001-B — compatibility evidence

- [ ] Run `php artisan nexora:runtime:compatibility-status --deep`.
- [ ] Require `status=pass`.
- [ ] Require `mismatches=[]`.
- [ ] Require `compatible=true`.
- [ ] Require `mode=installed-data-plane`.

### RUNTIME-CLOSURE-001-C — post-install readiness

- [ ] Run `php artisan nexora:runtime:post-install-status --assert-ready`.
- [ ] Record exact command result/evidence.

### RUNTIME-CLOSURE-001-D — product handoff

- [ ] Open `/login` on real target.
- [ ] Confirm application reaches login flow without runtime/tenant/bootstrap failure.
- [ ] Update `SYS-RUNTIME-IDENTITY` registry status/evidence.
- [ ] Update `.ai/state.json` and handoff.
- [ ] Advance to `CORE-QA-001` only when required target evidence passes.

## Tests / regression

- Existing runtime compatibility/readiness contracts remain required.
- Any source fix discovered from this blocker class must add regression protection.
- Do not infer browser/target PASS from static/source checks.

## Rollback / recovery

The repair pack must roll back permitted fingerprint changes if post-repair convergence fails. It must not use an rc.94 overwrite as rollback or repair.

## Definition of Done

`RUNTIME-CLOSURE-001` can advance only when:

1. the real rc.93 target was repaired through the approved bounded path;
2. deep compatibility reports PASS with zero mismatches and installed-data-plane mode;
3. post-install readiness assertion passes;
4. `/login` is reachable without the current runtime blocker;
5. evidence is recorded in state/handoff/registry;
6. no immutable trust plane was relaxed or rewritten merely to force PASS.

## Next stage

`CORE-QA-001 — Super Admin + Core Application Functional QA`.

After `CORE-QA-001`, the revised builder-first plan proceeds through `AI-GOV-AUTOMATION-100`, `ADMIN-UX-CLOSURE-001`, `SECURITY-BASELINE-200` and `ARCH-BOUNDARY-100` before major website-platform expansion.
