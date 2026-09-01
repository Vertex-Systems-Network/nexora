# Active Plan — CRM-MEMBERSHIP-HELPDESK-CLOSURE-001

## Identity

- Stable stage: `CRM-MEMBERSHIP-HELPDESK-CLOSURE-001 — CRM, Membership & Helpdesk Closure`
- Registered development unit: `SYS-CRM-MEMBERSHIP-HELPDESK`
- Legacy execution alias: `N1.11 — CRM / Membership / Customer Portal`
- Release train: `platform`
- Status: `PARTIAL` — source exists; bounded real-target verification pending
- Current source release: `1.0.0-rc.94`
- Last exact dev source already governed: `43314a111405245f151ec66c01e9261af675c992`
- Current prerequisite activity: semantically integrate current `main@6d0bb2cf7f92777b8f5f7f4f84ae0f041069124a` control-plane lineage into the long-lived development lineage and obtain exact-head hosted governance before target execution.
- Method: verification/control of existing behavior. No feature expansion is authorized by this plan.

## Scope boundary

This plan continues the user-authorized historical N1.9–N1.26 **target/product QA sequence**. The historical alias map resolves N1.11 to current CRM/Membership closure plus a later broader `PORTAL-200` product stage.

This bounded pass may verify the Customer Portal surfaces that already exist in source as part of the current CRM/Membership customer experience, but it MUST NOT silently implement or certify the broader `PORTAL-200` portal-builder expansion. Likewise, execution-order continuation does not imply that unrelated canonical dependency stages have become TARGET_VERIFIED.

No new product feature, dependency, migration, permission, external provider, network destination, payment capability or public API is authorized merely to obtain N1.11 acceptance. If a genuine prerequisite defect is discovered, classify and fix only that bounded defect under the appropriate registered scope, then re-run exact evidence.

## Prerequisite governance integration

Current main advanced after the prior N1.10 governed source and introduced the `.ai/**` AI-native control plane plus the version-pinned rc.93 repair-pack lineage. That caused PR #1 to become merge-conflicted and prevented GitHub from creating the required exact-head `pull_request` governance run for the N1.10 state-only closure head.

The integration control is:

1. preserve both histories using a real two-parent merge;
2. preserve current main `.ai/**`, Copilot instructions and rc.93 repair-pack files;
3. semantically merge `AGENTS.md` so `.ai/state.json` is canonical active state while `NEXORA_PROGRESS.md` remains synchronized detailed target/release evidence for the long-lived dev program;
4. semantically merge `package.json` so main `repair:rc93` and dev C5/dev-target scripts all survive;
5. reconcile stale `.ai` runtime-blocked state to already accepted later evidence rather than reopening completed Issue #2;
6. run exact-head GitHub-hosted governance on the reconciled integration;
7. merge to dev only after the integration head is green and evidence is reviewed.

This is governance reconciliation, not product implementation.

## Existing accepted prerequisites that remain factual

### Runtime replacement recovery

Issue #2 is CLOSED using the accepted separate disposable current-source rc.94 replacement recovery gate. PR #17 remains CLOSED + UNMERGED. Artifact `9500449768` / `sha256:1ac7ccf409181322e74ca1444bfd2ed3cca1539875eba398ad0d98a06e7e4aba` is accepted bounded evidence.

The preserved rc.93 installation remains historical evidence. The newly integrated rc.93 repair pack is retained as a safe version-pinned control tool, not as authority to reverse the later accepted replacement closure.

### N1.9 Marketplace

Issue #20 CLOSED; PR #21 CLOSED + UNMERGED. Accepted run `32671245015`, artifact `9501470648`, digest `sha256:b26036aa0ad8c7ac075f1a60e213163ce10121e2ff3f606cdd06406ce3fb6aed`.

### N1.10 Commerce

Issue #32 CLOSED; PR #33 CLOSED + UNMERGED. Frozen exact source `43314a111405245f151ec66c01e9261af675c992`.

