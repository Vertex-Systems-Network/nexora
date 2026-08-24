# Nexora AI Control Plane

This directory is the deterministic execution control plane for AI-assisted Nexora development.

Its purpose is simple: a new AI session must be able to discover what Nexora is, what already exists, what is only planned, what capabilities are still missing, what stage is active, what must be verified, and exactly what comes next without reconstructing project state from chat history.

## Authority and precedence

For active development state, use this order:

1. `.ai/state.json` — canonical current execution state.
2. `.ai/handoff/current.md` — human-readable current handoff.
3. `.ai/roadmap/stages.md` — canonical stable stage sequencing and dependency gates.
4. `.ai/plans/master-execution-plan.md` — zero-skip execution protocol.
5. `.ai/roadmap/capability-matrix.md` — complete platform capability/gap registry.
6. `.ai/roadmap/systems.md` — documented existing/built/foundation system inventory.
7. `.ai/roadmap/competitive-benchmark.md` — external platform concepts used as capability benchmarks.
8. `.ai/architecture/ai-platform.md` — product-level AI-native architecture contract.
9. `.ai/design/ai-design-professional.md` — AI Design Professional / Studio design contract.
10. `ARCHITECTURE.md` + `SECURITY.md` — architecture/security constitution.
11. `docs/NEXORA_PLAN_STATUS.md` — historical/master milestone source.
12. `NEXORA_AI_PROJECT_STATE.md` — legacy operational ledger and historical evidence.

If two historical documents use the same `N1.x` label for different scopes, never guess. Resolve through `.ai/roadmap/legacy-aliases.md` and use the stable semantic stage ID.

## Status vocabulary

Only these execution statuses may be used in `.ai`:

- `SOURCE_DONE` — implementation exists and source/static checks are satisfied.
- `TARGET_VERIFIED` — the behavior was executed successfully on the real target.
- `PARTIAL` — meaningful implementation exists but product/end-to-end closure is incomplete.
- `BLOCKED` — a known blocker prevents the next gate.
- `PLANNED` — approved but not implemented.
- `EXTERNAL` — intentionally outside Core and delivered as an installable package family.
- `DEFERRED_CERTIFICATION` — certification intentionally postponed until product usability is closed.

The capability matrix also uses planning descriptors such as `FOUNDATION`, `LEGACY_PLANNED` and `NEW_REQUIRED`. These describe origin/completeness context; they do not replace execution statuses.

Never translate a historical `DONE` label into `TARGET_VERIFIED` unless real-target evidence exists.

## Required startup protocol

Every agent must:

1. Read `AGENTS.md` and this file.
2. Read `state.json` and compare its baseline/verified source references with current repository HEAD.
3. If HEAD differs, inspect the diff before continuing; do not assume state is fresh.
4. Read the active stage and its prerequisites in `roadmap/stages.md`.
5. Read `plans/master-execution-plan.md`.
6. Inspect the relevant rows in `roadmap/capability-matrix.md`.
7. Inspect the relevant implementation and tests.
8. Work only on the active stage unless the user explicitly changes roadmap priority.

## Required pre-implementation plan

Before coding a new stage or substantial stage chunk, create/update `.ai/plans/active.md` with exact scope, dependencies, existing implementation, architecture, data/migrations, permissions, UI, extension/API/AI surfaces, security, tests, target verification and rollback/recovery behavior.

No large stage should be implemented from a chat-only plan.

## Required completion protocol

At the end of every meaningful pass:

1. Record what changed and what was verified.
2. Keep source verification and real-target verification separate.
3. Update `state.json` with blocker, next action, evidence and stage status.
4. Update `handoff/current.md` so the next agent can resume without chat context.
5. Update `plans/active.md` for active-stage progress.
6. Update roadmap/capability files only when scope/status/dependencies actually changed.
7. Never delete historical evidence to make a stage appear complete.

## Stage integrity rule

A stage cannot be skipped merely because later code already exists. Existing downstream foundations are registered in `roadmap/systems.md`, but each active stage must still satisfy its own Definition of Done before the execution cursor advances.

New audit-derived systems are not optional merely because they were absent from the historical roadmap. Once accepted into the canonical capability matrix/stage graph, they are part of the product plan.

## Phase 1 — imported repository truth

Phase 1 imported and normalized systems/plans already documented in the repository, established stable semantic stage IDs, machine-readable state, handoff and Definition of Done.

## Phase 2 — capability-gap and AI-native roadmap expansion

Phase 2 adds the missing platform work required by Nexora's stated product goal, including explicit:

- architecture-boundary reconciliation;
- complete dynamic content model;
- generic taxonomy platform;
- typed query engine;
- permalink/routing/redirect platform;
- public navigation/menu engine;
- Theme Contract 2.0/template hierarchy;
- typed Extension SDK 2.0 surfaces;
- Site Builder/Theme Studio 2.0 expansion;
- API/headless/config-as-code platform;
- governed AI Kernel;
- AI content/design/developer product layers;
- expanded commerce/marketplace/security/migration/operations requirements.

The current execution cursor is intentionally unchanged by planning work: real-target runtime closure remains first.
