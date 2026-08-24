# Nexora Repository Instructions

Before planning, editing, reviewing, testing or certifying this repository:

1. Read `/AGENTS.md`.
2. Read `/.ai/README.md`.
3. Read `/.ai/state.json` and `/.ai/handoff/current.md`.
4. Resolve the active stage through `/.ai/roadmap/stages.md`.
5. Read `/.ai/plans/master-execution-plan.md` and the current `/.ai/plans/active.md`.
6. Check relevant capabilities in `/.ai/roadmap/capability-matrix.md` and existing foundations in `/.ai/roadmap/systems.md`.
7. Use `/.ai/roadmap/legacy-aliases.md` before interpreting any historical `N1.x` label.
8. Follow `/.ai/quality/definition-of-done.md` and `/.ai/quality/verification-matrix.md`.
9. Preserve `/ARCHITECTURE.md` and `/SECURITY.md` boundaries.
10. For AI product work, follow `/.ai/architecture/ai-platform.md`; for AI design/Studio work, follow `/.ai/design/ai-design-professional.md`.

Do not skip stages, do not treat historical `DONE` labels as real-target verification, and do not mark runtime/browser behavior complete from source/static checks alone.

Substantial implementation requires a coherent active plan covering dependencies, architecture, data/migrations, permissions, UI, extension/API/AI surfaces, security, tests, target verification and rollback/recovery.

At the end of meaningful work, update `/.ai/state.json`, `/.ai/handoff/current.md` and the active plan so another agent can resume without chat context.