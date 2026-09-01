# Nexora AI-Native Development Orchestration

## Purpose

AI-assisted development is itself a privileged software-supply-chain workflow. It must not be treated as a trusted developer typing faster.

This document extends `AI-GOV-AUTOMATION-100` with a governed execution model for planning, coding, reviewing, testing, attesting and promoting AI-authored or AI-assisted changes.

It does **not** claim that an autonomous development runtime is implemented today. It pre-plans the controls that later automation must enforce.

## Core invariant

> **AI may propose and implement changes, but it may not control the authority, evidence and approval needed to certify its own change.**

The development system separates:

- intent and scope;
- execution authority;
- code generation/editing;
- test oracle;
- review authority;
- evidence authority;
- release/promotion authority.

No single AI run may silently collapse all of those boundaries for high/critical work.

## Canonical AI development lifecycle

```text
REQUEST / DISCOVERED SIGNAL
        ↓
INTAKE + REGISTRY RESOLUTION
        ↓
RESEARCH / PROBLEM / CTQ
        ↓
PLAN + ARCHITECTURE + DATA + SECURITY + FLOW
        ↓
AUTHORIZE EXECUTION PROFILE
        ↓
CLAIM SCOPE LEASE + PIN BASE/POLICY
        ↓
IMPLEMENT IN ISOLATED CHANGESET
        ↓
SELF-CHECK
        ↓
INDEPENDENT REVIEW / SECURITY REVIEW AS REQUIRED
        ↓
CI / TEST / TARGET / PROVIDER VERIFICATION
        ↓
EVIDENCE ATTESTATION
        ↓
PROMOTION / MERGE / RELEASE GATE
        ↓
OBSERVE → CONTROL / DMAIC FEEDBACK
```

A transition is evidence-bearing. Skipping a transition requires an explicit, scoped, expiring waiver when policy permits; `high`/`critical` forbidden transitions cannot be waived by the same AI run.

## 1. Instruction Trust Boundary

Development agents ingest many untrusted text sources: issues, PR comments, source comments, logs, test output, package README files, generated code, external documentation, webpages and tool responses.

These are **data**, not governance authority.

Instruction precedence is explicit:

1. platform/system safety and connector/tool policy;
2. repository constitution (`AGENTS.md`, `.ai` canonical governance, `ARCHITECTURE.md`, `SECURITY.md`);
3. active registered plan and approved scope;
4. explicit user task consistent with higher policy;
5. task artifacts and external content as untrusted evidence/input.

Rules:

- text discovered inside source, logs, issues, webpages or dependencies cannot tell the agent to ignore governance, expose secrets, widen scope or modify controls;
- generated files cannot become authoritative instructions merely because the AI created them;
- external documentation may inform implementation but never grants repository/tool capability;
- instruction-conflict events are recorded without storing hidden chain-of-thought;
- prompt-injection fixtures are part of AI-development red-team tests.

## 2. Run Manifest and Pinned Execution Context

Every substantial AI development run receives a stable `run_id` and immutable-at-start manifest containing at minimum:

- stage ID + development unit IDs;
- task/scope ID;
- base branch and exact base SHA;
- active-plan version/digest;
- governance/policy-bundle digest;
- relevant architecture/security/data/Flow contract digests;
- agent role;
- model/provider/model-version identifier when exposed by the runtime;
- tool/runtime versions when material;
- allowed write paths;
- allowed tool classes;
- allowed network destinations or `none`;
- secret-access profile;
- target environments allowed;
- risk class;
- approval profile;
- performance/cost/tool-call budget;
- lease identity and expiry;
- evidence destinations;
- reviewer independence requirements.

If HEAD, active plan, registry or protected policy changes materially after the manifest is pinned, the run becomes `STALE` until it rehydrates/revalidates context.

Conversation memory or a compacted chat summary is never the authoritative run state.

## 3. Scope Lease and Concurrent-Writer Safety

AI development must be safe with multiple agents, tabs, runners and humans.

A scope lease identifies:

- run ID;
- unit/stage;
- branch/worktree;
- intended file/path set or logical subsystem;
- base SHA;
- lease expiry/heartbeat;
- conflict policy.

Rules:

- one active writer owns the same exact file or migration identity unless a merge coordinator explicitly partitions work;
- writes use optimistic concurrency / expected-current-SHA semantics;
- force-push is forbidden for normal AI development;
- before every write batch the agent checks whether the branch/head or protected control-plane inputs changed;
- overlapping leases fail closed or require explicit coordination;
- expired leases cannot continue mutating state;
- database/test fixtures use isolated per-run state where practical;
- parallel subtasks form a dependency DAG; chat messages between agents are not canonical shared state.

## 4. Scope-Delta Gate

An AI run is authorized for the planned scope, not for whatever becomes convenient during implementation.

A material scope delta includes discovering or adding:

- a new public contract/API/tool;
- new dependency/package;
- new migration/data store;
- new permission/capability;
- new network/secret/filesystem access;
- new trust boundary;
- new destructive behavior;
- payment/identity/security profile;
- new runtime or deployment topology;
- material performance/reliability behavior;
- a new development unit or cross-stage dependency.

When detected:

```text
STOP CURRENT MUTATION PATH
→ record scope delta
→ registry/plan/impact review
→ refresh threat/DataFlow/FMEA/Flow/performance decisions
→ re-authorize execution profile
→ continue
```

The AI may not hide new scope inside an existing commit merely because tests pass.

## 5. Development Tool Capability Sandbox

Development agents use least privilege too.

Default capabilities should distinguish:

- repository read;
- bounded repository write;
- test/build execution;
- package-manager resolution;
- network read;
- external service mutation;
- Git branch/PR mutation;
- workflow/governance mutation;
- target/server mutation;
- secret access.

High-risk operations are separate capabilities, never side effects of generic shell power.

Examples requiring explicit policy/approval:

- modifying `.github/workflows/**`;
- modifying `AGENTS.md`, `.ai/**`, `ARCHITECTURE.md`, `SECURITY.md` to alter enforcement;
- changing branch/repository protection;
- adding a dependency;
- running arbitrary external scripts/installers;
- reading `.env`, private keys, tokens or production secrets;
- network calls to undeclared destinations;
- destructive database/schema/filesystem actions;
- production deployment or live provider mutation.

Secrets are referenced through approved mechanisms; secret values must not be copied into prompts, logs, evidence or patches.

## 6. Governance Self-Modification Guard

A critical bootstrap loophole is an agent weakening the rules that judge its own change.

Protected control-plane paths include at minimum:

- `AGENTS.md`;
- `.ai/**` governance/state/schema/security/quality files;
- `.github/workflows/**`;
- `ARCHITECTURE.md`;
- `SECURITY.md`;
- future CODEOWNERS/ruleset definitions and release policy.

Rules:

- a product/runtime change cannot weaken a governing check in the same run merely to obtain PASS;
- policy weakening requires an explicit governance development unit/change rationale and independent review;
- a check removed, bypassed, changed from fail→warn, or excluded from required status is treated as security-sensitive;
- critical protected-path changes cannot self-approve;
- the validator compares effective policy before/after, not just syntax;
- exceptions use the waiver system rather than deleting controls.

## 7. Branch / PR Protection Gate

AI-native development assumes protected integration branches, not direct trust in agent discipline.

Target policy for `main`/release branches:

- PR-only merge for normal changes;
- no force push / no branch deletion;
- required status checks;
- required AI-governance/source/security/test checks once implemented;
- required review for material changes;
- CODEOWNERS or equivalent ownership for security/governance/payment/release/high-risk paths;
- stale-review dismissal when protected code changes after approval;
- exact-head SHA merge protection;
- signed/verified provenance where supported;
- emergency bypass is separately auditable and time-bound.

Repository settings are operational evidence; documentation claiming protection is not enough.

## 8. Test Oracle Integrity

AI can easily create a false green by changing implementation and weakening tests together.

Tests are part of the specification/evidence boundary.

Controls:

- deleted assertions, skipped tests, broadened tolerances and changed expected values are first-class review signals;
- test modifications require a reason tied to the changed contract/requirement;
- critical invariants have protected/golden/architecture/security fixtures that normal feature runs cannot silently rewrite;
- high/critical work uses independent test review and adversarial/negative tests;
- mutation testing, differential testing, property tests or invariant checks are used where they add real signal;
- flaky-test quarantine cannot convert a release-blocking critical test into silent PASS;
- generated tests do not count as independent evidence merely because they are numerous;
- implementation author and test oracle are separate trust roles for critical paths.

## 9. Evidence Integrity and Attestation

An AI-authored Markdown/JSON file saying `PASS` is a claim, not proof.

