# Nexora AI Control Plane

This directory is the deterministic execution control plane for AI-assisted Nexora development.

Its purpose is simple: a new AI session must be able to discover what Nexora is, what already exists, what is only planned, what capabilities are still missing, what development units are authorized, what stage is active, what must be verified, and exactly what comes next without reconstructing project state from chat history.

## Authority and precedence

For active development state, use this order:

1. `.ai/state.json` — canonical current execution state.
2. `.ai/handoff/current.md` — human-readable current handoff.
3. `.ai/roadmap/stages.md` — canonical stable stage sequencing/dependency graph.
4. `.ai/governance/development-intake.md` — mandatory pre-planning/intake rules for every new system/module/feature/package/AI unit.
5. `.ai/registry/development-units.json` — canonical registry of pre-planned implementation units.
6. `.ai/plans/active.md` — current executable plan.
7. `.ai/plans/plan-template.md` — mandatory planning fields for substantial work.
8. `.ai/plans/master-execution-plan.md` — zero-skip execution protocol.
9. `.ai/roadmap/release-trains.md` — Builder Beta / Pro / Platform / Production product gates.
10. `.ai/roadmap/capability-matrix.md` — platform capability/gap inventory.
11. `.ai/roadmap/systems.md` — documented existing/built/foundation systems.
12. `.ai/roadmap/future-systems.md` — accepted future systems.
13. `.ai/security/security-program.md` — continuous security baseline/advanced-security contract.
14. `.ai/roadmap/competitive-benchmark.md` — external platform concepts used as benchmarks.
15. `.ai/architecture/ai-platform.md` — product-level AI-native architecture contract.
16. `.ai/design/ai-design-professional.md` — AI Design Professional / Studio contract.
17. `ARCHITECTURE.md` + `SECURITY.md` — architecture/security constitution.
18. `docs/NEXORA_PLAN_STATUS.md` — historical/master milestone source.
19. `NEXORA_AI_PROJECT_STATE.md` — legacy operational ledger/historical evidence.

If historical documents use the same `N1.x` label for different scopes, never guess. Resolve through `.ai/roadmap/legacy-aliases.md` and use stable semantic stage/unit IDs.

## Execution status vocabulary

- `SOURCE_DONE` — implementation exists and source/static checks are satisfied.
- `TARGET_VERIFIED` — behavior executed successfully on the real target.
- `PARTIAL` — meaningful implementation exists but end-to-end closure is incomplete.
- `BLOCKED` — a known blocker prevents the next gate.
- `PLANNED` — approved but not implemented.
- `EXTERNAL` — intentionally outside Core and delivered as package.
- `DEFERRED_CERTIFICATION` — certification postponed until product usability is closed.

Development-unit planning additionally uses `IDEA`, `PROPOSED`, `ACTIVE`, `DEFERRED`, `REJECTED` and `DEPRECATED` as defined by the intake protocol/schema.

Never translate historical `DONE` into `TARGET_VERIFIED` without real-target evidence.

## Required startup protocol

Every agent must:

1. Read `AGENTS.md` and this file.
2. Read `state.json`; compare baseline/verified source references with current HEAD.
3. If HEAD differs, inspect the diff before trusting state/handoff claims.
4. Read active stage/prerequisites in `roadmap/stages.md`.
5. Read `governance/development-intake.md`.
6. Resolve requested work to development-unit IDs in `registry/development-units.json`.
7. Read `plans/active.md`, `plans/plan-template.md` and `plans/master-execution-plan.md`.
8. Read relevant release-train/capability/system/security/architecture files.
9. Inspect relevant implementation and tests.
10. Work only on the active stage unless user explicitly changes priority.

## Mandatory pre-planned development rule

**No new system, module, feature, extension, app, integration, studio pack, theme, AI tool/agent, migration adapter, operations capability or security control may begin implementation unless it is registered and planned first.**

If the unit is missing:

1. classify it;
2. create stable ID;
3. add registry entry;
4. map parent stage/release train/dependencies;
5. plan architecture/data/migrations/permissions/tenancy/security/privacy/UI/API/theme/Studio/extension/AI/observability/performance/tests/verification/rollback;
6. create/update active plan;
7. only then implement.

An explicitly requested user feature can move through this planning gate without asking the user to repeat the same request. A feature discovered independently by AI may be registered/proposed, but must not be silently implemented unless required by approved active scope.

## Package planning rule

Every Extension/App/Integration/Studio Pack/Theme must be planned before package creation with package family, compatibility, public contracts, capabilities, runtime mode, migrations, lifecycle, Sentinel/Supply Chain, slots/surfaces, security and rollback behavior.

First-party packages receive no private Core exemption.

## Security rule

Security is continuous, not a final audit. Every development unit receives a risk class. `high` and `critical` units require an explicit threat model; security/architecture/public-contract changes require review evidence.

`SECURITY-BASELINE-200` is an early mandatory gate. `SENTINEL-200` later adds advanced package/runtime isolation and vulnerability/revocation controls.

## Required pre-implementation active plan

Before coding a substantial stage/chunk, `plans/active.md` must include registered unit IDs plus exact scope, dependencies, architecture, data/migrations, permissions/tenancy, security/threat model, privacy, UI/accessibility, extension/API/theme/Studio/AI surfaces, observability/performance, tests/evals, target verification and rollback/recovery behavior.

No substantial implementation may rely on a chat-only plan.

## Required completion protocol

At the end of every meaningful pass:

1. record what changed/verified;
2. keep source vs target verification separate;
3. update affected development-unit registry status/evidence;
4. update `state.json`;
5. update `handoff/current.md`;
6. update `plans/active.md`;
7. update roadmap/capability/architecture/security docs when scope changed;
8. never delete historical evidence to make work look complete.

## Phase 1 — imported repository truth

Imported/normalized already documented systems/plans, stable IDs, machine-readable state, handoff and Definition of Done.

## Phase 2 — capability-gap + AI-native expansion

Added dynamic content/taxonomy/query/routing/navigation/theme/extension/site-builder/API/AI/migration/market/security expansion required for Nexora's stated market target.

## Phase 3 — pre-planned development operating model

Phase 3 converts the roadmap into a stricter development operating model:

- mandatory development-unit intake/registry before implementation;
- stable IDs for systems/modules/features/packages/AI/ops/security work;
- Builder Beta -> Pro -> Platform -> Production release trains;
- early continuous security baseline;
- security threat-model template and future CI enforcement;
- preview/staging/branching/release workflow;
- templates/patterns/starter ecosystem;
- privacy/consent;
- external agent interoperability;
- AEO/AI-readable web expansion;
- experimentation/personalization;
- design/Figma import;
- capability-bounded App Runtime;
- optional Managed Cloud;
- builder-first sequencing before deep CRM/enterprise productization.

Planning work does not bypass the active real-target blocker. The current execution cursor remains `RUNTIME-CLOSURE-001`.
