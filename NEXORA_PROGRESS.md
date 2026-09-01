# Nexora Progress Dashboard

> **MANDATORY LIVE EVIDENCE FILE** — Update this dashboard after every meaningful implementation, fix, audit closure, CI correction, target verification, issue closure, governance integration, or release/certification apply.
>
> `.ai/state.json` is the canonical active stage/unit cursor. This dashboard is the detailed live target/release evidence state for the long-lived development program and MUST remain synchronized with it. `NEXORA_AI_PROJECT_STATE.md` remains append-only historical evidence. `SOURCE_DONE != TARGET_VERIFIED`; bounded target acceptance never implies final release certification.

---

## 1. Current checkpoint

- Date: `2026-09-02` (Asia/Karachi).
- Canonical long-lived engineering branch: `dev/n1-0b-core-functional-qa` / PR #1, still **DRAFT + OPEN**.
- N1.12 accepted frozen product source: **`78976e9f44e290155b119ae722a77fb37fd59018`**.
- Exact-head Development Execution QA on that source: run `33553824752` / job `100009648131` **PASS**; artifact `9818655365`; digest `sha256:471949869471e6db95dcf0fd0da0f47b65e062ce8f196f2105145741fd8a8fa4`.
- N1.12 Search 2.0 is **BOUNDED TARGET VERIFIED** under Issue #41; diagnostic PR #45 is **CLOSED + DRAFT + UNMERGED**.
- Certified N1.12 carrier is exactly `449ea9b3d5c0d2ad176031a1849ed80c349e0023`; later PR #45 tree-equivalent/no-content heads are not acceptance evidence.
- Accepted N1.12 run `33563871904` / job `100042466730`; artifact `9822383456`; GitHub digest and independently downloaded ZIP SHA-256 exactly match at `sha256:6d298f5d60c5dc7a0423aec8d73d107de79888662cc919d62d53824b0e723731`.
- The N1.12 closure/N1.13 cursor apply is state/control-plane only. It does not alter or newly certify product/runtime bytes.
- Active target-QA cursor advances to **N1.13 / `COLLAB-200` / `SYS-COLLABORATION`** after this synchronized state commit is exact-head governance-green.
- Current source release: `1.0.0-rc.94`; installer protocol `v5.29`; generation `n1-v5.29`.
- Source `composer.lock` remains intentionally absent. Hosted dependency resolution remains development evidence only; reviewed release locks are a later C6 boundary.
- W3C Nu HTML + W3C CSS + WAVE tooling is source-wired, but final accessibility/browser certification still requires real target evidence.

### Governance policy

Required PR status context: **`governance`**.

Development execution QA policy: **GitHub-hosted `ubuntu-latest` only**.

Historical Actions: **DEFERRED BY USER** during an earlier quota/self-hosted transition. This is historical state only and is not the current execution policy or blocker.

Target Power remains real-target evidence-bound. Source/static/hosted governance alone cannot increase Target Power; the unchanged dashboard boundary is `TARGET POWER    50.0%`.

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

Last exact bounded-target accepted source before this state-only apply:

```text
Head: 78976e9f44e290155b119ae722a77fb37fd59018
Governance run: 33553824752
Governance job: 100009648131
Conclusion: SUCCESS
Governance artifact: 9818655365
Governance digest: sha256:471949869471e6db95dcf0fd0da0f47b65e062ce8f196f2105145741fd8a8fa4
N1.12 target run: 33563871904
N1.12 target artifact: 9822383456
N1.12 target digest: sha256:6d298f5d60c5dc7a0423aec8d73d107de79888662cc919d62d53824b0e723731
```