- Primary target run `33540575198`, artifact `9813554570`, digest `sha256:68e8e9cefcb32a49a6d9912b5a3b1a4f7eaf0b3ac94850ec8418262c82cad882`.
- Provider supplement run `33540575159`, artifact `9813440996`, digest `sha256:1af3fd58308e92e1f90431588a045ecc7810d1de96bc7152bed4e9a0c0bcd330`.

No external gateway/PCI/five-engine/final-release claim follows from this evidence.

## Objective

Produce reviewed real-target evidence that the **currently implemented** CRM, membership/access and customer-portal workflows are usable, tenant/customer scoped, authorization-safe, state-consistent and correctly linked to existing Commerce where current source defines that relationship.

The acceptance contract is not frozen until the post-integration exact source is governed and current routes/services/tests are audited. The tracker must be derived from source truth, not guessed from the historical roadmap label.

## Pre-execution source audit

After governance PASS, inspect at minimum the current equivalents of:

- Customer Portal controller/routes/layout/pages and its authentication/authorization middleware;
- CRM Contact, Lead, Opportunity, Organization and Settings controllers/models/services;
- CRM↔Commerce link model/service;
- Membership controllers, plans, membership manager and Commerce sync service;
- tenant context/global scopes and route-binding policy used by these models;
- roles/permissions/audit events relevant to portal, CRM and membership mutations;
- existing feature/unit/architecture tests;
- `scripts/crm-membership-product-contract-verify.php` and `scripts/customer-portal-product-contract-verify.php`;
- migration constraints for tenant-scoped CRM/membership identities.

Do not assume Helpdesk/ticket/SLA features exist merely because the canonical stable stage name includes Helpdesk. If current source lacks a ticket/SLA subsystem, record that explicitly as outside the existing N1.11 source boundary rather than fabricating acceptance evidence.

## Provisional target acceptance dimensions

The exact issue checklist must be refined from the source audit, but candidate dimensions are:

1. exact source + target/toolchain binding;
2. fresh disposable rc.94 install/bootstrap and accepted post-install reconcile where required;
3. real HTTP login/session plus guest fail-closed behavior;
4. portal route accessibility only to the correct authenticated customer/member principal;
5. no cross-user or cross-tenant portal data exposure;
6. CRM contact/organization/lead/opportunity lifecycle and tenant-scoped identity constraints where implemented;
7. lead conversion/linking behavior without duplicate or cross-tenant linkage;
8. membership plan/member lifecycle and entitlement/access state transitions where implemented;
9. Commerce↔CRM/Membership relationship consistency without duplicating financial authority;
10. idempotency/state preservation for existing retryable synchronization paths where implemented;
11. permission boundary: principals lacking required CRM/membership/admin capability fail closed;
12. relevant audit/event evidence and target database state captured with secrets/PII minimized or redacted;
13. applicable existing product-contract/unit/feature tests pass on the target toolchain;
14. evidence artifact includes source binding, HTTP statuses, database assertions, test output and concise summary; green workflow alone is not acceptance.

## Security / privacy / data-flow boundary

Risk is `high` because customer/member/CRM records are identity-linked and tenant-scoped.

Required controls:

- default deny outside authenticated/authorized routes;
- tenant context and customer/member ownership enforced at query/mutation boundaries;
- no cross-tenant relation IDs accepted merely because a UUID exists;
- CRM and Membership must not become alternate financial truth stores for Commerce;
- customer/member portal exposes only data authorized for the current principal;
- evidence must avoid raw credentials, session cookies, secret tokens and unnecessary customer PII;
- diagnostic helpers/carriers may create deterministic `.test` identities but must not bypass canonical application services for the behavior being certified;
- any discovered permission/data-scope defect is a product blocker, not a reason to weaken the acceptance harness.

No new external network or secret capability is planned. Payment-provider activation is out of scope.

## Quality / reliability method

This is verification/control of existing product behavior, not new feature design. Broad VOC/market research and DMADV are `NOT_APPLICABLE` to this bounded target-QA pass.