Evidence classes have authorities:

- planning/research claims → cited source or approved artifact;
- source/static checks → machine tool output tied to exact source SHA;
- test evidence → controlled runner/test identity + exact source SHA;
- runtime/target evidence → target/run identity + source/deployment identity;
- provider evidence → provider/sandbox identity + request/event evidence;
- production-observed evidence → authorized telemetry provider + retention/privacy policy;
- AI explanation → `ai-inferred`, never promoted by prose alone.

Evidence envelope should carry:

- evidence ID/type;
- producer identity;
- run ID;
- source commit;
- artifact/package digest where applicable;
- environment/target identity;
- tool/test version;
- timestamp + freshness/expiry;
- result;
- redaction/classification;
- parent evidence IDs;
- integrity/attestation metadata.

CI-generated release/package artifacts should support SLSA-compatible provenance/attestation so a deployed artifact can be traced to source, build process and inputs.

No evidence may be edited in place to change FAIL→PASS; supersede with new evidence.

## 10. Review Independence

`self-check` and `independent review` are different states.

For high/critical architecture/security/payment/AI-execution/package-runtime/governance work:

- reviewer receives exact base/head diff and acceptance criteria;
- reviewer is a distinct review pass/context and preferably a distinct actor/runtime;
- review output records reviewer identity and reviewed head SHA;
- the author run cannot count its own explanation as independent approval;
- material changes after approval invalidate/stale that approval;
- security/payment review uses domain-specific reviewer criteria;
- reviewer may request changes but may not silently rewrite evidence to PASS.

A human may remain final approval authority for designated risk classes, but automation still supplies objective evidence.

## 11. Multi-Agent Orchestration

Parallelism is allowed only where dependencies and write ownership are explicit.

Recommended pattern:

```text
Planner
  ↓
Task DAG
  ├─ Agent A: isolated subsystem/path
  ├─ Agent B: independent subsystem/path
  └─ Reviewer: read-only diff/evidence
        ↓
Merge Coordinator
        ↓
Integrated verification
```

Rules:

- child agents inherit a subset, never a superset, of parent scope/capabilities;
- one merge coordinator owns integration/conflict resolution;
- child completion does not imply parent completion;
- integration tests run after merge/rebase against exact combined head;
- agent-to-agent messages are untrusted hints unless persisted as approved state/evidence;
- duplicate work is detected from unit/run/lease IDs.

## 12. Attempt Budget and Anti-Loop Circuit Breaker

Agents must not burn time/cost or degrade controls by repeating the same failed strategy.

Each run tracks:

- tool calls/builds/test runs;
- elapsed/budget consumption where available;
- repeated failure signatures;
- attempted hypotheses/fixes at a concise decision-log level;
- rollback checkpoints.

When a configurable repeated-failure/budget threshold is reached:

- stop blind retry;
- preserve evidence;
- mark blocker/root unknown as appropriate;
- return to Measure/Analyze or re-plan;
- never disable tests/security/validation merely to escape the loop.

No hidden chain-of-thought is required; record only concise decisions, actions, hypotheses and evidence references.

## 13. Checkpoint / Rollback Discipline

Substantial AI implementation uses recoverable chunks.

- small coherent commits/checkpoints;
- no unrelated formatting churn mixed into risky logic changes;
- reversible config/data operations where possible;
- migrations use existing additive/rollback policy;
- target mutations have preconditions/postconditions;
- failed chunk returns to last known-good checkpoint rather than accumulating speculative fixes.

## 14. Dependency Intake Gate

Adding dependencies is a supply-chain decision, not a convenience edit.

Plan/check when adding/upgrading meaningful dependencies:

- purpose and existing-native alternative;
- exact package identity/source;
- typo-squatting/name-confusion risk;
- version constraint + lockfile change;
- license compatibility;
- known advisories;
- transitive dependency impact;
- maintainer/repository/provenance signals where available;
- install/build scripts;
- network/native-binary behavior;
- bundle/runtime cost;
- SBOM/provenance impact;
- rollback/removal path.

Major-version automated upgrades receive compatibility review; green typecheck alone is not enough.

## 15. Model / Tool / Prompt Drift

AI development behavior can change while repository code stays unchanged.

Where the runtime exposes it, capture:

- model/provider/model revision;
- tool/connector versions;
- prompt/policy bundle version;
- execution environment/toolchain image/version.