The resulting N1.12-closure/N1.13-cursor state-only head must receive its own exact-head governance PASS before it is frozen for N1.13.

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
Primary run: 33540575198 / job 99965508822
Primary artifact: 9813554570
Primary digest: sha256:68e8e9cefcb32a49a6d9912b5a3b1a4f7eaf0b3ac94850ec8418262c82cad882
Provider supplement run: 33540575159 / job 99965508292
Provider artifact: 9813440996
Provider digest: sha256:1af3fd58308e92e1f90431588a045ecc7810d1de96bc7152bed4e9a0c0bcd330
Independent downloaded ZIP digests: MATCH
```

Accepted evidence covers fresh install/reconcile, real HTTP Commerce workspaces, product→order→invoice, historical line-item snapshot integrity, 13 Commerce tests / 91 assertions, deterministic provider-contract payment/refund/subscription persistence and retry/failure behavior.

**Explicit exclusions:** no Stripe/PayPal/live provider certification, no live credentials/webhooks/PCI claim, no jurisdictional tax/VAT compliance claim, no five-engine matrix, no N1.11+ certification by this evidence, no HA/backup/C5/C6/final release promotion.

### N1.11 Customer Portal / CRM / Membership

**BOUNDED TARGET VERIFIED.**

```text
Issue: #39 CLOSED completed
Carrier: PR #40 CLOSED + UNMERGED
Frozen exact product source: 2f5eb3b9dcf1c146f4e647fb3441318c4bf2c829
Carrier head: b1481c4064bbad96b79cf13d40ac5d07be10a434
Target run: 33550851207
Job: 99999628418
Artifact: 9817460169
Digest: sha256:b973ffdc424daf3e5b0987d75bff2c6a79b94f1c5e3530a7058091b1af08e1c8
Independent downloaded ZIP SHA-256: MATCH
Frozen feature contract: 10 tests / 64 assertions PASS
```

Reviewed target gates include fresh install/reconcile, Customer Portal + CRM/Membership product contracts, real `/account` guest/auth boundaries, linked current-user customer/membership visibility, tenant-member isolation and cross-tenant CRM/Membership/Commerce references failing closed.

**Explicit exclusions:** Helpdesk, `PORTAL-200`, external providers/connectors, remaining five-engine DB matrix, later stages, HA/recovery/C5/C6/final release.

### N1.12 Search 2.0

**BOUNDED TARGET VERIFIED.**

```text
Issue: #41 CLOSED completed
Carrier: PR #45 CLOSED + DRAFT + UNMERGED
Frozen exact product source: 78976e9f44e290155b119ae722a77fb37fd59018
Certified carrier head: 449ea9b3d5c0d2ad176031a1849ed80c349e0023
Target run: 33563871904
Job: 100042466730
Artifact: 9822383456
Digest: sha256:6d298f5d60c5dc7a0423aec8d73d107de79888662cc919d62d53824b0e723731
Independent downloaded ZIP SHA-256: MATCH
```

Reviewed target gates:

- exact frozen source/carrier/toolchain binding;
- fresh disposable rc.94 install and guarded post-install reconcile;
- Search product-contract verifier + warning-hard frozen isolation test;
- document/media index create/update/delete/restore behavior and single-projection idempotency;
- SEO-derived URL refresh;
- stale projection removal and repeatable rebuild convergence;
- deterministic bounded local ranking/query normalization/type/limit behavior;
- tenant isolation for indexed document/media and tenant member discovery;
- public Search document-only/private-membership visibility boundary;
- real browser-equivalent Admin Search with required deployment-generation header;
- global RBAC plus tenant-local EnterpriseRole authorization path;
- `search.use`, `documents.view`, `media.view`, `search.index.manage` permission boundaries;
- GPC/DNT opt-out requests do not create query-log evidence;
- authorized reindex records audit evidence;
- accepted server logs contain no hidden fatal/uncaught runtime failure.

Only carrier `449ea9b3d5c0d2ad176031a1849ed80c349e0023` / run `33563871904` is certified. Later PR #45 tree-equivalent/no-content heads are explicitly excluded.

**Explicit exclusions:** external Search providers; distributed indexing/replication/HA; unimplemented facets; Search performance/scale/relevance SLOs; broader `PORTAL-200`; remaining five-engine DB matrix; N1.13+; HA/recovery/C5/C6/final release.

---

## 3. Current active roadmap cursor

`.ai/state.json` and this dashboard agree:

```text
Stable stage: COLLAB-200
Registered unit: SYS-COLLABORATION
Legacy alias: N1.13 — Collaboration
Status: PARTIAL (source exists; bounded real-target verification pending)
```

Canonical dependencies: `SITE-BUILDER-200`, `RELEASE-WORKFLOW-200`, `PORTAL-200`. They remain distinct evidence/product claims and are not silently satisfied by the user-authorized legacy target-QA execution order.

Registry local contracts include `App\Nexora\Collaboration\CollaborationRepository`, `App\Nexora\Collaboration\ApprovalWorkflow`, and `App\Nexora\Collaboration\LockManager`. The bounded pass must verify only behavior actually implemented in the exact governed source.

Current source state table:

| Block | Source state | Target / release state |
|---|---|---|
| DEV-0–DEV-4 | substantial source closure | bounded runtime recovery accepted; broader QA remains |
| DEV-5 SQL/Data Services | source/harness substantially closed | five-engine matrix + connector evidence pending |
| N1.9 Marketplace | SOURCE DONE | **BOUNDED TARGET VERIFIED** |
| N1.10 Commerce | SOURCE DONE | **BOUNDED TARGET VERIFIED** |
| N1.11 CRM / Membership / Customer Portal | SOURCE DONE for frozen bounded workflow | **BOUNDED TARGET VERIFIED** |
| N1.12 Search 2.0 | SOURCE DONE for current implemented workflow | **BOUNDED TARGET VERIFIED** |
| N1.13 Collaboration | SOURCE DONE for current implemented workflow | **ACTIVE NEXT TARGET-QA SLICE after exact-head governance** |
| N1.14–N1.21 | SOURCE DONE for bounded workflows | target execution pending |
| N1.22 Sentinel 2.0 | SOURCE DONE FOR CURRENT WORKFLOW | controlled target evidence pending |
| N1.23 Marketplace 2.0 | SOURCE DONE FOR CURRENT WORKFLOW | hardening/negative target matrix pending |
| N1.24 Cloud / HA | SOURCE DONE FOR CURRENT WORKFLOW | real multi-node evidence pending |
| N1.25 Backup / DR / Upgrade | SOURCE DONE FOR CURRENT WORKFLOW | real restore/upgrade rehearsal pending |
| N1.26 Performance + Accessibility + Release | source tooling implemented | real C5/C6 evidence pending |
| N2.0 Stable Production | not eligible | BLOCKED by remaining target/release evidence |

---

## 4. N1.13 bounded target-QA boundary

Do **not** freeze the final N1.13 issue checklist until this state-only transition head is exact-head governance-green and current Collaboration source/tests have been audited.

After governance, inspect current implementations for:

- `CollaborationRepository`, `ApprovalWorkflow`, `LockManager` and concrete models/services;
- comments/threads/replies and collaboration history where implemented;
- lock acquire/conflict/refresh/release/expiry and ownership semantics where implemented;
- approval states/transitions, actor authorization and invalid-transition rejection;
- tenant/organization/member/resource scopes and permission boundaries;
- collaboration migrations, uniqueness/concurrency constraints and TTL/timestamp fields;
- audit/events/queue/cache coordination where actually implemented;
- HTTP/API/admin UI routes/controllers/request validation;
- existing Collaboration feature/unit/architecture tests and source-contract verifier scripts;
- installer/upgrade/reconcile behavior relevant to Collaboration state.

Candidate target dimensions include exact source/carrier/toolchain binding, fresh install/reconcile, frozen verifier/tests, comment/history visibility and ownership, tenant isolation, lock lifecycle/conflict/concurrency, approval authorization/state transitions, retry/idempotency where claimed, real HTTP/API/UI auth boundaries, audit/log safety and independently reviewed artifact bytes/digest.

Do not fabricate realtime, WebSocket, CRDT, distributed Redis/lock coordination or external-provider certification. If those are not implemented and target-proven, keep them explicit non-claims.

---

## 5. Preserved evidence / safety rules

1. Preserve rc.93 historical evidence; Issue #2 stays closed.
2. Keep PR #17, #21, #33, #40 and #45 closed + unmerged; diagnostic code is evidence only, never accepted product source.
3. N1.9 does not certify N1.23 Marketplace 2.0.
4. N1.10 deterministic provider-contract evidence does not certify an external gateway or PCI.
5. N1.11 does not certify Helpdesk or `PORTAL-200`.
6. N1.12 does not certify external Search providers, distributed Search, facets or Search scale/relevance SLOs.
7. Hosted source CI never substitutes for real target/database/provider/HA/recovery/browser/a11y evidence.
8. Do not weaken tests, middleware, tenant scopes, lock semantics, approval rules or acceptance assertions to obtain green.
9. Every target acceptance artifact must bind exact source and be independently reviewed; workflow green alone is insufficient.
10. The state-only closure/cursor commit does not become new product evidence merely because its Git SHA changes.
11. Canonical `COLLAB-200` dependencies remain separate evidence claims even while legacy N1.13 target QA proceeds.
12. Keep PR #1 DRAFT until all applicable target/release gates genuinely pass.

---

## 6. Remaining broader target / release sequence

```text
1. obtain exact-head governance PASS on the synchronized N1.12-closure/N1.13-cursor dev head
2. audit current Collaboration source/tests/contracts on that exact governed head
3. freeze one bounded N1.13 acceptance tracker with exact inclusions/exclusions and source binding
4. execute bounded N1.13 disposable real-target/product QA on an unmerged diagnostic carrier
5. independently review artifact bytes/digest; close tracker only if complete; close carrier unmerged
6. reconcile .ai + this dashboard and require exact-head governance
7. continue legacy N1.14–N1.26 target/product QA in explicit user-priority order without false canonical dependency certification
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

