# Nexora Progress Dashboard

> **MANDATORY LIVE EVIDENCE FILE** — Update after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, governance integration, or release/certification apply.
>
> `.ai/state.json` is the canonical active stage/unit cursor. This dashboard is the detailed live target/release evidence state for the long-lived development program and MUST remain synchronized with it. `NEXORA_AI_PROJECT_STATE.md` remains append-only historical evidence. `SOURCE_DONE != TARGET_VERIFIED`; bounded target acceptance never implies final release certification.

---

## 1. Current checkpoint

- Date: `2026-09-01` (Asia/Karachi).
- Canonical long-lived engineering branch: `dev/n1-0b-core-functional-qa` / PR #1, still **DRAFT + OPEN**.
- Canonical dev state before current main-control-plane integration: **`f4b8daa94781907ae78649cbc3ac1bfe26380803`** (`docs: record N1.10 target acceptance and advance to N1.11`).
- Accepted runtime/product implementation bytes remain based on **`e52e67bbd997c13a04ab2a5a2ca3fef7e992b8ca`**; later state/evidence/integration applies do not become new product acceptance merely by changing Git head.
- Current `main`: **`6d0bb2cf7f92777b8f5f7f4f84ae0f041069124a`**. It advanced seven commits beyond the previously integrated `f854c50c0f7687fc87fdfab01b49562392af4ef4` baseline.
- Current main delta contains the governed `.ai/**` development control plane, `.github/copilot-instructions.md`, a new root `AGENTS.md`, the version-pinned rc.93 identity-repair pack + regression test, and the `repair:rc93` npm alias. The delta is primarily additive.
- PR #1 became merge-conflicted against advanced `main`; GitHub therefore did not create the required `pull_request` `governance` run for `f4b8daa…`. Close/reopen and a same-head governance carrier could not legitimately bypass the conflict.
- Diagnostic PR #34 is a governance-only carrier at `f4b8daa…`; it must remain unmerged and is superseded by the integration repair.
- Diagnostic PR #35 records the raw `main → dev` conflict and must remain unmerged after the resolved integration is accepted.
- Resolved integration branch: `ops-main-ai-integration-resolved`.
- True two-parent integration commit: **`1e5994362720b2d4ec17b003af305335b44d05e5`**, parents `f4b8daa94781907ae78649cbc3ac1bfe26380803` + `6d0bb2cf7f92777b8f5f7f4f84ae0f041069124a`.
- Conflict resolution preserves full current-main `.ai/**`, Copilot instructions and rc.93 repair-pack lineage; merges `AGENTS.md` semantically; preserves both main `repair:rc93` and dev C5/Web-Standards/`dev:target-qa` package commands.
- `.ai/state.json`, `.ai/handoff/current.md` and `.ai/plans/active.md` are reconciled from the older main runtime-blocked cursor to later accepted Issue #2/N1.9/N1.10 evidence and the bounded N1.11 target-QA cursor.
- **Current blocker:** the final resolved integration head must pass exact-head GitHub-hosted `governance` before it can be merged to dev or used as N1.11 product evidence.
- Current source release: `1.0.0-rc.94`; installer protocol `v5.29`; generation `n1-v5.29`.
- Source `composer.lock` remains intentionally absent. Hosted dependency resolution remains development evidence only; reviewed release locks are a later C6 boundary.
- W3C Nu HTML + W3C CSS + WAVE tooling is source-wired, but final accessibility/browser certification still requires real target evidence.

### Governance policy

Required PR status context: **`governance`**.

Development execution QA remains:

```text
GitHub-hosted ubuntu-latest only
PHP >= 8.3
Node >= 22
Disposable MySQL 8.4
Exact PR-head checkout
Ephemeral .env.testing only; never root .env
Warning-hard PHPUnit (--display-warnings --fail-on-warning)
Vitest + TypeScript noEmit + production Vite build
Development Readiness evidence artifact
```

Last exact dev source already governed before integration:

```text
Head: 43314a111405245f151ec66c01e9261af675c992
Run: 32672492494 (#166)
Conclusion: SUCCESS
Artifact: 9501799033
Digest: sha256:e0a9398abbbdab708f4c48116456d11fa817942f9edc0867ac76d7c5eb05bd33
```

That PASS does not automatically certify the later main-control-plane integration head.

---

## 2. Accepted bounded target evidence

### Issue #2 — disposable rc.94 replacement recovery

**ACCEPTED + CLOSED** under the explicitly approved separate current-source rc.94 replacement acceptance.

