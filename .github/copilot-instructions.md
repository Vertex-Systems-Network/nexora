# Nexora Repository Instructions

Before planning, editing, reviewing, testing or certifying this repository:

1. Read `/AGENTS.md`, `/.ai/README.md`, `/.ai/state.json` and `/.ai/handoff/current.md`.
2. Resolve active stage/release train through `/.ai/roadmap/stages.md` and `/.ai/roadmap/release-trains.md`.
3. Read `/.ai/governance/development-intake.md` and resolve work to IDs in the main/relevant domain registries.
4. Read the master execution plan, active plan and plan template.
5. For substantial new/redesigned work follow `/.ai/quality/engineering-lifecycle.md`, `/.ai/quality/lean-six-sigma.md` and ResearchBrief/CTQ rules.
6. For material data work follow `/.ai/data/data-flow-governance.md`.
7. Follow `/.ai/quality/definition-of-done.md` and verification matrix.
8. Preserve `/ARCHITECTURE.md` and `/SECURITY.md` boundaries.
9. For high/critical work follow `/.ai/security/security-program.md` and threat-model policy. Payment-provider work must also follow `/.ai/security/payment-security.md`.
10. For runtime-affecting work follow performance/code-quality budgets; for critical stateful/provider work follow reliability policy.
11. For AI product/design work follow the AI architecture/design contracts.
12. Inspect current HEAD/source/tests before trusting historical completion claims.

## Mandatory planning gate

Do not implement an unregistered system/module/feature/extension/app/integration/studio-pack/theme/AI tool/agent/migration/ops/security control.

New/redesigned substantial work uses proportional Research/VOC/baseline/CTQ + DMADV; existing defect/incident/regression work uses DMAIC and closes with durable Control evidence. High/critical/complex material failure modes require FMEA where applicable in addition to threat modeling.

Material data changes require DataFlow/classification/authority/lineage/retention/delete/AI/package decisions. Runtime-affecting changes require a measurable performance budget/profile or explicit N/A. Critical stateful/provider flows require timeout/retry/idempotency/degradation/recovery decisions.

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
- generic Sentinel PASS is not payment certification.

Do not skip stages, do not treat historical `DONE` as target verification, and never fabricate research, measurement, provider, outcome or compliance evidence.

At meaningful completion update affected registries, state, handoff and active plan so the next agent needs no chat context.
