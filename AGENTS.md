# Nexora AI / Agent Handoff

Before planning, editing, auditing, packaging, or releasing Nexora:

1. Read `NEXORA_AI_PROJECT_STATE.md` in full.
2. Read `NEXORA_PROGRESS.md` in full and treat it as the mandatory human-readable live progress dashboard.
3. Treat `NEXORA_AI_PROJECT_STATE.md` as the canonical cross-session project state and append-only execution ledger.
4. Preserve Nexora's architecture rules and source-vs-target completion semantics.
5. Do not repeat completed work unless regression evidence exists.
6. Continue from the exact `NEXT ACTION` recorded in the ledger/progress dashboard unless the user explicitly changes priority.
7. After every meaningful implementation, fix, audit closure, CI correction, packaging change, target verification, issue closure, or release/certification apply, update `NEXORA_PROGRESS.md` immediately with the current head/evidence, active block state, weighted Project/Source/Target/Release Power where evidence changed, exact blockers, next action, and a new Apply Log entry.
8. After every meaningful implementation, audit, packaging, target-verification, or release pass, also update `NEXORA_AI_PROJECT_STATE.md` and append a history entry. Never delete prior history.
9. Do not mark source/static work as target-verified without real target evidence.
10. Keep final C1-C6/release certification late; product usability and runtime closure come first.
11. Inspect open GitHub issues at the start and end of every meaningful pass, and solve applicable defects alongside roadmap work.
12. A pull request may leave draft state only when its required source CI and real-target gates are satisfied. Once a PR is genuinely final/merge-ready, mark it Ready for review and merge it without waiting for a separate merge confirmation. Never merge a target-unverified or failing PR.
13. Progress percentages are evidence-based. Never increase Target Power from source CI alone and never inflate Project Power merely because many files changed.

When using GitHub, prefer a development branch + pull request for meaningful source changes. Do not push unverified runtime code directly to `main`.
