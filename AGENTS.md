# Nexora Agent Entry Point

Every AI agent, coding agent, reviewer, planner, or automation working in this repository MUST begin here.

## Mandatory startup sequence

1. Read `.ai/README.md`.
2. Read `.ai/state.json`.
3. Read `.ai/handoff/current.md`.
4. Read `.ai/roadmap/stages.md`.
5. Read `.ai/plans/master-execution-plan.md`.
6. Read `.ai/plans/active.md` for the current stage.
7. Read the relevant rows in `.ai/roadmap/capability-matrix.md` and `.ai/roadmap/systems.md`.
8. Read `ARCHITECTURE.md` and `SECURITY.md` before changing architecture, runtime trust, extensions, themes, tenancy, installer, deployment, public API or AI execution behavior.
9. Read `.ai/architecture/ai-platform.md` for AI product work and `.ai/design/ai-design-professional.md` for AI design/Studio work.
10. Inspect the current Git HEAD and relevant source/tests before trusting any historical completion claim.

## Execution rule

Work only on the active stage in `.ai/state.json` unless the user explicitly changes priority. Do not skip a stage, silently reopen a completed stage, or mark target behavior complete from source/static checks alone.

Before substantial implementation, make sure `.ai/plans/active.md` contains the exact scope, dependencies, architecture/data/permission/UI/API/AI/security/test/verification/rollback decisions required by the master execution protocol.

All meaningful passes must leave a deterministic handoff by updating `.ai/state.json`, `.ai/handoff/current.md` and the active plan. Roadmap, capability or architecture changes must also update the relevant `.ai` documents.

The legacy `NEXORA_AI_PROJECT_STATE.md` remains historical operational evidence during this migration. Where current execution state differs, `.ai/state.json` is the canonical active-state record. Historical `N1.x` names are aliases only; use stable semantic stage IDs for new work.
