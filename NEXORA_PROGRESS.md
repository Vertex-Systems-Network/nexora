# Nexora Progress Dashboard

> **MANDATORY UPDATE FILE** — Update this dashboard after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, or release/certification apply.
>
> `NEXORA_AI_PROJECT_STATE.md` is the append-only historical/cross-session ledger. This dashboard is the authoritative **live** execution state for current evidence, blockers and `NEXT ACTION`. Historical details remain preserved in Git history and the append-only ledger. `SOURCE DONE != TARGET VERIFIED` and bounded target acceptance never implies final release certification.

---

## 1. Current checkpoint

- Date: `2026-09-01` (Asia/Karachi).
- Active engineering branch: `dev/n1-0b-core-functional-qa` (PR #1; this dashboard reconciliation is state-only and does not alter accepted runtime/product implementation bytes).
- Accepted runtime/product implementation head remains **`e52e67bbd997c13a04ab2a5a2ca3fef7e992b8ca`**. Later canonical heads through N1.10 closure are state/evidence reconciliation commits unless explicitly recorded otherwise.
- Current source release: `1.0.0-rc.94`; installer protocol `v5.29`; generation `n1-v5.29`.
- Current certified `main`: **`f854c50c0f7687fc87fdfab01b49562392af4ef4`**.
- PR #1 remains **DRAFT + OPEN**. It must not be marked Ready or merged until all applicable target/release gates genuinely pass.
- Development execution QA remains GitHub-hosted `ubuntu-latest` only, PHP >= 8.3, Node >= 22, disposable MySQL 8.4. Required PR status context is **`governance`** on every PR head targeting `main`, including documentation/state-only heads.
- Source `composer.lock` remains intentionally absent. Hosted dependency resolution is development evidence only and does not promote reviewed release locks.
- W3C Nu HTML + W3C CSS + WAVE C5 tooling is source-wired, but final accessibility/browser certification still requires real target evidence.

### Accepted bounded target gates

#### Issue #2 / rc.94 recovery

- **ACCEPTED + CLOSED** via the explicitly approved separate disposable current-source rc.94 recovery rehearsal.
- Preserved `1.0.0-rc.93` installation remains untouched immutable historical legacy-failure evidence; never rewrite it into an in-place PASS.
- Diagnostic PR #17 remains **CLOSED + UNMERGED**.
- Accepted run `32667462959`, job `97263035327`, exact source `a6b6462954edddbe138bc26577625bac2a8bddd2`, carrier `452545490ce1f62d340080d619e93e5e20423da7`.
- Artifact `9500449768`, digest `sha256:1ac7ccf409181322e74ca1444bfd2ed3cca1539875eba398ad0d98a06e7e4aba`, independently reviewed.
- Fresh install, guarded reconcile, zero post-recovery mismatches, current ready receipt and required guest/authenticated HTTP routes passed.

#### N1.9 Marketplace

- **BOUNDED TARGET VERIFIED**. Issue #20 is **CLOSED completed**; diagnostic PR #21 is **CLOSED + UNMERGED**.
- Accepted run `32671245015`, job `97272315620`, exact source `8e359f07dc6b608b0d09468386fca13f066337a1`, carrier `eaaa8a91100f866511256162078801dee91f519e`.
- Artifact `9501470648`, digest `sha256:b26036aa0ad8c7ac075f1a60e213163ce10121e2ff3f606cdd06406ce3fb6aed`, independently reviewed.
- Fresh rc.94 install/reconcile, authenticated extensions workspace, `marketplace.manage` 403 boundary, source create/sync, current catalog generation, canonical stage, checksum/quarantine/Sentinel ALLOW, owning Extension-engine promotion, installed state and audits passed.
- N1.23 Marketplace 2.0 hardening is **not** certified by N1.9.

#### N1.10 Commerce 2.0

- **BOUNDED TARGET VERIFIED**. Issue #32 is **CLOSED completed**; diagnostic PR #33 is **CLOSED + UNMERGED**. Never merge its carrier workflows.
- Frozen exact governed product/dev source: **`43314a111405245f151ec66c01e9261af675c992`**.
- Prerequisite exact-head development QA: run **`32672492494`** / run #166 **SUCCESS**; artifact `9501799033`, digest `sha256:e0a9398abbbdab708f4c48116456d11fa817942f9edc0867ac76d7c5eb05bd33`.
- Accepted same-head diagnostic carrier: **`0e248bd80e00f29dce01e313d83dae8fde8f957b`**.

Primary fresh-install + real-HTTP evidence:

```text
Run: 33540575198 (#2)
Job: 99965508822
Exact source: 43314a111405245f151ec66c01e9261af675c992
Carrier: 0e248bd80e00f29dce01e313d83dae8fde8f957b
OS / arch: Windows / X64
PHP: 8.3.33
Composer: 2.10.3
Node: 22.23.2
npm: 10.9.8
Artifact: 9813554570
Digest: sha256:68e8e9cefcb32a49a6d9912b5a3b1a4f7eaf0b3ac94850ec8418262c82cad882
Independent ZIP digest review: MATCH
Fresh rc.94 installer commit: PASS; 302 -> /install/runtime-handoff; installed.lock=true
Guarded post-install reconcile: PASS
Frozen Commerce contract: 13 tests / 91 assertions PASS
Guest /admin/commerce: 302 -> /login
Authenticated Commerce workspaces: 6/6 HTTP 200
Real HTTP product -> order -> place -> invoice: PASS
Historical order-item snapshot after catalog mutation: PASS
Order total: 2500 minor units
Active invoice count after replay: 1
commerce.order.placed event count: 1
commerce.invoice.created event count: 1
```

Provider persistence/idempotency/failure supplement:

```text
Run: 33540575159 (#1)
Job: 99965508292
Exact source: 43314a111405245f151ec66c01e9261af675c992
Carrier: 0e248bd80e00f29dce01e313d83dae8fde8f957b
Artifact: 9813440996
Digest: sha256:1af3fd58308e92e1f90431588a045ecc7810d1de96bc7152bed4e9a0c0bcd330
Independent ZIP digest review: MATCH
Diagnostic PaymentProviderContract adapter: PASS
External gateway certified: false
Payment replay: 1 provider call / 1 transaction; invoice+order paid
Refund replay: 1 provider call / 1 refund; over-refund blocked before provider; refunded_minor=1250
Successful subscription create/cancel replay: converged without duplicate provider call
Failed subscription cancel replay: status remains active; cancelled_at=null; retry key/provider result retained; no duplicate failed cancel call
Billing events: payment.succeeded=1, refund.refunded=1, subscription.updated=3, subscription.cancel_failed=1
```

N1.10 acceptance also retains the frozen executable source contracts for tenant-local SKU/slug uniqueness, archived catalog rejection, minor-unit conversion/overflow rejection, explicit inclusive/exclusive tax calculation, duplicate provider-key rejection, disabled-provider fail-closed behavior, payment/refund/subscription idempotency, cumulative refund protection and failed-cancel state preservation.

**N1.10 exclusions remain explicit:** no Stripe/PayPal/other live gateway certification, no live credentials/webhooks or PCI claim, no jurisdictional tax/VAT compliance claim, no five-engine DB matrix closure, no N1.11+ certification, no HA/backup/C5/C6/release promotion.

### Current score boundary

```text
PROJECT POWER   76.5%
SOURCE POWER    99.0%
TARGET POWER    50.0%
RELEASE POWER   25.0%
```

Scores remain unchanged. Bounded N1.9 and N1.10 target workflows are meaningful evidence but do not justify broad Target/Release promotion by themselves.

**This N1.10 closure dashboard apply is state-only and must itself pass exact-head hosted `governance` before N1.11 target work begins.**

---

## 2. Current roadmap state

| Block | Source state | Target / release state |
|---|---|---|
| DEV-0–DEV-4 | substantial source closure; PR #13 portability hardening merged/PASS; PR #18 source-attestation prerequisite merged/PASS | rc.94 recovery bounded acceptance complete; broader QA continues |
| DEV-5 SQL/Data Services | source/harness substantially closed | real disposable SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix + connector evidence pending |
| N1.9 Marketplace | SOURCE DONE | **BOUNDED TARGET VERIFIED** on exact accepted source; N1.23 not implied |
| N1.10 Commerce 2.0 | SOURCE DONE | **BOUNDED TARGET VERIFIED** on exact source `43314a1114…`; external gateways/five-engine matrix not implied |
| N1.11 Customer Portal / CRM / Membership | SOURCE DONE | **NEXT target/product QA slice after this state head governance passes** |
| N1.12–N1.21 | SOURCE DONE for bounded workflows | target execution pending in roadmap order |
| N1.22 Sentinel 2.0 | SOURCE DONE FOR CURRENT WORKFLOW | controlled Sentinel 2.0 target evidence pending |
| N1.23 Marketplace 2.0 | SOURCE DONE FOR CURRENT WORKFLOW | generation/lifecycle/tenant/bounded-catalog hardening target evidence pending |
| N1.24 Cloud / HA | SOURCE DONE FOR CURRENT WORKFLOW | real multi-node evidence pending |
| N1.25 Backup / DR / Upgrade | SOURCE DONE FOR CURRENT WORKFLOW | real disposable restore/upgrade rehearsal pending |
| N1.26 Performance + Accessibility + Release | source workflow + W3C HTML/CSS/WAVE tooling implemented | real C5/C6 evidence pending |
| N2.0 Stable Production | not eligible | BLOCKED BY REMAINING TARGET + RELEASE EVIDENCE |

---

## 3. Development execution QA policy

Canonical workflow: `.github/workflows/development-execution-qa.yml`.

Required context: **`governance`**.

Invariant:

```text
every PR head targeting main
  -> GitHub-hosted Ubuntu
  -> disposable MySQL 8.4
  -> exact checkout
  -> ephemeral .env.testing only; never root .env
  -> PHP 8.3 + Composer 2
  -> Node 22 + npm 10
  -> composer install / npm install
  -> warning-hard Laravel bootstrap
  -> php scripts/development-readiness.php --full --tests --evidence
  -> full PHPUnit with --display-warnings --fail-on-warning
  -> Vitest
  -> TypeScript noEmit
  -> production Vite build
  -> asset budgets/provenance
  -> upload-artifact@v7
```

Latest prerequisite exact-head evidence before this state reconciliation:

```text
Head: 43314a111405245f151ec66c01e9261af675c992
Run: 32672492494 (#166)
Conclusion: SUCCESS
Artifact: 9501799033
Digest: sha256:e0a9398abbbdab708f4c48116456d11fa817942f9edc0867ac76d7c5eb05bd33
```

The new state-only N1.10 closure head must obtain its own exact-head governance PASS before N1.11 carrier work begins.

---

## 4. Preserved evidence / compatibility rules

1. Preserve the rc.93 installation unchanged as historical failure evidence. Issue #2 remains closed via the approved separate rc.94 replacement acceptance.
2. PR #17 remains closed + unmerged. Do not backport rc.94 files, edit fingerprints/`installed.lock`, or force/bypass preserved rc.93 checks.
3. PR #21 remains closed + unmerged. Its N1.9 carrier is accepted evidence only and is not a product fix.
4. PR #33 remains closed + unmerged. Its N1.10 carrier workflows are accepted evidence only and are not product/runtime source.
5. Diagnostic carrier code that was never merged cannot be treated as a product fix.
6. For rc.94 recovery, `environment`, `activation`, `service`, `process` are the complete allowed mutable-plane set; acceptance never requires manufacturing all four mismatches.
7. Guest `/admin` redirect semantics remain part of the accepted recovery HTTP boundary; authenticated admin access is additive.
8. N1.9 and N1.23 are separate acceptance boundaries.
9. N1.10 provider-neutral deterministic adapter evidence proves Core contract/state/idempotency behavior only; it cannot be promoted to Stripe/PayPal/live-gateway or PCI certification.
10. Commerce tax calculations prove deterministic source/product behavior only; they are not legal/tax compliance certification.
11. Hosted source CI cannot substitute for real runtime/database/provider/HA/recovery/browser/accessibility evidence.
12. Do not raise Target/Release Power from bounded product gates alone without the broader scoring evidence.

---

## 5. Remaining target / release sequence

Continue strictly in roadmap order after this state-only head is governed:

```text
1. preserve Issue #2, N1.9 and N1.10 accepted evidence; keep PR #17/#21/#33 closed + unmerged
2. require exact-head hosted governance on this N1.10 closure state-only apply
3. once green, freeze N1.11 Customer Portal / CRM / Membership acceptance from current source/runbook/tests
4. execute bounded N1.11 real-target/product QA on that exact governed dev head
5. continue N1.12–N1.26 target/product QA in roadmap order
6. run the real disposable SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix and persist exact-source evidence
7. run remaining controlled provider/connector/identity/API/import/observability/Sentinel/Marketplace evidence where applicable
8. prove real HA/multi-node operational behavior
9. perform real disposable backup/restore + upgrade rehearsal and retain recovery evidence
10. complete C5 W3C HTML + W3C CSS + WAVE + Chrome/Edge/Firefox + real assistive-tech + HTTP/Web-Vitals evidence
11. complete C6 multi-node/final operations + reviewed dependency locks + provenance/release evidence
12. only then mark PR #1 Ready and merge automatically
```

---

## 6. C5 accessibility / browser boundary

Canonical plan: `NEXORA_ACCESSIBILITY_CERTIFICATION_PLAN.md`.

Mandatory final target semantics remain:

```text
Required routes: / and /login
W3C Nu HTML errors: 0
W3C CSS errors: 0
WAVE Errors: 0
WAVE Contrast Errors: 0
WAVE Alerts: all human-reviewed
Chrome / Edge / Firefox
360 / 768 / 1440 widths
LTR + RTL
light + dark
keyboard-only + visible focus + focus restoration
real screen-reader names/roles/states
reduced motion
200% zoom/reflow
forced-colors/high-contrast
no horizontal page overflow
HTTP/security/latency + Web Vitals
exact source/session/evidence hash binding
```

`WAVE_API_KEY` must remain outside source/logs/evidence. WAVE output is never a complete accessibility approval.

---

## 7. AI execution rules

Every AI/agent must:

1. Read `AGENTS.md`, `NEXORA_AI_PROJECT_STATE.md`, this dashboard, and the accessibility plan before relevant work.
2. Treat this dashboard's Current checkpoint as authoritative over stale historical policy text while preserving history as history.
3. Continue from `Exact next action`; do not repeat accepted N1.9/N1.10 work without contradictory regression evidence.
4. Use GitHub-hosted development QA for PR source/development evidence; do not silently substitute local/self-hosted runners.
5. Require `governance` on every PR head targeting `main`, including state-only/documentation heads.
6. Never promote source/static/jsdom evidence to real target/browser evidence.
7. Fix root causes; never weaken assertions merely to get green.
8. Keep full PHPUnit warning-hard; never bypass `--fail-on-warning`.
9. Do not broaden a bounded target slice into later roadmap work.
10. Never commit provider/API/WAVE credentials or expose secrets in evidence.
11. Keep PR #1 DRAFT until all required target/release gates are genuinely final.
12. Never merge diagnostic carrier PRs #17, #21 or #33.
13. Do not promote external-gateway, five-engine DB, HA, recovery, C5/C6 or final release claims from N1.10 evidence.
14. Inspect open GitHub issues at the start/end of meaningful passes and address applicable defects in roadmap order.

---

## 8. Recent Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 083 | 2026-08-23 | PR #18 governance/merge/post-merge evidence | corrected generated-theme projection/source-attestation boundary and merged bounded prerequisite | Target/Release unchanged |
| 084–087 | 2026-08-24 | exact hosted governance + rc.94 carrier diagnostics | reconciled state/mutable-plane/HTTP acceptance semantics without weakening immutable fail-closed rules | unchanged |
| 088 | 2026-08-24 | PR #17 run `32667462959`, artifact `9500449768`; Issue #2 closure | independently reviewed disposable rc.94 recovery acceptance; preserved rc.93 unchanged | bounded recovery accepted; scores unchanged |
| 089 | 2026-08-24 | run `32667919982`; PR #21 run `32671245015`, artifact `9501470648`; Issue #20 closure | governed prior state then completed bounded N1.9 Marketplace acceptance; PR #21 closed unmerged | N1.9 bounded target verified; scores unchanged |
| 090 | 2026-08-23 | exact dev head `43314a111405245f151ec66c01e9261af675c992`; run `32672492494`, artifact `9501799033` | exact-head hosted governance PASS for the N1.9 closure state apply; unlocked N1.10 acceptance work | scores unchanged |
| 091 | 2026-09-01 | Issue #32; PR #33; run `33540575198` artifact `9813554570` digest `68e8e9ce…ad882`; provider run `33540575159` artifact `9813440996` digest `1af3fd58…cd330`; independent artifact review | froze and completed bounded N1.10 Commerce 2.0 real-target acceptance on exact source `43314…`; closed Issue #32; closed PR #33 unmerged; external gateways/five-engine DB explicitly remain pending | N1.10 bounded target verified; Project/Source/Target/Release remain 76.5/99/50/25 |

Full earlier apply history remains preserved in Git history and `NEXORA_AI_PROJECT_STATE.md`.

---

## 9. Exact next action

```text
A. Preserve rc.93 / Issue #2 evidence unchanged; keep PR #17 closed + unmerged.
B. Preserve N1.9 Issue #20 acceptance; keep PR #21 closed + unmerged and do not use it to certify N1.23.
C. Preserve N1.10 Issue #32 acceptance; keep PR #33 closed + unmerged. Do not promote its diagnostic provider adapter to external-gateway certification.
D. Require full hosted `governance` on this new N1.10 closure state-only dashboard head before beginning N1.11 target work. If it fails, fix only the exact blocker and rerun.
E. Once this exact state head is green, freeze the exact N1.11 Customer Portal / CRM / Membership real-target acceptance from current source/runbook/tests before creating any carrier.
F. Execute N1.11 against that exact governed dev head, then continue N1.12–N1.26 target/product QA strictly in roadmap order.
G. After applicable product slices, run the real disposable five-engine DB matrix and remaining controlled provider/connector/identity/API/import/observability/Sentinel/Marketplace/HA/recovery evidence.
H. Complete real C5 and C6/final release evidence.
I. Keep PR #1 DRAFT and Project/Source/Target/Release at 76.5% / 99.0% / 50.0% / 25.0% until broader evidence explicitly justifies a change.
J. Only after all remaining target/release boundaries are genuinely satisfied may PR #1 be marked Ready and merged.
```