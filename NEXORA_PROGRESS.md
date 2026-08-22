# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE** — Update this dashboard after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply. **After every meaningful apply**, update this dashboard with current evidence, blockers and next action.
>
> `NEXORA_AI_PROJECT_STATE.md` is preserved as append-only historical/cross-session history. This dashboard's **Current checkpoint** is authoritative for current branch head, runner policy, active evidence and `NEXT ACTION`. `NEXORA_ACCESSIBILITY_CERTIFICATION_PLAN.md` is the mandatory UI/accessibility operator + AI plan. **SOURCE DONE != TARGET VERIFIED.**

---

## 1. Current checkpoint

- Date: `2026-08-22`
- Branch: `dev/n1-0b-core-functional-qa`
- PR #1: **DRAFT + OPEN + MERGEABLE**; do not mark Ready or merge until required real target/release gates pass.
- Current implementation head before this dashboard-only commit: **`6877c6ff3c967b9de368676d575d3140b377507d`**.
- Development execution QA policy: **GitHub-hosted `ubuntu-latest` only**, PHP >= 8.3, Node >= 22, disposable MySQL 8.4. No self-hosted/local/Laragon runner is eligible for this workflow.
- User-directed execution order for the current pass: finish deterministic source/static cleanup first; inspect the GitHub-hosted runner only at the end. Code pushes may still auto-trigger the PR workflow, but intermediate runs are not authoritative until the final exact head is selected.
- GitHub-hosted development QA is development-checkout/source-functional evidence only. It does not prove the installed rc.93 Laragon recovery target, real browser behavior, W3C/WAVE target results, provider integrations, five-engine DB matrix, HA, recovery rehearsal, or final release certification.
- Latest inspected integrated hosted run: #90 `32571452424`, job `97027379902`, exact source `7c49cf7d92ba1c2234e56b3b1217eed97e536300`. All source/product contracts including N1.26 W3C HTML/CSS/WAVE wiring passed; Data Connections, Cloud/HA, Distribution, Search, Enterprise SSO/Multisite, valid Forms submission, Vitest 6/6, TypeScript, Vite build and asset budgets/provenance passed. PHPUnit reached **277 PASS / 1 FAIL / 191 pending**; the single fail-fast root was an auth-required public Form test receiving no session error because the shared route throttle identity had been consumed earlier in the suite.
- Run #90 evidence artifact: `9475533069`.
- Source fixes after run #90, intentionally prepared before final runner inspection:
  - `aa04c358df99db7690e9f078f0458c44a5db5c42`: every public Forms POST in `FormWorkflowTest` now uses an isolated documentation-only TEST-NET client identity so unrelated suite traffic cannot turn validation/honeypot/404 assertions into 429s; production `throttle:10,1` is unchanged.
  - `465176f26c979fee170d8c4872cbe1d2e6746d66`: Customer Portal password-login acceptance uses its own TEST-NET identity so the global `POST /login` `throttle:5,1` cache cannot hide portal routing behavior; production auth/throttle remains unchanged.
  - `6877c6ff3c967b9de368676d575d3140b377507d`: `N100V41ResourceEnvelopeArchitectureTest` now expects queue payload schema `13`, matching the current runtime resource-envelope contract and RuntimeVersionGuard/AppServiceProvider generation.
- PR-wide static review found no second direct Collection built-in callback arity hazard after the Laravel 13-safe Forms fix, and no remaining changed-test `POST /login` call without an isolated client identity.
- Marketplace 2.0, Marketplace lifecycle, Media, Content Migration, Observability, Publishing, Sentinel, SEO, Settings, Studio, Theme and DB round-trip pending blocks were statically reviewed for the current known failure classes; no additional deterministic source root was identified before final execution.
- W3C/WAVE stand-alone authentication remains hardened: shared `wave.webaim.org/api/request` always requires an API key; explicit `--wave-no-key` is allowed only for a custom stand-alone endpoint that genuinely requires no request key, and evidence verifies that boundary.
- Issue #2: **OPEN** and is the only current open repository issue. Existing rc.93 target still needs compatibility + post-install readiness + `/login` + `/admin` evidence.
- Current source release: `1.0.0-rc.94`; installer protocol `v5.29`; generation `n1-v5.29`.
- W3C Nu HTML + W3C CSS Validation Service + WAVE C5 source tooling is implemented and mandatory for final target accessibility closure.
- N1.26 Development Readiness `php -l` syntax-checks the live C5 PHP runner/verifier scripts so remote-target tooling cannot carry an undetected PHP parse error merely because live WAVE/W3C calls are intentionally not executed in PR QA.

