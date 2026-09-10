# Nexora

**Current development candidate:** `1.0.0-rc.94` — installer protocol `v5.29`.

> **Canonical current status (2026-09-10):** protected `main@e19d6fa818a7eebaf293d34bd87cff79ebc90ade` remains the accepted baseline. Active Draft PR #30 now contains the governed Issue #52 dependency closure and the integrated Issue #46 bounded-child runtime fix, but those changes are not protected-main acceptance. `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` remains **BLOCKED** until fresh real-target readiness/current-receipt, exact target↔web identity, and authoritative `/login` evidence pass on the same target.

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

- **Active stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY`; target status remains **BLOCKED**.
- **PR #30:** Draft/unmerged, current integrated source head before this status sync `68d6aa5d8610e05acf7c51bed503ae09bd4311cd`.
- **Issue #46 / PR #51:** bounded child-execution source fix is integrated into #30. Exact #51 head `2e0736e4da1d5a89b6979e9171c672a1c0c32745` passed release certification #885 / `34424303763`; #51 changed exactly the three bounded runtime/workflow paths and merged as `68d6aa5d8610e05acf7c51bed503ae09bd4311cd`.
- **Child execution boundary:** finite 120-second default deadline, 256 KiB per-stream output cap, lower-only certification overrides, deterministic soft→hard termination, bounded/redacted failure evidence, and behavioral cleanup/lock-reuse verification are source-integrated.
- **Review boundary:** exact-head AI reviews are recorded with honest provenance; independent-human approval is not claimed. Any final #30 review must bind the final post-sync head.
- **Real-target evidence:** fresh final readiness/current receipt, exact target↔web identity, and authoritative `/login` evidence remain required.
- **CORE-QA-001:** MUST NOT start until runtime closure is target-verified.
- **PR #1:** remains a separate Draft final carrier and is not release-complete.

## Current dependency closure

- **Issue #52:** CLOSED / completed after the original frontend dependency failure was eliminated on a fresh #51 exact-head rerun.
- **Governed candidate v5:** run `34420984941` SUCCESS, artifact `10130890580`.
- **AI lock review:** reviewer `AI:GPT-5.6-Sol@ChatGPT`; no human review or independent approval is claimed. The one-time repository-admin authorization is retained in `.ai/plans/issue-52-ai-lock-review-exception.md`.
- **Exact reviewed locks:** `composer.lock` SHA-256 `1e00ab9e4b63991260e20ae28f7c2f3e092da75425e27a474731c3ad8b86a198`; `package-lock.json` SHA-256 `09c913a87f16b13c47020b2bf36aaf9068dbe50948402c9fdcd1bfd644090c75`.
- **Promotion:** corrected fail-closed promotion run `34421381192` SUCCESS, dossier artifact `10131020021`; strict locks, provenance, supply-chain, reviewed-attestation verification and frontend replay passed.
- **Integration:** PR #55 exact-head certification #883 / `34423528195` SUCCESS, zero unresolved threads, then guarded integration into #30. Reviewed lock bytes remain unchanged after protected-main reconciliation.
- **Runtime rerun:** PR #51 certification #885 / `34424303763` SUCCESS using the committed reviewed dependency closure.

## Status boundary

Nexora has extensive source implementation and passing hosted certification evidence, but hosted source/CI evidence does **not** by itself establish target verification, release certification or production readiness. Any progress report must keep **Source**, **Target** and **Release** states distinct.

## Current next sequence

1. Finish this status-only reconciliation and require fresh exact-head #30 release certification plus a fresh exact-head review on the resulting head.
2. Keep PR #30 Draft/unmerged while target evidence is missing.
3. On the exact Windows/Laragon rc.93 target, obtain fresh `post-install-status --assert-ready` evidence requiring `status=pass`, `ready=true`, `runtime_ready=true`, `receipt_current=true`, `errors=[]`.
4. Prove the configured `app.url` web process belongs to that exact target through the fresh one-time CLI↔web acknowledgement and local `--require-web-ack` verification.
5. Obtain authoritative `/login` evidence on that same proven origin with TLS verification enabled and redirects disabled.
6. Only after the source/review/target gates all pass may PR #30 merge and `RUNTIME-CLOSURE-001` become `TARGET_VERIFIED`.
7. Start `CORE-QA-001` only after genuine target verification; continue broader release work through PR #1 without collapsing Source, Target and Release states.

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
