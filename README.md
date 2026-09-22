# Nexora

**Current development candidate:** `1.0.0-rc.94` — installer protocol `v5.29`.

> **Canonical current status (2026-09-22):** protected `main` is `87210e7674f67fb75e046c185206573bebd906f9` after governance/evidence PR #82 merged. PR #82 exact head `5d727efe1ba2810c590e8a86ebc3f122a0c998cf` passed release certification #980 / `35671637039` and merged under scoped admin waiver `WVR-NEX-PR82-MAIN-REVIEW-002`. Protected-main push certification #981 / `35672290666` completed **SUCCESS** on exact protected main. The active product stage remains `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` — **BLOCKED** only on real-target readiness/current receipt, exact target↔web identity, authoritative `/login`, and credential rotation after PR #71. PR #1 remains frozen future work and is not part of the active runtime-closure path.

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
- **Protected main:** `87210e7674f67fb75e046c185206573bebd906f9`
- **Active stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` — **BLOCKED**
- **Open remediation Issues:** #72 (target credential rotation only), #74 (runtime target evidence coordination)
- **Merged governance/evidence carrier:** PR #82 — exact head `5d727efe1ba2810c590e8a86ebc3f122a0c998cf`, #980 / `35671637039` **SUCCESS**
- **Protected-main push certification:** #981 / `35672290666` — **SUCCESS**
- **Active source/governance carrier:** PR #83, final state-only reconciliation; after merge no source carrier remains for this stage
- **Future PR:** PR #1, classified **FUTURE_CARRIER_FROZEN**
- **Closed stale maintenance:** PR #75 and PR #81 closed unmerged; recreate/rebase dependency maintenance only after runtime target verification
- **Current blockers:** real-target readiness/current receipt; exact target↔web identity; authoritative `/login`; credential rotation after PR #71
- **Next safe action after PR #83 merge:** continue real-target W03/W04/W07 evidence and Issue #72 credential rotation; do not start CORE-QA before TARGET_VERIFIED
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

1. Merge the final state-only PR #83 only after fresh exact-head certification and its applicable review/waiver gate.
2. Obtain fresh real-target readiness/current-receipt evidence.
3. Prove exact target-local CLI↔web identity and authoritative `/login` on the same origin.
4. Rotate the credential exposed by PR #71 through an authorized target/provider path.
5. Mark `RUNTIME-CLOSURE-001` TARGET_VERIFIED only after all target gates pass; only then start `CORE-QA-001`.

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