### Governance compatibility / evidence semantics

- Historical Actions/self-hosted policy in the append-only ledger is superseded for this PR workflow. Current Development execution QA is GitHub-hosted `ubuntu-latest` only.
- **Target Power** is evidence-based and never increases from source CI, source contracts, jsdom, or GitHub-hosted development-checkout evidence alone.
- Current target/release scoring boundary remains: `TARGET POWER    50.0%` and `RELEASE POWER   25.0%` until real exact-source target/release evidence justifies a change.
- Older ledger text describing self-hosted certification is historical/superseded for this PR development workflow. Never treat it as current runner policy.

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

No score is increased by W3C/WAVE source tooling, static review, or hosted development QA alone. Target/Release Power moves only from real exact-source target evidence.

---

## 3. Current roadmap state

| Block | Source state | Target / release state |
|---|---|---|
| DEV-0–DEV-4 | substantial source closure | live rc.93 recovery + broad product QA pending |
| DEV-5 SQL/Data Services | source/harness substantially closed | real disposable DB matrix + connector evidence pending |
| N1.9–N1.21 | SOURCE DONE for bounded workflows | target execution pending |
| N1.22 Sentinel 2.0 | SOURCE DONE FOR CURRENT WORKFLOW | controlled package target evidence pending |
| N1.23 Marketplace 2.0 | SOURCE DONE FOR CURRENT WORKFLOW | controlled marketplace target evidence pending |
| N1.24 Cloud / HA | SOURCE DONE FOR CURRENT WORKFLOW | real multi-node evidence pending |
| N1.25 Backup / DR / Upgrade | SOURCE DONE FOR CURRENT WORKFLOW | real disposable restore/upgrade rehearsal pending |
| N1.26 Performance + Accessibility + Release | source workflow + W3C HTML/CSS/WAVE C5 tooling implemented; latest inspected run #90 source gates green | real C5/C6 evidence pending |
| N2.0 Stable Production | not eligible | BLOCKED BY TARGET + RELEASE EVIDENCE |

---

## 4. Development execution QA checkpoint

Current workflow: `.github/workflows/development-execution-qa.yml`

Required behavior:

```text
GitHub-hosted Ubuntu
  -> PHP 8.3
  -> Node 22
  -> disposable MySQL 8.4
  -> composer install
  -> npm install
  -> php scripts/development-readiness.php --full --tests --evidence
  -> all product/source contracts including N1.26
  -> php -l live C5 standards/evidence runners
  -> PHPUnit fail-fast
  -> Vitest
  -> TypeScript
  -> production Vite build
  -> production asset budgets/provenance
```

Latest inspected fail-fast chain through run #90:

- Performance critical route budgets: PASS.
- Suspended-login security boundary: PASS.
- Cloud Operations recovery flow: PASS.
- Distributed Runtime Hardening: PASS.
- Content Collections: PASS.
- Data Connections: PASS.
- Distribution: PASS.
- Search tenant isolation: PASS.
- Enterprise SSO governance: PASS.
- Multisite/Organizations: PASS.
- Forms valid validation/storage/event flow: PASS after Laravel 13 callback fix.
- Forms auth-required guest test: run #90 exposed only shared throttle-state collision; isolated in `aa04c358…` without changing production throttles.
- Customer Portal login: proactively isolated in `465176f2…` because it is another later low-limit `POST /login` acceptance path.
- Resource Envelope architecture schema: stale expected `11` corrected to current contract `13` in `6877c6ff…`.
- W3C HTML/CSS/WAVE source contract: PASS.
- Vitest 6/6, TypeScript, production frontend build, asset budgets/provenance: PASS.

The PR workflow intentionally does **not** call the shared WAVE API or claim live W3C/WAVE target success.

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

### W3C Nu HTML gate per route

```text
request succeeds
HTML conformance errors = 0
warnings recorded for review
```

