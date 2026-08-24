# Nexora Master AI Execution Plan

## Objective

Deliver Nexora stage by stage without losing work, skipping prerequisites, creating hidden systems/packages, duplicating existing capabilities, optimizing the wrong problem, hiding architecture/data/runtime relationships, or declaring source-only work product-complete.

`.ai/roadmap/stages.md` controls canonical dependencies; main/domain registries control authorized development units; `.ai/quality/engineering-lifecycle.md` controls the closed-loop quality method; `.ai/flow/system-graph.md` controls canonical relationship/evidence planning where material flows change.

## Zero-skip + zero-hidden-work + evidence rule

The cursor advances only when the active stage satisfies its applicable Definition of Done and evidence is recorded.

Later code does not waive earlier gates. New work cannot be hidden in chat or an unrelated stage. AI assumptions are not measurements, VOC, root cause, compliance, System Graph runtime observation or target evidence.

## Unit lifecycle

```text
Signal / request
→ registry search
→ Research / problem / VOC / baseline
→ CTQs / intended outcome
→ stage + dependencies
→ architecture + DataFlow
→ security/privacy + threat model/FMEA
→ UX/design/accessibility
→ implementation
→ code quality + QA
→ performance
→ System Graph / relationship evidence
→ reliability
→ release
→ observe outcome
→ improve/control
→ evolve/deprecate
```

The flow can iterate; required decisions/evidence cannot be silently omitted.

## Method selection

### New/materially redesigned substantial capability

Use proportional **DMADV**:

`Define → Measure → Analyze → Design → Verify`

Use ResearchBrief, CTQs, alternatives/trade-offs and FMEA for high/critical systems.

### Existing defect/incident/regression/optimization

Use proportional **DMAIC**:

`Define → Measure → Analyze → Improve → Control`

Control means regression prevention: test, budget, SLO/alert, static/architecture/System-Graph drift rule, Sentinel policy, documentation or another durable control.

Trivial non-behavioral copy/style/typo changes stay lightweight.

## Before every new unit

1. Search main + relevant domain registries.
2. Register/reconcile stable unit ID.
3. Assign parent stage/release train/dependencies.
4. Decide Core vs first-party package vs external.
5. Establish ResearchBrief/problem/VOC/baseline/CTQs at proportional depth.
6. Define architecture/contracts/ADR.
7. Define DataFlow/classification/authority/lineage/migrations/retention.
8. Classify security risk; create threat model for high/critical.
9. Create FMEA for high/critical/complex material failures where applicable.
10. Define UI/accessibility/API/theme/Studio/package/AI surfaces.
11. Define performance/code-quality budget/profile.
12. Define System Graph/Flow contribution or explicit N/A: expected nodes/edges, ownership/version, data/network/secret/permission/state/error/retry/deployment relationships, evidence classes and drift checks.
13. Define reliability timeout/retry/idempotency/degradation/SLO/recovery.
14. Define privacy/compliance/cost/observability.
15. Define tests/target verification/rollback/post-release control.
16. Update roadmap/capability docs if scope is new.
17. Create/update `.ai/plans/active.md`.
18. Only then implement.

AI-discovered optional ideas may be `PROPOSED`; they are not silently promoted.

## System Graph / Flow special gate

Material runtime/package/data/security/permission/event/network/state/error/deployment changes follow `.ai/flow/system-graph.md`.

Core invariants:

- **Graph + evidence is truth; diagram is projection.**
- evidence classes remain distinct: `declared`, `static`, `observed`, `tested`, `production-observed`, `ai-inferred`;
- AI/static evidence is never silently promoted to runtime observation;
- one runtime trace does not prove all paths or concurrency safety;
- missing evidence remains missing/UNKNOWN;
- Theme/Extension/App/Integration/module paths retain stable package/source/version identity where applicable;
- expected-vs-observed drift is first-class;
- Flow Intelligence consumes Data Governance/Security/Sentinel/Performance/Reliability/Payment/Release/Observability evidence rather than duplicating it;
- sensitive topology is default-deny, tenant scoped, redacted and audited;
- production tracing is bounded/sampled and collector overhead is measured;
- a graph database requires measured need + ADR, not fashion.

