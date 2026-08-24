# Nexora Repository Instructions

Before planning, editing, reviewing, testing or certifying this repository:

1. Read `/AGENTS.md`.
2. Read `/.ai/README.md`.
3. Read `/.ai/state.json`.
4. Resolve the active stage through `/.ai/roadmap/stages.md`.
5. Use `/.ai/roadmap/legacy-aliases.md` before interpreting any historical `N1.x` label.
6. Follow `/.ai/quality/definition-of-done.md` and `/.ai/quality/verification-matrix.md`.
7. Preserve `/ARCHITECTURE.md` and `/SECURITY.md` boundaries.

Do not skip stages, do not treat historical `DONE` labels as real-target verification, and do not mark runtime/browser behavior complete from source/static checks alone.

At the end of meaningful work, update `/.ai/state.json` and `/.ai/handoff/current.md` so another agent can resume without chat context.