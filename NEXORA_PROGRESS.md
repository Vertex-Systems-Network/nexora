# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE** — Update this dashboard after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply. **After every meaningful apply**, update this dashboard with current evidence, blockers and next action.
>
> `NEXORA_AI_PROJECT_STATE.md` is preserved as append-only historical/cross-session history. This dashboard's **Current checkpoint** is authoritative for current branch head, runner policy, active evidence and `NEXT ACTION`. `NEXORA_ACCESSIBILITY_CERTIFICATION_PLAN.md` is the mandatory UI/accessibility operator + AI plan. **SOURCE DONE != TARGET VERIFIED.**

---

## 1. Current checkpoint

- Date: `2026-08-24` (Asia/Karachi).
- Active engineering branch: `dev/n1-0b-core-functional-qa` (PR #1 head; this dashboard update is state-only and does not change accepted runtime/product implementation bytes).
- Accepted runtime/product implementation head before this state-only reconciliation: **`e52e67bbd997c13a04ab2a5a2ca3fef7e992b8ca`**.
- Active target task remains **Issue #2 / PR #17 — disposable current-source rc.94 post-install recovery rehearsal**. Do not advance to later DB/provider/HA/C5/C6 work until this bounded recovery acceptance is resolved.
- PR #18, **`fix: ignore generated theme projection in source attestation`**, is **MERGED** into `dev/n1-0b-core-functional-qa` as merge commit **`e52e67bbd997c13a04ab2a5a2ca3fef7e992b8ca`**. Its scope is limited to excluding generated `public/nexora-themes/` from source attestation while keeping authoritative `themes/` attested, plus one regression test.
- PR #18 exact-head hosted governance run **`32661429088`** / run #153 on head **`0cccd5e62886676c8946af1ec131614fe2cf4619`** concluded **SUCCESS**; full Laravel/PHPUnit **470 passed / 4385 assertions**, Vitest 6/6, TypeScript noEmit and production build passed. Artifact **`9498940128`**, digest **`sha256:a6dd14f69c44c6cbbdfa60070fd28600ae5558c3c954730537ffdd6bce2a8b32`**.
- PR #19 was the temporary exact-head governance carrier for PR #18 and is **CLOSED + UNMERGED** as required.
- Post-merge exact-dev governance run **`32661923792`** / run #154 on **`e52e67bbd997c13a04ab2a5a2ca3fef7e992b8ca`** concluded **SUCCESS**. Artifact **`9499018803`**, digest **`sha256:a2c57b8fce6207b7ee9f60adcaa702a6bb25a442a7ce3fc2e5354f72180f3b1b`**. Development Readiness remained `ready`; the PR #18 regression was included in the full green suite.
- State-only head **`048cf684320d992261971c67f90af2f2a302dbb1`** completed required full hosted governance in run **`32662762919`** / run #155. Development Readiness remained ready; warning-hard Laravel/PHPUnit **470 passed / 4385 assertions**, Vitest 6/6, TypeScript noEmit, production build and evidence upload passed. Artifact **`9499326743`**, digest **`sha256:67f3b67d99c6c67665eb3233e03eee859da32dfa1e7b32532452eb7d9355c30e`**.
- PR #1 remains **DRAFT + OPEN + MERGEABLE**; do not mark Ready or merge until required real target/release gates pass.
- Current certified `main`: **`f854c50c0f7687fc87fdfab01b49562392af4ef4`**.
- Development execution QA policy: **GitHub-hosted `ubuntu-latest` only**, PHP >= 8.3, Node >= 22, disposable MySQL 8.4. No self-hosted/local/Laragon runner is eligible for the PR governance workflow.
- The full Development execution QA job publishes required status context **`governance`** and runs on every pull-request head targeting `main`, including Markdown/governance-only commits; no `paths-ignore` exemption remains.
- Completed portability task PR #13 remains **MERGED/PASS** with accepted post-merge implementation head **`21724569daba8e38e581ec603ebd08c2f4d58cad`** and clean Windows portability evidence run **`32638775552`** / artifact `9493045237`; do not reopen it.
- Issue #2 preserved `1.0.0-rc.93` evidence remains unchanged: the preserved target fails only `environment`, `activation`, `service`, `process`; its guarded one-time reconcile returned exit 1; source-marker evidence proves rc.93 lacks the permanent rc.94 finalization path. The preserved target must remain untouched.
- PR #17 is a **DRAFT diagnostic evidence carrier only — DO NOT MERGE**. Its workflow must check out the PR event's exact current dev base SHA and execute the real installer/recovery path on a separate GitHub-hosted Windows disposable SQLite target.
- Earlier PR #17 Windows attempts against dev head `8ed37242fff92a9e9d249ddd4fc232bbee286a0a` failed during the real installer commit because installation published generated base-theme assets under `public/nexora-themes/`, which source attestation incorrectly treated as source drift. That defect is the prerequisite now fixed by PR #18.
- PR #17 run #10 **`32663450531`** was correctly bound to exact current dev base/source **`048cf684320d992261971c67f90af2f2a302dbb1`** and is eligible failure evidence. The real installer commit succeeded and redirected to `/install/runtime-handoff`; the run then failed only because the carrier asserted that all four mutable planes must mismatch before recovery. Artifact **`9499373095`**, digest **`sha256:a8ca2a025d80dc4d8e25e7a62cacc1d03b84bb67498b2e7c22780294e4e64711`**.
- Run #10's actual pre-recovery mismatch set is **`environment`, `activation`**. `service` and `process` already match exactly. This is consistent with current rc.94 installer behavior: the long-lived installer request applies the committed cache/session/queue configuration and forgets service/process memoization before sealing `installed.lock`, while post-install finalization permits only the allow-listed mutable set `environment`, `activation`, `service`, `process` and rejects any mismatch outside that set.
- **Acceptance semantics reconciled explicitly:** the four names are the **maximum mutable reconciliation allow-list**, not a requirement that every current-source fresh install manufacture all four mismatches. For the disposable rc.94 recovery gate, pre-recovery compatibility must be non-PASS, every mismatch must be contained within `environment`, `activation`, `service`, `process`, no immutable/source/deployment mismatch may exist, and post-install status must be not-ready before the guarded reconcile. The exact observed subset is evidence and must be recorded; it must not be forced to reproduce the historical rc.93 four-plane symptom.
- Current source release: `1.0.0-rc.94`; installer protocol `v5.29`; generation `n1-v5.29`.
- Source `composer.lock` remains intentionally absent. Hosted Composer resolution is development evidence only; Development Readiness explicitly does not promote dependency locks or grant release certification.
- W3C Nu HTML + W3C CSS Validation Service + WAVE C5 source tooling is implemented and mandatory for final target accessibility closure.
- Project/Source/Target/Release scores remain **76.5% / 99.0% / 50.0% / 25.0%**. PR #18 source hardening, state reconciliation and failed PR #17 diagnostic evidence do not raise Target or Release Power.

### Closed source fail-fast chain since the earlier checkpoint

The hosted sequence exposed and closed deterministic stale-contract/CI mismatches without weakening runtime safety:

- C6 evidence-binding count aligned to the current analyzer.
- CI does not create or package a root `.env`; deterministic process `APP_KEY` remains CI-only.
- Enterprise server-normalized data is separated from outbound router payload typing.
- rc.3 SSO test aligned to the deliberate shallow secret-safe form boundary.
- rc.4 queue jobs/providers and middleware alias counts aligned to current source.
- rc.5 migration/table and portable-nullable-unique counts aligned to current database contracts.
- V30 distributed-upgrade leadership/drain tests assert readiness + runtime-version compatibility semantically rather than stale formatting.
- V35/V39/V40/V42 queue-payload schema assertions aligned to current fail-closed schema floors.
- V56 readiness metric key aligned to `readiness_components_minimum`.
- Runtime synchronizer integration expectation aligned to canonical `nexora.runtime` manifest version `0.5.0`; Discovery remains `0.26.0`.
- Run #130 produced the first complete exact-head source green after that fail-fast chain.
- Dependabot/governance maintenance `main` was integrated as a real merge parent and run #137 re-proved the full application suite after the final maintenance batch.
- Every-head governance enforcement was corrected at `375bfcb3…`; documentation-only heads are no longer exempt.
- `actions/upload-artifact` was moved to v7 and run #138 proved the workflow-runtime deprecation removed.
- PHPUnit warning noise was fixed without suppression via ephemeral `.env.testing`; later exact-head runs retain the warning-hard command.
- PR #13 removed server-vendor coupling from runtime activation/target QA while preserving vendor-specific local-server behavior only as optional adapters.
- The post-merge Windows rerun proved the hardened source on exact dev SHA `21724569…`; the temporary over-broadened SQLite full-suite diagnostics were discarded instead of being misclassified as product defects.
- PR #18 corrected the generated-theme projection/source-attestation boundary without weakening authoritative `themes/` attestation; exact-head and post-merge hosted governance both passed.

### Governance compatibility / evidence semantics

- Historical Actions: **DEFERRED BY USER** — superseded quota/capacity-era state retained only for source-contract and audit-history compatibility. It is not the current PR execution policy.
- Historical self-hosted policy in the append-only ledger is superseded for the PR governance workflow. Current Development execution QA is GitHub-hosted `ubuntu-latest` only.
- Historical ledger/dashboard text that requires preserved rc.93 to obtain in-place compatibility/post-install PASS is superseded by newer real Windows evidence proving that preserved rc.93 lacks the permanent rc.94 finalization implementation.
- Historical/current text describing the rc.94 reconciliation allow-list as an **exact required pre-recovery four-plane mismatch set** is superseded by run #10 plus the current rc.94 source contract. The allow-list remains exactly `environment`, `activation`, `service`, `process`; acceptance does not require every allowed plane to be mismatched.
- The clean post-merge Windows hosted run is accepted evidence for the bounded PR #13 portability contract because it checked out the literal dev SHA and reproduced the established Windows acceptance shape. It is not a substitute for later real provider/HA/browser/recovery/release evidence.
- PR #18 hosted governance is source/development evidence and a prerequisite for the active disposable rc.94 recovery rehearsal. It is not itself Issue #2 target acceptance.
- **Target Power** is evidence-based and does not increase from source CI, source contracts, jsdom, bounded portability evidence, Dependabot configuration, branch-governance wiring, source-attestation hardening or legacy failure evidence alone.
- Current target/release scoring boundary remains: `TARGET POWER    50.0%` and `RELEASE POWER   25.0%` until broader real exact-source target/release evidence justifies a change.

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

No score is increased by W3C/WAVE source tooling, static review, Dependabot/governance configuration, warning cleanup, portability source hardening, source-attestation hardening or bounded hosted Windows portability QA alone. Target/Release Power moves only from the remaining real exact-source target evidence.

---

## 3. Current roadmap state

| Block | Source state | Target / release state |
|---|---|---|
| DEV-0–DEV-4 | substantial source closure; PR #13 portability hardening merged/PASS; PR #18 generated-theme attestation prerequisite merged and exact hosted governance PASS | preserved rc.93 legacy failure evidence + active separate current-source rc.94 recovery rehearsal + broad product QA pending |
| DEV-5 SQL/Data Services | source/harness substantially closed | real disposable DB matrix + connector evidence pending |
| N1.9–N1.21 | SOURCE DONE for bounded workflows | target execution pending |
| N1.22 Sentinel 2.0 | SOURCE DONE FOR CURRENT WORKFLOW | controlled package target evidence pending |
| N1.23 Marketplace 2.0 | SOURCE DONE FOR CURRENT WORKFLOW | controlled marketplace target evidence pending |
| N1.24 Cloud / HA | SOURCE DONE FOR CURRENT WORKFLOW | real multi-node evidence pending |
| N1.25 Backup / DR / Upgrade | SOURCE DONE FOR CURRENT WORKFLOW | real disposable restore/upgrade rehearsal pending |
| N1.26 Performance + Accessibility + Release | source workflow + W3C HTML/CSS/WAVE C5 tooling implemented; exact hosted source gate green | real C5/C6 evidence pending |
| N2.0 Stable Production | not eligible | BLOCKED BY TARGET + RELEASE EVIDENCE |

---

## 4. Development execution QA checkpoint

Current workflow: `.github/workflows/development-execution-qa.yml`

Required PR status context: `governance`.

Trigger invariant: **every `pull_request` head targeting `main` must run it; documentation-only heads are not exempt.**

```text
GitHub-hosted Ubuntu
  -> disposable MySQL 8.4
  -> checkout@v7
  -> ephemeral .env.testing only; never root .env
  -> PHP 8.3 + Composer 2
  -> Node 22 + npm 10
  -> composer install
  -> npm install
  -> warning-clean Laravel bootstrap smoke with --fail-on-warning
  -> php scripts/development-readiness.php --full --tests --evidence
  -> all product/source contracts including N1.26
  -> full Laravel/PHPUnit suite with --display-warnings --fail-on-warning
  -> Vitest
  -> TypeScript noEmit
  -> production Vite build
  -> production asset budgets/provenance
  -> upload-artifact@v7 evidence upload
```

Current accepted prerequisite source evidence:

```text
PR #18 exact pre-merge head: 0cccd5e62886676c8946af1ec131614fe2cf4619
Run: 32661429088 (#153)
Conclusion: SUCCESS
Laravel/PHPUnit: 470 passed / 4385 assertions
Artifact: 9498940128
Digest: sha256:a6dd14f69c44c6cbbdfa60070fd28600ae5558c3c954730537ffdd6bce2a8b32

Post-merge dev implementation head: e52e67bbd997c13a04ab2a5a2ca3fef7e992b8ca
Run: 32661923792 (#154)
Conclusion: SUCCESS
Artifact: 9499018803
Digest: sha256:a2c57b8fce6207b7ee9f60adcaa702a6bb25a442a7ce3fc2e5354f72180f3b1b

State reconciliation head before this apply: 048cf684320d992261971c67f90af2f2a302dbb1
Run: 32662762919 (#155)
Conclusion: SUCCESS
Laravel/PHPUnit: 470 passed / 4385 assertions
Artifact: 9499326743
Digest: sha256:67f3b67d99c6c67665eb3233e03eee859da32dfa1e7b32532452eb7d9355c30e
```

Accepted portability source evidence:

- Implementation run #149 (`32634611400`) on exact implementation head `e6042c6949098970759cf56f92d78fb2900eb001`: SUCCESS.
- Final pre-merge exact-head run #150 (`32635512854`) on exact PR #13 head `7b2f7fab0b13f352bd13b2c9b58d78382c125423`: governance SUCCESS; artifact `9492242122`; digest `sha256:972c8a7f8fede167a6e9bcd7c86f2091c246c3d0b7a3737adf8cf8cb6632f506`.
- Run #149 Post-install runtime convergence source contract: PASS.
- DEV-4, Theme, Extension, Studio, Documents, Collections, Publishing/SEO, Admin UX, Forms/Data/Workflows: PASS.
- Data Connections + primary SQL portability + installer DB UX: PASS.
- Development Target QA source contract: **PASS**, explicitly enforcing server-vendor-agnostic core activation/target behavior with optional adapters only.
- Marketplace, Commerce, Customer Portal, CRM/Membership, Search, Collaboration, Automation, AI Platform, Multisite, Enterprise SSO: PASS.
- Public API/SDK, Content Migration, Observability, Forge, Sentinel 2.0, Marketplace 2.0, Cloud/HA, Backup/DR/Upgrade, Performance/Accessibility/Release: PASS.
- Full Laravel/PHPUnit suite at PR #13 checkpoint: **469 passed, 4378 assertions**.
- Vitest: **2 files / 6 tests PASS**.
- TypeScript noEmit: PASS.
- Production frontend build: PASS, Vite `8.2.2`, 3784 modules transformed.
- Production asset budgets/provenance: PASS — build 1,356,563 bytes; JS 1,251,628 bytes (gzip 394,919; initial gzip 223,997); CSS 65,471 bytes; 94 JS / 1 CSS assets.
- Run #149 evidence artifact: `9492014706`; digest `sha256:9f5a34ca634b79d5df1731aa77013b271daf7dc313254f8e46a6b9935ea7d3d5`.

Accepted post-merge Windows portability evidence:

```text
Run: 32638775552
Job: 97192491753
Exact dev source: 21724569daba8e38e581ec603ebd08c2f4d58cad
OS: Windows Server 2025 / X64
Development Target QA Contract: PASS
PORTABLE_CORE_BOUNDARY=PASS
Development Readiness: ready
TypeScript noEmit: PASS
Production Vite build: PASS
Vitest: 6/6 PASS
SQLite target matrix: PASSED (3 tests / 215 assertions)
PORTABLE_WINDOWS_TARGET_QA=PASS
Artifact: 9493045237
Digest: sha256:4e897e55069615b8a524f1039f381974c9450d2df930ad6192d70a468d10fec5
```

The canonical Ubuntu PR workflow intentionally does **not** call the shared WAVE API or claim live W3C/WAVE target success. The Windows portability gate is likewise bounded to PR #13 acceptance and does not replace the remaining target/release gates.

---

## 5. N1.26 W3C HTML / W3C CSS / WAVE / WCAG plan

Canonical plan: `NEXORA_ACCESSIBILITY_CERTIFICATION_PLAN.md`.

### Source wiring implemented

- `scripts/n1-c5-web-standards-certify.php`
- `scripts/n1-c5-web-standards-evidence-verify.php`
- `config/nexora-browser-certification.php` standards routes/gates
- `scripts/n1-c5-browser-performance-certify.php` live C5 integration
- `scripts/n1-c5-evidence-verify.php` final evidence hash binding/checks
- `scripts/lib/n1-c5-contracts.php` source guard
- `scripts/performance-accessibility-release-product-contract-verify.php` N1.26 product guard + PHP syntax checks
- `package.json` commands `certify:web-standards` and `verify:web-standards-evidence`
- `AGENTS.md` mandatory AI accessibility + current-policy precedence rules
- C5 operator kit/runbook updated with W3C/WAVE prerequisites.

### Required real target routes

```text
/
/login
```

Do not remove a failing required route merely to obtain a green result.

### Mandatory real-target standards gates

W3C Nu HTML per required route:

```text
request succeeds
HTML conformance errors = 0
warnings recorded for review
```

W3C CSS per required route:

```text
request succeeds
CSS validity = true
CSS validation errors = 0
warnings recorded for review
profile = css3
```

WAVE per required route:

```text
API evaluation succeeds
Errors = 0
Contrast Errors = 0
Alerts count recorded
all Alerts human-reviewed
```

Shared WAVE credential is `WAVE_API_KEY`; secrets must stay outside source, logs and evidence. `--wave-no-key` is allowed only for an explicit licensed/custom stand-alone endpoint that genuinely requires no per-request key and is forbidden for shared `wave.webaim.org/api/request`.

Canonical C5 invocation:

```bat
set WAVE_API_KEY=***
scripts\n1-c5-browser-performance-certify.bat --base-url=https://YOUR-TARGET --auditor=REAL-AUDITOR --wave-alerts-reviewed --evidence=PATH-TO-C5-EVIDENCE
```

C5 additionally requires Chrome / Edge / Firefox; 360 / 768 / 1440 widths; LTR + RTL; light + dark; keyboard-only navigation; visible focus + correct focus order/restoration; skip link; modal focus containment; real screen-reader labels/names/roles/states; reduced motion; 200% zoom/reflow; forced-colors/high-contrast behavior; no horizontal page overflow; HTTP/security/latency evidence; Web Vitals; and current exact-source + certification-session binding. WAVE output is never an accessibility approval or full WCAG certification.

---

## 6. Preserved rc.93 / Issue #2 boundary

Issue #2 remains **OPEN** as a real legacy-target blocker.

Verified real Windows evidence on the preserved `1.0.0-rc.93` installation:

- run `32631834245`: read-only probe confirmed exact rc.93; compatibility still fails only `environment`, `activation`, `service`, `process`; `nexora:runtime:post-install-reconcile` exists; post-install status remains FAIL.
- run `32631900595`: guarded one-time reconcile was attempted only after exact rc.93 + four-plane + command-presence preconditions passed; command exited `1`; no force/bypass/manual metadata edit was used.
- run `32631967773`: read-only follow-up confirmed the same failed state; activation epoch/fingerprint did not converge for the current process.
- run `32632077872`: read-only source-marker comparison proved preserved rc.93 has the `/install/runtime-handoff` route and `runtimeHandoff` method but lacks the permanent rc.94 finalization implementation: no `postInstallHandoff->verifyAndRecord()` call, no `finalizeCommittedRuntimeIdentity()`, no `post_install_identity_finalized` flag, and no explicit four-plane finalization allow-list.

Conclusion: preserved rc.93 cannot satisfy the later rc.94 post-install convergence through the available in-place rc.93 source path. Its historical mismatch set happened to contain all four currently allow-listed mutable planes. This is evidence of a legacy limitation, not a requirement that a fresh rc.94 target reproduce the same four-plane symptom, and not permission to mutate the preserved installation.

PR #13 portability closure adds a separate fact, not a rewrite of that history: exact post-merge dev source `21724569…` passed the clean Windows portability gate. That proves the current hardened source is portable under the bounded PR #13 contract; it does not turn preserved rc.93 into a PASS.

PR #18 adds a second prerequisite fact: the current-source fresh installer had been self-invalidating source provenance when it published generated base-theme assets under `public/nexora-themes/`; that generated projection is now excluded while authoritative `themes/` remains attested. The fix is merged at implementation head `e52e67bb…` and exact hosted governance is green. This removes the known prerequisite defect but does **not** itself prove the Windows recovery rehearsal.

Run #10 adds current-source diagnostic evidence: exact dev source `048cf684…` committed a real fresh install successfully and the first fresh process reported only `environment` + `activation` mismatches; `service` + `process` were already compatible. Source inspection confirms that service/process are explicitly synchronized in the long-lived installer request before lock commit and that the post-install finalizer treats `environment`, `activation`, `service`, `process` as an allow-list, rejecting only mismatches outside that set. Therefore the carrier's prior exact-four assertion was a stale test interpretation, not a product failure.

Forbidden issue-closing shortcuts:

```text
- overwrite preserved rc.93 with rc.94
- backport rc.94 files into the preserved target
- manually edit fingerprints or installed.lock
- force/bypass runtime checks
- repeatedly rerun the known-failing reconcile and call that progress
```

Safe continuation:

```text
1. keep preserved rc.93 untouched as failure evidence
2. retain PR #13 as accepted/merged with exact post-merge Windows PASS evidence
3. retain PR #18 as accepted/merged source-attestation prerequisite with exact hosted governance evidence
4. require this state-only dev head to be governance-green
5. correct PR #17's pre-recovery assertion so it requires a non-PASS mismatch set wholly contained in the approved mutable allow-list, with zero source/deployment/immutable-plane mismatches, and records the actual observed subset
6. rebind PR #17 to that exact current dev head and use a fresh synchronize event
7. on that separate disposable rc.94 Windows target, prove installer commit, bounded mutable-plane pre-recovery mismatch, not-ready handoff, guarded reconcile, fresh-process zero mismatches/post-install readiness, HTTP route exercise and sealed evidence
8. reconcile Issue #2 only when the issue's legacy evidence and approved replacement recovery acceptance are explicitly reconciled; never manufacture an rc.93 PASS
```

---

## 7. Remaining target / release sequence

PR #13 portability hardening is merged/PASS and PR #18's narrowly required source-attestation prerequisite is merged/governance-green. Continue in the existing roadmap order without reopening completed work:

```text
1. preserve the rc.93 installation and Issue #2 legacy-failure evidence unchanged
2. require the current state-only dev head to pass automatic full governance
3. correct/rebind PR #17 and run a fresh Windows disposable current-source / rc.94 recovery rehearsal using the explicit mutable-plane allow-list semantics
4. reconcile Issue #2 only against that approved replacement acceptance; never manufacture an rc.93 PASS
5. continue broad product QA across major N1.9–N1.26 workflows
6. run real disposable SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix and persist evidence
7. run controlled provider/connector/identity/API/import/observability/Sentinel/Marketplace evidence where applicable
8. prove real HA/multi-node operational behavior
9. perform real disposable backup/restore + upgrade rehearsal and retain recovery evidence
10. complete C5 W3C HTML + W3C CSS + WAVE + browser/AT + HTTP + Web Vitals evidence
11. complete C6 multi-node/final operations + reviewed dependency locks + provenance/release evidence
12. only then mark PR #1 Ready and merge automatically
```

---

## 8. AI execution rules

Every AI/agent must:

1. Read `AGENTS.md`, `NEXORA_AI_PROJECT_STATE.md`, this file, and `NEXORA_ACCESSIBILITY_CERTIFICATION_PLAN.md` before relevant work.
2. Treat this file's Current checkpoint as authoritative over stale historical policy text in older ledger entries.
3. Use GitHub-hosted development QA only for PR source/development evidence; do not silently substitute a local/self-hosted runner for that workflow.
4. Every PR head targeting `main`, including documentation/governance-only heads, must emit required `governance` context.
5. Never promote source/static/jsdom evidence to real browser/target evidence.
6. Never call WAVE output an accessibility approval.
7. Never remove a failing W3C/WAVE required route just to make C5 green.
8. Preserve both W3C HTML and W3C CSS zero-error gates.
9. Never commit WAVE/API credentials.
10. Fix root causes and rerun the exact failing gate.
11. Keep Issue #2 legacy-target evidence and C5 accessibility evidence as separate target boundaries.
12. Preserve rc.93 unchanged; do not require an impossible in-place rc.93 PASS after evidence has proved the required later finalization implementation is absent.
13. Keep PR #1 DRAFT until all required target/release evidence is genuinely final.
14. Do not raise Target/Release Power from hosted source/development CI or the bounded PR #13 portability gate alone.
15. Dependabot update PRs must be reviewed against Nexora architecture and pass the applicable required governance/source checks before merge; do not blindly auto-merge dependency changes.
16. Full development PHPUnit is warning-hard; do not remove or bypass `--fail-on-warning` to obtain a green run.
17. Do not silently broaden a bounded task into future roadmap feature work.
18. Diagnostic carrier test edits that were never merged are not accepted product fixes and must not be treated as roadmap completion evidence.
19. Do not accept a PR #17 recovery run whose event base SHA is older than the current dev HEAD, even if its runtime checks are otherwise green.
20. For rc.94 post-install recovery, treat `environment`, `activation`, `service`, `process` as the complete **allowed mutable set**. Never require an otherwise-correct current-source installer to manufacture mismatches in every allowed plane, and never accept a mismatch outside that set.

---

## 9. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–045 | 2026-08-21/22 | historical ledger + prior CI through `32533537041` | source roadmap through N1.26, prior runner/governance evolution and source closure | current verified Power baseline retained |
| 046 | 2026-08-22 | hosted run #49 / `32565782921` | GitHub-hosted Ubuntu development QA, HA/performance/auth/content fixes; PHPUnit reached 229 PASS | Target/Release unchanged |
| 047 | 2026-08-22 | `b43f15bd…` | Data Connections validator corrected for unsupported nullable fields | Power unchanged |
| 048–053 | 2026-08-22 | `97f95078…` → `572dc958…` + run #82 | W3C Nu HTML/CSS/WAVE target tooling, evidence binding, stand-alone auth boundary and source guards | Source strengthened; Target/Release unchanged |
| 054–060 | 2026-08-22 | runs #83–#94 + `50999b90…` | Search, governance, Distribution, Forms, Enterprise SSO, custom-role/default-tenant fail-fast roots closed | Target/Release unchanged |
| 061 | 2026-08-22 | prior dashboard apply | restored explicitly historical `Actions: DEFERRED BY USER` compatibility marker while retaining hosted-only current policy | governance repair only |
| 062 | 2026-08-22 | runs #113–#121 | C6/rc14/rc21/rc3/rc4/rc5 stale contract and CI-environment blockers closed against current analyzers/source | Target/Release unchanged |
| 063 | 2026-08-22 | `f840a00c…` → `a102eb0c…` | V30 distributed leadership/drain semantic guards; V35/V39/V40/V42 payload schema; V56 readiness metric alignment | safety preserved; Target/Release unchanged |
| 064 | 2026-08-22 | run #129 + `52ce08a4…` | architecture chain reached final integration; RuntimeSynchronizer stale `0.4.0` expectation aligned to canonical runtime manifest `0.5.0` | Target/Release unchanged |
| 065 | 2026-08-22 | run #130 `32602165519`, artifact `9483280452` | first exact-head full hosted Development QA success after fail-fast closure | Source evidence consolidated; Target/Release unchanged |
| 066 | 2026-08-23 | Dependabot PR #3, run #603 | removed nonexistent Docker updater, added Composer, retained npm/Actions schedules; repaired required `governance` status production; governance and Source certification green | repository governance strengthened; Power unchanged |
| 067 | 2026-08-23 | merge `eaa42f19…` | PR #3 squash-merged to `main` after required checks passed | dependency-maintenance infrastructure only; Target/Release unchanged |
| 068 | 2026-08-23 | `95a85b42…` + `bc8f5196…` + merge `b0094ab2…` | existing full Development QA job publishes required `governance` context; Dependabot config carried into dev; current `main` integrated as real second parent | PR #1 mergeability restored; Target/Release unchanged |
| 069 | 2026-08-23 | run #133 `32603121260`, job `97104286587`, artifact `9483481268` | exact post-main-integrated full Development QA/governance PASS; historical PHPUnit printer reported 122 passed / 347 warnings / 4378 assertions; frontend green | historical application source checkpoint; Target/Release unchanged |
| 070 | 2026-08-23 | `cc06df1a…` | canonical live state synchronized to run #133 + merged Dependabot/governance evidence | Power unchanged |
| 071 | 2026-08-23 | current-head status inspection | discovered `cc06df1a…` had zero contexts because Markdown was ignored; identified a real latest-SHA ruleset merge-governance hole | no score change |
| 072 | 2026-08-23 | `375bfcb3…`, run #135 `32603527701`, artifact `9483576591` | removed all PR path ignores so every head emits `governance`; complete Development QA passed on corrected workflow | governance closure; Target/Release unchanged |
| 073 | 2026-08-23 | maintenance merges through `f854c50c…`, integration `e5d41dbd…`, run #137 | final Dependabot batch + TypeScript 7 integrated and full Development QA re-certified | Source maintenance strengthened; Target/Release unchanged |
| 074 | 2026-08-23 | `1874e675…`, run #138 `32610019558` | moved development evidence upload to `actions/upload-artifact@v7`; full QA green and Node 20 action-runtime warning removed | governance/tooling only; Target/Release unchanged |
| 075 | 2026-08-23 | diagnostic runs #139–#141 | traced PHPUnit warnings to missing root `.env`; rejected root `.env` workaround because RC14 correctly forbids it | root cause isolated without weakening contract; Power unchanged |
| 076 | 2026-08-23 | `eb86afd3…`, run #142 `32611296975`, artifact `9485661803` | switched CI bootstrap to ephemeral `.env.testing`, made full PHPUnit warning-hard, source-guarded the flag; **469 passed / 0 warnings / 4378 assertions** | Source/governance quality strengthened; Target/Release unchanged |
| 077 | 2026-08-23 | prior dashboard apply | authoritative checkpoint synchronized to warning-clean implementation evidence | Power unchanged |
| 078 | 2026-08-23 | Windows runs `32631834245`, `32631900595`, `32631967773`, `32632077872`; PR #13 run #149 `32634611400`, artifact `9492014706` | reconciled stale rc.93 in-place continuation with real evidence; recorded preserved rc.93 as legacy failure evidence and PR #13 portability source acceptance | Power unchanged |
| 079 | 2026-08-23 | exact final PR #13 head `7b2f7fab…`; governance run #150 `32635512854`, artifact `9492242122`; merge `21724569…`; PR #14 closed unmerged | final exact-head governance passed and PR #13 portability hardening merged to dev without changing roadmap semantics | Target/Release unchanged |
| 080 | 2026-08-23 | diagnostic PR #15 runs through hosted Windows investigation; PR #15 closed unmerged | identified that forcing the entire PHPUnit suite onto SQLite `:memory:` broadened the historical Windows acceptance gate and generated unrelated rate-limiter/order artifacts; diagnostic test patches were discarded | Power unchanged |
| 081 | 2026-08-23 | clean PR #16 run `32638775552`, job `97192491753`, artifact `9493045237`, digest `sha256:4e897e55069615b8a524f1039f381974c9450d2df930ad6192d70a468d10fec5`; PR #16 closed unmerged | exact post-merge Windows portability gate PASS on literal dev SHA `21724569…`; PR #13 bounded acceptance closed with real evidence | Target/Release scores unchanged |
| 082 | 2026-08-23 | prior state-only dashboard reconciliation | canonical checkpoint advanced from the completed PR #13 sequence to the already-approved separate disposable current-source rc.94 recovery/upgrade rehearsal; no product/runtime code changed | Power unchanged |
| 083 | 2026-08-23 | PR #18 head `0cccd5e…`; governance run #153 `32661429088`, artifact `9498940128`; merge `e52e67bb…`; post-merge run #154 `32661923792`, artifact `9499018803`; PR #19 closed unmerged | fixed generated `public/nexora-themes/` self-attestation drift, kept authoritative `themes/` attested, added regression coverage and merged the bounded prerequisite after exact acceptance | Source trust boundary corrected; Target/Release unchanged |
| 084 | 2026-08-24 | state head `048cf684…`; governance run #155 `32662762919`, artifact `9499326743` | reconciled canonical live state and proved the state-only head under full hosted governance before accepting new recovery evidence | Power unchanged |
| 085 | 2026-08-24 | eligible PR #17 run #10 `32663450531`, artifact `9499373095`; current rc.94 installer/finalizer/source-contract review | real fresh install commit passed; diagnosed stale exact-four carrier assertion; explicitly reconciled four-plane terminology from required full set to bounded mutable allow-list without weakening immutable fail-closed behavior | Power unchanged; target gate still pending |

---

## 10. Exact next action

```text
A. Require automatic full `governance` on this new state-only dev head before attributing any new target evidence to it.
B. Preserve the existing rc.93 installation and Issue #2 evidence unchanged; do not retry destructive/manual/in-place recovery on that target.
C. Correct PR #17's diagnostic pre-recovery assertion: compatibility must be non-PASS; the actual mismatch set must be non-empty and wholly contained in `environment`, `activation`, `service`, `process`; no mismatch outside that allow-list is permitted; post-install status must be not-ready. Record the exact observed subset in evidence instead of forcing all four.
D. After this state-only head is governance-green, verify PR #17 still targets `dev/n1-0b-core-functional-qa`, refresh/rebind it so the new pull_request event's base SHA equals the new exact dev HEAD, and reject all older event-bound runs as stale evidence.
E. On that fresh Windows run, require the real installer commit to succeed, the bounded mutable-plane pre-recovery condition to pass, the guarded `nexora:runtime:post-install-reconcile --confirm=RECONCILE` to succeed, fresh-process compatibility to have zero mismatches, `post-install-status --assert-ready` to pass with a current receipt, and `/login`, authenticated `/admin`, and `/install/runtime-handoff` HTTP probes to match the bounded contract.
F. Review the uploaded source binding, before/after compatibility, before/after handoff, reconcile receipt, route evidence and sealed summary before accepting Issue #2's replacement recovery gate.
G. Reconcile/close Issue #2 only if that approved replacement acceptance is genuinely satisfied; never manufacture an rc.93 PASS and do not raise unrelated roadmap scores from this bounded gate alone.
H. Then continue separate development-target product QA, the real five-engine disposable DB matrix, and provider/identity/API/import/observability/Sentinel/Marketplace/HA/recovery evidence in the existing roadmap order.
I. Complete real C5 W3C HTML/CSS/WAVE/browser/AT/HTTP/Web-Vitals evidence and C6 reviewed-lock/final release evidence.
J. Keep PR #1 DRAFT and keep TARGET POWER 50.0% / RELEASE POWER 25.0% until those broader real boundaries are satisfied.
```