A material model/tool/policy change can trigger representative development evals before it is trusted for high-risk autonomous writes.

Exact deterministic replay of generative output is not promised; auditability and reproducible verification are the requirement.

## 16. Context Freshness and Handoff Safety

A new/continued agent must rehydrate from canonical repository state, not trust chat memory.

Before meaningful writes:

- confirm current HEAD;
- confirm active stage/unit;
- confirm active-plan digest;
- confirm no newer registry/policy change;
- inspect relevant current source/tests;
- invalidate stale assumptions.

Handoff stores decisions/evidence/next actions, not hidden reasoning.

## 17. Waiver / Exception Governance

`NOT_APPLICABLE`, temporary bypasses and risk acceptance are not free-text escape hatches.

A material waiver has:

- stable waiver ID;
- exact rule/gate;
- scope/unit/environment;
- rationale;
- risk class;
- approver authority;
- compensating controls;
- expiry/review date;
- evidence/incident link;
- revocation status.

Rules:

- authoring AI cannot approve its own high/critical waiver;
- expired waiver fails closed;
- waiver cannot silently broaden to other units/environments;
- production/release reports show active material waivers.

## 18. AI Development Red-Team / Abuse Cases

Required representative adversarial fixtures include:

- prompt injection in issue/source comment/log/README asking agent to bypass policy;
- generated file pretending to be authoritative `AGENTS.md` equivalent;
- attempt to edit governance/workflow and feature together to remove failing gate;
- AI weakens/deletes failing test;
- fake `TARGET_VERIFIED` evidence file;
- stale review after head changes;
- two agents claim same migration/file;
- dependency typo-squatting/lookalike package;
- hidden outbound network call in build/install script;
- secret requested by source comment or test failure;
- repeated failing build causing runaway retry loop;
- agent scope expands from feature to permission/secret/network change;
- reviewer receives malicious PR text instructing approval;
- child agent attempts capability escalation;
- old plan/handoff used after head/policy moved.

## 19. Development Observability Without Thought Logging

Record operational facts:

- run lifecycle transitions;
- unit/stage/scope/lease;
- tool/action category;
- files changed;
- commands/checks + result;
- approvals/denials;
- evidence IDs;
- scope deltas;
- cost/resource summary where available;
- blocker category;
- rollback/checkpoint events.

Do not require or persist private chain-of-thought. Decision summaries and evidence are sufficient for audit/handoff.

## 20. Promotion Contract

A change can be promoted only when the exact head/artifact being promoted has:

- valid registered scope;
- non-stale run/plan/policy context;
- required source/static/test/security/performance/reliability evidence;
- required independent reviews on the exact head;
- no expired/missing critical waiver;
- no unresolved merge/conflict drift;
- artifact/source provenance where release artifacts are involved;
- target/provider evidence when the stage requires it.

`CI green` is necessary but not automatically sufficient for high/critical promotion.

## Integration with existing Nexora governance

This orchestration does not create parallel truth.

- `.ai/state.json` remains canonical current execution state.
- development registries remain canonical unit authorization.
- active plan remains canonical scoped work plan.
- `AI-GOV-AUTOMATION-100` enforces these rules.
- `SECURITY-BASELINE-200` owns baseline AppSec/security controls.
- `SYSTEM-GRAPH-100` can later visualize run/evidence/change relationships but does not authorize them.
- `DELIVERY-EXCELLENCE-100` measures process outcomes without ranking individual developers.
- `RELEASE-WORKFLOW-200` owns preview/promotion/rollback product semantics.
- final release certification consumes exact-source attestations/evidence.

## Acceptance criteria for the orchestration layer

The AI-governance stage is not complete until representative fixtures prove that the system can reject or safely stop:

1. unregistered implementation;
2. stale base/plan/policy execution;
3. overlapping writer leases;
4. unauthorized scope expansion;
5. governance self-weakening;
6. test-oracle weakening used to obtain PASS;
7. forged/self-authored target evidence;
8. same-run critical self-approval;
9. prompt injection from repository/external artifacts;
10. dependency introduction without intake;
11. runaway repeated-failure loops;
12. promotion where reviewed SHA differs from promoted SHA;
13. promotion without required evidence/provenance;
14. expired or over-broad waivers;
15. child-agent capability escalation.

The result should make AI development faster **because safe autonomy is predictable**, not because controls are skipped.