Legacy source-contract compatibility marker for this same dashboard block: `## 2. Weighted Project Power Score`.

```text
PROJECT POWER   76.5%
SOURCE POWER    99.0%
TARGET POWER    50.0%
RELEASE POWER   25.0%
```

**No score change.** N1.12 adds another bounded exact-source target slice but does not justify broad Target/Release promotion while canonical dependencies, five-engine/provider/HA/recovery/C5/C6 boundaries remain open.

---

## 9. Apply Log

| Apply | Date | Evidence | Change | Power impact |
|---:|---|---|---|---|
| 089 | 2026-08-24 | N1.9 run `32671245015`, artifact `9501470648` | bounded N1.9 Marketplace target acceptance; PR #21 closed unmerged | scores unchanged |
| 090 | 2026-09-01 | N1.10 runs `33540575198` + `33540575159`; artifacts `9813554570` + `9813440996`; Issue #32; PR #33 | independently reviewed bounded N1.10 Commerce acceptance | scores unchanged |
| 091 | 2026-09-01 | dev state head `f4b8daa…`; current main `6d0bb2…`; PR #1/#34 merge conflict; PR #35 diagnostic | identified that main divergence, not a test result, prevented exact-head governance from materializing | scores unchanged |
| 092 | 2026-09-01 | two-parent integration `1e599436…`; resolved `AGENTS.md`/`package.json`; `.ai` revision 8 | preserved current-main AI control plane + rc.93 repair lineage and newer dev evidence semantics; activated bounded N1.11 target-QA cursor pending governance | scores unchanged |
| 093 | 2026-09-01 | governance run `33542987240` on `73270099…` | full product/runtime QA green; isolated six failures to legacy governance/progress source-contract markers; restored compatibility wording without changing product/runtime/verifier source | scores unchanged |
| 094 | 2026-09-01 | governed integration merged to dev at `2f5eb3b9…`; governance run `33545705598` | canonical dev exact head green; froze N1.11 product source | scores unchanged |
| 095 | 2026-09-02 | Issue #39; PR #40; target run `33550851207`; artifact `9817460169`; digest `b973ffdc…e1c8` | independently reviewed bounded N1.11 Customer Portal / CRM / Membership acceptance; carrier closed unmerged; advanced active cursor to N1.12 Search 2.0 | scores unchanged |
| 096 | 2026-09-02 | Issue #41; PR #45; governed source `78976e9…`; certified carrier `449ea9b3…`; run `33563871904`; artifact `9822383456`; digest `6d298f5d…23731` | independently reviewed bounded N1.12 Search 2.0 acceptance; carrier closed unmerged; advanced active cursor to N1.13 Collaboration | scores unchanged |

---

## 10. Exact next action

```text
A. Require GitHub-hosted governance PASS on the synchronized N1.12-closure/N1.13-cursor dev head.
B. Audit current Collaboration source, tests, comments/locks/approvals/history, tenant/permission/data-flow boundaries and registered local contracts on that exact governed head.
C. Freeze one exact N1.13 acceptance tracker derived from source truth; explicitly exclude missing distributed/realtime/provider or canonical dependency expansion work rather than fabricating it.
D. Execute the disposable N1.13 real-target carrier only after the tracker is frozen; keep the carrier DRAFT + DO NOT MERGE.
E. Independently review artifact bytes/digest and close the tracker only if every bounded gate passes; close the carrier unmerged.
F. Keep Project/Source/Target/Release at 76.5% / 99.0% / 50.0% / 25.0% until broader evidence justifies change.
G. Keep PR #1 DRAFT until final target/release closure.
```
