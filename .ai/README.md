# Nexora AI Control Plane

This directory is the deterministic execution control plane for AI-assisted Nexora development.

A new AI session must be able to discover what Nexora is, what exists, what is planned, why a capability is needed, which data/security/reliability boundaries apply, what development units are authorized, what stage is active, what must be measured/verified, and exactly what comes next without reconstructing project state from chat history.

## Authority and precedence

For active development state, use this order:

1. `.ai/state.json` — canonical current execution state.
2. `.ai/handoff/current.md` — human-readable current handoff.
3. `.ai/roadmap/stages.md` — canonical stable stage/dependency graph.
4. `.ai/governance/development-intake.md` — mandatory intake/pre-planning protocol.
5. `.ai/registry/development-units.json` plus domain child registries such as `performance-units.json` and `quality-payment-units.json` — authorized/pre-planned units.
6. `.ai/plans/active.md` — current executable plan.
7. `.ai/plans/plan-template.md` — mandatory planning fields.
8. `.ai/plans/master-execution-plan.md` — zero-skip/zero-hidden-work execution protocol.
9. `.ai/quality/engineering-lifecycle.md` — closed-loop Quality & Engineering Operating System.
10. `.ai/quality/lean-six-sigma.md` — proportional DMADV/DMAIC/VOC/CTQ/FMEA/control model.
11. `.ai/research/research-brief-template.md` — Research/VOC/baseline/alternatives/CTQ discovery artifact.
12. `.ai/data/data-flow-governance.md` — data flow/classification/ownership/lineage/retention/AI/package policy.
13. `.ai/security/security-program.md` — continuous security program.
14. `.ai/security/payment-security.md` — critical payment-provider security architecture.
15. `.ai/reliability/reliability-program.md` — SLI/SLO/error-budget/failure/recovery policy.
16. `.ai/performance/performance-platform.md` — performance/code-quality architecture.
17. `.ai/performance/performance-budget-template.md` — measurable performance budget template.
18. `.ai/delivery/delivery-excellence.md` — delivery-flow/stability/rework governance.
19. `.ai/roadmap/release-trains.md` — Builder Beta / Pro / Platform / Production gates.
20. `.ai/roadmap/capability-matrix.md` plus accepted addenda — capability/gap registry.
21. `.ai/roadmap/systems.md` and `.ai/roadmap/future-systems.md` — existing/future system inventory.
22. `.ai/roadmap/competitive-benchmark.md` — external platform capability benchmark.
23. `.ai/architecture/ai-platform.md` — product AI architecture contract.
24. `.ai/design/ai-design-professional.md` — AI Design Professional / Studio contract.
25. `ARCHITECTURE.md` + `SECURITY.md` — architecture/security constitution.
26. `docs/NEXORA_PLAN_STATUS.md` and `NEXORA_AI_PROJECT_STATE.md` — historical/master evidence.

Historical `N1.x` labels are never interpreted by guess; resolve them through `.ai/roadmap/legacy-aliases.md` and use stable semantic stage/unit IDs.

## Execution status vocabulary

- `SOURCE_DONE` — implementation + applicable source/static evidence satisfied.
- `TARGET_VERIFIED` — required behavior executed successfully on the real target.
- `PARTIAL` — meaningful work exists but closure is incomplete.
- `BLOCKED` — root blocker prevents next gate.
- `PLANNED` — approved, not implemented.
- `EXTERNAL` — intentionally outside Core and delivered as package.
- `DEFERRED_CERTIFICATION` — final certification postponed until product closure.

Development units additionally use `IDEA`, `PROPOSED`, `ACTIVE`, `DEFERRED`, `REJECTED`, `DEPRECATED` as defined by intake/schema.

Historical `DONE` never becomes `TARGET_VERIFIED` without real-target evidence.

## Required startup protocol

Every agent must:

1. Read `AGENTS.md` and this file.
2. Read `state.json`; compare its baseline/verified refs with current HEAD.
3. If HEAD differs, inspect the diff before trusting state/handoff claims.
4. Read active stage/prerequisites in `roadmap/stages.md`.
5. Read `governance/development-intake.md` and resolve work to main/domain registry unit IDs.
6. Read `plans/active.md`, `plans/plan-template.md` and `plans/master-execution-plan.md`.
7. For substantial new/redesigned work read `quality/engineering-lifecycle.md`, `quality/lean-six-sigma.md` and the ResearchBrief requirements.
8. For material data work read `data/data-flow-governance.md`.
9. For high/critical work read security/threat-model policy; payment work must also read `security/payment-security.md`.
10. Read performance/reliability/delivery documents where applicable.
11. Read relevant capability/system/architecture/AI/design documents.
12. Inspect current implementation/tests before trusting prose completion claims.
13. Work only on the active stage unless the user explicitly changes priority.

## Mandatory pre-planned development rule

**No new system, module, feature, extension, app, integration, studio pack, theme, AI tool/agent, migration adapter, operations capability or security control begins implementation unless it is registered and planned first.**

If missing:

1. classify it;
2. create stable ID;
3. add registry entry;
4. map stage/release train/dependencies;
5. establish problem/research/VOC/baseline/CTQs at proportional depth;
6. plan architecture/data/security/privacy/design/API/theme/Studio/AI/performance/reliability/observability/cost/testing/rollback;
7. create/update active plan;
8. only then implement.

AI-discovered optional ideas may be `PROPOSED`; they may not be silently implemented unless required by approved active scope.

## Quality-by-design rule

Nexora uses a risk-proportional Quality OS, not paperwork for its own sake.

