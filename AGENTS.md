# Nexora Agent Entry Point

Every AI agent, coding agent, reviewer, planner or automation working in this repository MUST begin here.

## Mandatory startup sequence

1. Read `.ai/README.md`.
2. Read `.ai/state.json`.
3. Read `.ai/handoff/current.md`.
4. Read `.ai/roadmap/stages.md`.
5. Read `.ai/roadmap/release-trains.md`.
6. Read `.ai/governance/development-intake.md`.
7. Read `.ai/registry/development-units.json` and resolve the requested work to registered unit ID(s).
8. Read `.ai/plans/master-execution-plan.md`.
9. Read `.ai/plans/active.md` for the current stage.
10. Read the relevant rows/files in `.ai/roadmap/capability-matrix.md`, `.ai/roadmap/systems.md` and `.ai/roadmap/future-systems.md`.
11. Read `ARCHITECTURE.md` and `SECURITY.md` before changing architecture, runtime trust, extensions, themes, tenancy, installer, deployment, public API, security or AI execution behavior.
12. Read `.ai/security/security-program.md` for security-sensitive work; use `.ai/security/threat-model-template.md` when required.
13. Read `.ai/architecture/ai-platform.md` for AI product work and `.ai/design/ai-design-professional.md` for AI design/Studio work.
14. Inspect the current Git HEAD and relevant source/tests before trusting historical completion claims.

## Mandatory pre-planning rule

**Do not start implementation for an unregistered system/module/feature/extension/app/integration/studio-pack/theme/AI tool/AI agent/migration adapter/ops capability/security control.**

If requested work is not already represented in `.ai/registry/development-units.json`:

1. classify it using `.ai/governance/development-intake.md`;
2. create a stable development-unit ID;
3. add it to the registry as `PROPOSED` or `PLANNED`;
4. map it to a canonical stage/release train and dependencies;
5. perform architecture/data/permission/security/privacy/API/theme/Studio/AI/test/rollback impact planning;
6. create/update the active plan;
7. only then implement it.

If AI itself discovers an unrequested feature/gap, it may register/plan it but must not silently implement it unless it is required by the approved active scope or explicitly approved/promoted.

## Execution rule

Work only on the active stage in `.ai/state.json` unless the user explicitly changes priority. Do not skip a stage, silently reopen a completed stage, or mark target behavior complete from source/static checks alone.

Before substantial implementation, `.ai/plans/active.md` must contain the exact registered unit IDs, scope, dependencies, architecture/data/migration/permission/UI/API/theme/Studio/AI/security/privacy/test/verification/rollback decisions required by `.ai/plans/plan-template.md`.

High/critical-risk units require a threat model. Public-contract/security/tenancy/execution-model changes require architecture/security review evidence.

All meaningful passes must leave a deterministic handoff by updating `.ai/state.json`, `.ai/handoff/current.md`, the active plan and affected registry entries. Roadmap/capability/architecture/security changes must update the relevant `.ai` documents.

The legacy `NEXORA_AI_PROJECT_STATE.md` remains historical operational evidence during this migration. Where current execution state differs, `.ai/state.json` is the canonical active-state record. Historical `N1.x` names are aliases only; use stable semantic stage/unit IDs for new work.
