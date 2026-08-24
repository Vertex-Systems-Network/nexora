# Nexora Development Intake & Pre-Planning Protocol

This protocol applies to every new Core system, module, feature, extension, app, integration, studio pack, theme, AI tool/agent, API surface, migration adapter, security control and operational capability.

## Non-negotiable rule

**No implementation begins from an idea alone.** A requested or discovered change is represented in the AI control plane before code is written.

```text
request / discovered signal
→ classify development unit
→ search/reconcile registry
→ research/problem/VOC/baseline at proportional depth
→ define CTQs / outcome
→ map stage + dependencies
→ architecture + data flow
→ security/privacy + threat/FMEA
→ UX/design/accessibility
→ performance/code quality
→ System Graph / expected Flow contribution
→ reliability/cost
→ tests/verification/rollback/control plan
→ active plan
→ implementation
→ graph/evidence + source/target evidence
→ release/observe/outcome/improve/control
```

## Development unit types

- `core-system`
- `module`
- `feature`
- `extension`
- `app`
- `integration`
- `studio-pack`
- `theme`
- `ai-tool`
- `ai-agent`
- `migration-adapter`
- `ops-capability`
- `security-control`

## Unit lifecycle

`IDEA → PROPOSED → PLANNED → ACTIVE → SOURCE_DONE → TARGET_VERIFIED`

Special/terminal: `EXTERNAL`, `DEFERRED`, `BLOCKED`, `REJECTED`, `DEPRECATED`.

## Intake behavior

### Explicit user request

The AI may plan it immediately without asking the user to repeat the request. Before implementation it must register/reconcile the unit, map stage/dependencies, establish appropriate research/quality evidence, complete architecture/data/security/performance/System-Graph/reliability planning and update the active plan.

If planning materially changes product scope, trust boundary, destructive data behavior or intended outcome, make that visible rather than silently substituting a different product decision.

### AI-discovered gap/opportunity

AI may register as `PROPOSED`, explain evidence/value and roadmap placement, but may not silently implement it unless required by approved active scope or explicitly promoted.

## Stable unit IDs

Use semantic immutable IDs:

- `SYS-*`, `MOD-*`, `FEAT-*`, `EXT-*`, `APP-*`, `INT-*`, `SPK-*`, `THM-*`, `AIT-*`, `AIA-*`, `MIG-*`, `OPS-*`, `SEC-*`.

## Research / Quality method selection

### New/materially redesigned substantial capability

Use proportional **DMADV / Design for Six Sigma**:

1. Define problem/user/VOC/value/scope.
2. Measure baseline/CTQs/current alternatives.
3. Analyze options/trade-offs/architecture/data/security/design/FMEA.
4. Design contracts/flows/controls/tests/budgets/recovery.
5. Verify source + target + outcome constraints.

Use `.ai/research/research-brief-template.md` and `.ai/quality/engineering-lifecycle.md`.

### Existing defect/incident/regression/optimization

Use proportional **DMAIC**:

`Define → Measure → Analyze → Improve → Control`

Root-cause and baseline must be evidence, not AI inference. The Control step adds the regression test/budget/SLO/alert/static rule/process control that prevents silent recurrence.

### Proportionality

Trivial copy/style/typo-only changes with no behavior/contract/runtime impact do not require heavyweight research/FMEA/System-Graph work. High/critical systems, payments, auth, tenancy, destructive data, executable packages, secrets, AI execution and architecture/runtime relationship changes require the full applicable depth.

## Required planning fields

Every substantial unit records:

1. stable ID/type/status;
2. ResearchBrief/problem/source/confidence or proportional N/A;
3. intended users/VOC evidence;
4. baseline or explicit `UNKNOWN`;
5. CTQs/product outcome/guardrails;
6. parent stage/release train/dependencies/conflicts;
7. Core vs package/external decision;
8. architecture/contracts/ADR;
9. DataFlow, authoritative source, classification, ownership/tenant/lineage;
10. persistence/migration/derived-store/retention/export/delete impact;
11. permissions/runtime capabilities/tenancy;
12. security risk/threat-model requirement;
13. FMEA requirement/failure modes;
14. privacy/compliance;
15. UI/UX/accessibility;
16. API/webhook/SDK;
17. theme/Studio/package surfaces;
18. AI read/draft/execute/context exposure;
19. observability/audit;
20. performance/code-quality budget/test profile or explicit N/A;
21. **System Graph/Flow contribution or explicit N/A**;
22. reliability timeout/retry/idempotency/degradation/SLO/recovery impact;
23. cost/resource impact;
24. testing/evals/target verification;
25. rollback/recovery/update/deprecation compatibility;
26. post-release observation/outcome/control plan;
27. documentation/handoff;
28. explicit acceptance criteria/out-of-scope.

## Data-flow planning rule

Use `.ai/data/data-flow-governance.md` for material data changes. A DataFlow review is mandatory for new sensitive/financial data, external processors/providers, cross-tenant/shared data, new derived stores, analytics/RUM/AI, public write APIs/webhooks, destructive migrations and payment providers.

Derived caches/search indexes/analytics/vector stores are not silently authoritative. They need invalidation/rebuild/deletion/recovery semantics.

## System Graph / Flow planning rule

Use `.ai/flow/system-graph.md` for any unit that materially changes platform relationships.

The active plan must define or explicitly mark `NOT_APPLICABLE`:

