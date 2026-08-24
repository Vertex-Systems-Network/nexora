# Nexora Repository Instructions

Before planning, editing, reviewing, testing or certifying this repository:

1. Read `/AGENTS.md`.
2. Read `/.ai/README.md`.
3. Read `/.ai/state.json` and `/.ai/handoff/current.md`.
4. Resolve the active stage through `/.ai/roadmap/stages.md` and release train through `/.ai/roadmap/release-trains.md`.
5. Read `/.ai/governance/development-intake.md`.
6. Resolve requested work to registered IDs in `/.ai/registry/development-units.json`.
7. Read `/.ai/plans/master-execution-plan.md`, `/.ai/plans/plan-template.md` and current `/.ai/plans/active.md`.
8. Check relevant capability/system/future-system/security docs.
9. Use `/.ai/roadmap/legacy-aliases.md` before interpreting historical `N1.x` labels.
10. Follow `/.ai/quality/definition-of-done.md` and `/.ai/quality/verification-matrix.md`.
11. Preserve `/ARCHITECTURE.md` and `/SECURITY.md` boundaries.
12. For security-sensitive work follow `/.ai/security/security-program.md` and use the threat-model template when required.
13. For AI product work follow `/.ai/architecture/ai-platform.md`; for AI design/Studio work follow `/.ai/design/ai-design-professional.md`.

## Mandatory planning gate

Do not implement an unregistered system, module, feature, extension, app, integration, studio pack, theme, AI tool/agent, migration adapter, operations capability or security control.

If missing, register and plan it first with stable ID, stage/release train, dependencies, architecture/data/migration/permission/tenancy/security/privacy/UI/API/theme/Studio/AI/test/verification/rollback decisions and acceptance criteria. Then update the active plan before coding.

AI-discovered optional ideas may be registered as `PROPOSED` but may not be silently implemented unless required by already-approved active scope.

Do not skip stages, do not treat historical `DONE` labels as real-target verification, and do not mark runtime/browser behavior complete from source/static checks alone.

High/critical-risk units require threat modeling. First-party packages must use the same public contracts/capability/trust model as third-party packages.

At the end of meaningful work, update affected registry entries, `/.ai/state.json`, `/.ai/handoff/current.md` and the active plan so another agent can resume without chat context.
