# Nexora

**Current development candidate:** `1.0.0-rc.94` — installer protocol `v5.29`.

> **Canonical current status (2026-10-08):** protected main `55c249c06621fe7dc14868b3a5740b657bcb6b7a`; source remains `SOURCE_DONE`. Operator-provided local Laragon `1.0.0-rc.94` evidence reports current readiness, CLI↔web handoff, and same-origin TLS-verified `/login` PASS. Target/Release remain **BLOCKED** on the historical bootstrap-key audit/retirement (#72); hosted/production acceptance is not established.

> **Runtime closure:** local W03/W04/readiness/login evidence is now reported by the operator and recorded on [Issue #74](https://github.com/Vertex-Systems-Network/nexora/issues/74). Issue #72 still blocks stage promotion until historical key-use scope and safe retirement/non-use are accepted. CORE-QA stays locked.

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

- **Observed:** 2026-10-08
- **Last verified source baseline:** `55c249c06621fe7dc14868b3a5740b657bcb6b7a` — source-state main; no current target/release certification is inferred from this documentation reconciliation
- **Product acceptance stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` — **BLOCKED**
- **Current source batch:** source implementation remains `SOURCE_DONE`; no new runtime/source code is part of this state reconciliation
- **Current deployment:** operator-provided local Windows Laragon `1.0.0-rc.94`; readiness, CLI↔web identity and same-origin `/login` reported PASS; hosted/production acceptance unverified
- **Open remediation Issues:** #72 historical bootstrap-key use/retirement; #74 local runtime evidence recorded; #114 UI defects are deferred until the active stage allows them
- **Open PR intake:** #106–#113 remain open; all have green exact-head workflows observed, but review submissions are absent. #106 browser acceptance is pending; #113 changes installed source identity; none is merged by this reconciliation.
- **Next safe action:** perform the secret-safe read-only active/previous-key audit and affected-install inventory for #72; do not rotate APP_KEY without a data/session recovery plan.
- **Current module progress:** `[??????????] N/A — canonical numeric metric unavailable`
- **Overall progress:** `[??????????] N/A — canonical numeric metric unavailable`

## Current runtime closure

- Runtime recovery/orchestrator implementation, target containment, bounded child execution, mutation evidence, final readiness/current receipt, exact target↔web challenge and authoritative `/login` controls are present in source.
- The governed command is:
  ```bat
  npm run runtime:recover -- --target="<operator-provided-target-path>" --apply --confirm=RECOVER-RUNTIME
  ```
- The orchestrator fails closed, writes a machine-readable target receipt, and reports `target_verification_complete=true` only when compatibility, final readiness/current receipt, exact web identity and `/login` all pass.
- Source status remains **SOURCE_DONE**. Operator-provided local Laragon evidence now reports fresh target readiness, CLI↔web identity and same-origin TLS-verified `/login` PASS; target status remains **BLOCKED** on Issue #72 historical key-use/retirement and broader hosted/release acceptance.
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

1. Establish the historical key-use scope and compare the actual target's effective APP_KEY plus APP_PREVIOUS_KEYS without exposing values.
2. If the exposed key is accepted by an active or previous-key slot, plan encrypted-data/session recovery before rotating or retiring it; retain only non-secret evidence.
3. Keep CORE-QA locked until Issue #72 and all runtime closure gates are accepted.
4. Then reconcile W07 and advance to `CORE-QA-001`; defer Issue #114 until its registered stage is active.

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