Use DMAIC-style control only if a defect is discovered:

- **Define** the exact failing current workflow/gate;
- **Measure** reproducible HTTP/database/test evidence;
- **Analyze** source-level root cause without broadening scope;
- **Improve** only the bounded prerequisite defect;
- **Control** with regression coverage plus re-run exact target evidence.

FMEA focus for the acceptance harness:

- wrong tenant/customer resource selected;
- unauthorized portal access;
- membership state diverges from canonical entitlement/Commerce relationship;
- CRM conversion/link replay creates duplicates;
- stale/current-user session confusion in evidence;
- test-only bypass mistaken for real route/service proof;
- diagnostic carrier accidentally merged as product source.

## Performance / System Graph / cost

No performance claim or runtime architecture expansion is intended. Performance budget is `NOT_APPLICABLE` for the target-QA carrier itself beyond avoiding unbounded loops or data enumeration.

System Graph product changes are `NOT_APPLICABLE`; evidence may identify existing route/service/model relationships but must not be presented as complete runtime graph truth.

## Execution chunks

### G0 — control-plane reconciliation

- [x] Detect current-main divergence and reason exact-head PR governance did not start.
- [x] Audit main delta from the prior certified main baseline.
- [x] Identify actual semantic conflicts (`AGENTS.md`, `package.json`).
- [x] Create true two-parent resolved integration commit preserving both histories.
- [x] Reconcile `.ai/state.json` to later accepted runtime/N1.9/N1.10 evidence.
- [x] Refresh `.ai/handoff/current.md` and this active plan.
- [ ] Synchronize `NEXORA_PROGRESS.md` with the integration blocker/current cursor.
- [ ] Obtain exact-head hosted governance on the final reconciliation head.
- [ ] Merge the reviewed integration into dev and close temporary diagnostic carriers unmerged.
- [ ] Re-prove the resulting canonical dev head if its SHA differs from the governed integration head.

### N11-A — source acceptance freeze

- [ ] Audit current portal/CRM/membership source and executable contracts on the governed exact source.
- [ ] Resolve which current behavior belongs to bounded N1.11 versus later `PORTAL-200`.
- [ ] Create one acceptance tracker with exact inclusions/exclusions and source binding.

### N11-B — real target carrier

- [ ] Create isolated DRAFT / DO NOT MERGE diagnostic carrier if required.
- [ ] Check out the tracker-frozen canonical source, not carrier source as product evidence.
- [ ] Fresh disposable target install/reconcile.
- [ ] Run applicable existing source contracts/tests on the target toolchain.
- [ ] Exercise canonical HTTP/product flows and capture target DB/audit evidence.
- [ ] Upload bounded evidence artifact.

### N11-C — independent evidence review / closure

- [ ] Download artifact independently.
- [ ] Verify GitHub artifact digest against downloaded ZIP bytes.
- [ ] Inspect source binding, summary, HTTP/database/test evidence.
- [ ] Classify any failure as product vs carrier defect and fix only the bounded prerequisite.
- [ ] Close tracker only after evidence is complete; close carrier unmerged.
- [ ] Reconcile `.ai` + `NEXORA_PROGRESS.md` and require exact-head governance before moving to N1.12.

## Rollback / stop conditions

Stop rather than advance if:

- integration drops either main AI-governance controls or newer dev evidence/accessibility controls;
- exact-head governance is absent/failing;
- N1.11 requires silently implementing `PORTAL-200` or another future stage;
- evidence demonstrates cross-tenant/customer leakage or permission bypass;
- target behavior can be made green only by weakening assertions/middleware/policies;
- source binding is ambiguous;
- a carrier changes product/runtime source without a separately justified bounded defect fix.

Rollback for diagnostic integration/carrier branches is to close them unmerged; never rewrite preserved evidence histories.

## Exact next action

Synchronize `NEXORA_PROGRESS.md` with this reconciliation, then run exact-head GitHub-hosted governance on the final resolved integration head. **Do not start N1.11 target execution before that PASS.**
