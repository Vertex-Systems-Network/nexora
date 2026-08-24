# Nexora Master AI Execution Plan

## Objective

Deliver Nexora stage by stage without losing work, silently skipping prerequisites, creating unplanned systems/packages, duplicating completed work or declaring source-only work production-complete.

This plan is the execution method for `.ai/roadmap/stages.md`. The stage graph controls sequencing; `.ai/registry/development-units.json` controls which implementation units are authorized/planned.

## Zero-skip + zero-hidden-work rule

The execution cursor may advance only when the current stage satisfies its applicable Definition of Done and evidence is recorded.

Later source code does not waive earlier gates.

A new system/module/feature/package/AI/ops/security capability may not be hidden inside another stage or started directly from chat. It must pass `.ai/governance/development-intake.md` first.

## Before every new unit

1. Search `.ai/registry/development-units.json`.
2. If absent, classify/register it with stable unit ID.
3. Assign parent stage and release train.
4. Define dependencies/conflicts.
5. Decide Core vs first-party package vs external delivery.
6. Classify security risk and determine threat-model requirement.
7. Record architecture/data/migration/authorization/privacy/UI/API/theme/Studio/extension/AI/observability/performance impacts.
8. Define acceptance criteria, tests, target verification and rollback/recovery.
9. Add/update roadmap/capability docs if scope is new.
10. Create/update `.ai/plans/active.md`.
11. Only then implement.

An explicitly requested user feature may pass through this gate without asking the user to repeat the same instruction. An AI-discovered optional idea may be registered as `PROPOSED`, but may not be silently implemented unless required by approved active scope.

## Before every stage

The AI must create/update `.ai/plans/active.md` from `.ai/plans/plan-template.md` with:

- parent stable stage ID;
- registered development-unit IDs;
- release train;
- exact objective and acceptance criteria;
- imported existing implementation;
- gaps found by code/tests/docs inspection;
- dependencies and their evidence;
- scope and explicit non-scope;
- architecture/contracts/ADR impact;
- data model/migrations/fresh-install/upgrade/backfill impact;
- roles/permissions/runtime capabilities/tenancy;
- security risk/threat model/reviewer requirements;
- privacy/consent/retention/export/delete impact;
- backend services;
- Admin/Studio/frontend UX and accessibility;
- extension/package/theme surfaces;
- public API/headless/webhook/SDK surfaces;
- AI read/draft/execute/tools/approval/evals;
- performance/cache/delivery impact;
- observability/audit;
- tests/evals;
- target verification commands/workflows;
- rollback/recovery/upgrade compatibility;
- documentation/handoff updates;
- ordered execution chunks.

Coding does not start until that plan is internally coherent.

## Package-specific planning

Before creating an Extension/App/Integration/Studio Pack/Theme, the plan must define:

- stable package unit ID and family;
- manifest identity/version/compatibility policy;
- public Nexora contracts only;
- declared runtime capabilities;
- runtime mode (`declarative` preferred, `trusted-php` exceptional);
- migration policy (`none` or `forward-only` under current contracts);
- network/filesystem/secret access;
- Admin/Studio/theme/API slots;
- Sentinel/Supply Chain requirements;
- install/activate/deactivate/update/rollback/uninstall lifecycle;
- compatibility/regression matrix.

First-party status never grants private Core shortcuts.

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

Chunk suffixes are execution labels, not new canonical roadmap identities. A parent stage remains active until all required chunks close.

## Per-chunk loop

1. Re-read current state, active plan and registered unit(s).
2. Inspect current source; never rely only on previous prose claims.
3. Implement the smallest architecture-correct slice.
4. Add/update migrations/contracts/tests/security controls in the same slice where applicable.
5. Run source/static/unit/integration/security checks available to the environment.
6. Fix regressions before proceeding.
7. Record evidence and changed behavior.
8. Update active plan and unit status/evidence.
9. Continue only when current chunk postconditions are satisfied.

## Required cross-cutting checks

Every unit must explicitly decide whether each applies:

- architecture boundary / ADR;
- tenancy/site scoping;
- auth/roles/permissions;
- extension/runtime capabilities;
- security/Sentinel/threat model;
- privacy/consent/retention;
- migrations/fresh install/upgrade/backfill;
- audit log;
- localization;
- accessibility;
- SEO/AEO/routing implications;
- caching/performance;
- API/headless/webhook/SDK exposure;
- theme/Studio/extension surface;
- AI read/draft/execute/tool exposure;
- import/export/configuration;
- rollback/recovery;
- observability;
- tests/evals;
- documentation.

