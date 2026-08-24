# Nexora Agent Entry Point

Every AI agent, coding agent, reviewer, planner, or automation working in this repository MUST begin here.

## Mandatory startup sequence

1. Read `.ai/README.md`.
2. Read `.ai/state.json`.
3. Read `.ai/roadmap/systems.md`.
4. Read `.ai/roadmap/stages.md`.
5. Read `.ai/handoff/current.md`.
6. Read `ARCHITECTURE.md` and `SECURITY.md` before changing architecture, runtime trust, extensions, themes, tenancy, installer, or deployment behavior.
7. Inspect the current Git HEAD and relevant source before trusting any historical completion claim.

## Execution rule

Work only on the active stage in `.ai/state.json` unless the user explicitly changes priority. Do not skip a stage, silently reopen a completed stage, or mark target behavior complete from source/static checks alone.

All meaningful passes must leave a deterministic handoff by updating `.ai/state.json` and `.ai/handoff/current.md`. Roadmap or architecture changes must also update the relevant `.ai/roadmap/` or architecture documentation.

The legacy `NEXORA_AI_PROJECT_STATE.md` remains historical operational evidence during this migration. Where current execution state differs, `.ai/state.json` is the canonical active-state record.