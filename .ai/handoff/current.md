# Nexora Current AI Handoff

## Resume instruction

Read in order:

1. `AGENTS.md`
2. `.ai/README.md`
3. `.ai/state.json`
4. `.ai/roadmap/stages.md` + release trains
5. `.ai/governance/development-intake.md`
6. main + relevant domain registries (`development-units.json`, `performance-units.json`, `quality-payment-units.json`)
7. `.ai/plans/master-execution-plan.md`, plan template and active plan
8. `.ai/quality/engineering-lifecycle.md` + `.ai/quality/lean-six-sigma.md`
9. ResearchBrief/DataFlow documents where relevant
10. security program; payment work also reads `.ai/security/payment-security.md`
11. performance/reliability/delivery documents where relevant
12. capability matrices/addenda + system registries
13. architecture/AI/design constitutions and current source/tests.

## Current source context

- Baseline branch: `main`
- Baseline SHA when control plane was created: `f854c50c0f7687fc87fdfab01b49562392af4ef4`
- Documented source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Control-plane branch: `ai/control-plane-phase-1`
- Control-plane revision: `5`
- Canonical stage count: `73` (`0` through `72`)

Always inspect current HEAD; baseline SHA is historical reference, not a self-referential requirement.

## Core governance rule

No implementable system/module/feature/package/AI/ops/security capability begins from chat/idea alone. It must be registered, dependency-mapped and represented in the active plan first.

Substantial new/redesigned work uses proportional Research/VOC/baseline/CTQ + DMADV. Existing defects/incidents/regressions use DMAIC and must end with durable Control evidence. High/critical/complex material failures use FMEA where applicable in addition to threat modeling.

Material data changes use formal DataFlow/classification/ownership/lineage/retention/delete/AI/package exposure policy. Critical stateful/provider flows include reliability/idempotency/recovery policy.

## Phase 5 — Quality Engineering & Payment Security

New accepted planning layers:

- `RESEARCH-DISCOVERY-100` — ResearchBrief, VOC, problem validation, market/standards evidence, baseline and CTQ inputs;
- `QUALITY-GOVERNANCE-100` — Quality OS, DMADV/DMAIC, FMEA, root cause and control plans;
- `DATA-GOVERNANCE-200` — formal data flow/classification/lineage/authoritative-vs-derived stores, retention/export/delete and AI/package boundaries;
- `RELIABILITY-ENGINEERING-200` — SLI/SLO/error budgets, timeout/retry/idempotency/failure isolation/fault/recovery;
- `PRODUCT-OUTCOMES-100` — privacy-aware CTQ/adoption/task-success/time-to-value/feedback loop;
- `DELIVERY-EXCELLENCE-100` — engineering-flow/DORA-style stability/rework evidence without developer ranking;
- `EFFICIENCY-FINOPS-100` — provider-neutral cost/resource attribution/budgets;
- `PAYMENT-SECURITY-200` — mandatory critical payment boundary before Commerce 2.0.

New documents/registries:

- `.ai/quality/engineering-lifecycle.md`
- `.ai/quality/lean-six-sigma.md`
- `.ai/quality/fmea-template.md`
- `.ai/research/research-brief-template.md`
- `.ai/data/data-flow-governance.md`
- `.ai/reliability/reliability-program.md`
- `.ai/delivery/delivery-excellence.md`
- `.ai/security/payment-security.md`
- `.ai/registry/quality-payment-units.json`
- `.ai/roadmap/capability-matrix-phase5-quality-payments.md`

These are planning/control-plane changes only; they do not claim runtime/product implementation.

## Existing payment foundation preserved

N0.30 already established a good provider-neutral base:

- Core owns commerce records/state but embeds no gateway vendor;
- provider packages implement `PaymentProviderContract`/registry adapter;
- Commerce migration does not own gateway secret/private-key fields;
- amounts use integer minor units;
- provider enablement requires registered adapter + health check;
- idempotency/provider-event identity exist;
- refund concurrency uses locking/cumulative validation.

`PAYMENT-SECURITY-200` matures this foundation rather than replacing it.

## Payment security invariant

Under the standard Nexora payment profile:

**raw PAN/CVV/track/PIN data must not enter Nexora Core, generic extension runtime, application DB/log/cache/queue/analytics/search/backups/observability/AI context.**

Preferred flows: provider-hosted redirect → provider-hosted iframe/fields → approved tokenized SDK. Generic direct/raw account-data collection is forbidden by default and cannot be enabled by a manifest checkbox.

Payment providers receive `security_profile: payment-provider`, purpose-specific payment capabilities and payment-specific activation evidence. Generic DB/filesystem/secrets/network powers are not the model.

Core validates order/tenant/amount/currency/state. Browser `success` return is not proof of payment. Signed/fresh/replay-safe provider webhook/API reconciliation is authoritative. Ambiguous non-idempotent captures/refunds reconcile before retry.

Protected payment surfaces restrict arbitrary Theme/Extension/custom scripts, enforce payment-specific CSP/origins/script inventory/tamper controls and exclude payment fields from session replay/analytics.

Payment activation requires threat model + FMEA + independent payment review + provider sandbox authorization/capture/refund/webhook/reconciliation tests. Sentinel PASS alone is not payment certification and Nexora must not self-award a generic PCI-compliant badge.

## Active stage

`RUNTIME-CLOSURE-001 — Installation + Runtime Closure`

Registered active unit: `SYS-RUNTIME-IDENTITY`

Status: `BLOCKED` pending real-target execution.

Phase 5 planning does not move this cursor.

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

`CORE-QA-001 → AI-GOV-AUTOMATION-100 → RESEARCH-DISCOVERY-100 → QUALITY-GOVERNANCE-100 → ADMIN-UX-CLOSURE-001 → SECURITY-BASELINE-200 → ARCH-BOUNDARY-100 → existing website-platform closure → mature builder/data/performance kernel`.

Later Platform sequence explicitly requires `COMMERCE-CLOSURE-001 → PAYMENT-SECURITY-200 → COMMERCE-200`.

## Completion warning

Historical `DONE` is not target proof. Missing research/measurement/provider/outcome evidence is never inferred as PASS. Payment compliance scope depends on the deployed merchant/provider environment; Nexora architecture minimizes scope/risk but does not claim universal compliance or unhackability.
