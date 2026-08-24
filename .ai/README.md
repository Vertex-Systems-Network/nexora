# Nexora AI Control Plane

This directory is the deterministic execution control plane for AI-assisted Nexora development.

Its purpose is simple: a new AI session must be able to discover what Nexora is, what already exists, what is only planned, what is currently blocked, what stage is active, what must be verified, and exactly what comes next without reconstructing project state from chat history.

## Authority and precedence

For active development state, use this order:

1. `.ai/state.json` — canonical current execution state.
2. `.ai/handoff/current.md` — human-readable current handoff.
3. `.ai/roadmap/stages.md` — canonical stage sequencing and gates.
4. `.ai/roadmap/systems.md` — documented system inventory and implementation status.
5. `ARCHITECTURE.md` + `SECURITY.md` — architecture/security constitution.
6. `docs/NEXORA_PLAN_STATUS.md` — historical/master milestone source.
7. `NEXORA_AI_PROJECT_STATE.md` — legacy operational ledger and historical evidence.

If two historical documents use the same `N1.x` label for different scopes, never guess. Resolve through `.ai/roadmap/legacy-aliases.md` and the stable semantic stage ID.

## Status vocabulary

Only these execution statuses may be used in `.ai`:

- `SOURCE_DONE` — implementation exists and source/static checks are satisfied.
- `TARGET_VERIFIED` — the behavior was executed successfully on the real target.
- `PARTIAL` — meaningful implementation exists but product/end-to-end closure is incomplete.
- `BLOCKED` — a known blocker prevents the next gate.
- `PLANNED` — approved but not implemented.
- `EXTERNAL` — intentionally outside Core and delivered as an installable package family.
- `DEFERRED_CERTIFICATION` — certification intentionally postponed until product usability is closed.

Never translate a historical `DONE` label into `TARGET_VERIFIED` unless real-target evidence exists.

## Required startup protocol

Every agent must:

1. Read `AGENTS.md` and this file.
2. Read `state.json` and compare its `baseline_sha` / `last_verified_source_sha` with the current repository HEAD.
3. If HEAD differs, inspect the diff before continuing; do not assume state is fresh.
4. Read the active stage and its prerequisites in `roadmap/stages.md`.
5. Inspect the relevant implementation and tests.
6. Work only on the active stage unless the user explicitly changes the roadmap.

## Required completion protocol

At the end of every meaningful pass:

1. Record what changed and what was verified.
2. Keep source verification and real-target verification separate.
3. Update `state.json` with blocker, next action, evidence and stage status.
4. Update `handoff/current.md` so the next agent can resume without chat context.
5. Update roadmap files only when scope/status/dependencies actually changed.
6. Never delete historical evidence to make a stage appear complete.

## Stage integrity rule

A stage cannot be skipped merely because later code already exists. Existing downstream foundations are registered in `roadmap/systems.md`, but each active stage must still satisfy its own Definition of Done before the execution cursor advances.

## Phase-1 scope

This initial `.ai` control plane imports only systems and plans already documented in the repository. New product ideas, missing WordPress/Webflow/Wix/Shopify parity work, and audit-driven roadmap additions will be added in a later planning pass after this baseline is accepted.