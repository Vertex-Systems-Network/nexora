# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE** — Update this dashboard after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply.
>
> `NEXORA_AI_PROJECT_STATE.md` is preserved as append-only historical/cross-session history. This dashboard's **Current checkpoint** is authoritative for current branch head, runner policy, active evidence and `NEXT ACTION`. `NEXORA_ACCESSIBILITY_CERTIFICATION_PLAN.md` is the mandatory UI/accessibility operator + AI plan. **SOURCE DONE != TARGET VERIFIED.**

---

## 1. Current checkpoint

- Date: `2026-08-22`
- Branch: `dev/n1-0b-core-functional-qa`
- PR #1: **DRAFT + OPEN + MERGEABLE**; do not mark Ready or merge until required real target/release gates pass.
- Current code/governance head before this dashboard-only commit: **`7d38bdf8236630ff105b4666a6fcb06013462b21`**.
- Development execution QA policy: **GitHub-hosted `ubuntu-latest` only**, PHP >= 8.3, Node >= 22, disposable MySQL 8.4. No self-hosted/local/Laragon runner is eligible for this workflow.
- GitHub-hosted development QA is development-checkout/source-functional evidence only. It does not prove the installed rc.93 Laragon recovery target, real browser behavior, W3C/WAVE target results, provider integrations, five-engine DB matrix, HA, recovery rehearsal, or final release certification.
- Latest completed development QA before this standards batch: run #49 `32565782921` on `ff9a0444…` reached **229 PHPUnit PASS** before Data Connections fail-fast; Vitest 6/6, TypeScript, Vite build and production asset budgets were PASS.
- Data Connections validation root was fixed in `b43f15bd…`: unsupported optional connector fields now use semantic `prohibited` rules instead of `prohibited + string` false failures on converted null values.
- Issue #2: **OPEN**. Existing rc.93 target still needs compatibility + post-install readiness + `/login` + `/admin` evidence.
- Current source release: `1.0.0-rc.94`; installer protocol `v5.29`; generation `n1-v5.29`.
- W3C Nu HTML + W3C CSS Validation Service + WAVE C5 source tooling is implemented and mandatory for final target accessibility closure.
- N1.26 Development Readiness now also `php -l` syntax-checks the live C5 PHP runner/verifier scripts so remote-target tooling cannot carry an undetected PHP parse error merely because live WAVE/W3C calls are intentionally not executed in PR QA.

---

## 2. Weighted Project Power Score

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

No score is increased by W3C/WAVE source tooling alone. Target/Release Power moves only from real exact-source target evidence.

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
| N1.26 Performance + Accessibility + Release | source workflow + W3C HTML/CSS/WAVE C5 tooling implemented; latest hosted QA pending | real C5/C6 evidence pending |
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

Known chain before the current standards batch:

- Performance critical route budgets: PASS.
- Suspended-login security boundary: PASS.
- Cloud Operations recovery flow: PASS.
- Distributed Runtime Hardening stale final-class test issue: fixed.
- Content Collection omitted optional-field validation issue: fixed.
- Data Connections unsupported empty-field validation issue: fixed in `b43f15bd…`; integrated confirmation is part of the latest hosted run sequence.
- W3C HTML/CSS/WAVE source contract is now part of the mandatory N1.26 product contract executed by Development Readiness.

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

Default checker:

```text
https://validator.w3.org/nu/
```

Optional approved private/local checker:

```text
--w3c-validator-url=
```

### W3C CSS gate per route

```text
request succeeds
CSS validity = true
CSS validation errors = 0
warnings recorded for review
profile = css3
```

Default service:

```text
https://jigsaw.w3.org/css-validator/validator
```

Optional approved private/local service:

```text
--w3c-css-validator-url=
```

### WAVE gate per route

```text
API evaluation succeeds
Errors = 0
Contrast Errors = 0
Alerts count recorded
all Alerts human-reviewed
```

Shared WAVE API credential:

```text
WAVE_API_KEY
```

The key must stay outside source, logs and evidence. Private/intranet targets may use an approved licensed stand-alone endpoint with:

```text
--wave-api-url=
--wave-key-env=
```

WAVE output is **never** treated as an accessibility approval or full WCAG certification. Automated WAVE evidence supplements, but does not replace, real browser + assistive-technology observation.

### Canonical C5 invocation

```bat
set WAVE_API_KEY=***
scripts\n1-c5-browser-performance-certify.bat --base-url=https://YOUR-TARGET --auditor=REAL-AUDITOR --wave-alerts-reviewed --evidence=PATH-TO-C5-EVIDENCE
```

C5 still additionally requires:

- Chrome / Edge / Firefox.
- 360 / 768 / 1440 widths.
- LTR + RTL.
- light + dark.
- keyboard-only navigation.
- visible focus + correct focus order/restoration.
- skip link.
- modal focus containment.
- screen-reader labels/names/roles/states.
- reduced motion.
- 200% zoom/reflow.
- forced-colors/high-contrast behavior.
- no horizontal page overflow.
- HTTP/security/latency evidence.
- Web Vitals within configured ceilings.
- current exact-source + certification-session binding.

---

## 6. Live rc.93 / Issue #2 boundary

Do not replace installed rc.93 with rc.94 merely as a recovery shortcut.

Required live commands:

```bat
php artisan nexora:runtime:compatibility-status --deep
php artisan nexora:runtime:post-install-status --assert-ready
```

Then directly exercise:

```text
/login
/admin
```

Issue #2 may close only after those real target checks pass. GitHub-hosted Ubuntu CI cannot satisfy this boundary.

---

## 7. Remaining target / release sequence

```text
1. finish latest GitHub-hosted development QA to zero fail-fast roots
2. recover and verify existing rc.93 Laragon target
3. exercise /login + /admin and close issue #2 only on evidence
4. run separate development target QA across major N1.9–N1.26 product workflows
5. run real disposable SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix
6. run controlled provider/connector/identity/API/import/observability/Sentinel/Marketplace evidence where applicable
7. perform real disposable backup/restore + upgrade rehearsal
8. complete C5 W3C HTML + W3C CSS + WAVE + browser/AT + HTTP + Web Vitals evidence
9. complete C6 multi-node/final operations + reviewed dependency locks + provenance/release evidence
10. only then mark PR #1 Ready and merge automatically
```

---

## 8. AI execution rules

Every AI/agent must:

1. Read `AGENTS.md`, `NEXORA_AI_PROJECT_STATE.md`, this file, and `NEXORA_ACCESSIBILITY_CERTIFICATION_PLAN.md` before relevant work.
2. Treat this file's Current checkpoint as authoritative over stale historical policy text in older ledger entries.
3. Use GitHub-hosted development QA only; do not silently substitute a local/self-hosted runner.
4. Never promote source/static/jsdom evidence to real browser/target evidence.
5. Never call WAVE output an accessibility approval.
6. Never remove a failing W3C/WAVE required route just to make C5 green.
7. Preserve both W3C HTML and W3C CSS zero-error gates.
8. Never commit WAVE/API credentials.
9. Fix root causes and rerun the exact failing gate.
10. Keep issue #2 and C5 accessibility evidence as separate target boundaries.
11. Keep PR #1 DRAFT until all required target/release evidence is genuinely final.
12. Do not raise Target/Release Power from hosted source/development CI alone.

---

## 9. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 001–045 | 2026-08-21/22 | historical ledger + prior CI through `32533537041` | source roadmap through N1.26, prior runner/governance evolution and source closure | current verified Power baseline retained |
| 046 | 2026-08-22 | hosted run #49 / `32565782921` | GitHub-hosted Ubuntu development QA, HA/performance/auth/content fixes; PHPUnit reached 229 PASS | Target/Release unchanged |
| 047 | 2026-08-22 | `b43f15bd…` | Data Connections validator corrected for unsupported nullable fields | pending integrated hosted confirmation; Power unchanged |
| 048 | 2026-08-22 | `97f95078…` → `7cf4e1cc…` | W3C Nu HTML + WAVE target runner/verifier, C5 evidence binding, source contracts, AI plan, AGENTS/package/operator-kit wiring | Source candidate strengthened; Target/Release unchanged |
| 049 | 2026-08-22 | `b8aa616e…` → `b171d928…` | expanded standards gate to W3C CSS SOAP validation, CSS zero-error evidence verification, C5 forwarding/contracts/final manifest/AI plan | Source candidate strengthened; Target/Release unchanged |
| 050 | 2026-08-22 | `70a15470…` | N1.26 product source gate now parser-checks live C5 PHP executables with `php -l` | Source candidate strengthened; Target/Release unchanged |
| 051 | 2026-08-22 | `7d38bdf8…` | AI handoff defines live progress checkpoint precedence over preserved historical runner-policy entries | governance corrected; Power unchanged |

---

## 10. Exact next action

```text
A. Freeze source/docs after this dashboard sync and let the latest GitHub-hosted PR run execute.
B. If red: inspect exact fail-fast root, apply smallest architecture-correct fix, rerun GitHub-hosted QA.
C. If green: record exact run/job/artifact evidence in PR metadata/handoff without falsely promoting target evidence.
D. Do not execute/claim W3C HTML/CSS/WAVE target PASS until a reachable exact-source target + real auditor + WAVE credential/private licensed endpoint are available.
E. Continue live rc.93 recovery and target/release sequence without mixing evidence classes.
```