```text
Issue: #2 CLOSED completed
Carrier: PR #17 CLOSED + UNMERGED
Exact source: a6b6462954edddbe138bc26577625bac2a8bddd2
Run: 32667462959
Job: 97263035327
Artifact: 9500449768
Digest: sha256:1ac7ccf409181322e74ca1444bfd2ed3cca1539875eba398ad0d98a06e7e4aba
```

Fresh install, guarded reconcile, zero post-recovery mismatch, current ready receipt and required guest/authenticated HTTP routes passed. The preserved `1.0.0-rc.93` installation remains historical evidence and is **not** reclassified as an in-place PASS.

The newly integrated rc.93 repair pack from current main is retained as version-pinned control tooling/history. Its older `.ai` runtime-BLOCKED cursor is superseded by the later accepted replacement evidence; do not reopen Issue #2 merely because that lineage is now present on dev.

### N1.9 Marketplace

**BOUNDED TARGET VERIFIED.**

```text
Issue: #20 CLOSED completed
Carrier: PR #21 CLOSED + UNMERGED
Exact source: 8e359f07dc6b608b0d09468386fca13f066337a1
Run: 32671245015
Job: 97272315620
Artifact: 9501470648
Digest: sha256:b26036aa0ad8c7ac075f1a60e213163ce10121e2ff3f606cdd06406ce3fb6aed
```

Accepted scope: fresh rc.94 install/reconcile; authenticated Extensions workspace; `marketplace.manage` 403 boundary; source create/sync; current catalog generation; canonical package staging; checksum/quarantine/Sentinel ALLOW; promotion through owning Extension engine; installed-state and audit evidence.

**Not implied:** later `MARKETPLACE-200` / N1.23 Marketplace 2.0 generation/lifecycle/tenant/bounded-catalog hardening.

### N1.10 Commerce 2.0

**BOUNDED TARGET VERIFIED.**

```text
Issue: #32 CLOSED completed
Carrier: PR #33 CLOSED + UNMERGED
Frozen exact source: 43314a111405245f151ec66c01e9261af675c992
```

Primary fresh-install + HTTP artifact:

```text
Run: 33540575198 (#2)
Job: 99965508822
Carrier head: 0e248bd80e00f29dce01e313d83dae8fde8f957b
Artifact: 9813554570
Digest: sha256:68e8e9cefcb32a49a6d9912b5a3b1a4f7eaf0b3ac94850ec8418262c82cad882
Independent downloaded ZIP digest: MATCH
Fresh rc.94 installer + guarded reconcile: PASS
Frozen Commerce executable contract: 13 tests / 91 assertions PASS
Guest /admin/commerce: 302 -> /login
Authenticated Commerce workspaces: 6/6 HTTP 200
Real product -> order -> place -> invoice: PASS
Historical order-item snapshot after catalog mutation: PASS
Order total: 2500 minor units
Active invoice replay count: 1
commerce.order.placed event count: 1
commerce.invoice.created event count: 1
```

Provider persistence/idempotency supplement:

```text
Run: 33540575159 (#1)
Job: 99965508292
Artifact: 9813440996
Digest: sha256:1af3fd58308e92e1f90431588a045ecc7810d1de96bc7152bed4e9a0c0bcd330
Independent downloaded ZIP digest: MATCH
Deterministic PaymentProviderContract adapter: PASS
Payment replay: 1 provider call / 1 transaction
Refund replay: 1 provider call / 1 refund; over-refund blocked before provider
Subscription create/cancel replay: no duplicate provider calls
Failed cancel replay: active state preserved; no duplicate call
Billing events captured and reviewed
External gateway certified: false
```

N1.10 also retains source-contract evidence for tenant-local SKU/slug uniqueness, archived catalog rejection, minor-unit conversion/overflow rejection, explicit inclusive/exclusive tax calculation, duplicate provider-key rejection, disabled-provider fail-closed behavior, payment/refund/subscription idempotency, cumulative refund protection and failed-cancel state preservation.

**Explicit exclusions:** no Stripe/PayPal/live provider certification, no live credentials/webhooks/PCI claim, no jurisdictional tax/VAT compliance claim, no five-engine matrix, no N1.11+ certification, no HA/backup/C5/C6/final release promotion.

---

## 3. Current active roadmap cursor

`.ai/state.json` and this dashboard now agree:

