# Active Plan — RUNTIME-CLOSURE-001

## Stage

`RUNTIME-CLOSURE-001 — Installation + Runtime Closure`

Status: `BLOCKED` pending real-target execution.

## Objective

Close the currently installed rc.93 post-install runtime identity mismatch without disguising it as an rc.94 upgrade, prove compatibility/readiness on the real Laragon target, then advance to `CORE-QA-001`.

## Current evidence imported from the legacy handoff

Observed matching planes on the live rc.93 installation:

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

## Execution chunks

### RUNTIME-CLOSURE-001-A — safe rc.93 repair

- [ ] Confirm current live target path/version before mutation.
- [ ] Run the prepared external rc.93 Post-Install Identity Repair Pack.
- [ ] Require immutable identity-plane checks to pass before permitted fingerprint mutation.
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

- [ ] Open `/login` on the real target.
- [ ] Confirm the application reaches the login flow without runtime/tenant/bootstrap failure.
- [ ] Mark runtime stage evidence in `.ai/state.json` and handoff.
- [ ] Advance cursor to `CORE-QA-001` only when required target evidence passes.

## Cross-cutting decisions

- Architecture: no redesign expected; preserve existing runtime identity/trust model.
- Data/migrations: not a migration stage.
- Auth/permissions: only login handoff smoke is in scope; full auth QA belongs to `CORE-QA-001`.
- Extensions/themes: out of scope except ensuring runtime closure does not break their platform prerequisites.
- API/AI: not applicable to this repair stage.
- Security: fail closed; immutable planes may not be rewritten to force a PASS.
- Rollback: repair pack must roll back permitted fingerprint changes when convergence fails.
- Regression: any source fix discovered from this blocker class must include regression protection.

## Exit condition

`RUNTIME-CLOSURE-001` may advance only after real-target compatibility and post-install readiness are proven and `/login` is reachable without the current runtime blocker.

## Next stage

`CORE-QA-001 — Super Admin + Core Application Functional QA`.
