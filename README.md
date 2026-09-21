# Nexora

**Current development candidate:** `1.0.0-rc.94` — installer protocol `v5.29`.

> **Canonical current status (2026-09-22):** protected `main` is `8972632c230117c377c1e0e5d7219352518acda7` after source-only audit remediation PR #78 merged; stacked PR #79 was integrated first. PR #78 exact head `0812fbabaaf5b337fc80fb11209ebb8b20600cf8` passed release certification #973 / run `35659627211`. Superseded Draft PR #73 is now closed unmerged. Governance PR #82 previous head `cc4bd5ef98318b156bd4482ce8819488302a1fe4` passed certification #975 / run `35660675591`, but the new material closeout head requires fresh exact-head CI and independent review. The active stage remains `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` and is **BLOCKED** because real-target verification and credential rotation after quarantined PR #71 remain unresolved. Protected-main push certification still has not surfaced in bounded observations; it is not treated as PASS. Source integration is not target verification or release completion.

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

This compact ledger is mandatory and is updated on every **material engineering milestone**. It records repository truth, including FAIL/VERIFYING/WAITING states; it must not be rewritten as success-only reporting.

- **Observed:** 2026-09-22
- **Protected main:** `8972632c230117c377c1e0e5d7219352518acda7`
- **Active stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY` — **BLOCKED**
- **Open remediation Issues:** #72, #74
- **Source remediation:** PR #79 merged into PR #78; PR #78 merged into protected `main`
- **Latest terminal pre-merge evidence:** PR #78 certification #973 / run `35659627211` on exact head `0812fbabaaf5b337fc80fb11209ebb8b20600cf8` — **SUCCESS**
- **Protected-main push certification:** **WAITING_EXTERNAL_START/CI** — no run surfaced for `main@8972632c...` in the initial or one bounded follow-up observation; no rerun/tight-poll loop was used
- **Superseded carrier cleanup:** Draft PR #73 closed unmerged after verifying PR #78/#79 integration superseded its portable remediation scope
- **Governance:** PR #82 previous exact head `cc4bd5ef98318b156bd4482ce8819488302a1fe4` passed #975 / `35660675591`; this material closeout head is **VERIFYING** and still requires independent exact-head review before merge
- **Current blockers:** real-target readiness/current receipt + target↔web identity + authoritative `/login` evidence; credential rotation required after quarantined PR #71; PR #82 fresh exact-head CI/review; CORE-QA remains blocked
- **Next safe action:** reconcile one fresh exact-head certification for the new PR #82 head; do not tight-poll the missing main push run; continue Issues #72/#74 target-closure evidence only through an authorized real-target channel
- **Current module progress:** `[??????????] N/A — canonical numeric metric unavailable`
- **Overall progress:** `[??????????] N/A — canonical numeric metric unavailable`

## Current runtime closure

- **Active stage/unit:** `RUNTIME-CLOSURE-001 / SYS-RUNTIME-IDENTITY`.
- **PR #30:** remains **Draft and unmerged**. It is the bounded runtime-recovery carrier, not a completed release carrier.
- **Issue #46 / PR #51:** the bounded child-execution implementation exists on Draft PR #51, including finite execution deadline, deterministic termination/cleanup, bounded stdout/stderr capture and explicit timeout/output-limit evidence. It is not yet integrated into #30 and must be synchronized/re-certified after the dependency closure changes are accepted.
- **Independent review:** critical runtime-control changes still require exact-head independent review before PR #30 may merge.
- **Real-target evidence:** fresh final readiness/current receipt, exact target↔web identity and authoritative `/login` evidence on the same proven origin remain required.
- **CORE-QA-001:** MUST NOT start until runtime closure is target-verified.
- **PR #1:** remains the Draft final carrier and MUST NOT be represented as release-complete.

## Current dependency closure

- **Issue #52 / PR #55:** Draft PR #55 is the clean dependency carrier stacked on PR #30. Current clean source head `c9531d7b3571062ec932b8999d9cf77c54cd7f1f` passed exact-head Nexora release certification #867 / run `34367088183`.
- **Governed proof-v4:** disposable diagnostic PR #58 completed successfully and was closed unmerged. Exact diagnostic head `73b45bfde0e339c223f9c1b250e79ed391ecc936` passed governed lock proof run #4 / `34372657034` and release certification #868 / `34372656788`.
- **Exact candidate locks:** `composer.lock` SHA-256 `a96e562048532b4d9773877cea6c8dc0dd2a1adff52c25b7481493402beeced8`; `package-lock.json` SHA-256 `ad3e1dd0300ef0796865fa78ed63e547090940360925efd8939f43966f3ed804`.
- **Reproducibility:** A/B raw hashes match, semantic hashes match, raw package-lock differing paths are `0`, supply-chain/audit evidence passes with matching A/B fingerprints, and proof errors/warnings are empty.
- **Frontend replay:** the exact generated npm lock passes fresh `npm ci --ignore-scripts`, typecheck, Vitest, production build and build verification at **35 / 64 JavaScript assets**; the accepted ceiling remains unchanged.
- **Remaining gate:** the repository promotion tool explicitly requires `PROMOTE-REVIEWED` only after **human review of both candidate lockfiles**. That attestation is not replaced or fabricated by AI/self-review. Root lockfiles therefore remain unpromoted on PR #55 and Issue #52 remains open.

## Status boundary

Nexora has extensive source implementation and passing hosted certification evidence, but hosted source/CI evidence does **not** by itself establish target verification, release certification or production readiness. Any progress report must keep **Source**, **Target** and **Release** states distinct.

## Current next sequence

1. Complete the required human review of the exact governed `composer.lock` / `package-lock.json` candidate pair and promote only those reviewed bytes through the repository lock-review path.
2. Re-run exact-head release certification on the resulting lock-bearing PR #55 head and integrate #55 into PR #30 only after its review/thread gates are clean.
3. Synchronize the Issue #46 / PR #51 bounded-child fix onto the updated PR #30 base, re-run exact-head certification, and integrate it only when green/reviewed.
4. Obtain independent exact-head review for the resulting PR #30 runtime-control head.
5. Collect fresh real-target readiness/current-receipt, target↔web identity and authoritative `/login` evidence on the same proven origin.
6. Merge PR #30 only after every required source/review/target gate is satisfied.
7. Start `CORE-QA-001` only after runtime closure is target-verified.
8. Continue the broader final target/release sequence through PR #1 without collapsing source progress into release readiness.

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