Default checker: `https://validator.w3.org/nu/`.

Optional approved private/local checker: `--w3c-validator-url=`.

### W3C CSS gate per route

```text
request succeeds
CSS validity = true
CSS validation errors = 0
warnings recorded for review
profile = css3
```

Default service: `https://jigsaw.w3.org/css-validator/validator`.

Optional approved private/local service: `--w3c-css-validator-url=`.

### WAVE gate per route

```text
API evaluation succeeds
Errors = 0
Contrast Errors = 0
Alerts count recorded
all Alerts human-reviewed
```

Shared WAVE API credential: `WAVE_API_KEY`. The secret must stay outside source, logs and evidence.

Licensed custom stand-alone endpoint modes:

```text
--wave-api-url=https://CUSTOM-ENDPOINT
--wave-key-env=CUSTOM_SECRET_ENV
```

or, only if that explicit stand-alone endpoint genuinely requires no per-request key:

```text
--wave-api-url=https://CUSTOM-ENDPOINT
--wave-no-key
```

`--wave-no-key` is forbidden for the shared `wave.webaim.org/api/request` service and fails closed. WAVE output is **never** treated as an accessibility approval or full WCAG certification.

### Canonical C5 invocation

```bat
set WAVE_API_KEY=***
scripts\n1-c5-browser-performance-certify.bat --base-url=https://YOUR-TARGET --auditor=REAL-AUDITOR --wave-alerts-reviewed --evidence=PATH-TO-C5-EVIDENCE
```

C5 still additionally requires Chrome / Edge / Firefox; 360 / 768 / 1440 widths; LTR + RTL; light + dark; keyboard-only navigation; visible focus + correct focus order/restoration; skip link; modal focus containment; screen-reader labels/names/roles/states; reduced motion; 200% zoom/reflow; forced-colors/high-contrast behavior; no horizontal page overflow; HTTP/security/latency evidence; Web Vitals; and current exact-source + certification-session binding.

---

## 6. Live rc.93 / Issue #2 boundary

Do not replace installed rc.93 with rc.94 merely as a recovery shortcut.

Required live commands:

```bat
php artisan nexora:runtime:compatibility-status --deep
php artisan nexora:runtime:post-install-status --assert-ready
```

Then directly exercise `/login` and `/admin`.

Issue #2 may close only after those real target checks pass. GitHub-hosted Ubuntu CI cannot satisfy this boundary.

---

## 7. Remaining target / release sequence

```text
1. finish deterministic source/static cleanup before runner inspection
2. run one authoritative GitHub-hosted development QA on the final exact source head; fix any actual remaining fail-fast root and repeat only as necessary
3. recover and verify existing rc.93 Laragon target
4. exercise /login + /admin and close issue #2 only on evidence
5. run separate development target QA across major N1.9–N1.26 product workflows
6. run real disposable SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix
7. run controlled provider/connector/identity/API/import/observability/Sentinel/Marketplace evidence where applicable
8. perform real disposable backup/restore + upgrade rehearsal
9. complete C5 W3C HTML + W3C CSS + WAVE + browser/AT + HTTP + Web Vitals evidence
10. complete C6 multi-node/final operations + reviewed dependency locks + provenance/release evidence
11. only then mark PR #1 Ready and merge automatically
```

---

## 8. AI execution rules

Every AI/agent must:

1. Read `AGENTS.md`, `NEXORA_AI_PROJECT_STATE.md`, this file, and `NEXORA_ACCESSIBILITY_CERTIFICATION_PLAN.md` before relevant work.
2. Treat this file's Current checkpoint as authoritative over stale historical policy text in older ledger entries.
3. Use GitHub-hosted development QA only; do not silently substitute a local/self-hosted runner.
4. For the current user-directed pass, complete deterministic source/static review before spending time monitoring Actions; inspect the final exact-head hosted run at the end.
5. Never promote source/static/jsdom evidence to real browser/target evidence.
6. Never call WAVE output an accessibility approval.
7. Never remove a failing W3C/WAVE required route just to make C5 green.
8. Preserve both W3C HTML and W3C CSS zero-error gates.
9. Never commit WAVE/API credentials.
10. Fix root causes and rerun the exact failing gate.
11. Keep issue #2 and C5 accessibility evidence as separate target boundaries.
12. Keep PR #1 DRAFT until all required target/release evidence is genuinely final.
13. Do not raise Target/Release Power from hosted source/development CI alone.

