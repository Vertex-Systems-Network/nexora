# Nexora Current AI Handoff

## Resume instruction

Read in order:

1. `AGENTS.md`
2. `.ai/README.md`
3. `.ai/state.json`
4. `.ai/roadmap/stages.md` + release trains
5. `.ai/governance/development-intake.md`
6. main + relevant domain registries (`development-units.json`, `performance-units.json`, `quality-payment-units.json`, `flow-units.json`)
7. `.ai/plans/master-execution-plan.md`, plan template and active plan
8. `.ai/quality/engineering-lifecycle.md` + `.ai/quality/lean-six-sigma.md`
9. ResearchBrief/DataFlow documents where relevant
10. `.ai/flow/system-graph.md` for material runtime/package/data/security/permission/event/network/state/error/deployment relationships
11. security program; payment work also reads `.ai/security/payment-security.md`
12. performance/reliability/delivery documents where relevant
13. capability matrices/addenda + system registries
14. architecture/AI/design constitutions and current source/tests.

## Current source context

- Baseline branch: `main`
- Baseline SHA when control plane was created: `f854c50c0f7687fc87fdfab01b49562392af4ef4`
- Documented source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Control-plane branch: `ai/control-plane-phase-1`
- Control-plane revision: `6`
- Canonical stage count: `75` (`0` through `74`)

Always inspect current HEAD; baseline SHA is historical reference, not a self-referential requirement.

## Core governance rule

No implementable system/module/feature/package/AI/ops/security capability begins from chat/idea alone. It must be registered, dependency-mapped and represented in the active plan first.

Substantial new/redesigned work uses proportional Research/VOC/baseline/CTQ + DMADV. Existing defects/incidents/regressions use DMAIC and must end with durable Control evidence. High/critical/complex material failures use FMEA where applicable in addition to threat modeling.

Material data changes use formal DataFlow/classification/ownership/lineage/retention/delete/AI/package exposure policy. Critical stateful/provider flows include reliability/idempotency/recovery policy.

Material relationship changes now also require System Graph/Flow planning: expected nodes/edges, ownership/version, sensitive visibility, evidence provider/class and drift checks or explicit `NOT_APPLICABLE`.

## Phase 6 — System Graph & Flow Intelligence

Accepted canonical stages:

- `SYSTEM-GRAPH-100` — Builder Beta foundation after architecture/data/Extension SDK/performance/code-quality foundations.
- `FLOW-INTELLIGENCE-200` — Pro layer after Performance Intelligence + Reliability + API + AI Kernel.

### Fundamental invariant

**Graph + evidence is source of truth. Diagram is a projection of that truth.**

Evidence classes:

- `declared`
- `static`
- `observed`
- `tested`
- `production-observed`
- `ai-inferred`

`ai-inferred`/static evidence is never promoted to runtime truth. One runtime trace never proves all possible paths or concurrency safety.

### New planning artifacts

- `.ai/flow/system-graph.md`
- `.ai/registry/flow-units.json`
- `.ai/roadmap/capability-matrix-phase6-flow-intelligence.md`

The Flow registry pre-plans canonical graph, declared/static/runtime/data/security/permission/error/state/package/basic UI providers, advanced Flow Intelligence, deployment/supply-chain/test-evidence/diff-impact/replay-incident/scenario capabilities, and governed AI Flow explain/root-cause/security-path/impact tools.

### System Graph target

Canonical provider-neutral typed nodes/edges/evidence should represent, where applicable:

- actors/routes/middleware/controllers/services/contracts/registries;
- Theme/templates/components/assets;
- Extension/App/Integration/Studio/module/package/version identity;
- hooks/events/filters/slots/jobs/queues/schedules;
- data/DB/cache/search/files/derived stores;
- permissions/capabilities/approvals;
- secrets/brokers/network/external providers;
- conditions/gateways/state transitions;
- transactions/locks/idempotency/concurrency/retry/reconciliation;
- errors/fallbacks/recovery;
- deployment/config/feature flags;
- supply-chain/ownership/test/SLO/incident/release identity;
- AI/payment flows.

### Flow Center target

Do not expose one giant unreadable graph. Use progressive zoom:

`Ecosystem → System → Feature → Execution`

with grouped lenses for Architecture, Runtime, Data, Security, Quality, Operations and Packages. Sub-lenses include code, permissions, errors, events, queues, network, DB/cache, state/transactions/retries, deployment/configuration, supply chain, tests, performance/reliability/cost, payments, AI, release and incident/change-impact.

Flowchart notation is a projection aid: oval start/end/event, rectangle process, diamond condition/gateway, cylinder store, component/package shapes, external provider/cloud, queue, shield/key and trust/deployment/transaction boundaries. Do not rely on color alone.

