# Nexora AI / Agent Handoff

Before planning, editing, auditing, packaging, or releasing Nexora:

1. Read `NEXORA_AI_PROJECT_STATE.md` in full.
2. Treat it as the canonical cross-session project state and execution ledger.
3. Preserve Nexora's architecture rules and source-vs-target completion semantics.
4. Do not repeat completed work unless regression evidence exists.
5. Continue from the exact `NEXT ACTION` recorded in the ledger unless the user explicitly changes priority.
6. After every meaningful implementation, audit, packaging, target-verification, or release pass, update `NEXORA_AI_PROJECT_STATE.md` and append a history entry. Never delete prior history.
7. Do not mark source/static work as target-verified without real target evidence.
8. Keep final C1-C6/release certification late; product usability and runtime closure come first.
9. Inspect open GitHub issues at the start and end of every meaningful pass, and solve applicable defects alongside roadmap work.
10. A pull request may leave draft state only when its required source CI and real-target gates are satisfied. Once a PR is genuinely final/merge-ready, mark it Ready for review and merge it without waiting for a separate merge confirmation. Never merge a target-unverified or failing PR.

When using GitHub, prefer a development branch + pull request for meaningful source changes. Do not push unverified runtime code directly to `main`.
