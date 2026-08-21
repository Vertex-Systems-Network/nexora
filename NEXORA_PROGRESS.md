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
- Previous hosted Actions failure: zero source steps; not a product-contract execution failure.
- Runner directive: **user attached Windows `self-hosted` runner; switch certification to it and restore PR trigger after N1.21 gate wiring.**
- Ledger: `2.4` — governance sync for deferred N1.18–N1.21 remains pending
- Issue #2: **OPEN**
- N1.18 Public APIs / Webhooks / SDK: **implementation complete / executable verification deferred**
- N1.19 Import / Export / WordPress migrations: **implementation complete / executable verification deferred**
- N1.20 Observability: **implementation complete / executable verification deferred**
- Active source block: **N1.21 Forge / Developer Experience — ~86% implementation candidate**
- Latest N1.21 contract head before this progress commit: `39beaac0309c8085d19f13698248ab53eb646d30`

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Verified Power remains unchanged until executable certification and real-target evidence exist. Implementation percentages never promote Target Power.

---

## 3. Roadmap

| Block | Source / implementation | Target | State |
|---|---:|---:|---|
| N1.9–N1.17 | 100% verified source | target pending | SOURCE DONE |
| N1.18 Public APIs/Webhooks/SDK | implementation complete | 0% current target | executable verification deferred; NOT SOURCE DONE |
| N1.19 Import/Export/WP migrations | implementation complete | 0% current target | executable verification deferred; NOT SOURCE DONE |
| N1.20 Observability | implementation complete | 0% current target | executable verification deferred; NOT SOURCE DONE |
| N1.21 Forge / Developer Experience | **~86% candidate** | 0% current target | **ACTIVE** |
| N1.22 Sentinel 2.0 | foundation | 0% | Next |
| N1.23–N1.26 | planned/partial | 0% | Later roadmap |

---

## 4. Deferred implementation-complete blocks

N1.18, N1.19 and N1.20 remain implementation-complete / executable-verification-deferred. None is promoted to SOURCE DONE until consolidated executable certification passes on the current branch.

---

## 5. N1.21 Forge implemented so far

- Existing `nexora:make:extension` is retained as the public first-flow CLI instead of creating a duplicate generator.
- `ForgeExtensionScaffolder` separates deterministic planning/writes from CLI presentation.
- Identifier/type/name validation is service-level.
- Generated `nexora.json` is self-validated through authoritative `ExtensionManifestValidator` before writes.
- Workspace is restricted to `extensions/<identifier>` and uses `PortablePath` lexical + existing-symlink checks.
- Workspace/target symlinks and file-vs-directory conflicts fail closed.
- `--dry-run` performs the same validation and reports deterministic destination/files with **zero writes**.
- Existing destinations are refused by default.
- `--force` is accepted only for `.nexora-forge.json` ownership with matching schema + identifier; arbitrary directories cannot be clobbered.
- Force refresh overwrites deterministic Forge-managed files only; developer-created files remain untouched because the scaffold directory is never deleted.
- Stable generated contract includes `.nexora-forge.json`, README, composer.json, `nexora.json`, `src`, `resources`, migrations and tests placeholders.
- Generated content has no timestamps/random IDs and is deterministic for equal input.
- CLI/README/developer guide explicitly preserve trust boundary: Forge generates source only; Sentinel ALLOW and normal Extension lifecycle remain mandatory.
- `tests/Feature/Forge/ForgeDeveloperExperienceTest.php` covers zero-write dry-run, traversal/non-directory rejection, arbitrary force denial, deterministic Forge-owned refresh, developer-file preservation and manifest validation.
- `docs/FORGE_DEVELOPER_GUIDE.md` documents stable CLI/file contract, safe force semantics, capabilities, migration/runtime defaults and package-to-Sentinel flow.
- `scripts/forge-developer-experience-product-contract-verify.php` statically guards these developer/security semantics and forbids Forge from directly using `ExtensionPackageInstaller`/`SupplyChainArtifact` trust paths.

**Evidence boundary:** N1.21 still requires Development Readiness/workflow wiring and a current executable self-hosted certification before SOURCE DONE.

---

## 6. Actions / runner mode

- User has attached a Windows GitHub Actions runner with `self-hosted` label.
- Certification workflow will be changed from `ubuntu-latest` to `self-hosted`.
- PR auto-trigger can now be restored because the intended certification execution is on the local runner rather than GitHub-hosted minutes.
- First self-hosted run must execute actual workflow steps; zero-step historical hosted failures do not count against source correctness.
- Consolidated run must cover N1.18, N1.19, N1.20, N1.21 and every prior required source gate.

---

## 7. Main protection / target blockers

`main` remains reported `protected=false`; current connector still exposes no branch/ruleset mutation endpoint. Desired policy remains PR required + Source certification + stale review dismissal + review/conversation resolution + no force push/delete + admin enforcement. Issue #2 remains OPEN. Target Power remains 50%.

---

## 8. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–013 | 2026-08-21 | through `45e527c4…`; CI `32509858655` | N1.16/N1.17 verified closures/governance | Project 76.5%, Source 99%, Target 50% |
| 014–020 | 2026-08-21 | through `95eb4bd4…`; zero-step hosted Actions | N1.18 API/token/SDK implementation | implementation complete; verified Power held |
| 021 | 2026-08-22 | user directive | hosted Actions deferred after quota exhaustion | Power unchanged |
| 022–027 | 2026-08-22 | through `8afd1f36…` + progress `9f17b1ea…` | N1.19 import/export/WXR implementation | implementation complete; verified Power held |
| 028–031 | 2026-08-22 | through `97824bd4…` + progress `e796d283…` | N1.20 tenant observability/privacy/correlation/retention + tests/contracts | implementation complete; verified Power held |
| 032 | 2026-08-22 | service `2e922995…`; command `567ead33…` | N1.21 deterministic Forge planner/writer, dry-run, ownership/symlink safety, Sentinel trust-boundary CLI | ~45% candidate |
| 033 | 2026-08-22 | hardening `f1d717ba…`; tests `0ccdecf8…`; guide `57d3dc50…`; contract `39beaac0…` | N1.21 filesystem edge hardening, executable acceptance source, developer guide and static Forge product contract | N1.21 **~86% candidate**; verified Power unchanged |

---

## 9. Exact next action

```text
N1.21 FORGE APPLY-03 + SELF-HOSTED CERTIFICATION
  1. wire Forge contract into Development Readiness
  2. update release workflow to runs-on: self-hosted
  3. restore pull_request trigger
  4. add Forge product-contract step to workflow
  5. run/observe consolidated Source certification on local Windows runner
  6. fix any source/runtime portability failures until green
  7. if green: promote N1.18-N1.21 SOURCE DONE only for source semantics; Target remains pending
  8. update THIS FILE + canonical ledger + PR/issue checkpoints

DO NOT
  - do not mark TARGET VERIFIED from the self-hosted source workflow alone
  - do not close issue #2 without live rc.93 compatibility/post-install/browser evidence
```
