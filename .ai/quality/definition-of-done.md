# Nexora AI Definition of Done

No stage/unit advances because code exists or tests were added. Completion requires every applicable gate and recorded evidence. `NOT_APPLICABLE` is explicit, never inferred from omission.

## Universal preconditions before substantial implementation

- canonical parent stage + registered unit ID(s);
- dependencies mapped;
- active plan complete at proportional depth;
- new/redesigned work has ResearchBrief/problem/VOC/baseline/CTQs where applicable;
- DMADV or DMAIC method selected where substantial;
- architecture/DataFlow decisions recorded;
- high/critical security work has threat model;
- high/critical/complex material failure modes have FMEA where applicable;
- runtime-affecting work has performance budget/profile or explicit N/A;
- material relationship changes have System Graph/Flow impact/evidence plan or explicit N/A;
- critical stateful/provider work has reliability/idempotency/recovery plan;
- payment work has payment-provider profile and payment security plan before code.

## Universal completion gates

### 1. Research / scope / outcome integrity

- problem is distinguished from a prescribed solution;
- VOC/market/standards/baseline claims cite real evidence or are labeled assumption/UNKNOWN;
- CTQs and intended user/product outcome are objective;
- existing Nexora capability was inspected before adding new architecture;
- optional AI-discovered work was not silently promoted;
- out-of-scope remains out-of-scope until plan/registry/roadmap changes first.

### 2. Architecture

- public contracts/capabilities used where required;
- no first/third-party private Core shortcut;
- ADR/review exists for public-contract, data-authority, tenancy, security, execution, payment, canonical graph/provider/storage or protocol changes;
- persistence/domain/runtime/package boundaries remain fail-closed;
- migrations remain additive/portable/fresh-install safe where applicable;
- backward compatibility/deprecation path is explicit.

### 3. Data architecture / governance

When material data is affected:

- authoritative source/store identified;
- data classification recorded;
- tenant/site/user ownership and access path explicit;
- transformations and derived stores mapped;
- cache/search/analytics/vector/export are not silently authoritative;
- package/API/webhook/AI exposure is least-purpose/minimized;
- retention/export/delete propagation defined;
- migration/backfill/backup/restore/recovery behavior verified;
- logs/telemetry/error reports follow classification/redaction policy.

### 4. System Graph / Flow evidence

When material platform relationships are affected:

- expected graph nodes/edges and stable identities are declared;
- package/module/source/build/deployment ownership/version is attributable where applicable;
- relationship evidence class is explicit: `declared`, `static`, `observed`, `tested`, `production-observed` or `ai-inferred`;
- `ai-inferred` is never represented as observed fact;
- static analysis is never represented as runtime evidence;
- missing evidence is visible as missing/UNKNOWN rather than implicit PASS;
- relevant route/service/hook/event/job/data/DB/cache/network/secret/permission/capability/state/error/retry/deployment relations are captured or explicitly N/A;
- expected-vs-static/observed drift is tested where required;
- critical undeclared package capabilities/network/data paths fail/warn according to the owning architecture/security policy;
- Flow GUI uses canonical evidence rather than becoming a second architecture/security/performance/data source of truth;
- sensitive graph topology/data is tenant scoped, redacted and default-deny;
- `flow.*` access/export/deep-trace actions are auditable and re-auth/approval protected where policy requires;
- production trace/cardinality/retention overhead is bounded and measured;
- a manually/AI-drawn diagram alone cannot satisfy this gate.

### 5. Security / privacy

- risk class recorded;
- required threat model complete;
- auth/tenancy negative tests pass;
- secrets never leak to logs/source/AI;
- network/filesystem/package/AI powers are least privilege;
- privacy/consent/retention/export/delete addressed;
- controls are not weakened to make tests/scores pass;
- residual high/critical risk has explicit acceptance authority rather than implicit silence;
- Flow Intelligence is not allowed to expose restricted internal topology or sensitive values merely for debugging convenience.

### 6. FMEA / failure control

For applicable high/critical/complex flows:

- important failure modes/effects/causes identified;
- critical severity receives mitigation even if believed rare;
- prevention + detection + recovery/reconciliation defined;
- residual risk recorded;
- regression/control evidence added after fixes/incidents.

