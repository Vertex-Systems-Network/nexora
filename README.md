# Nexora

**Current development candidate:** `1.0.0-rc.94` — installer protocol `v5.29`.

> **Canonical current status (2026-09-22):** protected `main` is `8972632c230117c377c1e0e5d7219352518acda7` after source-only audit remediation PR #78 (with stacked PR #79 fixes) integrated. Active stage remains `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` and is **BLOCKED**. PR #30 is being reconciled onto this hardened main in a two-parent merge commit that preserves current-main security workflow, repository hygiene, dependency locks and regression fixes. This source reconciliation does not establish real-target verification, credential rotation, deployment or release readiness. Fresh exact-head CI and a distinct exact-head review are required on the resulting PR #30 head.

## AI development startup gate

Every AI development session MUST execute this gate before unrelated new implementation begins:

1. Inspect all live open **Issues** and all open **PRs/MRs** first.
2. Verify each candidate's exact head, draft/mergeability state, required reviews, unresolved threads and CI/checks.
3. Merge every safe, approved, green, non-stale ready PR/MR before starting new development.
4. Resolve or explicitly document blocked, draft, red, stale or review-pending items; never bypass them silently.
5. Re-read protected `main` and the canonical AI state/handoff after accepted merges.
6. Only then select and start the next authorized development unit.
7. Before reporting a material milestone complete, blocked, verifying or waiting, synchronize the compact **AI-Native Progress Ledger** below with repository-backed state. A missed README sync is an incomplete milestone.

This rule applies on every AI development start, including work resumed from an existing plan.

## AI-Native Progress Ledger

- **Observed:** 2026-09-22
- **Protected main:** `8972632c230117c377c1e0e5d7219352518acda7`
- **Active stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` — **BLOCKED**
- **Open remediation Issues:** #72, #74
- **Audit remediation:** PR #79 → PR #78 → protected main integrated; superseded PR #73 closed unmerged
- **Runtime carrier:** PR #30 is reconciled onto current hardened main in this material commit; exact resulting head is bound on the PR conversation after commit creation
- **Merge resolution:** 19 shared runtime/dependency files were already byte-identical; current-main versions win for release certification, README/status, `package-lock.json`, and the runtime recovery regression test
- **Current state:** **VERIFYING** — fresh exact-head release certification required for the resulting PR #30 head
- **Remaining blockers:** fresh real-target readiness/current receipt; exact target↔web one-time identity proof; authoritative `/login` evidence on that same origin; rotation of the credential exposed by quarantined PR #71; independent exact-head review
- **Next safe action:** reconcile one fresh PR #30 exact-head certification; if green, retain the head unchanged for independent review and authorized real-target evidence. Do not start CORE-QA
- **Current module progress:** `[??????????] N/A — canonical numeric metric unavailable`
- **Overall progress:** `[??????????] N/A — canonical numeric metric unavailable`

## Current runtime closure

- **Active stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` — **BLOCKED**.
- **PR #30:** open and unmerged; this reconciliation removes its stale-main divergence without advancing target state.
- **Source controls:** runtime recovery/orchestrator implementation, target containment, mutation evidence, readiness/exit contracts and bounded child execution are retained. The corresponding implementation files that overlap current main were already byte-identical.
- **Main hardening preserved:** exact-head checkout, immutable GitHub Action SHAs, `persist-credentials:false`, MySQL-backed PHPUnit execution, repository-hygiene guard, deterministic Composer/npm installs, and current regression fixes are carried from protected main.
- **Review boundary:** high/critical runtime-control promotion still requires a distinct exact-head review. AI author/self-review is not independent approval.
- **Real-target evidence:** fresh final readiness/current receipt, exact target↔web identity, authoritative `/login` evidence on the same proven origin, and target credential rotation after PR #71 remain unresolved.
- **CORE-QA-001:** MUST NOT start until runtime closure is genuinely target-verified.

## Current dependency closure

- `composer.lock` on PR #30 was already byte-identical to protected main.
- The older PR #30 `package-lock.json` representation is superseded in this reconciliation by the current protected-main lockfile. Historical PR #30 lock-review evidence remains historical evidence and is not silently promoted to the new exact head.
- The resulting head must pass deterministic `composer install`, `npm ci --ignore-scripts`, PHPUnit, typecheck, Vitest and build checks through release certification before current source evidence is accepted.
- Installed-target dependency sealing remains a separate target-evidence question; source lock reconciliation does not prove the rc.93 target consumed or resealed current source dependency state.

## Status boundary

Nexora has extensive source implementation and passing hosted certification evidence, but hosted source/CI evidence does **not** by itself establish target verification, release certification or production readiness. Any progress report must keep **Source**, **Target** and **Release** states distinct.

## Current next sequence

1. Require fresh exact-head release certification on the reconciled PR #30 head.
2. Keep that exact head frozen after green CI and obtain a distinct independent review for the critical runtime-control change; do not use author-context AI review as independent approval.
3. Through an authorized real-target channel, obtain fresh `post-install-status --assert-ready` evidence with `status=pass`, `ready=true`, `runtime_ready=true`, `receipt_current=true`, `errors=[]`.
4. Prove the exact target-local CLI↔web one-time acknowledgement and authoritative `/login` evidence on the same configured origin with TLS verification enabled and redirects disabled.
5. Rotate the credential exposed by quarantined PR #71 on the real target/provider and retain evidence without exposing the replacement secret.
6. Only after source/review/target gates all pass, reconcile canonical state and make the PR #30 merge decision.
7. Start `CORE-QA-001` only after `RUNTIME-CLOSURE-001` is `TARGET_VERIFIED`.

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