- New or materially redesigned high-impact capabilities use **DMADV / Design for Six Sigma**: Define → Measure → Analyze → Design → Verify.
- Existing defect/incident/regression/optimization work uses **DMAIC**: Define → Measure → Analyze → Improve → Control.
- High/critical or complex material flows use FMEA where applicable in addition to security threat modeling.
- VOC, baseline and CTQs must be evidence-backed; AI must not fabricate customer research, measurements, statistical significance or root cause.
- `Implemented` is an output; completion means the intended outcome/CTQs are verified without violating architecture/data/security/performance/reliability constraints.

See `.ai/quality/engineering-lifecycle.md` and `.ai/quality/lean-six-sigma.md`.

## Data-flow rule

Material data work must identify authoritative source, classification, tenant/site ownership, access, transformations, derived stores, API/package/AI exposure, retention/export/delete propagation and recovery implications. Caches, indexes, analytics and AI/vector stores are derived systems unless an explicit architecture decision says otherwise.

Use `.ai/data/data-flow-governance.md`.

## Package planning rule

Every Extension/App/Integration/Studio Pack/Theme is planned before package creation with package family, compatibility, public contracts, capabilities, runtime mode, migrations, lifecycle, Sentinel/Supply Chain, data purpose, security, performance/code-quality/reliability budgets and rollback behavior.

First-party packages receive no private Core exemption.

## Payment-provider rule

Payment integration is a critical security/financial profile, not an ordinary extension.

Under the standard profile:

- raw PAN/CVV/track/PIN data does not enter Nexora Core, generic package runtime, DB/log/cache/queue/analytics/backup/AI context;
- provider-hosted redirect/iframe/hosted-fields or tokenized provider flows are preferred;
- generic direct/raw card collection is forbidden by default;
- payment providers receive purpose-specific capabilities, not generic DB/filesystem/secrets/network access;
- Core remains authoritative for order/amount/currency/transaction-state validation;
- browser success return is not payment proof; signed webhook/provider reconciliation is authoritative;
- payment secrets use brokered scoped references/rotation/revocation;
- protected payment pages restrict scripts/slots and use strict CSP/tamper controls;
- payment package activation requires payment-specific sandbox/security/replay/idempotency/reconciliation evidence;
- generic Sentinel PASS or marketplace rating is not payment certification.

Use `.ai/security/payment-security.md`. A payment-enabled deployment still has environment/provider-specific PCI/compliance responsibilities; architecture minimizes scope/risk but does not declare an installation automatically compliant or unhackable.

## Performance-by-design rule

Performance is not postponed to `PERF-CWV-CERT-100`. Runtime-affecting units define or explicitly mark not applicable:

- frontend/Admin/backend impact;
- query/cache/network/memory impact;
- Theme/Extension/App attribution;
- code-quality/build impact;
- performance budget/test profile/baseline;
- regression/override policy.

Layering remains:

`FRONTEND-RUNTIME-200 → PERFORMANCE-FOUNDATION-200 → CODE-QUALITY-200 → PERFORMANCE-INTELLIGENCE-200 → OBSERVABILITY-200 → PERF-CWV-CERT-100`.

## Reliability rule

Critical recurring workflows define meaningful timeout/retry/idempotency/degradation/recovery behavior and, when suitable, SLIs/SLOs/error budgets. Non-idempotent financial/destructive operations are never blindly retried after ambiguous outcomes; reconcile first.

Use `.ai/reliability/reliability-program.md`.

## Security rule

Security is continuous. Every unit receives a risk class; `high` and `critical` require threat modeling. `SECURITY-BASELINE-200` is early; `SENTINEL-200` later adds advanced package/runtime isolation/revocation.

## Required completion protocol

At every meaningful pass:

1. record what changed/verified;
2. keep source vs target evidence separate;
3. update affected registry status/evidence;
4. update `state.json`, `handoff/current.md` and `plans/active.md`;
5. update roadmap/capability/quality/data/security/performance/reliability docs if scope changed;
6. preserve historical evidence.

## Control-plane phases

### Phase 1 — imported repository truth

Imported/normalized existing documented systems/plans, stable IDs, state, handoff and DoD.

### Phase 2 — capability-gap + AI-native expansion

Added dynamic content/taxonomy/query/routing/navigation/theme/extension/site-builder/API/AI/migration/market/security expansion.

### Phase 3 — pre-planned development operating model

Added mandatory unit registry/intake, release trains, early security, release workflow, templates, privacy, agent interop, AEO, experimentation/personalization, design import, App Runtime and Managed Cloud planning.

### Phase 4 — Performance & Code Quality Intelligence

Added performance foundation, code-quality intelligence and PageSpeed/GTmetrix-class Performance Intelligence while preserving final CWV certification as a distinct gate.

### Phase 5 — Quality Engineering, Data, Reliability & Payment Security

Added:

- `RESEARCH-DISCOVERY-100`;
- `QUALITY-GOVERNANCE-100` and full Quality OS;
- DMADV for new/redesigned systems and DMAIC for existing problems;
- VOC/CTQ/SIPOC/FMEA/control-plan artifacts;
- `DATA-GOVERNANCE-200` for formal data flow/classification/lineage/retention/AI/package exposure;
- `RELIABILITY-ENGINEERING-200` with SLI/SLO/error-budget/fault/recovery policy;
- `PRODUCT-OUTCOMES-100`;
- `DELIVERY-EXCELLENCE-100`;
- `EFFICIENCY-FINOPS-100`;
- `PAYMENT-SECURITY-200` before Commerce 2.0;
- payment Data Boundary, Secret Broker, Webhook Gateway, Surface Guard and Provider SDK units;
- payment-specific PCI-scope-minimizing provider-hosted/tokenized architecture.

The canonical graph now contains **73 ordered stages/gates (`0` through `72`)**. Planning work does not bypass the active real-target blocker: current cursor remains `RUNTIME-CLOSURE-001`.