A decision node explains the human-readable question, source/policy, inputs, true/false meaning, permission/state implications and tests rather than showing only a diamond.

### Advanced intelligence

Planned features include:

- expected-vs-static/observed architecture/package drift;
- source-to-sink/trust-boundary paths tied to analyzer/runtime evidence;
- root vs propagated/secondary/recovered errors;
- path-aware test/evidence coverage;
- graph diff/history/time travel across release/package/branch/environment;
- read-only runtime visual replay;
- change impact/blast radius with potential vs tested/observed distinction;
- incident/containment/recovery views;
- modelled what-if scenarios labelled predicted until tested;
- Flow AI explanations grounded only in authorized graph evidence.

### Security / overhead

Flow topology is sensitive reconnaissance material. Planned controls include default-deny `flow.*` permissions, tenant/site scope, redaction, export/deep-trace audit/re-auth policy and AI field filtering.

Production tracing is sampled/bounded with retention/cardinality limits; deep traces are on-demand. Collector/profiler overhead is itself measured.

Do not select a graph database simply because the product is graph-shaped. Use provider-neutral storage contracts; specialized graph storage requires measured need + ADR.

### Authority separation

Flow Intelligence does not replace authoritative systems:

- Data Governance owns data policy/lineage semantics;
- Security/Sentinel owns security enforcement/findings;
- Performance owns performance evidence;
- Reliability owns SLO/recovery rules;
- Payment Security owns payment controls;
- Release Workflow owns release state;
- Observability owns broad operations telemetry.

The System Graph references/correlates their evidence; the Flow GUI queries/projects/explains it.

## Phase 5 — Quality Engineering & Payment Security remains in force

Accepted layers remain `RESEARCH-DISCOVERY-100`, `QUALITY-GOVERNANCE-100`, `DATA-GOVERNANCE-200`, `RELIABILITY-ENGINEERING-200`, `PRODUCT-OUTCOMES-100`, `DELIVERY-EXCELLENCE-100`, `EFFICIENCY-FINOPS-100` and `PAYMENT-SECURITY-200`.

Payment standard profile continues to forbid raw PAN/CVV/track/PIN in Nexora/generic package runtime/storage/logs/AI, prefers provider-hosted/tokenized flows, requires purpose-specific capabilities, Core-authoritative financial state, Secret/Network Brokers, hardened webhooks/payment surface, idempotency/reconciliation, provider sandbox and independent payment security evidence. Payment state/security can be projected into Flow Intelligence only using safe/redacted evidence; the graph is never financial authority.

## Active stage

`RUNTIME-CLOSURE-001 — Installation + Runtime Closure`

Registered active unit: `SYS-RUNTIME-IDENTITY`

Status: `BLOCKED` pending real-target execution.

Phase 6 planning does not move this cursor.

### Current target blocker

Installed rc.93 has stale post-install identity planes:

- environment
- activation
- service
- process

Do **not** overwrite rc.93 with rc.94 merely to repair these fingerprints; repair and upgrade remain distinct.

### Exact next actions

1. Run prepared rc.93 Post-Install Identity Repair Pack against `D:\laragon\www\nexora`.
2. `php artisan nexora:runtime:compatibility-status --deep`
3. Require `status=pass`, `mismatches=[]`, `compatible=true`, `mode=installed-data-plane`.
4. `php artisan nexora:runtime:post-install-status --assert-ready`
5. If both pass, open `/login` and advance to `CORE-QA-001`.

## Immediate sequence after runtime closure

`CORE-QA-001 → AI-GOV-AUTOMATION-100 → RESEARCH-DISCOVERY-100 → QUALITY-GOVERNANCE-100 → ADMIN-UX-CLOSURE-001 → SECURITY-BASELINE-200 → ARCH-BOUNDARY-100 → existing website-platform closure → mature builder/data/performance kernel → SYSTEM-GRAPH-100`.

Later Pro includes `PERFORMANCE-INTELLIGENCE-200 → RELIABILITY-ENGINEERING-200 → FLOW-INTELLIGENCE-200` when their other dependencies are satisfied.

Later Platform sequence explicitly requires `COMMERCE-CLOSURE-001 → PAYMENT-SECURITY-200 → COMMERCE-200`.

## Completion warning

Historical `DONE` is not target proof. Missing research/measurement/graph/provider/outcome evidence is never inferred as PASS. A beautiful/AI-generated flow diagram is not runtime evidence. Payment compliance scope depends on the deployed merchant/provider environment; Nexora architecture minimizes scope/risk but does not claim universal compliance or unhackability.
