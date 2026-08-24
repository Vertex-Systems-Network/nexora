# Nexora Agent Entry Point

Every AI agent, coding agent, reviewer, planner or automation working in this repository MUST begin here.

## Mandatory startup sequence

1. Read `.ai/README.md`.
2. Read `.ai/state.json`.
3. Read `.ai/handoff/current.md`.
4. Read `.ai/roadmap/stages.md` and release trains.
5. Read `.ai/governance/development-intake.md`.
6. Read `.ai/registry/development-units.json` plus relevant child registries (`performance-units.json`, `quality-payment-units.json`, future domain registries) and resolve requested work to registered unit ID(s).
7. Read `.ai/plans/master-execution-plan.md`, `.ai/plans/active.md` and the plan template.
8. For substantial new/redesigned work read `.ai/quality/engineering-lifecycle.md`, `.ai/quality/lean-six-sigma.md` and use the ResearchBrief/CTQ requirements.
9. For material data work read `.ai/data/data-flow-governance.md`.
10. Read `ARCHITECTURE.md` and `SECURITY.md` before architecture/runtime trust/tenancy/package/public API/security changes.
11. Read `.ai/security/security-program.md`; use the threat-model template for high/critical work. Payment-provider work must additionally read `.ai/security/payment-security.md` and the payment child registry.
12. Read `.ai/performance/performance-platform.md` and performance budgets for runtime-affecting work.
13. Read `.ai/reliability/reliability-program.md` for critical recurring/provider/stateful workflows.
14. Read `.ai/delivery/delivery-excellence.md` for release/CI/process work.
15. Read AI architecture/design contracts when relevant.
16. Read relevant capability matrices/addenda and system/future-system registries.
17. Inspect current Git HEAD and relevant source/tests before trusting historical completion claims.

## Mandatory pre-planning rule

**Do not start implementation for an unregistered system/module/feature/extension/app/integration/studio-pack/theme/AI tool/AI agent/migration adapter/ops capability/security control.**

If requested work is absent from the main/relevant domain registry:

1. classify it using development intake;
2. create stable development-unit ID;
3. add as `PROPOSED` or `PLANNED`;
4. map stage/release train/dependencies;
5. establish problem/research/VOC/baseline/CTQs at proportional depth;
6. plan architecture/data/permissions/security/privacy/design/API/theme/Studio/AI/performance/reliability/observability/cost/test/rollback impact;
7. create/update active plan;
8. only then implement.

AI-discovered optional work may be registered/planned but not silently implemented unless required by approved scope or explicitly promoted.

## Quality method rule

- New/materially redesigned high-impact capability: use proportional **DMADV** (`Define → Measure → Analyze → Design → Verify`).
- Existing defect/incident/regression/optimization: use **DMAIC** (`Define → Measure → Analyze → Improve → Control`).
- High/critical or complex material flows: use FMEA where applicable, in addition to security threat modeling.
- AI may not invent VOC, baselines, statistical significance, root cause or verification evidence.
- Trivial copy/style-only changes do not require heavyweight quality artifacts when behavior/contracts are unchanged.

## Data-flow rule

Material data changes declare authoritative source, classification, tenant/site/user scope, transformations, derived copies, package/API/AI exposure, retention/export/delete and recovery implications. Derived caches/search/analytics/vector stores are not silently authoritative.

## Payment security rule

Payment-provider integrations are critical and use `.ai/security/payment-security.md`.

Standard profile invariants include:

- raw PAN/CVV/track/PIN data stays out of Nexora Core/generic package runtime/logs/cache/queues/analytics/backups/AI;
- provider-hosted/tokenized flows are preferred; generic direct raw-card collection is forbidden by default;
- no generic DB/filesystem/secrets/network capability for payment packages;
- Core validates canonical order/amount/currency/financial state;
- browser return URL is not proof of payment;
- signed provider webhook/API reconciliation, idempotency and concurrency guards are mandatory;
- protected payment pages restrict scripts/slots and use payment-specific browser controls;
- payment package activation requires payment-specific sandbox/security evidence; Sentinel PASS alone is insufficient.

## Performance-by-design rule

Runtime-affecting work defines a measurable budget/test profile or `NOT_APPLICABLE` with reason. Theme/Extension/App work plans frontend/main-thread, Admin/backend, DB/cache/network/memory, package attribution and code-quality impact.

Performance/quality/security verdicts remain separate.

## Reliability rule

Critical stateful/provider workflows define timeout/retry/idempotency/failure-isolation/degradation/recovery behavior. Never blindly retry a non-idempotent financial/destructive operation after an ambiguous result; reconcile authoritative state first.

## Execution rule

Work only on the active stage in `.ai/state.json` unless the user explicitly changes priority. Do not skip stages, silently reopen completed stages, or mark target behavior complete from source/static checks.

Before substantial implementation, the active plan must contain the required research/quality/architecture/data/security/privacy/design/API/theme/Studio/AI/performance/reliability/observability/testing/verification/rollback decisions.

High/critical units require a threat model and independent review evidence as defined by governance. Payment/financial work additionally requires FMEA/payment-specific evidence.

Every meaningful pass updates `.ai/state.json`, `.ai/handoff/current.md`, the active plan and affected registry entries. Scope changes update relevant roadmap/capability/quality/data/security/performance/reliability docs.

`NEXORA_AI_PROJECT_STATE.md` remains historical evidence. `.ai/state.json` is canonical active state. Historical `N1.x` names are aliases only; use stable semantic stage/unit IDs.
