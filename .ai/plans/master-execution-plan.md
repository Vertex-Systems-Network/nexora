# Nexora Master AI Execution Plan

## Objective

Deliver Nexora stage by stage without losing work, silently skipping prerequisites, duplicating completed work or declaring source-only work production-complete.

This plan is the execution method for `.ai/roadmap/stages.md`; the stage graph remains the canonical sequence.

## Zero-skip rule

The execution cursor may advance only when the current stage satisfies its applicable Definition of Done and evidence is recorded.

Later source code does not waive earlier gates.

If a later system already exists as a foundation, its stage becomes a closure/refactor/verification pass rather than being skipped.

## Before every stage

The AI must create/update `.ai/plans/active.md` with:

- parent stable stage ID;
- exact objective;
- imported existing implementation;
- gaps found by code/tests/docs inspection;
- dependencies and their evidence;
- scope and explicit non-scope;
- architecture/contracts;
- data model/migrations;
- permissions/capabilities;
- backend services;
- Admin/Studio/frontend UX;
- extension surfaces;
- public API/headless surfaces;
- AI tool surfaces;
- security/threat analysis;
- tests/evals;
- target verification commands/workflows;
- rollback/recovery/upgrade impact;
- documentation updates;
- ordered execution chunks.

Coding does not start until that plan is internally coherent.

## Stage chunking

Large parent stages may be split into chunks such as:

```text
CONTENT-MODEL-200-A  contracts + schema model
CONTENT-MODEL-200-B  persistence + migrations
CONTENT-MODEL-200-C  Admin CRUD
CONTENT-MODEL-200-D  extension/API/AI registration
CONTENT-MODEL-200-E  Studio/query integration
CONTENT-MODEL-200-F  tests + target verification
```

Chunk suffixes are execution labels, not new canonical roadmap identities.

A parent stage remains active until all required chunks close.

## Per-chunk loop

For each chunk:

1. Re-read active plan and current state.
2. Inspect current source; never rely only on previous prose claims.
3. Implement the smallest architecture-correct slice.
4. Add or update migrations/contracts/tests in the same slice where applicable.
5. Run source/static/unit/integration checks available to the environment.
6. Fix regressions before proceeding.
7. Record evidence and changed behavior.
8. Update active plan checkboxes/status.
9. Continue to the next chunk only if the current chunk's postconditions are satisfied.

## Required cross-cutting checks

Every feature stage must explicitly decide whether each item applies:

- architecture boundary;
- tenancy/site scoping;
- auth/roles/permissions;
- extension capabilities;
- security/Sentinel;
- migrations/upgrade path;
- audit log;
- localization;
- accessibility;
- SEO/routing implications;
- caching/performance;
- API/headless exposure;
- AI tool exposure;
- import/export/configuration;
- rollback/recovery;
- observability;
- tests/evals;
- documentation.

`Not applicable` must be an explicit decision, not an omission.

## Definition of Done levels

### SOURCE_DONE

Requires applicable source contracts, migrations, backend/frontend implementation, tests/static checks and documentation to be coherent.

### TARGET_VERIFIED

Requires real environment/browser/operator evidence for behavior that cannot be proven statically.

### Stage closure

A stage is closed only when the required DoD level defined for that stage is satisfied. Product workflows generally require target evidence before final production release even if the execution cursor can temporarily move after a documented source-complete gate explicitly approved by the user.

## Failure handling

When blocked:

1. set state to `BLOCKED`;
2. record first/root blocker rather than a vague error list;
3. preserve successful evidence;
4. do not jump to unrelated roadmap stages;
5. fix the blocker or request an explicit user roadmap change;
6. add regression protection for repeated blocker classes.

## Architecture-change handling

When implementation conflicts with `ARCHITECTURE.md`:

- do not silently alter code to match whichever side is easier;
- identify whether the constitution or implementation is intentionally authoritative;
- create an ADR for a deliberate architecture decision;
- update architecture tests with the decision;
- preserve migration/backward-compatibility impact.

## Roadmap-change handling

A new feature request is processed as:

```text
request
→ capability classification
→ check existing matrix/system/stage
→ reuse existing stage OR define new stable ID
→ define dependencies
→ update capability matrix/stage graph
→ user-priority decision if it changes the cursor
→ implementation
```

Do not bury substantial new work inside an unrelated existing stage.

## AI execution safety

AI product development must obey the same rule as the future AI product runtime: structured plans before mutations, typed contracts, capabilities, validation and audit.

The development agent must not:

- disable security boundaries to make tests pass;
- call static validation real-target verification;
- edit migrations that have already shipped when an additive migration is required;
- create private Core shortcuts for first-party extensions;
- hard-code a vertical package into Core merely because it is easier;
- create parallel sources of truth for roadmap state;
- silently renumber historical milestones;
- overwrite target installations to conceal repair/upgrade problems.

## End-of-pass handoff

Before ending a meaningful pass:

1. update `.ai/state.json`;
2. update `.ai/handoff/current.md`;
3. update `.ai/plans/active.md` if a stage is active;
4. update capability/stage docs if scope changed;
5. record source checks and target evidence separately;
6. state the next exact action;
7. never delete historical evidence.

## Final release rule

`N2-STABLE-100` cannot be reached by percentage estimates. It requires the preceding production-quality gates and `RELEASE-CERT-100` evidence. Capability coverage is tracked by the matrix, not a vague global completion percentage.