## Payment-provider special gate

Any work that authorizes/captures/refunds payments, receives provider webhooks, stores payment-method references or affects payment-entry UI follows `.ai/security/payment-security.md` and `PAYMENT-SECURITY-200`.

Standard-profile invariants:

- raw PAN/CVV/track/PIN data does not enter Nexora Core/generic package runtime/storage/logs/analytics/backup/AI;
- hosted/tokenized provider flows preferred; generic direct raw-card collection forbidden by default;
- purpose-specific payment capabilities only;
- Core validates canonical amount/currency/order/state;
- Secret/Network Brokers mediate credentials/egress;
- signed/fresh/replay-safe tenant-bound webhook gateway;
- browser return is UX, not financial truth;
- payment page restricts scripts/slots with CSP/tamper controls;
- non-idempotent ambiguous mutations reconcile before retry;
- threat model + FMEA + independent payment security review + sandbox tests before activation;
- payment path can be represented in the System Graph only with safe/redacted evidence;
- generic Sentinel/package PASS is not payment certification.

## Package-specific planning

Before Extension/App/Integration/Studio Pack/Theme creation define identity/version/compatibility, public contracts, capabilities, runtime/migration mode, data purpose, network/filesystem/secrets, UI slots, Sentinel/Supply Chain, lifecycle, security/performance/reliability/code-quality tests, expected System Graph/Flow contribution and rollback/uninstall behavior.

First-party status never grants private Core shortcuts. Rich Flow visibility never grants additional privilege.

## Stage chunking

Large parent stages may use execution chunks (`STAGE-ID-A/B/C`). Chunk suffixes are not new canonical roadmap IDs. Parent stays active until all required chunks close.

## Per-chunk loop

1. Re-read state, active plan and registered units.
2. Inspect current source; do not trust prose alone.
3. Implement smallest architecture-correct slice.
4. Add contracts/migrations/tests/security/data/System-Graph/reliability controls in the same slice where applicable.
5. Run source/static/unit/integration/security/performance/graph checks available.
6. Fix regressions/root blocker before proceeding.
7. Record evidence, changed behavior and residual risk.
8. Update active plan/unit status.
9. Continue only after chunk postconditions pass.

## Required cross-cutting applicability decisions

Every substantial unit explicitly decides:

- research/problem/VOC/baseline/CTQs;
- architecture/ADR;
- data flow/classification/lineage/retention;
- tenancy/auth/permissions/runtime capabilities;
- security/Sentinel/threat model;
- FMEA/failure modes;
- privacy/compliance;
- migrations/fresh install/upgrade/backfill;
- UX/accessibility/i18n;
- SEO/AEO/routing;
- API/headless/webhook/SDK;
- theme/Studio/package surfaces;
- AI context/read/draft/execute;
- performance/code quality;
- **System Graph/Flow contribution, evidence class/provenance, sensitive access and drift checks;**
- reliability/SLO/recovery;
- observability/audit;
- cost/resource efficiency;
- tests/evals/target evidence;
- release/rollback/recovery;
- post-release outcome/control;
- documentation/deprecation.

`NOT_APPLICABLE` is explicit, not an omission.

## Security gates

Security is continuous. High/critical units require threat modeling. Auth/tenancy/public-write/executable-package/secret/network/payment/destructive/AI-tool/Flow-sensitive-topology units require explicit security review. `SENTINEL-200` later adds advanced isolation/revocation and does not replace earlier controls.

AI-generated code and AI-generated graph explanations are untrusted contributor outputs until objective evidence passes.

## Reliability gates

Critical recurring/stateful/provider workflows define bounded timeouts/retries/idempotency/concurrency/failure isolation/degradation/recovery and meaningful SLO/error-budget policy where applicable. Fault tests run in approved safe environments.

Financial/destructive operations are never blindly retried after ambiguous results. State/retry/recovery Flow projections cannot substitute for actual fault/reconciliation tests.