---

## 9. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–045 | 2026-08-21/22 | historical ledger + prior CI through `32533537041` | source roadmap through N1.26, prior runner/governance evolution and source closure | current verified Power baseline retained |
| 046 | 2026-08-22 | hosted run #49 / `32565782921` | GitHub-hosted Ubuntu development QA, HA/performance/auth/content fixes; PHPUnit reached 229 PASS | Target/Release unchanged |
| 047 | 2026-08-22 | `b43f15bd…` | Data Connections validator corrected for unsupported nullable fields | later hosted runs confirm Data Connections flow PASS; Power unchanged |
| 048 | 2026-08-22 | `97f95078…` → `7cf4e1cc…` | W3C Nu HTML + WAVE target runner/verifier, C5 evidence binding, source contracts, AI plan, AGENTS/package/operator-kit wiring | Source candidate strengthened; Target/Release unchanged |
| 049 | 2026-08-22 | `b8aa616e…` → `b171d928…` | expanded standards gate to W3C CSS SOAP validation, CSS zero-error evidence verification, C5 forwarding/contracts/final manifest/AI plan | Source candidate strengthened; Target/Release unchanged |
| 050 | 2026-08-22 | `70a15470…` | N1.26 product source gate parser-checks live C5 PHP executables with `php -l` | later hosted runs confirm source gate PASS; Target/Release unchanged |
| 051 | 2026-08-22 | `7d38bdf8…` | AI handoff defines live progress checkpoint precedence over preserved historical runner-policy entries | governance corrected; Power unchanged |
| 052 | 2026-08-22 | run #82 `32569505463`, artifact `9474981496` | W3C HTML/CSS/WAVE source gate green; PHPUnit 236 PASS before Search 409; frontend/build all green | Target/Release unchanged |
| 053 | 2026-08-22 | `a1617fa4…` → `572dc958…` | WAVE stand-alone auth boundary + evidence verification + source guard; shared API key remains mandatory | source hardening; Target/Release unchanged |
| 054 | 2026-08-22 | `5f1a1e63…` onward | Search protocol, governance, Distribution, Forms and Enterprise SSO fail-fast roots closed through hosted runs #83–#90 | Source functional evidence advanced; Target/Release unchanged |
| 055 | 2026-08-22 | run #90 `32571452424`, artifact `9475533069` | all source/product gates green; PHPUnit reached 277 PASS with one auth-required Forms throttle-state failure; frontend/build green | Target/Release unchanged |
| 056 | 2026-08-22 | `aa04c358…` | all public FormWorkflow POSTs isolated from unrelated suite throttle state using TEST-NET identities; production throttle unchanged | pending final hosted confirmation; Power unchanged |
| 057 | 2026-08-22 | `465176f2…` | later Customer Portal login path proactively isolated from shared login throttle state; production auth/throttle unchanged | pending final hosted confirmation; Power unchanged |
| 058 | 2026-08-22 | `6877c6ff…` | stale Resource Envelope architecture expectation updated from queue schema 11 to current contract 13 | deterministic source/test alignment; pending final hosted confirmation; Power unchanged |

---

## 10. Exact next action

```text
A. Keep the current implementation head stable while completing any last deterministic source/static review; do not spend time monitoring intermediate auto-triggered runs.
B. Once source/static cleanup is finished, inspect/run the GitHub-hosted PR QA on the final exact head only.
C. If red: inspect the exact next fail-fast root, apply the smallest architecture-correct fix, and repeat final-head hosted QA.
D. If green: record exact run/job/artifact evidence without promoting it to target evidence.
E. Then continue the real rc.93 Laragon recovery: compatibility PASS -> post-install readiness PASS -> /login -> /admin -> issue #2 closure only on real target evidence.
F. Do not execute/claim W3C HTML/CSS/WAVE target PASS until a reachable exact-source target + real auditor + WAVE credential/private licensed endpoint are available.
G. Continue DB/provider/identity/import/observability/Sentinel/Marketplace/HA/recovery/C5/C6 target-release evidence in the prescribed order; keep PR #1 DRAFT until genuinely final.
```
