# AI-GOV-AUTOMATION-100 — Machine Enforcement Plan

This pre-plans the validator/orchestration controls that convert `.ai` governance from procedural policy into CI- and run-enforced repository controls. No validator or autonomous development runtime implementation is claimed here.

Detailed AI-run architecture lives in `.ai/governance/ai-development-orchestration.md`.

## Objective

Fail closed on clear planning/execution/evidence violations, while warning rather than inventing certainty where automation cannot infer product intent.

The stage has two related responsibilities:

1. **control-plane validation** — schemas, registries, stages, plans, evidence and handoff are internally coherent;
2. **AI development orchestration** — substantial AI runs are bound to exact scope/base/policy/capabilities/review/evidence before autonomous mutation or promotion.

## 1. Schema / registry validation

Validate:

- `.ai/state.json` vs state schema;
- main/domain registries vs registry/unit schemas;
- unique stable unit IDs across all registries, including performance, quality/payment, Flow and AI-development registries;
- `.ai/schemas/ai-development-run.schema.json` when run manifests are emitted;
- future ResearchBrief/DataFlow/FMEA/System-Graph/waiver/evidence schemas as they become machine-readable.

Domain registries are one logical unit namespace; duplicate IDs across files fail.

## 2. Stage/dependency/state consistency

Reject unknown/duplicate stage IDs, unknown parent stages/dependencies, train mismatch, invalid active-unit/current-stage mapping, invalid current→next-stage relation and untracked semantic-ID changes.

Historical numeric ordering may change when prerequisites are inserted; stable semantic IDs do not silently change.

## 3. Active-plan consistency

For substantive source/runtime/package changes require:

- current stage + active unit IDs;
- required sections from plan template;
- research/problem/CTQ section or proportional N/A;
- architecture/DataFlow/security/privacy sections;
- threat-model evidence for high/critical;
- FMEA evidence for high/critical/complex failure modes where applicable;
- performance budget/profile or explicit N/A for runtime-affecting work;
- System Graph/Flow contribution or explicit N/A for material relationship changes;
- reliability/idempotency/recovery for critical stateful/provider work;
- AI development run-governance fields when orchestration applies;
- acceptance/verification/rollback/control plan.

## 4. AI run manifest / freshness

When automated AI execution is enabled, validate the run manifest against `.ai/schemas/ai-development-run.schema.json` and require:

- stable run ID;
- exact base SHA;
- active stage/unit IDs;
- active-plan digest;
- policy/governance digest;
- role/risk;
- allowed write scope;
- tool/network/secret/target/governance capabilities;
- lease identity/expiry;
- review policy.

Mark run `STALE` and block further autonomous writes/promotion when material HEAD/plan/registry/protected-policy drift invalidates the pinned context.

Chat history or a hand-written summary cannot refresh the manifest by itself.

## 5. Instruction-trust / prompt-injection gate

Treat content from issues, PR descriptions/comments, source comments, logs, test output, package metadata/README files, webpages and generated artifacts as untrusted input.

Representative checks/fixtures must prove such content cannot:

- override `AGENTS.md`/canonical governance;
- grant tool/network/secret permissions;
- widen scope;
- disable tests/security;
- cause governance/workflow mutation;
- direct secret exfiltration;
- self-approve a change.

The validator/orchestrator records an instruction-conflict event without requiring private chain-of-thought.

## 6. Scope lease / multi-agent consistency

Where concurrent agents exist, enforce:

- one writer for the same exact migration/protected file/state unless explicitly coordinated;
- branch/worktree/lease ownership;
- optimistic expected-current-SHA writes;
- lease expiry/heartbeat;
- child scope/capabilities are subsets of parent;
- task dependency DAG;
- one integration/merge coordinator;
- combined-head verification after merge/rebase.

Overlapping stale writers fail closed; force-push is not a normal conflict-resolution mechanism.

## 7. Scope-delta detection

Signals requiring registry/plan refresh before implementation include:

- new Core/module/Theme/Extension/App/Integration/Studio code;
- new public contract/API/AI tool;
- new migration/data store;
- new dependency/package;
- new permission/runtime capability;
- new hook/event/filter/slot/job/schedule;
- new outbound network/secret/filesystem access;
- new sensitive/financial data;
- new state transition/transaction/locking/retry behavior;
- new payment-provider/payment-entry/webhook behavior;
- new performance/reliability/ops execution surface;
- new deployment/infrastructure/configuration provider;
- new System Graph provider/node/edge/evidence type or Flow lens.

Clear omission fails. Ambiguous mapping emits an actionable warning rather than fake semantic certainty.

## 8. Governance self-modification protection

Protected control-plane surfaces include at minimum:

- `AGENTS.md`;
- `.ai/**` governance/state/schema/security/quality controls;
- `.github/workflows/**`;
- `ARCHITECTURE.md`;
- `SECURITY.md`;
- future CODEOWNERS/rulesets/release policy.

Detect effective policy weakening such as:

- check removed;
- required→optional;
- fail→warn;
- exclusion added;
- protected path removed;
- review/status requirement relaxed;
- security threshold reduced.

