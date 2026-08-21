# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE** — update after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply.
>
> `NEXORA_AI_PROJECT_STATE.md` remains canonical append-only history. This dashboard is the human-readable Power view. **SOURCE DONE != TARGET VERIFIED.**

---

## 1. Current checkpoint

- Date: `2026-08-22`
- Branch: `dev/n1-0b-core-functional-qa`
- PR #1: **DRAFT + MERGEABLE**, formal metadata through N1.17
- Last executable green source CI: `32509858655` on `45e527c43c69f89c5519dde13bad6c771d171915`
- GitHub Actions: **DEFERRED BY USER — quota exhausted; PR auto-trigger temporarily disabled; do not trigger/re-run**
- Ledger: `2.4` — governance sync for deferred N1.18–N1.20 remains pending
- Issue #2: **OPEN**
- N1.18 Public APIs / Webhooks / SDK: **implementation complete / executable verification deferred**
- N1.19 Import / Export / WordPress migrations: **implementation complete / executable verification deferred**
- N1.20 Observability: **implementation complete / executable verification deferred**
- Active source block: **N1.21 Forge / Developer Experience — ~45% implementation candidate**
- Latest N1.21 implementation head before this progress commit: `567ead33ff34f3fac662dd4d6d03dc173b7b785a`

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Verified Power remains unchanged while N1.18+ blocks await consolidated executable certification. Implementation percentages never promote Target Power.

---

## 3. Roadmap

| Block | Source / implementation | Target | State |
|---|---:|---:|---|
| N1.9–N1.17 | 100% verified source | 0% current target | SOURCE DONE / target pending |
| N1.18 Public APIs/Webhooks/SDK | implementation complete | 0% | executable verification deferred; NOT SOURCE DONE |
| N1.19 Import/Export/WP migrations | implementation complete | 0% | executable verification deferred; NOT SOURCE DONE |
| N1.20 Observability | implementation complete | 0% | executable verification deferred; NOT SOURCE DONE |
| N1.21 Forge / Developer Experience | **~45% candidate** | 0% | **ACTIVE** |
| N1.22 Sentinel 2.0 | foundation | 0% | Next |
| N1.23–N1.26 | planned/partial | 0% | Later roadmap |

---

## 4. Deferred implementation-complete blocks

N1.18, N1.19 and N1.20 remain implementation-complete / executable-verification-deferred. Their implementation details and acceptance/product contracts are retained in prior Apply Log checkpoints. None is promoted to SOURCE DONE until consolidated executable certification is available.

---

## 5. N1.21 Forge implemented so far

- Existing `nexora:make:extension` foundation was retained rather than creating a second generator.
- New `ForgeExtensionScaffolder` separates planning/writes from CLI presentation.
- Identifier/type/name validation is service-level, not CLI-only.
- Generated `nexora.json` is self-validated through the authoritative `ExtensionManifestValidator` before any scaffold write.
- Workspace is restricted to project `extensions/<identifier>` and lexical/symlink traversal is checked with `PortablePath`.
- Forge workspace itself may not be a symbolic link.
- `--dry-run` provides deterministic destination/file preview with **zero writes**.
- Existing destination is refused by default.
- `--force` is accepted only when the destination carries `.nexora-forge.json` with the same Forge schema + identifier; arbitrary directories cannot be clobbered.
- Existing symbolic-link generated files are refused even in force mode.
- Generated JSON and README content is deterministic (no timestamps/random IDs).
- Scaffold README/CLI explicitly state trust boundary: Forge generates source only; package review/Sentinel ALLOW remains mandatory and Forge does not install/enable/grant trust.

**Evidence boundary:** N1.21 remains an implementation candidate. Acceptance tests, template edge hardening, docs and static product contract remain.

---

## 6. Actions quota mode

- Release workflow PR auto-trigger remains temporarily removed; `push: main` + `workflow_dispatch` remain.
- Do not trigger/re-run Actions while quota is exhausted.
- When capacity returns: restore `pull_request`, then run one consolidated certification across deferred blocks.

---

## 7. Main protection / target blockers

`main` remains reported `protected=false`; connector has no branch/ruleset mutation endpoint. Issue #2 remains OPEN. Target Power remains 50%.

---

## 8. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | N1.16/N1.17 verified closures/governance | Project 76.5%, Source 99%, Target 50% |
| 014–020 | 2026-08-21 | through `95eb4bd4…`; zero-step Actions | N1.18 API/token/SDK implementation | implementation complete; verified Power held |
| 021 | 2026-08-22 | user directive | Actions deferred after quota exhaustion | Power unchanged |
| 022–027 | 2026-08-22 | through `8afd1f36…` + progress `9f17b1ea…` | N1.19 import/export/WXR implementation | implementation complete; verified Power held |
| 028–031 | 2026-08-22 | through `97824bd4…` + progress `e796d283…` | N1.20 tenant observability/privacy/correlation/retention + tests/contracts | implementation complete; verified Power held |
| 032 | 2026-08-22 | service `2e922995…`; command `567ead33…` | N1.21 deterministic Forge planner/writer, dry-run, Forge-owned force guard, symlink safety and Sentinel trust-boundary CLI | N1.21 **~45% candidate**; verified Power unchanged |

---

## 9. Exact next action

```text
N1.21 FORGE APPLY-02
  1. harden file-vs-directory and generated directory/template edge cases
  2. add executable tests for dry-run/no-write, traversal, arbitrary overwrite refusal, Forge-owned force refresh and deterministic manifest validation
  3. add Forge developer guide + stable scaffold contract
  4. add static N1.21 product contract + readiness/workflow source wiring
  5. implementation complete => N1.22 active; certification still deferred
  6. update THIS FILE after each meaningful apply
  7. DO NOT trigger GitHub Actions

GOVERNANCE
  - canonical ledger 2.4 full history has been recovered safely; sync N1.18–N1.21 in one append-only governance commit after Forge checkpoint
```
