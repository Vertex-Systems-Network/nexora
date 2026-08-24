# AI-GOV-AUTOMATION-100 — Machine Enforcement Plan

This pre-plans the validator that converts `.ai` governance from procedural policy into CI-enforced repository controls. No validator implementation is claimed here.

## Objective

Fail CI on clear control-plane violations while warning rather than inventing certainty when static analysis cannot infer product intent.

## 1. Schema / registry validation

Validate:

- `.ai/state.json` vs state schema;
- main/domain registries vs registry/unit schemas;
- unique stable unit IDs across all registries, including `.ai/registry/flow-units.json`;
- future ResearchBrief/DataFlow/FMEA/System-Graph/plan/evidence schemas as they become machine-readable.

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
- **System Graph/Flow contribution or explicit N/A for material relationship changes**;
- reliability/idempotency/recovery for critical stateful/provider work;
- acceptance/verification/rollback/control plan.

## 4. New-work detection

Signals requiring registered active scope include:

- new Core/module/Theme/Extension/App/Integration/Studio package code;
- new public contract/API/AI tool;
- new migration/data store;
- new permission/runtime capability;
- new hook/event/filter/slot/job/schedule;
- new outbound network/secret access;
- new sensitive/financial data;
- new state transition/transaction/locking/retry behavior;
- new payment-provider/payment-entry/webhook behavior;
- new performance/reliability/ops execution surface;
- new deployment/infrastructure/configuration provider;
- new System Graph provider/node/edge/evidence type or Flow lens.

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

## 7. System Graph / Flow gates

When changed source/package/manifest/registration indicates material relationships, require the active plan to declare expected graph contribution or reviewed `NOT_APPLICABLE`.

Machine-checkable requirements should include where possible:

- stable graph identities for newly registered packages/contracts/routes/hooks/events/jobs/tools;
- expected node/edge families;
- ownership/package/version identity;
- data/network/secret/permission/capability/trust-boundary impact;
- state/transaction/retry/error/deployment relationships where applicable;
- evidence provider/class expectation;
- sensitive graph access/redaction requirement;
- test/evidence coverage expectation;
- expected-vs-observed/static drift policy.

Reject/flag invalid evidence promotion:

- `ai-inferred` presented as `observed` without runtime evidence;
- `static` presented as runtime observation;
- `tested` without referenced controlled test evidence;
- `production-observed` without authorized production telemetry provenance;
- missing evidence silently serialized as PASS.

Required fixtures should also prove:

- a generated/manual diagram cannot satisfy graph evidence by itself;
- undeclared observed package network/capability edge is surfaced;
- sensitive graph export/deep-trace permissions are explicit;
- graph provider/collector changes cannot silently introduce unrestricted sensitive telemetry;
- a specialized graph database cannot be introduced without an ADR/approved storage change.

The governance validator does not decide vulnerability/exploitability from graph adjacency. Security findings remain owned by security/analyzer/runtime evidence.

## 8. Threat/security gates

For high/critical:

- `threat_model_required=true`;
- threat-model reference before completion;
- required security evidence not omitted.

Stronger rule sets apply to auth/tenancy/package runtime/secrets/network/destructive/AI/payment/System-Graph sensitive-topology units.

## 9. Payment-provider hard gates

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
- payment-provider completion relying only on generic Sentinel status;
- payment Flow evidence that includes forbidden raw account data or treats the Flow graph as financial authority.

The validator cannot prove PCI compliance and must never emit such a claim.

## 10. Performance / reliability gates

Runtime-affecting units require performance applicability/budget/profile metadata or explicit N/A.

Critical recurring/provider/stateful units require reliability applicability plus timeout/retry/idempotency/degradation/recovery decisions.

Financial/destructive operations missing ambiguous-outcome reconciliation policy should fail relevant plan validation.

Performance/reliability evidence linked into Flow must reference canonical evidence IDs/provider identity rather than create a second truth source.

## 11. Completion/evidence gates

Before `SOURCE_DONE` require applicable acceptance, architecture/data/System-Graph/security/FMEA/test/performance/reliability/control evidence and synchronized state/handoff/docs.

Before `TARGET_VERIFIED` require real target/provider evidence. Source/static/AI-generated graph output cannot satisfy target/runtime/provider gates.

Post-release product outcomes may remain pending/observation-required; validator must not require fabricated future metrics just to close source work.

## 12. Historical alias protection

Reject new execution metadata using ambiguous legacy `N1.x` instead of stable semantic IDs. Historical docs remain untouched evidence.

## 13. PR/CI flow

```text
checkout
→ AI governance validator
→ research/quality/data/System-Graph/payment plan checks
→ architecture/security/source guards
→ dependency/supply-chain checks
→ tests/build
→ performance/reliability checks
→ graph/static/drift/path-evidence checks where applicable
→ integration/browser/provider/target workflows where applicable
```

Errors name violated rule, file/unit/stage and remediation.

## Planned artifacts

Likely:

- `scripts/ai-governance-check.php` or integration into existing certification scripts;
- valid/invalid fixtures for registries/quality/data/payment/Flow rules;
- required `ai-governance` CI job;
- optional System Graph schema/provider consistency check;
- generated non-authoritative active stage/unit/risk/evidence report.

No second state database or second graph/security/performance truth store is introduced.

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
- material runtime/package relationship change without System Graph decision;
- Flow evidence marking static/AI inference as observed;
- observed undeclared package network edge fixture;
- sensitive Flow export/deep-trace without explicit permission/audit policy;
- graph-storage architecture change without ADR;
- critical provider workflow without idempotency/recovery;
- payment provider without payment profile;
- payment profile declaring raw account-data access;
- payment package generic secret/network power;
- payment provider without sandbox/threat/FMEA evidence;
- browser-return-as-paid source fixture;
- raw PAN/CVV-like schema/config/log/Flow field fixture;
- `TARGET_VERIFIED` without target/provider evidence;
- bare ambiguous historical milestone as canonical ID;
- broken schema/state/handoff sync.

## Non-goals

Validator does not prove code correctness, architecture quality, security, exploitability, PCI compliance, user value, causal root cause or production readiness. It ensures required planning/evidence cannot be silently omitted or mislabeled.

## Exit condition

`AI-GOV-AUTOMATION-100` completes when tested CI enforcement rejects representative invalid governance/quality/data/System-Graph/payment states, gives actionable diagnostics and reads the existing `.ai` sources without creating competing truth.