```text
Stable stage: CRM-MEMBERSHIP-HELPDESK-CLOSURE-001
Registered unit: SYS-CRM-MEMBERSHIP-HELPDESK
Legacy alias: N1.11 — CRM / Membership / Customer Portal
Status: PARTIAL (source exists; bounded real-target verification pending)
```

Historical N1.11 resolves to `CRM-MEMBERSHIP-HELPDESK-CLOSURE-001`, with broader Customer/Member Portal product expansion represented later by `PORTAL-200`.

The user-authorized N1.9–N1.26 target-QA order may continue after governance, but that execution priority does **not** silently certify or skip unmet canonical semantic dependencies/product-expansion stages.

Current source state table:

| Block | Source state | Target / release state |
|---|---|---|
| DEV-0–DEV-4 | substantial source closure | bounded runtime recovery accepted; broader QA remains |
| DEV-5 SQL/Data Services | source/harness substantially closed | five-engine matrix + connector evidence pending |
| N1.9 Marketplace | SOURCE DONE | **BOUNDED TARGET VERIFIED** |
| N1.10 Commerce | SOURCE DONE | **BOUNDED TARGET VERIFIED** |
| N1.11 CRM / Membership / Customer Portal | SOURCE DONE for current implemented workflow | **ACTIVE NEXT TARGET-QA SLICE after exact-head governance** |
| N1.12–N1.21 | SOURCE DONE for bounded workflows | target execution pending |
| N1.22 Sentinel 2.0 | SOURCE DONE FOR CURRENT WORKFLOW | controlled target evidence pending |
| N1.23 Marketplace 2.0 | SOURCE DONE FOR CURRENT WORKFLOW | hardening/negative target matrix pending |
| N1.24 Cloud / HA | SOURCE DONE FOR CURRENT WORKFLOW | real multi-node evidence pending |
| N1.25 Backup / DR / Upgrade | SOURCE DONE FOR CURRENT WORKFLOW | real restore/upgrade rehearsal pending |
| N1.26 Performance + Accessibility + Release | source tooling implemented | real C5/C6 evidence pending |
| N2.0 Stable Production | not eligible | BLOCKED by remaining target/release evidence |

---

## 4. N1.11 bounded target-QA boundary

Do **not** freeze the final N1.11 issue checklist until the final post-integration exact source is governance-green and its current source/tests have been audited.

After governance, inspect current implementations for:

- Customer Portal routes/controller/layout/pages and auth/data-exposure boundary;
- CRM Contact, Lead, Opportunity, Organization and Settings flows;
- CRM↔Commerce link model/service;
- Membership plan/member lifecycle, entitlement/access and Commerce sync behavior;
- tenant context/global scopes/route binding;
- permissions/audit events;
- existing feature/unit/architecture tests;
- `scripts/crm-membership-product-contract-verify.php`;
- `scripts/customer-portal-product-contract-verify.php`;
- tenant-scoped CRM/Membership migration constraints.

Candidate acceptance dimensions include exact source/target binding, fresh disposable install, real login/session and guest fail-closed behavior, portal ownership/tenant isolation, CRM lifecycle, membership/entitlement lifecycle, Commerce relationship consistency, permission boundaries, applicable retry/idempotency behavior, DB/audit evidence, target-toolchain tests, and independently reviewed artifact digests.

Do not assume Helpdesk/ticket/SLA features exist merely because the stable stage name includes Helpdesk. If absent in current source, record the narrower implemented boundary instead of fabricating target evidence.

Do not implement `PORTAL-200` features merely to obtain bounded N1.11 acceptance.

---

## 5. Preserved evidence / safety rules

1. Preserve rc.93 historical evidence; Issue #2 stays closed.
2. Keep PR #17, #21 and #33 closed + unmerged; their diagnostic code is evidence only, never accepted product source.
3. PR #34 and raw-conflict PR #35 are temporary governance/integration diagnostics and must not be merged after the resolved integration supersedes them.
4. Do not backport rc.94 into the preserved rc.93 target, manually edit fingerprints/`installed.lock`, or force/bypass runtime checks.
5. Current-main rc.93 repair tooling may remain available but cannot override later accepted replacement-recovery facts.
6. N1.9 does not certify N1.23.
7. N1.10 deterministic provider-contract evidence does not certify an external gateway or PCI.
8. Commerce tax calculator evidence is not legal/tax compliance certification.
9. Hosted source CI never substitutes for real target/database/provider/HA/recovery/browser/a11y evidence.
10. Do not weaken tests, middleware, tenant scopes or acceptance assertions to obtain green.
11. Every target acceptance artifact must bind exact source and be independently reviewed; workflow green alone is insufficient.
12. Keep PR #1 DRAFT until all applicable target/release gates genuinely pass.