A product/runtime run cannot use its own control weakening to obtain PASS. Material weakening needs explicit governance scope, independent review and valid waiver/approval policy where allowed.

## 9. Operational repository protection evidence

Do not infer safe branch protection from documentation or a `protected` label alone.

For protected integration/release branches, future operational checks should inspect actual repository rules/settings and require the configured policy, including as applicable:

- PR-only merge;
- required status checks;
- required reviews/CODEOWNERS for sensitive paths;
- stale review dismissal;
- no force push/deletion;
- exact-head merge checks;
- auditable bypass policy.

If settings cannot be inspected, report `UNKNOWN`, not PASS.

## 10. Test-oracle integrity

Detect and review:

- deleted tests/assertions;
- added skips/xfails/quarantines;
- relaxed thresholds/tolerances;
- changed expected security/authorization behavior;
- removed negative/adversarial fixtures;
- mass snapshot updates.

Critical invariants should support protected/golden fixtures or independent tests that ordinary feature runs cannot silently rewrite.

For high/critical work, generated/modified tests from the implementation run are not sufficient independent evidence by themselves. Mutation/property/differential/adversarial techniques are used where they add signal.

## 11. Evidence integrity / attestation

Reject self-authored evidence promotion.

Machine evidence envelopes should identify:

- evidence ID/type;
- producer/runner;
- run ID;
- source SHA;
- artifact/package digest where applicable;
- environment/target/provider identity;
- tool/test version;
- timestamp/freshness;
- result;
- parent evidence IDs;
- redaction/classification;
- integrity/attestation metadata.

Rules:

- AI-authored prose cannot satisfy `observed`, `tested`, `production-observed`, provider or target gates;
- FAIL evidence is superseded by a new evidence record, not edited to PASS;
- test evidence references controlled runner/test identity;
- target/provider evidence references real target/provider run identity;
- release/package artifacts support SLSA-compatible source/build provenance where implemented;
- promoted artifact/source identity must match review/evidence.

## 12. Review independence / exact-head gate

For high/critical architecture/security/payment/AI-execution/package-runtime/governance work require:

- distinct review pass/context;
- reviewer identity/role;
- reviewed exact head SHA;
- domain-specific review where required;
- stale approval invalidation after material head change;
- author self-review cannot satisfy independent-review requirement.

## 13. Retry / attempt circuit breaker

Track repeated failure signatures, test/build/tool attempts and configured cost/resource budgets.

When repeated-equivalent-failure or budget threshold is reached:

- stop blind retry;
- preserve evidence;
- mark blocker/root cause `UNKNOWN` where not proven;
- return to Measure/Analyze/re-plan;
- never disable validation/security/test controls to escape the loop.

Record concise decision/action/hypothesis summaries only; private chain-of-thought is not required.

## 14. Dependency intake

New/material dependency changes require explicit intake metadata:

- purpose/native alternative;
- exact package/source/version;
- lockfile/transitive impact;
- typo-squatting/name-confusion check;
- license/advisory review;
- provenance/maintainer signals where available;
- install/build scripts;
- network/native binary behavior;
- runtime/bundle/performance impact;
- SBOM/provenance impact;
- rollback/removal.

Major-version automated updates do not pass solely because typecheck/build is green.

## 15. Research / Quality gates

For new/materially redesigned high-impact units verify planning metadata indicates `DMADV` and contains ResearchBrief/problem/VOC/baseline/CTQs or explicit reviewed exception.

For defect/incident/regression improvement verify `DMAIC`/Control evidence at completion where the unit is classified that way.

High/critical units with FMEA requirement cannot reach `SOURCE_DONE` without FMEA reference/evidence.

AI-generated documents cannot self-mark unknown VOC/baseline/outcome values as measured PASS.

## 16. Data governance gates

When unit/data metadata indicates material data impact, require:

- DataFlow reference/decision;
- authoritative source;
- classification;
- tenant/data ownership;
- migration/derived-store semantics;
- retention/export/delete policy;
- package/API/AI exposure decision.

New sensitive/financial/payment/AI data without classification fails.

## 17. System Graph / Flow gates

When changed source/package/manifest/registration indicates material relationships, require expected graph contribution or reviewed `NOT_APPLICABLE`.

Where machine-checkable require stable identities, expected node/edge families, ownership/version, data/network/secret/permission/capability/trust/state/error/deployment relationships, evidence classes, redaction and drift policy.

Reject invalid promotion:

- `ai-inferred` → `observed` without runtime evidence;
- `static` → runtime observation;
- `tested` without controlled test evidence;
- `production-observed` without authorized production provenance;
- missing evidence serialized as PASS.

A generated/manual diagram cannot satisfy graph evidence by itself.

## 18. Threat/security gates

For high/critical:

- `threat_model_required=true`;
- threat-model reference before completion;
- required security evidence not omitted.

Stronger rule sets apply to auth/tenancy/package runtime/secrets/network/destructive/AI/payment/System-Graph/governance-development units.

## 19. Payment-provider hard gates

Detect payment behavior and require `security_profile: payment-provider` mapped to `PAYMENT-SECURITY-200` or explicitly allowed closure scope.