### 7. Functional implementation / UX

- happy path + permissions + validation + failure states;
- empty/loading/error/destructive states;
- accessibility/responsive/i18n as applicable;
- upgrade/backward compatibility;
- install/enable/disable/update/rollback/uninstall for packages;
- release/preview/staging implications for publishable work.

### 8. Public surfaces

Applicable Admin/public/API/webhook/SDK/theme/Studio/package/AI/import-export/Flow surfaces are intentionally handled and versioned.

### 9. Code quality / performance

- static/type/lint/build checks pass;
- complexity/duplication/dead-code/bundle regressions handled where in scope;
- frontend/Admin/backend/query/cache/network/memory/package attribution measured where applicable;
- budget/profile baseline comparison recorded;
- performance security/privacy overhead is considered;
- quality/performance/Sentinel security verdicts remain separate;
- performance metrics projected in Flow views remain linked to authoritative Performance evidence IDs rather than copied into an independent truth store.

### 10. Reliability

For critical recurring/stateful/provider flows:

- timeout/retry/backoff/idempotency/concurrency policy verified;
- failure isolation/degradation/fallback defined;
- SLI/SLO/error budget defined where meaningful;
- provider/partial/ambiguous failure scenarios tested;
- rollback/recovery/reconciliation exists;
- non-idempotent financial/destructive mutation is never blindly retried after ambiguous outcome;
- state/transaction/retry/error/recovery graph relationships are evidence-backed where Flow visibility applies.

### 11. Verification & Validation

As applicable:

- unit;
- integration/contract;
- architecture;
- data/migration;
- System Graph schema/provider/identity;
- declared-vs-static/observed drift;
- path-aware test/evidence coverage;
- authorization/tenancy;
- security/adversarial;
- Flow sensitive-access/redaction/export/deep-trace;
- browser/E2E/accessibility;
- package compatibility;
- performance/code quality;
- reliability/fault/recovery;
- AI evals;
- real target/provider sandbox.

Source checks produce `SOURCE_DONE`, not `TARGET_VERIFIED`. Real browser/runtime/DB/provider behavior is executed before target claims. A single observed trace does not prove all paths, concurrency safety or production behavior.

### 12. Outcome / Control

For outcome-dependent units:

- observation metric/window/trigger recorded;
- CTQ/outcome evidence linked when available;
- future evidence is never fabricated to close a current task;
- repeated/critical defects use DMAIC Control: regression test/budget/SLO/alert/static rule/graph-drift/process guard;
- learning feeds Research/quality docs rather than remaining only in incident/chat history.

### 13. Evidence / state

Record unit IDs, changed files/components, commands/tests, research/architecture/data/System-Graph/security/FMEA review, source/target/provider outcome, residual risk, blocker and exact next action. Update affected registries, state, handoff, active plan and changed governance docs; preserve history.

## System Graph / Flow-specific gates

`SYSTEM-GRAPH-100` must additionally verify:

1. versioned provider-neutral node/edge/evidence schemas;
2. stable identity for Core/Theme/Extension/App/Integration/Studio/module/package/version/route/service/data/provider/deployment nodes;
3. declared/static/observed/tested/production-observed/AI-inferred evidence separation;
4. provider ingestion contracts rather than hard-coded single-tool dependence;
5. representative architecture/data/security/package/runtime graph projections;
6. condition/gateway explanations retain source/policy meaning rather than only shapes;
7. expected-vs-observed drift detection on representative violations;
8. permissions/redaction/audit/export/deep-trace policy;
9. bounded storage/retention/cardinality and measured collector overhead;
10. integration with Performance/Data/Security/package evidence without duplicate authoritative telemetry;
11. representative Core, Theme and Extension real-target graph evidence.

`FLOW-INTELLIGENCE-200` must additionally verify:

1. accessible ecosystem→system→feature→execution zoom/collapse/search;
2. architecture/runtime/code/data/security/permission/error/event/queue/network/DB/cache/package/state/transaction/retry/deployment/supply-chain/test/performance/reliability/payment/AI/release lenses as applicable;
3. root vs propagated/secondary/recovered failure visualization;
4. source-to-sink/trust-boundary paths cite analyzer/runtime evidence and do not infer exploitability from adjacency;
5. path-aware test/evidence gaps;
6. version/branch/environment graph diff/history/time travel;
7. read-only runtime visual replay that cannot re-execute production side effects;
8. change impact/blast radius distinguishes potential/modelled from tested/observed impact;
9. incident view obeys sensitive access and forensic retention policy;
10. what-if results remain explicitly `modelled/predicted` until tested/observed;
11. Flow AI explanations cite authorized evidence classes and do not fabricate paths/causality;
12. demonstrated explanation of representative architecture drift, unexpected package network edge, data lineage, permission denial, error cascade, performance bottleneck and critical untested path.

## Package-specific gates

For Extension/App/Integration/Studio-Pack/Theme verify complete supported lifecycle:

`stage/upload → quarantine → Sentinel/Supply Chain → manifest/compatibility → capabilities/grants → dependencies → install → enable → runtime → disable → version/update/rollback → uninstall`

Also verify data purpose, external destinations, runtime/migration mode, network/filesystem/secrets, package attribution, performance/reliability/code-quality budgets, expected graph/Flow contribution and no private Core shortcut.

The package Flow profile must be able to distinguish declared package behavior from actual observed/static behavior where evidence exists. Flow richness never grants extra package privilege.

## Payment-provider gates

Any provider integration/payment-entry surface must additionally prove:

1. standard profile does not expose raw PAN/CVV/track/PIN to Nexora/generic package runtime/storage/logs/AI;
2. allowed flow is provider-hosted redirect/iframe/fields or approved tokenized SDK; generic raw-card collection remains forbidden by default;
3. package uses `security_profile: payment-provider` and only purpose-specific capabilities;
4. Core validates order/tenant/amount/currency/state; browser values cannot establish financial truth;
5. Secret Broker and Network Broker boundaries are enforced;
6. webhook signature/freshness/tenant binding/replay/deduplication/schema/out-of-order behavior is tested;
7. idempotency + concurrency prevents duplicate capture/refund/transition;
8. ambiguous provider timeout reconciles before retry;
9. browser success/return URL alone cannot mark paid;
10. 3DS/SCA/action-required/asynchronous states are modeled where supported;
11. protected payment surface has approved script inventory/slots, strict CSP/origins, tamper detection and session-replay/analytics exclusion;
12. payment logs/traces/errors/backups/AI are scanned/tested for sensitive-data leakage;
13. sandbox authorization/capture/refund/webhook/reconciliation tests pass;
14. test/live credentials are separated and live enablement is explicit;
15. threat model + FMEA + independent payment security review exist;
16. package kill switch, credential rotation and in-flight reconciliation are tested/planned;
17. generic Sentinel/Marketplace status is not presented as automatic PCI/payment compliance;
18. payment security/state/data paths can be projected in Flow Intelligence without exposing account data and without making the GUI the payment authority.

## AI-specific gates

AI tools/agents use registered typed tools, normal identity/capabilities, no unrestricted shell/DB/filesystem/secrets/network, prompt/tool-content trust boundaries, approvals, audit, budgets, rollback metadata and independent eval review for critical execution paths.

Flow AI tools additionally cannot elevate `ai-inferred` relationships to observed/tested truth, expose graph fields the current user/tool cannot access, or claim causal certainty from correlation alone.

## Managed/operations gates

Managed/cloud/ops claims require tenant isolation, auditable domain/SSL/secrets/deploy/backup operations, failure/drain/rollback/recovery, secret-safe diagnostics and real HA evidence for HA claims. Cost/resource attribution cannot leak tenant-sensitive data. Infrastructure/incident Flow views inherit the same restrictions and must not become unrestricted reconnaissance surfaces.

## Cursor advancement rule

The cursor advances only when the current stage reaches its required evidence status. Later foundations cannot waive earlier quality/security/data/System-Graph/payment gates. A newly discovered substantial capability is registered/planned first rather than silently pulling implementation outside the current cursor.
