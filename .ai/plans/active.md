# Active Plan — SEARCH-200

## Identity

- Stable stage: `SEARCH-200 — Search 2.0`
- Registered development unit: `SYS-SEARCH`
- Legacy execution alias: `N1.12 — Search 2.0`
- Release train: `builder-beta`
- Status: `PARTIAL` — source exists; bounded real-target verification pending
- Current source release: `1.0.0-rc.94`
- Last exact product source governed and bounded-target accepted for N1.11: `2f5eb3b9dcf1c146f4e647fb3441318c4bf2c829`
- Method: verification/control of existing Search behavior. No feature expansion is authorized by this plan.

## Accepted prerequisite closure

N1.11 Customer Portal / CRM / Membership is **BOUNDED TARGET VERIFIED** and Issue #39 is CLOSED completed. Diagnostic PR #40 is CLOSED + UNMERGED.

Accepted evidence:

- frozen product source `2f5eb3b9dcf1c146f4e647fb3441318c4bf2c829`;
- exact-head development governance run `33545705598` PASS;
- target run `33550851207` / job `99999628418` SUCCESS;
- artifact `9817460169`;
- GitHub and independently downloaded ZIP digest both `sha256:b973ffdc424daf3e5b0987d75bff2c6a79b94f1c5e3530a7058091b1af08e1c8`;
- frozen feature contract `10 tests / 64 assertions PASS`;
- real HTTP and tenant/customer/member isolation gates PASS.

Carrier iterations fixed only diagnostic-harness defects (generated-PHP BOM, CSRF extraction, verified-user seed mass assignment). Frozen product/runtime source was not changed and assertions were not weakened.

Explicit N1.11 non-claims remain: Helpdesk, `PORTAL-200`, external providers/connectors, remaining five-engine database matrix, N1.12+, HA/recovery/C5/C6/final release.

## Scope boundary

The historical alias map resolves N1.12 to canonical `SEARCH-200`. The registry defines `SYS-SEARCH` as facets, provider abstraction and advanced indexing/querying, with canonical data remaining authoritative.

This plan continues the explicitly authorized legacy N1.9–N1.26 **target/product QA sequence**. Execution order is not a claim that all canonical SEARCH-200 dependencies (`CONTENT-MODEL-200`, `TAXONOMY-200`, `QUERY-ENGINE-200`) are product-complete or target-certified. The bounded pass must verify only Search behavior that already exists in the frozen source.

No new provider, external search service, dependency, schema, migration, permission, network destination, API, feature or UI expansion is authorized merely to obtain N1.12 acceptance. If source truth shows that a historical "Search 2.0" roadmap expectation is not implemented, record it as an explicit exclusion/future canonical requirement rather than fabricating evidence.

## Governance gate before freeze

The N1.11 closure and N1.12 cursor are being synchronized as a state-only commit. That exact canonical dev head must pass GitHub-hosted `governance` before it can be frozen as N1.12 product evidence.

The state-only commit itself does not become new product acceptance. If the exact-head governance run fails, fix only the governance/documentation defect unless the failure reveals a genuine product regression.

## Pre-execution source audit

After exact-head governance PASS, inspect at minimum the current equivalents of:

- public/admin search routes and controllers;
- query/index/search services and provider abstractions;
- searchable resource/document projection models and canonical source-of-truth links;
- indexing/reindex/delete/update paths and idempotency behavior;
- filters/facets/sort/pagination behavior where implemented;
- tenant context, permission/global-scope and resource visibility boundaries;
- migrations/index constraints relevant to search identities and tenant isolation;
- audit/events/queue behavior relevant to indexing where implemented;
- existing Search feature/unit/architecture tests;
- `scripts/search-product-contract-verify.php`;
- any existing operational target guidance used by the current search implementation.

Do not assume an external provider exists because `SEARCH-200` includes provider abstraction. If current source is internal/database-backed only, freeze that narrower implementation boundary and explicitly exclude external-provider certification.

## Provisional target acceptance dimensions

The final issue checklist must be derived from the governed source audit, but candidate dimensions are:

1. exact canonical source + carrier/toolchain binding;
2. fresh disposable rc.94 install/bootstrap and accepted post-install reconcile where required;
3. frozen Search product-contract verifier PASS;
4. frozen Search feature/unit contract PASS with warnings fail-hard;
5. real HTTP/search endpoint authorization and guest/admin boundaries where exposed;
6. tenant/resource visibility isolation and no cross-tenant result leakage;
7. canonical-source authority: index/search projection cannot become an alternate writable truth store;
8. create/update/delete/reindex convergence and duplicate/idempotency behavior where implemented;
9. deterministic query/filter/sort/facet/pagination behavior where implemented;
10. provider-selection/default/fail-closed behavior where implemented, without claiming an untested external provider;
11. target database/state evidence and applicable audit/event evidence with secrets/PII minimized;
12. artifact includes exact source binding, install/reconcile, verifier/tests, HTTP/state evidence, logs, summary and explicit exclusions;
13. green workflow alone is insufficient — downloaded artifact digest and contents require independent review.

## Security / privacy / data-flow boundary

Risk is `moderate` in the registry, but data-leak impact can become high if tenant/private resources are indexed incorrectly.

Required controls:

- search never broadens canonical authorization/tenant visibility;
- index/projected data is derived and rebuildable from authoritative canonical data;
- filters/provider names/resource IDs are validated rather than passed to unsafe dynamic SQL or shell execution;
- evidence avoids raw credentials, tokens and unnecessary PII;
- diagnostic carriers may seed deterministic `.test` data but must exercise canonical Search services/routes for the behavior being accepted;
- any cross-tenant/private-result disclosure is a product blocker, not a reason to weaken the harness.

No new external network or secret capability is planned.

## Quality / reliability method

This is verification/control of existing behavior, not a new Search design. Broad VOC/market research and DMADV are `NOT_APPLICABLE` to this bounded target-QA pass.

Use DMAIC-style control only when a reproducible defect is found. FMEA focus:

- stale index after canonical update/delete;
- duplicate projection/index rows on replay;
- cross-tenant/private resource leak;
- facet/filter/sort mismatch between source and indexed representation;
- unsupported provider silently falling back in a way that changes data visibility;
- target harness bypassing the owning Search service;
- diagnostic carrier accidentally merged as product source.

Performance claims are out of scope unless the current frozen acceptance explicitly measures them. `SEARCH-200` performance/provider scale remains a separate evidence boundary if not already implemented and measured.

## Execution chunks

### N12-A — state transition + source acceptance freeze

- [x] Record independently reviewed N1.11 bounded target acceptance.
- [x] Close Issue #39 and diagnostic PR #40 unmerged.
- [x] Resolve N1.12 to stable stage `SEARCH-200` and registered unit `SYS-SEARCH`.
- [ ] Commit synchronized `.ai/state.json`, `.ai/plans/active.md`, `.ai/handoff/current.md` and `NEXORA_PROGRESS.md` as state-only change.
- [ ] Obtain exact-head GitHub-hosted `governance` PASS on that state commit.
- [ ] Audit current Search source/tests/contracts on the exact governed head.
- [ ] Freeze one acceptance tracker with exact inclusions/exclusions and source binding.

### N12-B — real target carrier

- [ ] Create isolated DRAFT / DO NOT MERGE diagnostic carrier if required.
- [ ] Check out the tracker-frozen canonical source, not carrier source as product evidence.
- [ ] Fresh disposable target install/reconcile.
- [ ] Run applicable frozen Search source contracts/tests on target toolchain.
- [ ] Exercise canonical real HTTP/Search/state/tenant flows.
- [ ] Upload bounded evidence artifact.

### N12-C — independent evidence review / closure

- [ ] Download artifact independently.
- [ ] Verify GitHub artifact digest against downloaded ZIP bytes.
- [ ] Inspect source binding, summary, HTTP/state/test evidence and explicit exclusions.
- [ ] Classify any failure as product vs carrier defect and fix only the bounded prerequisite.
- [ ] Close tracker only after evidence is complete; close carrier unmerged.
- [ ] Reconcile `.ai` + `NEXORA_PROGRESS.md`, require exact-head governance, then advance to N1.13 / `COLLAB-200`.

## Rollback / stop conditions

Stop rather than advance if:

- state/progress/handoff disagree on the active stage or accepted N1.11 evidence;
- exact-head governance is absent/failing;
- N1.12 requires silently implementing missing canonical Search 2.0 expansion;
- evidence demonstrates tenant/private-result leakage or authorization bypass;
- index state is accepted as authoritative over canonical product data;
- tests can be made green only by weakening assertions/scopes/policies;
- source binding is ambiguous;
- a diagnostic carrier changes product/runtime source without a separately justified bounded defect fix.

## Exact next action

Commit this synchronized state-only transition, require exact-head GitHub-hosted `governance`, then audit and freeze the bounded N1.12 Search 2.0 acceptance contract from the governed source. Do not start target execution before that PASS.