---

## 6. Remaining broader target / release sequence

After the resolved integration is exact-head governed:

```text
1. merge resolved main-control-plane integration to dev; close PR #34/#35 unmerged
2. ensure the resulting canonical dev/PR #1 exact head is governance-green
3. audit current N1.11 source/tests and freeze one exact acceptance tracker
4. execute bounded N1.11 disposable real-target/product QA
5. independently review artifact bytes/digests; close tracker only if complete; close carrier unmerged
6. reconcile .ai + this dashboard and require exact-head governance
7. continue legacy N1.12–N1.26 target/product QA in the explicit user-priority order without converting that order into false canonical dependency certification
8. run real disposable SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix
9. complete controlled provider/connector/identity/API/import/observability/Sentinel/Marketplace evidence
10. prove real HA/multi-node operations
11. perform disposable backup/restore + upgrade rehearsal
12. complete C5 W3C HTML/CSS/WAVE/browser/AT/HTTP/Web-Vitals evidence
13. complete C6 reviewed locks/provenance/final operations/release evidence
14. only then mark PR #1 Ready and merge
```

---

## 7. Accessibility / browser boundary

Canonical plan: `NEXORA_ACCESSIBILITY_CERTIFICATION_PLAN.md`.

Final target requirements remain, at minimum:

```text
Required routes: / and /login
W3C Nu HTML errors = 0
W3C CSS errors = 0
WAVE Errors = 0
WAVE Contrast Errors = 0
WAVE Alerts human-reviewed
Chrome / Edge / Firefox
360 / 768 / 1440 widths
LTR + RTL
light + dark
keyboard + visible focus/focus restoration
real assistive-technology names/roles/states
reduced motion
200% zoom/reflow
forced colors/high contrast
no horizontal overflow
HTTP/security/latency + Web Vitals
exact source/session/evidence binding
```

`WAVE_API_KEY` stays outside source/logs/evidence. WAVE is not full WCAG approval.

---

## 8. Weighted Project Power

```text
PROJECT POWER   76.5%
SOURCE POWER    99.0%
TARGET POWER    50.0%
RELEASE POWER   25.0%
```

**No score change.** Integrating a governance control plane, source CI, and bounded N1.9/N1.10 target slices do not by themselves justify broader Target/Release promotion.

---

## 9. Recent Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 089 | 2026-08-24 | N1.9 run `32671245015`, artifact `9501470648` | bounded N1.9 Marketplace target acceptance; PR #21 closed unmerged | scores unchanged |
| 090 | 2026-09-01 | N1.10 runs `33540575198` + `33540575159`; artifacts `9813554570` + `9813440996`; Issue #32; PR #33 | independently reviewed bounded N1.10 Commerce acceptance | scores unchanged |
| 091 | 2026-09-01 | dev state head `f4b8daa…`; current main `6d0bb2…`; PR #1/#34 merge conflict; PR #35 diagnostic | identified that main divergence, not a test result, prevented exact-head governance from materializing | scores unchanged |
| 092 | 2026-09-01 | two-parent integration `1e599436…`; resolved `AGENTS.md`/`package.json`; `.ai` revision 8 | preserved current-main AI control plane + rc.93 repair lineage and newer dev evidence semantics; activated bounded N1.11 target-QA cursor pending governance | scores unchanged |

---

## 10. Exact next action

```text
A. Finalize the resolved integration branch state/handoff/active-plan/dashboard reconciliation.
B. Open a clean integration PR from the resolved branch to dev and an exact-head governance carrier to main if needed.
C. Require GitHub-hosted `governance` PASS on the final reconciled integration head. If it fails, fix only the exact integration/governance blocker and rerun.
D. Merge the resolved integration to dev only after PASS; close PR #34 and PR #35 unmerged.
E. Ensure the resulting canonical PR #1 dev head is exact-head governance green.
F. Only then audit current portal/CRM/membership source/tests and freeze the bounded N1.11 acceptance tracker.
G. Execute/review/close N1.11 evidence, then reconcile state and continue N1.12 in the explicit target-QA order.
H. Keep Project/Source/Target/Release at 76.5% / 99.0% / 50.0% / 25.0% until broader evidence justifies change.
I. Keep PR #1 DRAFT until final target/release closure.
```
