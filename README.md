# Nexora

**Current development candidate:** `1.0.0-rc.94` — installer protocol `v5.29`.

> **Canonical current status (2026-09-30):** protected main `186138b0f7b7ca0da1ce3b2bb37b051edc75c855` includes merged PR #103 source reconciliation and PR #104 SQLite provenance correction. Exact main release certification 36735105784 SUCCESS. No current live hosting target is provisioned; product acceptance remains **BLOCKED**.

> **Stable disposable runtime batch:** prior service mismatch reproduced by workspace proxy port only. Authentication public-counter interference fixed with the shared five/minute/IP auth budget preserved. Fresh installation, activation, exact CLI↔web proof, governed recovery, public HTTP smoke, login POST 302, admin GET 200 and final readiness/current receipt PASS. Local 10 tests / 40 assertions and source certification PASS; final PR CI pending. [Measured report](docs/stable-runtime-verification-2026-09-30.md).

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
- **Last verified source baseline:** `186138b0f7b7ca0da1ce3b2bb37b051edc75c855` — protected-main release certification `36735105784` **SUCCESS**
- **Product acceptance stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` — **BLOCKED**
- **Current source batch:** stable runtime verification + auth throttle repair on `codex/stable-runtime-verification`; local checks and fresh disposable runtime PASS; final remote CI pending
- **Current deployment:** disposable SQLite/loopback HTTP tested; no live target provisioned
- **Open remediation Issues:** #72 historical bootstrap-key non-use/rotation; #74 live runtime acceptance; #87 review enforcement reconciliation
- **Open planning PR:** #102 remains draft; #1 closed; #103/#104 merged
- **Next safe action:** exact-head CI/review and integration of this bounded repair; provisioned TLS target, other engines and key acceptance remain separate.
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