- expected graph node types/identities;
- expected relationships/edges;
- Core/module/Theme/Extension/App/Integration/package ownership/version;
- route/controller/service/contract/registry/hook/event/filter/slot/job/schedule relationships where applicable;
- data/DB/cache/search/file/queue flows;
- network/external-provider origins;
- secret/broker relationships;
- human permission/runtime capability/approval paths;
- trust zones/boundaries;
- conditions/gateways and state transitions;
- transaction/lock/idempotency/concurrency/retry/reconciliation relationships;
- errors/fallbacks/recovery paths;
- infrastructure/deployment/config/feature-flag conditions where applicable;
- test/evidence coverage expected for critical paths;
- sensitive graph fields/redaction/access policy;
- evidence providers expected (`declared`, `static`, `observed`, `tested`, `production-observed`, `ai-inferred`);
- expected-vs-observed drift checks.

### Flow evidence rules

- A manually drawn diagram is not evidence.
- An AI-generated path is `ai-inferred` until independently supported.
- Static analysis is not runtime observation.
- One runtime trace does not prove all possible paths or concurrency safety.
- Missing evidence is `UNKNOWN`/missing, never silent PASS.
- Flow Intelligence does not replace Data Governance, Sentinel/Security, Performance, Reliability, Payment Security, Release or Observability as their domain authority.

### Package visibility rule

A Theme/Extension/App/Integration/Studio Pack/module intended to execute or contribute runtime behavior should expose enough stable identity/registration metadata for the System Graph to attribute its:

- lifecycle;
- contracts/hooks/slots/routes/jobs/components;
- capabilities;
- data stores/classifications;
- network/secret usage through allowed brokers;
- frontend assets/origins;
- errors;
- performance/runtime spans;
- dependency/supply-chain identity;
- tests/security/reliability evidence.

A package must not gain new privilege merely to make its graph richer.

## Performance planning rule

Runtime-affecting units plan public/Admin/backend execution, DB/cache/network/memory, frontend assets/main-thread, package attribution, code-quality/build, baseline/budget and reproducible test profile. Stable graph/Flow identity should be planned where attribution is required. Use `.ai/performance/performance-budget-template.md`.

## Reliability planning rule

Critical recurring/stateful/provider flows plan:

- timeout;
- retry/backoff;
- idempotency;
- concurrency/locking;
- failure isolation;
- graceful degradation;
- provider outage/ambiguous response;
- recovery/reconciliation;
- meaningful SLI/SLO/error budget where applicable;
- fault test;
- graphable state/retry/error/recovery evidence where applicable.

Use `.ai/reliability/reliability-program.md`.

## Package planning rule

Before Extension/App/Integration/Studio Pack/Theme creation define:

- family, identity/version/compatibility;
- public contracts/capabilities only;
- runtime mode (`declarative` preferred; `trusted-php` exceptional);
- migration policy;
- UI/theme/Studio/API slots;
- data purpose/fields/copies/external transfer;
- network/filesystem/secret access;
- Sentinel/Supply Chain;
- lifecycle install/enable/disable/update/rollback/uninstall;
- expected System Graph/Flow contribution and package identity;
- compatibility/security/performance/reliability/code-quality/Flow tests.

First-party status never grants private Core shortcuts.

## Payment-provider intake rule

Any package/module that authorizes, captures, refunds, stores payment-method references, receives payment-provider webhooks or affects payment-entry UI is a **critical payment-provider unit** and must follow `.ai/security/payment-security.md`.

Required additional decisions:

- `security_profile: payment-provider`;
- payment flow class (`redirect`, `hosted-iframe/fields`, approved tokenized provider SDK); direct/raw account-data collection is forbidden by default;
- account-data access must be `none` or `token-only` under standard profile;
- provider API/frontend origins;
- payment Secret Broker slots;
- purpose-specific payment capabilities;
- canonical amount/currency/order/state authority;
- idempotency/concurrency/state-machine contract;
- webhook signature/freshness/replay/tenant/schema/reconciliation contract;
- payment-page script/slot/CSP/tamper policy;
- 3DS/SCA/asynchronous state support where applicable;
- test/live credential separation;
- sandbox activation tests;
- kill-switch/secret rotation/in-flight reconciliation;
- threat model + FMEA + independent payment security review;
- graphable payment security path without raw account data;
- compliance/assessment metadata without self-awarding a generic PCI-compliant badge.

Generic package installation or Sentinel PASS is not sufficient for payment activation.

## Architecture decision triggers

ADR required for public-contract/runtime execution/tenancy/security/storage/isolation/protocol/content-routing/data-authority/performance-telemetry/**canonical System Graph schema/storage/provider model**/payment-boundary/new AI-execution changes.

A specialized graph database is not selected merely because the product is graph-shaped; measured need and an ADR are required.

## Security rule

Security is continuous. `high`/`critical` units require threat models. Performance runners, Flow deep tracing/exports, payment providers, secret/network brokers, external URLs, authenticated traces and executable packages are security-sensitive.

The Flow Center itself is sensitive reconnaissance material and requires default-deny tenant-scoped permissions, redaction, export/deep-trace audit and AI field filtering.

## AI development rule

AI can plan/implement/test but cannot self-certify critical correctness. Independent review evidence is required for high-risk architecture/security/payment/AI execution/package-runtime/System-Graph trust-boundary work. AI may not invent VOC/baselines/root cause/performance/security/reliability/outcome/Flow PASS evidence.

Flow AI tools may explain/rank/hypothesize only from authorized evidence and must preserve the distinction between `ai-inferred`, static, tested and observed paths.

## Enforcement destination

`AI-GOV-AUTOMATION-100` will machine-enforce registry/stage/plan/evidence consistency. Planned enforcement should also reject material source/package/runtime changes whose active plan omits required System Graph/Flow impact metadata.

Until implemented, `AGENTS.md`, registries, intake, active plan and review controls are mandatory procedural policy.
