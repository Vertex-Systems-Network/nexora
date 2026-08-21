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
- Historical hosted failure: zero source steps; not a product-contract execution failure.
- Actions runner: **Windows local runner via `runs-on: self-hosted`**.
- PR certification trigger: **RESTORED**; `push: main` + `workflow_dispatch` preserved.
- Ledger: `2.4` — governance sync for N1.18–N1.21 pending after certification result
- Issue #2: **OPEN**
- N1.18 Public APIs / Webhooks / SDK: **implementation complete / awaiting consolidated executable certification**
- N1.19 Import / Export / WordPress migrations: **implementation complete / awaiting consolidated executable certification**
- N1.20 Observability: **implementation complete / awaiting consolidated executable certification**
- N1.21 Forge / Developer Experience: **implementation complete candidate / awaiting self-hosted certification**
- Latest workflow head before this progress commit: `8e612c5ae9b7ad39c80da76d3d53ec6da36e80ba`

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Verified Power remains unchanged until current executable source certification passes and real-target evidence exists. A self-hosted source run may promote SOURCE DONE semantics only; it does not promote Target Power.

---

## 3. Roadmap

| Block | Source / implementation | Target | State |
|---|---:|---:|---|
| N1.9–N1.17 | 100% verified source | target pending | SOURCE DONE |
| N1.18 Public APIs/Webhooks/SDK | implementation complete | 0% current target | certification pending |
| N1.19 Import/Export/WP migrations | implementation complete | 0% current target | certification pending |
| N1.20 Observability | implementation complete | 0% current target | certification pending |
| N1.21 Forge / Developer Experience | implementation complete candidate | 0% current target | **SELF-HOSTED CERTIFICATION PENDING** |
| N1.22 Sentinel 2.0 | foundation | 0% | Next source block after green certification |
| N1.23–N1.26 | planned/partial | 0% | Later roadmap |

---

## 4. Deferred implementation-complete blocks

N1.18–N1.20 remain implementation-complete but not SOURCE DONE until the current consolidated source chain executes successfully. N1.21 has all intended first-workflow implementation/tests/docs/static-gate source and joins the same certification boundary.

---

## 5. N1.21 Forge implementation checkpoint

- Existing `nexora:make:extension` retained as the stable first-flow developer CLI.
- `ForgeExtensionScaffolder` provides deterministic plan/create behavior.
- Service-level identifier/type/name validation and authoritative `ExtensionManifestValidator` self-check.
- Workspace fixed to `extensions/<identifier>` with `PortablePath` lexical/symlink safety.
- Workspace/target symlinks and file-vs-directory conflicts fail closed.
- `--dry-run` is zero-write and deterministic.
- Existing target is refused by default; `--force` requires same-identifier `.nexora-forge.json` ownership.
- Force refresh never deletes the scaffold directory and preserves developer-created files.
- Stable generated contract: marker, README, composer manifest, `nexora.json`, `src`, `resources`, migrations and tests placeholders.
- Forge generates source only; it does not install/enable/trust/grant capabilities or create supply-chain artifacts.
- Sentinel ALLOW remains mandatory in the independent Extension installer lifecycle.
- Acceptance coverage proves dry-run/no-write, traversal/non-directory rejection, arbitrary overwrite denial, deterministic refresh, developer-file preservation and manifest validation.
- `docs/FORGE_DEVELOPER_GUIDE.md` defines the stable developer contract and intended Forge -> package/sign -> Sentinel -> normal install flow.
- `scripts/forge-developer-experience-product-contract-verify.php` guards the above semantics.
- Forge contract is now required by `scripts/development-readiness.php` and `.github/workflows/release-certification.yml`.

---

## 6. Actions / self-hosted runner mode

- Workflow now uses `runs-on: self-hosted`.
- `pull_request` trigger on `main` is restored.
- `push: main` and `workflow_dispatch` remain available.
- Timeout increased to 30 minutes for local runner variability.
- Consolidated Source certification includes all prior required gates plus Public API/SDK, Content Migration, Observability and Forge / Developer Experience.
- The first successful self-hosted run must show actual job steps. Historical zero-step hosted failures remain infrastructure-only evidence.

---

## 7. Main protection / target blockers

`main` remains reported `protected=false`; current connector exposes no branch/ruleset mutation endpoint. Desired policy remains PR required + Source certification + stale review dismissal + review/conversation resolution + no force push/delete + admin enforcement. Issue #2 remains OPEN. Target Power remains 50%.

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
| 033 | 2026-08-22 | hardening `f1d717ba…`; tests `0ccdecf8…`; guide `57d3dc50…`; contract `39beaac0…` | N1.21 edge hardening, acceptance source, developer guide and static contract | ~86% candidate |
| 034 | 2026-08-22 | readiness `74ca8c89…`; workflow `8e612c5a…`; user local runner directive | Forge gate required in Development Readiness; PR trigger restored; certification switched from GitHub-hosted to `self-hosted` | implementation-complete candidate; verified Power unchanged pending run |

---

## 9. Exact next action

```text
SELF-HOSTED CONSOLIDATED SOURCE CERTIFICATION
  1. inspect workflow run generated by this PR synchronize commit
  2. confirm job has real checkout/setup/source-contract steps on self-hosted runner
  3. fix every actual failed gate until full Source certification is green
  4. only after green: N1.18-N1.21 may be promoted to SOURCE DONE
  5. update this dashboard, canonical ledger, PR #1 and issue #2 source checkpoint
  6. begin N1.22 Sentinel 2.0 while Target remains separately pending

TARGET BOUNDARY
  - do not close issue #2 without real rc.93 compatibility + post-install + /login + /admin evidence
  - do not increase Target Power from self-hosted source certification alone
```
