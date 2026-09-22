# Nexora

**Current development candidate:** `1.0.0-rc.94` — installer protocol `v5.29`.

> **Canonical current status (2026-09-22):** source/governance closure is integrated and verified through protected-main baseline `fde0667b150de551184ec5c5261a34f18538ceed`. PR #83 final exact head `75d5436b0394f246bdca626571666abe77cd4012` passed release certification #985 / `35673582569`, merged under scoped admin waiver `WVR-NEX-PR83-MAIN-REVIEW-001`, and resulting-main certification #986 / `35767459733` completed **SUCCESS**. No active source/governance carrier remains for `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY`. The stage remains **BLOCKED** only on real-target readiness/current receipt, exact target↔web identity, authoritative `/login`, and rotation of the credential exposed by PR #71. PR #1 remains frozen future work and is not part of the active runtime-closure path. Live `main` must always be re-read before mutation; later state-only documentation commits do not by themselves change this Source/Target verdict.

## AI development startup gate

Every AI development session MUST execute this gate before unrelated new implementation begins:

1. Inspect all live open **Issues** and all open **PRs/MRs** first.
2. Verify each candidate's exact head, draft/mergeability state, required reviews, unresolved threads and CI/checks.
3. Merge every safe, approved, green, non-stale ready PR/MR before starting new development.
4. Resolve or explicitly document blocked, draft, red, stale or review-pending items; never bypass them silently.
5. Re-read protected `main` and the canonical AI state/handoff after accepted merges.
6. Only then select and start the next authorized development unit.
7. Before reporting any material milestone complete, blocked, verifying or waiting, update the fixed **AI-Native Progress Ledger** below with repository-backed state. Missing this README sync means the milestone is not fully complete.

This rule applies on every AI development start, including work resumed from an existing plan.

## AI-Native Progress Ledger

- **Observed:** 2026-09-22
- **Last verified source/governance baseline:** `fde0667b150de551184ec5c5261a34f18538ceed` — protected-main #986 / `35767459733` **SUCCESS**
- **Active stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` — **BLOCKED**
- **Active source/governance carrier:** none
- **Open remediation Issues:** #72 — target/provider credential rotation; #74 — real-target readiness and identity coordination
- **Future PR:** PR #1 — **FUTURE_CARRIER_FROZEN**
- **Closed stale maintenance:** PR #75 and PR #81 closed unmerged; recreate/rebase only after target verification if still applicable
- **Current blockers:** real-target readiness/current receipt; exact target↔web identity; authoritative `/login`; credential rotation after PR #71
- **Next safe action:** execute the governed `runtime:recover` apply path on `D:\laragon\www\nexora`; separately complete authorized credential rotation; do not start CORE-QA before TARGET_VERIFIED
- **Current module progress:** `[??????????] N/A — canonical numeric metric unavailable`
- **Overall progress:** `[??????????] N/A — canonical numeric metric unavailable`

## Current runtime closure

- Runtime recovery/orchestrator implementation, target containment, bounded child execution, mutation evidence, final readiness/current receipt, exact target↔web challenge and authoritative `/login` controls are present in source.
- The governed command is:
  ```bat
  cd /d D:\laragon\www\nexora
  npm run runtime:recover -- --target="D:\laragon\www\nexora" --apply --confirm=RECOVER-RUNTIME
  ```
- The orchestrator fails closed, writes a machine-readable target receipt, and reports `target_verification_complete=true` only when compatibility, final readiness/current receipt, exact web identity and `/login` all pass.
- Source status is **SOURCE_DONE**; target status remains **BLOCKED** because no fresh target execution output has been accepted after the current source closure.
- Credential rotation after PR #71 is a separate real target/provider action and cannot be completed by repository edits.
- `CORE-QA-001` MUST NOT start until `RUNTIME-CLOSURE-001` is `TARGET_VERIFIED`.

## Current dependency closure

- Protected main contains current deterministic Composer/npm lockfiles and hardened certification workflow.
- Historical Issue #52 review/integration evidence is retained under `.ai/evidence/`.
- Historical exact-artifact attestations remain audit evidence; they do not override current protected-main lock identity or prove installed-target consumption.
- Future dependency changes require fresh supply-chain intake and exact-head evidence.

## Status boundary

Hosted source/CI evidence does **not** establish real-target verification, credential rotation, release certification or production readiness. Any progress report must keep **Source**, **Target** and **Release** states distinct.

## Current next sequence

1. On the exact Windows/Laragon target, run the governed `runtime:recover` apply command and retain its non-secret machine-readable receipt.
2. Accept target closure only if final readiness/current receipt, exact target↔web proof and authoritative same-origin `/login` are all PASS.
3. Rotate the credential exposed by PR #71 through an authorized target/provider path and retain non-secret evidence.
4. Reconcile W07 canonical state only after both target evidence and credential rotation are accepted.
5. Mark `RUNTIME-CLOSURE-001` TARGET_VERIFIED only after all gates pass; only then start `CORE-QA-001`.

## Core stack

- Laravel 13 / PHP 8.3+
- Inertia v3
- React 19 + strict TypeScript
- Vite 8 + Tailwind CSS 4
- PHPUnit + Vitest
- Relational primary-database abstraction with multi-engine certification gates
- Sentinel/package supply-chain and runtime fail-closed controls

## Historical README / feature record

The previous long-form README, including historical N0.x/N1.x feature notes, deployment instructions and release-development record, is preserved at [`docs/README_HISTORY_PRE_STATUS_SYNC.md`](docs/README_HISTORY_PRE_STATUS_SYNC.md). Historical statements do not override live repository state or accepted exact-head/target evidence.

## Governance sources

Before extending runtime/deployment/release behavior, read `AGENTS.md`, `.ai/state.json`, `.ai/handoff/current.md`, `ARCHITECTURE.md`, `SECURITY.md`, the applicable runtime/release plans, and live Issues/PRs.
