# Nexora Current AI Handoff

## Resume instruction

Always begin with `AGENTS.md`, `.ai/state.json`, this handoff, the canonical roadmap/registries, and `.ai/plans/active.md`. Then inspect current `main` HEAD, open PRs, exact active unit and exact target evidence before implementation.

## Current source context

- Canonical branch: `main`
- Baseline at start of current orchestration pass: `dffb238e655a1c474f4f7ce7e75c6eda004c0c32`
- Documented source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Control-plane revision: `7`
- Canonical stage count: `75`
- Existing canonical rc.93 repair tooling originated in PR #26

Historical SHAs are evidence references. Always re-read current HEAD.

## Governance invariants

- No future stage starts before the active stage is genuinely accepted.
- Source/CI evidence and real-target evidence remain separate.
- Existing defects use DMAIC/control evidence; high/critical work keeps FMEA/threat controls.
- AI-authored PASS prose is not runtime proof.
- Critical changes require exact-head independent review in addition to authoring tests/CI.
- No test/security/governance weakening merely to obtain PASS.
- Dependency, migration, permission, network, secret, trust-boundary or destructive scope deltas require re-plan before implementation.
- System Graph evidence classes remain distinct; static/inferred evidence is never runtime truth.

## Active stage

`RUNTIME-CLOSURE-001 — Installation + Runtime Closure`

Active unit:

`SYS-RUNTIME-IDENTITY`

Status:

**BLOCKED pending final real-target readiness + `/login` evidence.**

Do not start `CORE-QA-001` yet.

## Real target

```text
D:\laragon\www\nexora
installed release: 1.0.0-rc.93
```

## Live target evidence received on 2026-08-25

The operator supplied real command output from the target.

### rc.93 repair dry-run — PASS

Observed:

- exact running/installed `1.0.0-rc.93`;
- only `activation`, `environment`, `process`, `service` mismatched;
- dry-run performed no mutation.

### rc.93 bounded repair apply — PASS

Observed:

- only the four approved planes repaired;
- sealed installation lock changed from SHA-256 `5db5fbf7f33a5d901146544463547800a114acad80bb62053d17c7e88e069d88` to `1576c4212323c11dbb591a45a27988afa6e5c55a62982808af738e918828022a`;
- protected backup + repair receipt paths emitted;
- mutation completed through the approved bounded repair path.

### Independent deep compatibility — PASS

Observed:

```text
status=pass
mismatches=[]
runtime.compatible=true
runtime.mode=installed-data-plane
deployment_drift.status=pass
```

Environment, activation, service and process now match. Immutable/source/deployment/framework/data-plane/storage/host/resource/policy/dependency compatibility remained PASS.

The dependency runtime fingerprint is compatible. `reviewed dependency-lock attestation = missing` remains a separate release/dependency-governance item and must not be confused with the closed four-plane identity mismatch.

### Post-install handoff

First readiness assertion correctly reported:

```text
status=receipt-refresh-required
ready=false
runtime_ready=true
receipt_current=false
```

The old post-install receipt referenced the pre-repair installation-lock/activation identity.

The operator then ran:

```text
php artisan nexora:runtime:post-install-reconcile --confirm=RECONCILE
```

and received `status=pass` with a new receipt bound to the repaired installation lock.

Still missing for current-stage acceptance:

1. a **fresh** `php artisan nexora:runtime:post-install-status --assert-ready` after that reconcile, proving `status=pass`, `ready=true`, `runtime_ready=true`, `receipt_current=true`, `errors=[]`;
2. `/login` target evidence without runtime/tenant/bootstrap failure.

## Operator-automation gap discovered

The architecture correctly separated repair authorization, compatibility verification, readiness and receipt reconciliation, but after approval the operator had to chain those commands manually. That is a real control/UX gap.

Current branch work under the same active unit adds a **Runtime Recovery / Closure Orchestrator**. It is not a new roadmap stage or upgrade engine.

Planned/coded contract:

```text
Observe compatibility
→ if compatible, skip repair
→ else use only an approved version-specific adapter
→ independent compatibility re-check
→ assert readiness
→ auto-reconcile only receipt-refresh-required + runtime_ready=true
→ assert readiness again
→ verified GET /login
→ machine-readable recovery receipt
→ PASS / BLOCKED / FAIL
```

Mutation still requires one explicit confirmation:

```text
--apply --confirm=RECOVER-RUNTIME
```

The orchestrator must never install/update dependencies, run migrations, copy source, change versions, disable TLS verification or broaden unrelated mismatches.

Canonical files in the current implementation branch:

- `scripts/runtime-recovery-orchestrator.php`
- `tests/Unit/Certification/RuntimeRecoveryOrchestratorTest.php`
- `docs/runtime/RUNTIME_RECOVERY_ORCHESTRATOR.md`
- `package.json` → `runtime:recover`

## Current source-work acceptance requirements

Before merging the orchestrator:

1. exact-head certification preflight PASS;
2. Source Guard PASS;
3. unified source certification PASS;
4. frontend dependency compatibility PASS;
5. orchestrator regression contract PASS;
6. exact-head independent review for the critical recovery-control change;
7. no scope expansion outside `SYS-RUNTIME-IDENTITY`.

Source merge does **not** advance the runtime stage by itself.

## Exact target continuation after orchestrator source closure

The intended operator experience becomes one bounded command from a canonical source checkout:

```bat
npm run runtime:recover -- --target="D:\laragon\www\nexora" --apply --confirm=RECOVER-RUNTIME
```

Because this specific target is already repaired and reconciled, the orchestrator should observe compatibility PASS, skip the identity repair, assert final readiness, avoid unnecessary reconcile if the receipt is current, and attempt `/login` HTTP smoke.

If HTTP smoke is blocked only by local TLS trust/client reachability while runtime readiness passes, preserve the result as `BLOCKED`; do not disable TLS verification to force green.

## Next stage after genuine closure

Only after `RUNTIME-CLOSURE-001` becomes `TARGET_VERIFIED`:

`CORE-QA-001 — Super Admin + Core Application Functional QA`

Then canonical sequence remains:

`AI-GOV-AUTOMATION-100 → RESEARCH-DISCOVERY-100 → QUALITY-GOVERNANCE-100 → ADMIN-UX-CLOSURE-001 → SECURITY-BASELINE-200 → ARCH-BOUNDARY-100`.

Later Builder Beta reaches `SYSTEM-GRAPH-100` only when dependencies are satisfied; later Pro reaches `FLOW-INTELLIGENCE-200` only after its dependency graph is satisfied.

## Completion warning

Do not infer final target PASS from the successful repair, compatibility output, reconcile output, source CI or an orchestrator-authored receipt. Final readiness/current-receipt and `/login` evidence remain mandatory for the active stage.