`Not applicable` must be an explicit decision, not an omission.

## Security gates

Security is continuous.

- `SECURITY-BASELINE-200` executes early, before large platform expansion.
- Every unit receives risk class.
- `high` and `critical` units require threat modeling.
- auth/tenancy/public-write/executable-package/secret/network/payment/destructive/AI-tool units require explicit security review.
- `SENTINEL-200` later adds advanced package vulnerability/revocation/isolation capabilities but does not replace earlier security controls.

AI-generated code is treated as untrusted contributor output until tests/review/evidence pass.

## Independent review rule

The same AI may plan and implement work, but self-asserted correctness is not sufficient certification for critical boundaries.

High-risk architecture/security/AI execution/package-runtime changes require at least one independent review pass or reviewer context plus automated evidence. Final target/release claims require real execution evidence where applicable.

## Definition of Done levels

### SOURCE_DONE

Requires applicable source contracts, migrations, backend/frontend implementation, architecture/security checks, tests/static checks and documentation to be coherent.

### TARGET_VERIFIED

Requires real environment/browser/operator evidence for behavior that cannot be proven statically.

### Stage closure

A stage closes only when its required DoD level is satisfied. Product workflows generally require target evidence before stable production claims.

## Failure handling

When blocked:

1. set state/unit to `BLOCKED`;
2. record the first/root blocker, not a vague list;
3. preserve successful evidence;
4. do not jump to unrelated roadmap stages;
5. fix blocker or obtain explicit user roadmap change;
6. add regression protection for repeated blocker classes.

## Architecture-change handling

When implementation conflicts with `ARCHITECTURE.md`:

- do not silently choose the easiest side;
- identify intended authority;
- create/update ADR for deliberate architecture change;
- update architecture tests;
- preserve migration/backward-compatibility/security impact.

## Roadmap-change handling

A new feature/request/gap is processed as:

```text
request / discovered gap
-> intake classification
-> registry search
-> reuse existing unit OR create stable unit ID
-> map stage + release train + dependencies
-> architecture/security/data/API/AI impact plan
-> update roadmap/capability docs when new
-> update active plan
-> implementation
```

If changing priority would move the active cursor, that requires explicit user priority change. Do not bury substantial new work inside unrelated stages.

## AI execution safety

Development AI and future Nexora AI runtime follow the same philosophy: structured plan before mutation, typed contracts, least privilege, validation, approval where required, audit and recovery.

The development agent must not:

- disable security boundaries to make tests pass;
- call static validation real-target verification;
- edit shipped migrations when additive migration is required;
- create private Core shortcuts for first-party packages;
- hard-code vertical products into Core merely because it is easier;
- create parallel roadmap/state sources of truth;
- silently renumber historical milestones;
- overwrite target installations to conceal repair/upgrade problems;
- implement an unregistered unit;
- silently promote AI-discovered optional ideas to implementation;
- grant AI unrestricted shell/database/filesystem/secret/network powers as product features.

## Release-train sequencing

The default commercial sequence is:

1. **Builder Beta** — secure CMS/site-builder kernel and professional publishing workflow.
2. **Pro** — AI-native design/content/DX, AEO, APIs, migration, experimentation and interoperability.
3. **Platform** — marketplace, commerce, portals, collaboration, managed cloud, enterprise and advanced runtime security/operations.
4. **Production** — performance, accessibility and final exact-source/target certification.

Do not block Builder Beta on deep CRM/enterprise/cloud productization.

## End-of-pass handoff

Before ending a meaningful pass:

1. update affected registry unit status/evidence;
2. update `.ai/state.json`;
3. update `.ai/handoff/current.md`;
4. update `.ai/plans/active.md`;
5. update capability/stage/release/security docs if scope changed;
6. record source and target evidence separately;
7. state next exact action;
8. never delete historical evidence.

## Final release rule

`N2-STABLE-100` cannot be reached by percentage estimates. It requires preceding production gates and `RELEASE-CERT-100` evidence. Capability coverage is tracked by the registry/matrix, not a vague global completion percentage.