## Independent review

One model/session may perform multiple practical roles, but high-risk architecture/security/payment/AI execution/package-runtime/System-Graph trust-boundary/provider/storage work requires a distinct review pass/context plus automated/target evidence. Self-asserted correctness is never certification.

## Definition of Done levels

### SOURCE_DONE

Applicable source/contracts/migrations/data/security/backend/frontend/tests/static/performance/System-Graph/reliability/docs are coherent and source evidence passes.

### TARGET_VERIFIED

Required real environment/browser/provider/operator behavior is exercised successfully. A static/AI-generated graph cannot satisfy a runtime target gate.

### Outcome/control evidence

For units whose success depends on post-release use/operations, target verification does not fabricate future outcomes. Record required observation trigger/window and keep the unit/release control obligation visible until evidence exists.

## Failure handling

When blocked:

1. set stage/unit `BLOCKED`;
2. record first/root blocker;
3. preserve successful evidence;
4. do not jump to unrelated stages;
5. fix blocker or obtain explicit roadmap change;
6. use DMAIC/root-cause evidence for recurrent failures;
7. add durable Control protection, including graph-drift/path evidence where useful.

## Architecture/data/System-Graph change handling

When implementation conflicts with architecture/data authority/observed relationships:

- do not choose the easiest side silently;
- identify intended authority;
- compare declared/static/observed/test evidence;
- write/update ADR when public contract/data authority/System-Graph provider/storage semantics change;
- update architecture/data/graph tests;
- preserve compatibility/migration/security impact;
- do not convert unexpected observed behavior into approved architecture merely because it exists.

## Roadmap-change handling

```text
request / discovered gap
→ research/classify
→ registry search
→ reuse or stable new unit ID
→ stage/release train/dependencies
→ quality/architecture/data/security/performance/System-Graph/reliability plan
→ update roadmap/capability docs
→ active plan
→ implementation
```

Moving the active cursor requires explicit user priority change.

## AI execution safety

Development AI and future product AI share structured-plan/typed-contract/least-privilege/validation/approval/audit/recovery principles.

The development agent must not:

- disable controls to make tests/scores pass;
- fabricate research/measurements/root cause/target verification;
- fabricate graph paths, causality or evidence classes;
- mark AI/static graph evidence as observed runtime truth;
- edit shipped migrations when additive migration is required;
- use private Core shortcuts;
- hide vertical products in Core;
- create parallel roadmap/state/graph truth;
- silently renumber semantic IDs;
- overwrite targets to hide repair/upgrade issues;
- implement an unregistered unit;
- silently promote optional AI ideas;
- grant product AI unrestricted shell/DB/filesystem/secrets/network;
- expose restricted Flow topology or sensitive fields to unauthorized AI/users;
- allow raw payment account data into the standard Nexora/payment-package runtime;
- mark a payment paid from browser redirect alone;
- retry an ambiguous non-idempotent financial mutation without reconciliation.

## Release-train sequencing

1. **Builder Beta** — research/quality-governed secure CMS/site-builder + data/performance/code-quality/**System Graph** foundations.
2. **Pro** — AI-native, Performance Intelligence, Reliability, **Flow Intelligence**, Product Outcomes and Delivery Excellence.
3. **Platform** — marketplace, Payment Security + Commerce, portals, managed cloud, enterprise/security/ops/cost with graph-aware incident/package evidence.
4. **Production** — performance/accessibility/payment-enabled/security/reliability/Flow/exact-source certification.

## End-of-pass handoff

Update affected registries, state, handoff, active plan, and changed roadmap/quality/data/security/performance/flow/reliability docs. Record source/target/provider/graph/outcome evidence separately and state the exact next action. Preserve history.

## Final release rule

`N2-STABLE-100` cannot be reached by percentage estimates. It requires preceding production gates and `RELEASE-CERT-100` evidence for the intended source/deployment capabilities. Flow evidence must be internally consistent for release-critical paths; payment-specific evidence is conditional on payment support being enabled, but cannot be skipped when it is enabled.
