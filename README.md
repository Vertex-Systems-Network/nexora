# Nexora

**Current development candidate:** `1.0.0-rc.94` — installer protocol `v5.29`.

> **Canonical current status (2026-09-09):** protected `main` includes the merged Vitest 5 maintenance update and the AI GitHub intake governance update through `bb3089c4254fd787338052e7685b41b3ad498227`. The active stage remains `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` and is **BLOCKED**. Source/CI acceptance is not the same as real-target or release completion.

## AI development startup gate

Every AI development session MUST execute this gate before unrelated new implementation begins:

1. Inspect all live open **Issues** and all open **PRs/MRs** first.
2. Verify each candidate's exact head, draft/mergeability state, required reviews, unresolved threads and CI/checks.
3. Merge every safe, approved, green, non-stale ready PR/MR before starting new development.
4. Resolve or explicitly document blocked, draft, red, stale or review-pending items; never bypass them silently.
5. Re-read protected `main` and the canonical AI state/handoff after accepted merges.
6. Only then select and start the next authorized development unit.
7. Before finishing the session, synchronize this README's current-status section with the actual accepted repository state.

This rule applies on every AI development start, including work resumed from an existing plan.

## Current runtime closure

- **Active stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY`.
- **PR #30:** remains **Draft and unmerged**. It is the bounded runtime-recovery carrier, not a completed release carrier.
- **Issue #46:** remains the code-side blocker for PR #30. Recovery child execution still requires a finite deadline, deterministic termination/cleanup, and bounded stdout/stderr capture with explicit failure evidence.
- **Independent review:** critical runtime-control changes still require exact-head independent review before merge.
- **Real-target evidence:** fresh final readiness/current receipt, exact target↔web identity and authoritative `/login` evidence on the same proven origin remain required.
- **Dependency-lock governance:** reviewed dependency-lock attestation remains separate from runtime identity closure.
- **CORE-QA-001:** MUST NOT start until runtime closure is target-verified.
- **PR #1:** remains the Draft final carrier and MUST NOT be represented as release-complete.

## Status boundary

Nexora has extensive source implementation and passing hosted certification evidence, but hosted source/CI evidence does **not** by itself establish target verification, release certification or production readiness. Any progress report must keep **Source**, **Target** and **Release** states distinct.

## Current next sequence

1. Complete Issue #46 without broadening PR #30 scope.
2. Re-run exact-head release certification for the resulting PR #30 head.
3. Obtain independent exact-head review for PR #30.
4. Collect the remaining real-target readiness/current-receipt, target↔web identity and `/login` evidence.
5. Merge PR #30 only after every required source/review/target gate is satisfied.
6. Start `CORE-QA-001` only after runtime closure is target-verified.
7. Continue the broader final target/release sequence through PR #1 without collapsing source progress into release readiness.

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
