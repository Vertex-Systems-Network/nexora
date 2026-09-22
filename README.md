# Nexora

**Current development candidate:** `1.0.0-rc.94` — installer protocol `v5.29`.

> **Canonical current status (2026-09-22):** protected `main` is `8972632c230117c377c1e0e5d7219352518acda7`. Runtime/source implementation, hardened release certification, repository hygiene, dependency locks and regression fixes are integrated on main. PR #30 exact head `20b75291c761e3c52cb3f89845be76fc06ce27a0` passed certification #978 / `35662411404` and has no unique product/runtime/build-config diff after scope scrub; its remaining evidence/state is consolidated into governance PR #82 rather than merging a second runtime carrier. PR #82 previous exact head `09e548a067a75295f3110a045df567c90718039f` passed #976 / `35661610368`; this material consolidation creates a new head requiring fresh exact-head CI and independent review. The active stage remains `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` — **BLOCKED** on real-target evidence and credential rotation. Source integration is not target verification or release completion.

## AI development startup gate

Every AI development session MUST execute this gate before unrelated new implementation begins:

1. Inspect all live open **Issues** and all open **PRs/MRs** first.
2. Verify each candidate's exact head, draft/mergeability state, required reviews, unresolved threads and CI/checks.
3. Merge every safe, approved, green, non-stale ready PR/MR before starting new development.
4. Resolve or explicitly document blocked, draft, red, stale or review-pending items; never bypass them silently.
5. Re-read protected `main` and the canonical AI state/handoff after accepted merges.
6. Only then select and start the next authorized development unit.
7. Before reporting any material milestone complete, blocked, verifying or waiting, update the fixed **AI-Native Progress Ledger** below with exact repository-backed state. Missing this README sync means the milestone is not fully complete.

This rule applies on every AI development start, including work resumed from an existing plan.

## AI-Native Progress Ledger

- **Observed:** 2026-09-22
- **Protected main:** `8972632c230117c377c1e0e5d7219352518acda7`
- **Active stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` — **BLOCKED**
- **Open remediation Issues:** #72, #74
- **Source implementation:** integrated on protected main
- **PR #30:** `20b75291c761e3c52cb3f89845be76fc06ce27a0` — #978 / `35662411404` **SUCCESS**; no unique runtime/product/build-config diff remains; evidence/state consolidated into #82; not a second merge path
- **PR #82:** prior head `09e548a067a75295f3110a045df567c90718039f` — #976 / `35661610368` **SUCCESS**; consolidated head is **VERIFYING**
- **Current blockers:** fresh PR #82 exact-head CI + independent review; real-target readiness/current receipt; exact target↔web identity; authoritative `/login`; credential rotation after PR #71
- **Next safe action:** certify/review consolidated PR #82, merge only with expected-head protection when green/approved, then continue real-target closure; do not start CORE-QA before TARGET_VERIFIED
- **Current module progress:** `[??????????] N/A — canonical numeric metric unavailable`
- **Overall progress:** `[??????????] N/A — canonical numeric metric unavailable`

## Current runtime closure

- Runtime recovery/orchestrator implementation, target containment, bounded child execution, mutation evidence and readiness/identity controls are present on protected main.
- PR #30 is source-superseded after scope scrub; final exact head #978 is green and remaining evidence/state is consolidated into PR #82.
- Source status is **SOURCE_DONE** for integrated runtime implementation; target status remains **BLOCKED**.
- Still required: real-target final readiness/current receipt, exact target↔web one-time proof, authoritative `/login` on the same origin, and credential rotation after PR #71.
- `CORE-QA-001` MUST NOT start until `RUNTIME-CLOSURE-001` is `TARGET_VERIFIED`.

## Current dependency closure

- Protected main contains current deterministic Composer/npm lockfiles and hardened certification workflow.
- Historical Issue #52 review/integration evidence is retained under `.ai/evidence/` through the consolidated governance carrier.
- Historical exact-artifact attestations remain audit evidence; they do not override current protected-main lock identity or prove installed-target consumption.
- Future dependency changes require fresh supply-chain intake and exact-head evidence.

## Status boundary

Nexora has extensive source implementation and passing hosted certification evidence, but hosted source/CI evidence does **not** by itself establish target verification, release certification or production readiness. Any progress report must keep **Source**, **Target** and **Release** states distinct.

## Current next sequence

1. Run fresh exact-head release certification for consolidated PR #82.
2. Obtain distinct independent exact-head review for PR #82.
3. Merge PR #82 only when CI/review pass and expected head is unchanged.
4. Obtain fresh real-target readiness/current-receipt evidence.
5. Prove exact target-local CLI↔web identity and authoritative `/login` on the same origin.
6. Rotate the credential exposed by PR #71 through an authorized target/provider path.
7. Mark `RUNTIME-CLOSURE-001` TARGET_VERIFIED only after all target gates pass; only then start `CORE-QA-001`.

## Core stack

- Laravel 13 / PHP 8.3+
- Inertia v3
- React 19 + strict TypeScript
- Vite 8 + Tailwind CSS 4
- PHPUnit + Vitest
- Relational primary-database abstraction with multi-engine certification gates
- Sentinel/package supply-chain and runtime fail-closed controls

## Historical README / feature record

The previous long-form README, including historical N0.x/N1.x feature notes, deployment instructions and release-development record, is preserved byte-for-byte at [`docs/README_HISTORY_PRE_STATUS_SYNC.md`](docs/README_HISTORY_PRE_STATUS_SYNC.md). It is historical/reference material; when any historical statement conflicts with the canonical current status above, the current protected-main state, `.ai/state.json`, `.ai/handoff/current.md`, live Issues/PRs and accepted exact-head evidence take precedence.

## Governance sources

Before extending runtime/deployment/release behavior, read the repository governance and architecture sources, especially `AGENTS.md`, `.ai/state.json`, `.ai/handoff/current.md`, `ARCHITECTURE.md`, `SECURITY.md`, applicable release/runtime plans, and the live Issue/PR set.
