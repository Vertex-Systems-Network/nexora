# Nexora

**Current development candidate:** `1.0.0-rc.94` — installer protocol `v5.29`.

> **Canonical current status (2026-09-30):** GitHub source development continues under user authorization. Protected-main `d8e83e87305bc8c11d8b073b42f13cb69414c550` passed push certification #1125 / `36689146406`. Environment-neutral tooling and source maintenance PRs #94–#99 are integrated. Parser maintenance #100 merged after exact-head #1126 SUCCESS; resulting-main push CI must be verified separately. No current installation path or live URL has been provisioned. Historical rc.93 evidence is not current target verification; runtime target acceptance remains **BLOCKED**. Provider rotation (#72), target identity/readiness (#74), repository review enforcement (#87), and draft #1 remain distinct outstanding gates.

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

- **Observed:** 2026-09-30
- **Last verified source baseline:** `d8e83e87305bc8c11d8b073b42f13cb69414c550` — protected-main #1125 / `36689146406` **SUCCESS**
- **Product acceptance stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` — **BLOCKED**
- **Current source batch:** environment-neutral tooling and reviewed dependency maintenance; #94–#99 integrated, #100 merged after fresh exact-head #1126 SUCCESS; resulting-main push CI must be verified separately
- **Current deployment:** not provisioned; user scope is GitHub source development
- **Open remediation Issues:** #72 provider credential rotation; #74 target readiness/identity; #87 repository security review enforcement
- **Future PR:** #1 remains draft; no blanket promotion from source CI
- **Next safe action:** verify resulting main and the source checkpoint; continue authorized source development. Target provisioning/certification is separate.
- **Current module progress:** `[??????????] N/A — canonical numeric metric unavailable`
- **Overall progress:** `[??????????] N/A — canonical numeric metric unavailable`

## Current runtime closure

- Runtime recovery/orchestrator implementation, target containment, bounded child execution, mutation evidence, final readiness/current receipt, exact target↔web challenge and authoritative `/login` controls are present in source.
- The governed command is:
  ```bat
  npm run runtime:recover -- --target="<operator-provided-target-path>" --apply --confirm=RECOVER-RUNTIME
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

Hosted source/CI evidence does **not** establish target verification, credential rotation, release certification or production readiness. Any progress report must keep **Source**, **Target** and **Release** states distinct.

## Current next sequence

1. On the operator-provided target environment, run the governed `runtime:recover` apply command and retain its non-secret machine-readable receipt.
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
