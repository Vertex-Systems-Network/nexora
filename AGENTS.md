# Nexora AI / Agent Handoff

Before planning, editing, auditing, packaging, or releasing Nexora:

1. Read `NEXORA_AI_PROJECT_STATE.md` in full. It is the append-only historical/cross-session ledger; do not delete or rewrite prior history merely because policy evolved.
2. Read `NEXORA_PROGRESS.md` in full and treat its **Current checkpoint** as authoritative for the present branch head, runner policy, active evidence and `NEXT ACTION`. If an older historical ledger entry conflicts with the current checkpoint, the current checkpoint wins and the history remains preserved as history.
3. Read `NEXORA_ACCESSIBILITY_CERTIFICATION_PLAN.md` before any UI/accessibility/browser/release work. W3C Nu HTML, W3C CSS, WAVE, WCAG/manual browser and assistive-technology evidence are mandatory C5 boundaries; never claim WAVE approval or infer target accessibility from source tests.
4. Treat `NEXORA_AI_PROJECT_STATE.md` as the canonical append-only execution history and `NEXORA_PROGRESS.md` as the canonical live execution state.
5. Preserve Nexora's architecture rules and source-vs-target completion semantics.
6. Do not repeat completed work unless regression evidence exists.
7. Continue from the exact `NEXT ACTION` recorded in the live progress dashboard unless the user explicitly changes priority.
8. After every meaningful implementation, fix, audit closure, CI correction, packaging change, target verification, issue closure, or release/certification apply, update `NEXORA_PROGRESS.md` immediately with the current head/evidence, active block state, weighted Project/Source/Target/Release Power where evidence changed, exact blockers, next action, and a new Apply Log entry.
9. After every meaningful implementation, audit, packaging, target-verification, or release pass, append a history entry to `NEXORA_AI_PROJECT_STATE.md` when practical. Never delete prior history. If connector editing limitations prevent a safe append without rewriting history, preserve the ledger and record the current state in `NEXORA_PROGRESS.md` + PR metadata instead of destructively replacing it.
10. Do not mark source/static work as target-verified without real target evidence.
11. Keep final C1-C6/release certification late; product usability and runtime closure come first.
12. Inspect open GitHub issues at the start and end of every meaningful pass, and solve applicable defects alongside roadmap work.
13. A pull request may leave draft state only when its required source CI and real-target gates are satisfied. Once a PR is genuinely final/merge-ready, mark it Ready for review and merge it without waiting for a separate merge confirmation. Never merge a target-unverified or failing PR.
14. Progress percentages are evidence-based. Never increase Target Power from source CI alone and never inflate Project Power merely because file/change volume is large.
15. W3C/WAVE target checks are fail-closed: required routes cannot be removed to hide failures; API credentials must never enter source/evidence/logs; W3C HTML and W3C CSS each require zero validation errors; WAVE requires zero Errors/Contrast Errors plus human Alert review; real browser/AT observation remains mandatory.

Current development execution QA uses GitHub-hosted runners as recorded in `NEXORA_PROGRESS.md`. Do not silently substitute historical self-hosted/local runner policy.

When using GitHub, prefer a development branch + pull request for meaningful source changes. Do not push unverified runtime code directly to `main`.
