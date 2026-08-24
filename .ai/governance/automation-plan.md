# AI-GOV-AUTOMATION-100 — Machine Enforcement Plan

This pre-plans the validator that converts `.ai` governance from procedural policy into CI-enforced repository controls. No validator implementation is claimed here.

## Objective

Fail CI on clear control-plane violations while warning rather than inventing certainty when static analysis cannot infer product intent.

## 1. Schema / registry validation

Validate:

- `.ai/state.json` vs state schema;
- main/domain registries vs registry/unit schemas;
- unique stable unit IDs across all registries;
- future ResearchBrief/DataFlow/FMEA/plan/evidence schemas as they become machine-readable.

Domain registries are one logical unit namespace; duplicate IDs across files fail.

## 2. Stage/dependency/state consistency

Reject unknown/duplicate stage IDs, unknown parent stages/dependencies, train mismatch, invalid active-unit/current-stage mapping, invalid current→next-stage relation and untracked semantic-ID changes.

Historical numeric ordering may change when prerequisites are inserted; stable semantic IDs do not silently change.

## 3. Active-plan consistency

For substantive source/runtime/package changes require:

- current stage + active unit IDs;
- required sections from plan template;
- research/problem/CTQ section or explicit proportional N/A;
- architecture/DataFlow/security/privacy sections;
- threat-model evidence for high/critical;
- FMEA evidence for high/critical/complex failure modes where applicable;
- performance budget/profile or explicit N/A for runtime-affecting work;
- reliability/idempotency/recovery for critical stateful/provider work;
- acceptance/verification/rollback/control plan.

## 4. New-work detection

Signals requiring registered active scope include:

- new Core/module/Theme/Extension/App/Integration/Studio package code;
- new public contract/API/AI tool;
- new migration/data store;
- new permission/runtime capability;
- new outbound network/secret access;
- new sensitive/financial data;
- new payment-provider/payment-entry/webhook behavior;
- new performance/reliability/ops execution surface.

Clear omission fails. Ambiguous mapping emits actionable warning rather than fake semantic certainty.

## 5. Research / Quality gates

For new/materially redesigned high-impact units verify planning metadata indicates `DMADV` and contains ResearchBrief/problem/VOC/baseline/CTQs or explicit reviewed exception.

For defect/incident/regression improvement verify `DMAIC`/Control evidence at completion where the unit is classified that way.

High/critical units with `quality.fmea_required=true` cannot reach `SOURCE_DONE` without FMEA reference/evidence.

AI-generated documents cannot self-mark unknown VOC/baseline/outcome values as measured PASS.

## 6. Data governance gates

When unit/data metadata indicates material data impact, require:

- DataFlow reference/decision;
- authoritative source;
- classification;
- tenant/data ownership;
- migration/derived-store semantics;
- retention/export/delete policy;
- package/API/AI exposure decision.

New sensitive/financial/payment/AI data without classification should fail.

## 7. Threat/security gates

For high/critical:

- `threat_model_required=true`;
- threat-model reference before completion;
- required security evidence not omitted.

Stronger rule sets apply to auth/tenancy/package runtime/secrets/network/destructive/AI/payment units.

## 8. Payment-provider hard gates

Detect payment behavior from registered profile/contracts/capabilities/routes/manifests and require a unit with `security_profile: payment-provider` mapped to `PAYMENT-SECURITY-200` or an explicitly allowed parent closure stage during existing-foundation verification.

For standard payment-provider profile reject/planning-fail:

- `account_data_access` outside `none|token-only`;
- undeclared provider API/frontend origins;
- generic database/filesystem/secret/network capabilities where purpose-specific payment broker contract is required;
- missing provider sandbox verification;
- missing threat model/FMEA/independent-review marker;
- direct browser-return payment-settlement logic without authoritative provider verification;
- source patterns/fields attempting to persist raw PAN/CVV/track/PIN data in Nexora schema/config/log fixtures;
- payment-entry package custom script/slot without approved payment-surface declaration;
- payment-provider completion relying only on generic Sentinel status.

The validator cannot prove PCI compliance and must never emit such a claim.

## 9. Performance / reliability gates

Runtime-affecting units require performance applicability/budget/profile metadata or explicit N/A.

Critical recurring/provider/stateful units require reliability applicability plus timeout/retry/idempotency/degradation/recovery decisions.

Financial/destructive operations missing ambiguous-outcome reconciliation policy should fail relevant plan validation.

## 10. Completion/evidence gates

Before `SOURCE_DONE` require applicable acceptance, architecture/data/security/FMEA/test/performance/reliability/control evidence and synchronized state/handoff/docs.

Before `TARGET_VERIFIED` require real target/provider evidence. Source-only checks cannot satisfy target/provider gates.

Post-release product outcomes may remain pending/observation-required; validator must not require fabricated future metrics just to close source work.

## 11. Historical alias protection

Reject new execution metadata using ambiguous legacy `N1.x` instead of stable semantic IDs. Historical docs remain untouched evidence.

## 12. PR/CI flow

```text
checkout
→ AI governance validator
→ research/quality/data/payment plan checks
→ architecture/security/source guards
→ dependency/supply-chain checks
→ tests/build
→ performance/reliability checks
→ integration/browser/provider/target workflows where applicable
```

Errors name violated rule, file/unit/stage and remediation.

## Planned artifacts

Likely:

- `scripts/ai-governance-check.php` or integration into existing certification scripts;
- valid/invalid fixtures for all registries/quality/payment rules;
- required `ai-governance` CI job;
- generated non-authoritative active stage/unit/risk/evidence report.

No second state database is introduced.

## Required failure fixtures

At minimum:

- duplicate unit across registries;
- unknown stage/dependency;
- active unit wrong stage;
- stale active plan;
- new package/migration/API/AI tool without unit;
- high-risk without threat model;
- required FMEA missing;
- new material data without classification/DataFlow;
- runtime feature without performance decision;
- critical provider workflow without idempotency/recovery;
- payment provider without payment profile;
- payment profile declaring raw account-data access;
- payment package generic secret/network power;
- payment provider without sandbox/threat/FMEA evidence;
- browser-return-as-paid source fixture;
- raw PAN/CVV-like schema/config/log field fixture;
- `TARGET_VERIFIED` without target/provider evidence;
- bare ambiguous historical milestone as canonical ID;
- broken schema/state/handoff sync.

## Non-goals

Validator does not prove code correctness, architecture quality, security, PCI compliance, user value or production readiness. It ensures required planning/evidence cannot be silently omitted.

## Exit condition

`AI-GOV-AUTOMATION-100` completes when tested CI enforcement rejects representative invalid governance/quality/data/payment states, gives actionable diagnostics and reads the existing `.ai` sources without creating competing truth.
