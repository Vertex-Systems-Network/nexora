# Active Plan — COLLAB-200

## Identity

- Stable stage: `COLLAB-200 — Collaboration`
- Registered development unit: `SYS-COLLABORATION`
- Legacy execution alias: `N1.13 — Collaboration`
- Release train: `platform`
- Status: `PARTIAL` — source exists; bounded real-target verification pending
- Current source release: `1.0.0-rc.94`
- Last exact bounded-target accepted source: `78976e9f44e290155b119ae722a77fb37fd59018`
- Method: verification/control of existing Collaboration behavior. No feature expansion is authorized by this plan.

## Accepted prerequisite closure

N1.12 Search 2.0 is **BOUNDED TARGET VERIFIED**. Issue #41 is CLOSED completed and diagnostic PR #45 is CLOSED + DRAFT + UNMERGED.

Accepted evidence:

- frozen source `78976e9f44e290155b119ae722a77fb37fd59018`;
- exact-head governance run `33553824752` / job `100009648131` PASS;
- certified carrier `449ea9b3d5c0d2ad176031a1849ed80c349e0023`;
- target run `33563871904` / job `100042466730` SUCCESS;
- artifact `9822383456`;
- GitHub and independently downloaded ZIP digest both `sha256:6d298f5d60c5dc7a0423aec8d73d107de79888662cc919d62d53824b0e723731`;
- fresh install/reconcile, frozen Search verifier/tests, index lifecycle/rebuild/idempotency, tenant isolation, dual RBAC, real HTTP permission/privacy boundaries and reindex audit PASS.

Only the certified carrier SHA/run above is acceptance evidence. Later PR #45 no-content/tree-equivalent heads are excluded.

N1.12 explicit non-claims remain: external Search providers, distributed indexing/HA, unimplemented facets, Search scale/relevance SLOs, broader `PORTAL-200`, five-engine database matrix, N1.13+, HA/recovery/C5/C6/final release.

## Scope boundary

Legacy N1.13 resolves to canonical `COLLAB-200` and registered unit `SYS-COLLABORATION`.

Canonical roadmap dependencies are `SITE-BUILDER-200`, `RELEASE-WORKFLOW-200`, and `PORTAL-200`. The explicitly authorized N1.9–N1.26 target-QA sequence is an execution priority only; it does **not** certify, skip or satisfy these canonical dependency claims. If the bounded Collaboration acceptance truly requires an unfinished dependency, stop and classify that dependency instead of manufacturing evidence.

Registry local contracts include:

- `App\Nexora\Collaboration\CollaborationRepository`;
- `App\Nexora\Collaboration\ApprovalWorkflow`;
- `App\Nexora\Collaboration\LockManager`.

No new collaboration provider, cache/lock backend, queue, schema, migration, permission, network destination, API or UI expansion is authorized merely to obtain N1.13 acceptance.

## Governance gate before freeze

This N1.12 closure/N1.13 cursor synchronization is a state-only commit. Its exact canonical dev head must pass GitHub-hosted `governance` before it may be frozen as N1.13 source evidence.

The state-only commit itself is not new product acceptance. If exact-head governance fails, classify the failure and fix only the relevant governance/documentation defect unless the run exposes a genuine product regression.

## Pre-execution source audit

After exact-head governance PASS, inspect the current source equivalents of:

- Collaboration repository/service contracts and concrete models;
- comment/thread/reply creation, update/delete and visibility behavior where implemented;
- lock acquire/conflict/heartbeat/refresh/release/expiry paths where implemented;
- approval workflow states, transitions, actor authorization and invalid-transition rejection;
- collaboration history/audit/event provenance;
- HTTP/API/admin UI routes/controllers and request validation;
- tenant context, organization/member resolution, global scopes, authorization policies/middleware;
- persistence tables/migrations, uniqueness/concurrency constraints and timestamps/TTLs;
- queue/cache/lock backend behavior only where the source actually uses it;
- existing Collaboration feature/unit/architecture tests and product-contract verifier scripts;
- installer/upgrade/reconcile effects on Collaboration state.

Do not infer distributed collaboration, Redis coordination, presence, realtime/WebSocket delivery, conflict-free editing or external providers merely from stage naming. Freeze only behavior that current source implements and can prove.

## Provisional target acceptance dimensions

The final issue checklist must be derived from the governed source audit. Candidate dimensions are:

1. exact canonical source + carrier/toolchain binding;
2. fresh disposable rc.94 install/bootstrap and accepted post-install reconcile;
3. frozen Collaboration source-contract verifier/tests PASS warning-hard;
4. installed-target collaboration state uses canonical product services rather than direct manufactured truth;
5. comment/history creation, ownership, ordering and visibility where implemented;
6. tenant/organization isolation with no cross-tenant actor/resource/history disclosure;
7. lock acquire/conflict/refresh/release/expiry semantics where implemented;
8. competing writers cannot steal or bypass an active lock and stale ownership fails closed;
9. approval workflow valid transitions succeed only for authorized actors and invalid/unauthorized transitions fail closed;
10. replay/retry/idempotency and duplicate protection where the current implementation claims them;
11. real HTTP/API/UI auth and permission boundaries where exposed;
12. audit/history evidence is attributable and cannot be silently accepted as mutable authoritative truth when the source defines append/history semantics;
13. target logs contain no hidden fatal/uncaught runtime exception in the accepted flow;
14. evidence artifact includes exact binding, install/reconcile, tests, state/concurrency/auth/tenant evidence, logs, summary and explicit exclusions;
15. green workflow alone is insufficient — downloaded artifact digest/content require independent review;
16. diagnostic carrier remains DRAFT / DO NOT MERGE and is closed unmerged after acceptance or abandonment.

## Security / privacy / data-flow boundary

Collaboration data can expose user identity, document/resource activity and approval history. Required controls:

- tenant and resource authorization remain fail closed;
- comments/history/approvals never broaden canonical resource visibility;
- actor/member identifiers are resolved through tenant-aware paths where required;
- lock tokens/owners cannot be forged or transferred by a weaker permission path;
- evidence minimizes PII and excludes credentials, session secrets and raw lock/auth tokens;
- diagnostic fixtures may use deterministic `.test` identities but must exercise canonical services/routes;
- any cross-tenant disclosure, lock theft, approval bypass or history tampering is a product blocker, not a reason to weaken the harness.

No new external network or secret capability is planned.

## Quality / reliability method

This is verification/control of existing behavior, not a new Collaboration design. Broad VOC/market research and DMADV are `NOT_APPLICABLE` to this bounded target-QA pass.

Use DMAIC-style control only when a reproducible defect is found. FMEA focus:

- cross-tenant comment/history disclosure;
- stale lock surviving expiry/release incorrectly;
- competing writer steals or bypasses a lock;
- lock refresh accepts wrong owner/token;
- approval privilege escalation or invalid state transition;
- duplicate comment/history/approval action on retry;
- history/audit evidence detached from the canonical actor/resource;
- target fixture bypasses production authorization or tenant context;
- distributed/cache semantics are claimed without real backend evidence;
- diagnostic carrier accidentally merged as product source.

Performance/realtime/distributed claims are out of scope unless current source implements and the frozen acceptance explicitly measures them.

## Execution chunks

### N13-A — state transition + source acceptance freeze

- [x] Record independently reviewed N1.12 bounded target acceptance.
- [x] Close Issue #41 and diagnostic PR #45 unmerged.
- [x] Resolve N1.13 to `COLLAB-200` / `SYS-COLLABORATION`.
- [ ] Commit synchronized `.ai/state.json`, `.ai/plans/active.md`, `.ai/handoff/current.md` and `NEXORA_PROGRESS.md` as one state-only change.
- [ ] Obtain exact-head GitHub-hosted `governance` PASS.
- [ ] Audit current Collaboration source/tests/contracts on that exact governed head.
- [ ] Freeze one acceptance tracker with exact inclusions/exclusions and source binding.

### N13-B — real target carrier

- [ ] Create isolated DRAFT / DO NOT MERGE diagnostic carrier only after the tracker is frozen.
- [ ] Check out the tracker-frozen canonical source, not carrier source as product evidence.
- [ ] Fresh disposable target install/reconcile.
- [ ] Run applicable frozen Collaboration source contracts/tests on target toolchain.
- [ ] Exercise canonical state/concurrency/auth/tenant/HTTP flows that exist in source.
- [ ] Upload bounded evidence artifact.

### N13-C — independent evidence review / closure

- [ ] Download artifact independently.
- [ ] Verify GitHub artifact digest against downloaded ZIP bytes.
- [ ] Inspect source binding, summary, state/concurrency/auth/tenant/test/log evidence and exclusions.
- [ ] Classify each failure as product vs carrier defect; fix only the bounded prerequisite.
- [ ] Close tracker only after evidence is complete; close carrier unmerged.
- [ ] Reconcile `.ai` + `NEXORA_PROGRESS.md`, require exact-head governance, then advance to N1.14 / `AUTO-200`.

## Rollback / stop conditions

Stop rather than advance if:

- state/progress/handoff disagree on active stage or N1.12 accepted evidence;
- exact-head governance is absent/failing;
- N1.13 requires silently implementing missing Collaboration expansion or an unmet canonical dependency;
- evidence shows cross-tenant disclosure, authorization bypass, lock theft, invalid approval escalation or hidden runtime failure;
- tests can be made green only by weakening assertions/scopes/policies;
- source binding is ambiguous;
- a diagnostic carrier changes product/runtime source without a separately justified bounded defect fix.

## Exact next action

Commit this synchronized state-only transition, require exact-head GitHub-hosted `governance`, then audit and freeze the bounded N1.13 Collaboration acceptance contract from source truth. Do not start target execution before that PASS.
