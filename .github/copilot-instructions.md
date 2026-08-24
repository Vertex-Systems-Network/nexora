# Nexora Repository Instructions

Before planning, editing, reviewing, testing or certifying this repository:

1. Read `/AGENTS.md`, `/.ai/README.md`, `/.ai/state.json` and `/.ai/handoff/current.md`.
2. Resolve active stage/release train through `/.ai/roadmap/stages.md` and `/.ai/roadmap/release-trains.md`.
3. Read `/.ai/governance/development-intake.md` and resolve work to IDs in the main/relevant domain registries, including `flow-units.json` when applicable.
4. Read the master execution plan, active plan and plan template.
5. For substantial new/redesigned work follow `/.ai/quality/engineering-lifecycle.md`, `/.ai/quality/lean-six-sigma.md` and ResearchBrief/CTQ rules.
6. For material data work follow `/.ai/data/data-flow-governance.md`.
7. For material runtime/package/data/security/permission/event/network/state/error/deployment relationship changes follow `/.ai/flow/system-graph.md`.
8. Follow `/.ai/quality/definition-of-done.md` and verification matrix.
9. Preserve `/ARCHITECTURE.md` and `/SECURITY.md` boundaries.
10. For high/critical work follow `/.ai/security/security-program.md` and threat-model policy. Payment-provider work must also follow `/.ai/security/payment-security.md`.
11. For runtime-affecting work follow performance/code-quality budgets; for critical stateful/provider work follow reliability policy.
12. For AI product/design work follow the AI architecture/design contracts.
13. Inspect current HEAD/source/tests before trusting historical completion claims.

## Mandatory planning gate

Do not implement an unregistered system/module/feature/extension/app/integration/studio-pack/theme/AI tool/agent/migration/ops/security control.

New/redesigned substantial work uses proportional Research/VOC/baseline/CTQ + DMADV; existing defect/incident/regression work uses DMAIC and closes with durable Control evidence. High/critical/complex material failure modes require FMEA where applicable in addition to threat modeling.

Material data changes require DataFlow/classification/authority/lineage/retention/delete/AI/package decisions. Runtime-affecting changes require a measurable performance budget/profile or explicit N/A. Critical stateful/provider flows require timeout/retry/idempotency/degradation/recovery decisions.

Material relationship changes must declare expected System Graph nodes/edges/evidence, ownership/version, trust/permission/data/network/state/error/retry/deployment paths and expected-vs-observed checks or explicit `NOT_APPLICABLE`.

## System Graph / Flow Intelligence

- Graph + evidence is source of truth; diagrams are projections only.
- Keep `declared`, `static`, `observed`, `tested`, `production-observed` and `ai-inferred` evidence distinct.
- Never treat AI-generated/static paths as runtime observation.
- Never infer all-path or concurrency safety from one successful trace.
- Flow Intelligence consumes authoritative Data Governance, Security/Sentinel, Performance, Reliability, Payment, Release and Observability evidence; do not create duplicate truth stores.
- Theme/Extension/module/package work should expose stable graph identity for contracts/hooks/slots/routes/jobs/data/network/secrets/assets/errors/performance where applicable, without gaining extra privilege.
- Flow Center sensitive topology is default-deny, tenant-scoped, redacted and audited for export/deep-trace access.
- What-if/blast-radius results must distinguish modelled/potential from tested/observed effects.

## Payment security

Payment-provider integrations are critical. Under the standard profile:

- raw PAN/CVV/track/PIN data must not enter Nexora Core/generic package runtime/storage/logs/analytics/backups/AI;
- prefer provider-hosted/tokenized flows; generic raw-card collection is forbidden by default;
- payment packages use purpose-specific capabilities and brokered secrets/network access, not generic power;
- Core validates canonical order/tenant/amount/currency/financial state;
- browser return/success URL is not proof of payment;
- signed/replay-safe tenant-bound webhook/API reconciliation, idempotency and concurrency controls are mandatory;
- payment-entry pages restrict scripts/slots through payment-specific CSP/origin/tamper policy;
- payment activation requires provider sandbox tests, threat model, FMEA and independent payment-security review;
- payment Flow views must never expose account data and never become the financial authority;
- generic Sentinel PASS is not payment certification.

Do not skip stages, do not treat historical `DONE` as target verification, and never fabricate research, measurement, graph, provider, outcome or compliance evidence.

At meaningful completion update affected registries, state, handoff and active plan so the next agent needs no chat context.
