# AI-GOV-AUTOMATION-100 — Machine Enforcement Plan

This document pre-plans the future implementation that turns the `.ai` governance model from procedural documentation into CI-enforced repository policy.

## Objective

Create a deterministic validator, preferably integrated with Nexora's existing PHP certification/governance scripts, that fails CI when AI development control-plane invariants are violated.

No validator implementation is claimed by this document.

## Planned validator responsibilities

### 1. Schema validation

Validate:

- `.ai/state.json` against `.ai/schemas/state.schema.json`;
- `.ai/registry/development-units.json` against `.ai/schemas/development-registry.schema.json`;
- every unit against `.ai/schemas/development-unit.schema.json`;
- future machine-readable plan/evidence schemas when introduced.

### 2. Stable ID validation

Reject:

- duplicate development-unit IDs;
- duplicate canonical stage IDs;
- invalid unit/stage ID formats;
- renamed/remapped IDs that would break historical references without explicit migration/alias record.

### 3. Registry/stage consistency

For every non-external implementable unit:

- `parent_stage_id` must exist in canonical stage graph;
- release train must agree with stage/release-train plan;
- dependency references must resolve to known stages/units or explicitly approved external prerequisites;
- active units must belong to current active stage unless explicit parallel-track metadata exists.

### 4. Active-state consistency

Validate:

- `state.current_stage.id` exists;
- `state.active_development_units` exist in registry;
- active unit parent stage matches state current stage;
- `state.next_stage.id` exists;
- current/next-stage relationship is allowed by dependency graph;
- source vs target status vocabulary remains valid;
- state timestamp/revision format is valid.

### 5. Active-plan consistency

When product/runtime source changes occur outside governance-only files, require:

- `.ai/plans/active.md` exists and names current stage;
- active plan lists every active development-unit ID;
- plan contains mandatory sections from `.ai/plans/plan-template.md` or future machine-readable equivalent;
- high/critical units identify required threat-model evidence;
- acceptance criteria/verification/rollback are not empty.

A future machine-readable plan schema may replace Markdown section parsing when practical.

### 6. New-work detection

The validator should help detect suspicious hidden work, without pretending static filename mapping can fully understand intent.

Planned signals:

- changed Core/module/extension/theme/API/AI directories require at least one active registered unit;
- new installable package manifest requires a registered `EXT/APP/INT/SPK/THM` unit;
- new AI tool/agent registration requires registered `AIT/AIA` unit or explicitly registered parent unit;
- new public contract/API surface requires active plan architecture/API section and review marker;
- new migration requires active plan migration/rollback section;
- new permission/capability requires active plan authorization/security section.

The validator should fail on clear omissions and warn on ambiguous scope rather than inventing false certainty.

### 7. Threat-model/security gates

For units marked `high` or `critical`:

- `threat_model_required` must be true unless an explicit reviewed exception exists;
- active plan must reference the threat-model artifact/evidence before implementation completion;
- security checks/evidence must not be omitted at `SOURCE_DONE`.

For AI/package/auth/tenant/payment/destructive-operation units, stronger required sections/checks may be hard-coded as governance policy.

### 8. Completion/evidence checks

Before unit/stage may move to `SOURCE_DONE`:

- acceptance criteria must exist;
- required source/security/architecture tests must have evidence references;
- affected docs/state/handoff must be synchronized.

Before `TARGET_VERIFIED`:

- target evidence must be recorded;
- source-only evidence cannot satisfy the target gate.

### 9. Historical alias protection

Reject new roadmap/code/PR metadata that treats bare ambiguous historical `N1.x` labels as canonical execution IDs when a stable semantic ID exists.

Historical docs remain readable; new execution metadata must use stable IDs.

### 10. PR/CI integration

Target CI behavior:

```text
checkout
-> AI governance validator
-> architecture/security/source guards
-> dependency/security pipeline
-> tests/build
-> integration/browser/target workflows as applicable
```

A governance failure should be actionable and name:

- violated rule;
- affected file/unit/stage;
- required remediation.

## Planned implementation artifacts

Likely future artifacts, exact names subject to active-stage inspection:

- `scripts/ai-governance-check.php` or equivalent existing certification integration;
- fixture directory with both valid/invalid control-plane states;
- CI job `ai-governance`;
- unit tests for registry/stage/state dependency validation;
- optional generated report summarizing active stage/unit/risks/evidence.

Do not create a second authoritative state database; validator reads the `.ai` source-of-truth files.

## Failure cases that must be tested

- duplicate unit ID;
- unknown parent stage;
- active unit in wrong stage;
- next stage does not exist;
- high-risk unit without threat-model requirement;
- new package without registered package unit;
- new migration with missing migration plan;
- new AI tool without registered AI unit/parent;
- `TARGET_VERIFIED` without target evidence;
- stale active plan naming another stage;
- unrecognized historical `N1.x` used as new canonical ID;
- broken JSON/schema;
- missing handoff/state update when stage/unit status changes.

## Non-goals

The validator does not:

- prove code correctness;
- replace human/AI architecture review;
- replace security tests/threat modeling;
- infer all product intent from filenames;
- automatically approve new roadmap scope;
- treat passing governance checks as production certification.

## Exit condition

`AI-GOV-AUTOMATION-100` is complete when the validator is tested, integrated into required CI, blocks representative invalid fixtures/PR states, produces actionable failures and does not create a competing source of truth.