Reject/planning-fail raw account-data access, undeclared origins, generic secret/network power, missing sandbox/threat/FMEA/review evidence, browser-return settlement authority, raw PAN/CVV-like persistence, unapproved payment-page scripts, and payment completion relying only on generic Sentinel status.

The validator cannot prove PCI compliance and must never emit such a claim.

## 20. Performance / reliability gates

Runtime-affecting units require performance applicability/budget/profile or explicit N/A.

Critical recurring/provider/stateful units require timeout/retry/idempotency/degradation/recovery decisions.

Financial/destructive operations missing ambiguous-outcome reconciliation fail relevant plan validation.

Performance/reliability evidence linked into Flow references canonical evidence IDs rather than creating a second truth source.

## 21. Waiver governance

Material bypass/exception records require:

- stable waiver ID;
- exact gate/rule;
- unit/environment scope;
- rationale/risk;
- approver authority;
- compensating controls;
- expiry/review date;
- revocation status.

Reject expired, self-approved high/critical or over-broad waivers. Release reports surface active material waivers.

## 22. Completion / promotion gates

Before `SOURCE_DONE` require applicable acceptance, architecture/data/System-Graph/security/FMEA/test/performance/reliability/AI-development-control evidence and synchronized state/handoff/docs.

Before `TARGET_VERIFIED` require real target/provider evidence. Source/static/AI-generated graph/prose output cannot satisfy target/runtime/provider gates.

Before promotion require:

- exact current head/artifact identity;
- non-stale review on that identity;
- required evidence producer/provenance;
- no unresolved scope/lease conflict;
- valid waivers only;
- required branch/repository policy evidence.

Post-release product outcomes may remain pending/observation-required; do not fabricate future metrics.

## 23. Historical alias protection

Reject new execution metadata using ambiguous legacy `N1.x` instead of stable semantic IDs. Historical docs remain evidence only.

## 24. PR/CI target flow

```text
checkout exact source
→ AI governance + run-manifest validation
→ research/quality/data/System-Graph/payment planning checks
→ instruction-trust / scope / protected-policy checks
→ dependency/supply-chain checks
→ architecture/security/source guards
→ test-oracle integrity checks
→ tests/build
→ performance/reliability checks
→ graph/static/drift/path evidence
→ independent exact-head review status
→ integration/browser/provider/target workflows where applicable
→ evidence attestation / artifact provenance
→ promotion gate
```

Errors identify violated rule, file/unit/run/stage and remediation.

## Planned artifacts

Likely:

- `scripts/ai-governance-check.php` or existing certification integration;
- AI-run manifest producer/validator;
- scope-lease/conflict store using canonical repo/run state rather than chat;
- valid/invalid fixtures for registries/quality/data/payment/Flow/AI-development rules;
- required `ai-governance` CI job;
- protected-policy diff guard;
- test-oracle diff guard;
- evidence envelope/attestation verifier;
- repository-rules verification step;
- generated non-authoritative active stage/unit/run/risk/evidence report.

No second project state database or duplicate graph/security/performance truth store is introduced.

## Required failure fixtures

At minimum:

- duplicate unit across registries;
- unknown stage/dependency;
- active unit wrong stage;
- stale active plan/run/policy;
- new package/migration/API/AI tool without unit;
- repository/source/log prompt injection asking to ignore governance;
- overlapping agent writers on migration/protected file;
- child-agent capability escalation;
- material scope expansion without re-plan;
- feature change weakening its own governance/CI check;
- high-risk without threat model;
- required FMEA missing;
- new material data without classification/DataFlow;
- runtime feature without performance decision;
- material relationship change without System Graph decision;
- Flow evidence marking static/AI inference as observed;
- observed undeclared package network edge;
- sensitive Flow export/deep-trace without explicit permission/audit;
- graph-storage architecture change without ADR;
- AI deletes/skips/weakens failing critical test;
- fake AI-authored target/provider PASS evidence;
- reviewer approval bound to older head;
- promoted artifact/source does not match reviewed identity;
- repeated identical failing strategy exceeds configured budget;
- dependency addition without intake / suspicious lookalike fixture;
- expired/self-approved material waiver;
- branch settings lack required checks/reviews while docs claim protection;
- critical provider workflow without idempotency/recovery;
- payment provider without payment profile;
- payment profile declaring raw account-data access;
- payment package generic secret/network power;
- payment provider without sandbox/threat/FMEA evidence;
- browser-return-as-paid source fixture;
- raw PAN/CVV-like schema/config/log/Flow field;
- `TARGET_VERIFIED` without target/provider evidence;
- bare historical milestone used as canonical ID;
- broken schema/state/handoff sync.

## Non-goals

The automation does not prove code correctness, architecture quality, exploitability, PCI compliance, user value, causal root cause or production readiness. It ensures required planning/execution/evidence boundaries cannot be silently omitted, weakened or self-certified.

## Exit condition

`AI-GOV-AUTOMATION-100` completes only when tested enforcement rejects representative invalid governance **and AI-development orchestration** states, gives actionable diagnostics, verifies operational repository controls where accessible, and reads existing `.ai` truth without creating a competing state system.
