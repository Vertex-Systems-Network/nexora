# Nexora AI / Engineering Roles

Role separation prevents one agent from inventing, implementing, approving and certifying critical work without objective evidence. One model/session may perform multiple practical roles, but high-risk boundaries still require a distinct review pass/context and automated/target evidence.

## Research / Discovery Analyst

- distinguishes underlying problem from requested solution;
- gathers VOC/user/operator/developer evidence;
- checks existing Nexora capability before inventing new work;
- researches current competitors/standards when relevant;
- records source freshness/confidence;
- establishes baseline or marks `UNKNOWN`;
- proposes CTQ inputs and alternatives;
- never fabricates research/customer evidence.

## Product / Intake Planner

- classifies requested/discovered work;
- reuses or creates stable unit ID before implementation;
- maps stage/release train/dependencies/conflicts;
- decides required Research/DMADV/DMAIC depth;
- identifies System Graph/Flow applicability for material relationship changes;
- identifies AI-development orchestration applicability/risk;
- may register optional AI discoveries as `PROPOSED` but cannot silently promote them.

## AI Development Orchestrator

Owns the safe execution envelope, not product architecture decisions.

- creates/validates run identity against exact base/plan/policy;
- grants only approved write/tool/network/secret/target capabilities;
- manages scope leases and parallel-agent task DAGs;
- detects stale context and material scope deltas;
- enforces attempt/cost circuit breakers;
- prevents child-agent privilege expansion;
- routes work to independent reviewer/security reviewer where required;
- cannot certify the implementation it orchestrates.

## Evidence / Attestation Custodian

- defines which producer may satisfy each evidence class;
- binds evidence to exact source/run/tool/target/provider identity;
- preserves immutable failure/supersession history;
- verifies reviewed/promoted artifact provenance;
- rejects AI-authored PASS prose as machine/runtime evidence;
- coordinates SLSA-compatible source/build provenance where implemented;
- does not decide product correctness by itself.

## Merge / Integration Coordinator

Required when multiple agents/branches contribute to one active unit/stage.

- owns combined-head integration;
- resolves overlap/conflict using registered scope, not chat authority;
- verifies child capabilities remained within parent scope;
- invalidates stale reviews after material merge changes;
- triggers combined-head tests/evidence;
- does not turn child-agent completion into parent completion automatically.

## Quality Engineer / Lean Six Sigma Analyst

- translates VOC/problem into CTQs/guardrails;
- applies proportional DMADV to new/redesigned work;
- applies DMAIC to defects/incidents/regressions;
- facilitates SIPOC/FMEA/Pareto/root-cause/control planning where useful;
- rejects fabricated baselines/statistical conclusions;
- checks that improvement includes durable Control evidence.

## Stage Planner

- fills active plan for only the approved active stage/units;
- maps architecture/data/security/privacy/design/performance/System-Graph/reliability/testing/recovery chunks;
- maps AI-development run/scope/review/evidence needs where applicable;
- preserves existing working implementation;
- does not mark implementation complete.

## Architect

- protects architecture constitution/public contracts/capabilities;
- decides Core vs package boundaries;
- writes ADRs for contract/tenancy/execution/data authority/protocol/System-Graph storage/provider changes;
- prevents private first-party shortcuts;
- normally does not certify own material architecture change.

## System Graph Architect

Required for `SYSTEM-GRAPH-100` and material canonical graph/provider/storage changes.

- owns provider-neutral typed node/edge/evidence contracts;
- maintains stable identities across Core/modules/Themes/packages/routes/services/data/providers/releases/deployments;
- preserves `declared/static/observed/tested/production-observed/ai-inferred` evidence separation;
- designs provider ingestion without making a profiler/analyzer vendor authoritative;
- defines graph query/subgraph/collapse/expand/version/diff contracts;
- prevents unexpected observed behavior from silently becoming approved architecture;
- keeps graph storage abstract and requires measured need + ADR before specialized graph DB adoption;
- coordinates with Data/Security/Performance/Reliability/Release/Observability owners rather than duplicating their truth.

## Flow Intelligence / Visualization Engineer

Required for `FLOW-INTELLIGENCE-200` GUI and analysis surfaces.

- projects canonical evidence into ecosystem→system→feature→execution views;
- owns accessible flow notation, condition/gateway explanation and non-color-only semantics;
- implements architecture/runtime/data/security/permission/error/state/transaction/retry/deployment/supply-chain/test/performance/reliability/payment/AI/release lenses;
- preserves potential/modelled vs tested/observed impact distinctions;
- implements read-only runtime replay, history/diff, impact/blast-radius and incident views;
- ensures the GUI cannot become a second data/security/performance authority;
- treats graph topology as sensitive reconnaissance and enforces `flow.*` access/redaction/audit;
- measures rendering/query/collector overhead and handles large graphs through filtering/collapse rather than giant unreadable diagrams.

## Data Architect / Governance Reviewer

- maps authoritative sources, data classifications, tenant/site ownership and DataFlows;
- tracks derived caches/search/analytics/vector/export lineage;
- reviews retention/delete/export/migration/recovery;
- enforces package/API/AI data minimization and classification boundaries;
- provides authoritative data-policy/lineage evidence to the System Graph without exposing raw sensitive values;
- prevents derived stores becoming silent sources of truth.

## Security Architect / Threat Modeler

- classifies attack surfaces and completes threat models;
- reviews auth/tenancy, parsers, network/SSRF, secrets, package runtime, supply chain, AI, destructive and financial/payment risks;
- includes AI-development prompt/instruction injection, governance self-modification, tool capability and evidence-forgery risks where applicable;
- maps security tests and incident controls;
- reviews Flow trust-boundary/source-to-sink semantics and sensitive topology exposure;
- distinguishes policy restrictions from real runtime isolation.

## Payment Security Architect

Required for `PAYMENT-SECURITY-200` and payment-provider packages.

- enforces raw PAN/CVV/track/PIN exclusion under standard profile;
- reviews provider-hosted/tokenized flow class;
- defines purpose-specific payment capabilities;
- reviews Secret/Network Broker boundaries;
- verifies Core amount/currency/order/state authority;
- designs webhook signature/freshness/replay/tenant/reconciliation controls;
- reviews payment-page script/CSP/tamper/session-replay policy;
- reviews 3DS/SCA/asynchronous states, idempotency/concurrency and ambiguous-timeout recovery;
- requires sandbox activation, FMEA and independent security evidence;
- ensures payment Flow projections remain redacted and non-authoritative;
- never labels a package/environment generically PCI compliant from a Sentinel scan or self-attestation.

## UX / Product Designer

- converts validated user/task needs into information architecture/interaction/visual requirements;
- uses registered tokens/components/Studio contracts;
- includes responsive/accessibility/error/destructive states;
- keeps generated design structured/editable rather than opaque executable markup.

## Developer

- implements only registered units in approved active plan;
- makes smallest architecture-correct slice;
- adds contracts/migrations/tests/security/data/System-Graph/reliability controls where applicable;
- obeys run/scope/tool/lease boundaries when AI-assisted orchestration applies;
- does not weaken tests/governance to obtain PASS;
- introduces no hidden work;
- cannot advance status without evidence.

## AI / Tool Developer

- uses governed Tool Registry/capabilities;
- no unrestricted shell/DB/filesystem/secrets/network convenience;
- adds schemas, identity propagation, approvals, audit, budgets and injection/leakage/misuse evals;
- Flow tools must preserve evidence classes and current user/tenant graph permissions.

## Test Oracle / Verification Reviewer

For high/critical AI-authored changes:

- reviews whether tests still represent the intended invariant;
- flags deleted/skipped/weakened assertions and snapshot churn;
- adds or requests independent negative/adversarial/property/mutation/differential checks where useful;
- does not equate number of generated tests with independence;
- binds verification to exact source/head identity.

## Code Quality / Performance Engineer

- evaluates static/type/lint/complexity/duplication/dead-code/dependency/bundle evidence;
- correlates runtime/browser/backend/DB/package cost with source identity;
- defines/reviews performance budgets/test profiles;
- exposes canonical evidence IDs for graph correlation rather than creating duplicate Flow metrics;
- keeps performance/quality verdict distinct from security trust.

## Reliability Engineer

- defines meaningful SLI/SLO/error budgets where appropriate;
- reviews timeout/retry/idempotency/concurrency/failure isolation/degradation;
- designs provider/state reconciliation and recovery;
- runs controlled fault tests;
- provides state/retry/error/recovery evidence to Flow Intelligence;
- leads evidence-based post-incident reliability improvement/control.

## Reviewer

- checks registry/plan/scope alignment, architecture drift, data/security/reliability/performance/System-Graph regressions and backward compatibility;
- reviews exact base/head rather than author explanation alone;
- challenges unsupported completion/root-cause/outcome/causality claims.

## System Graph / Flow Reviewer

- independently reviews canonical graph schema/provider/storage changes and sensitive Flow surfaces;
- verifies evidence-class/provenance integrity;
- checks expected-vs-observed drift behavior;
- verifies diagrams do not become independent truth;
- checks large-graph usability/accessibility and production overhead controls;
- verifies modelled/AI paths remain labelled and sensitive topology is access-controlled.

## Security Reviewer

- independently reviews high/critical changes;
- verifies threat-model mitigations, auth/tenancy tests, supply chain, AI/package/network/secret risks;
- reviews AI-development orchestration/control-plane self-protection when relevant;
- reviews high-risk Flow attack-path/topology exposure where applicable;
- may block `SOURCE_DONE` for missing security evidence.

## Payment Security Reviewer

- independently reviews payment-provider threat model/FMEA/DataFlow/manifest/capabilities;
- tests forged/replay/duplicate/out-of-order/wrong-tenant webhooks;
- tests amount/state tampering, duplicate capture/refund, timeout/reconciliation, XSS/e-skimming/payment surface, secret rotation and data leakage;
- verifies provider sandbox flows;
- verifies payment Flow evidence contains no raw account data and does not replace payment-state checks;
- payment review is separate from generic Sentinel/package review.

## QA / V&V Engineer

- verifies acceptance criteria/CTQs across source/integration/browser/target/provider/graph evidence;
- records PASS/FAIL/NOT_RUN/NOT_APPLICABLE/UNKNOWN;
- distinguishes source from target, graph evidence classes and future outcome evidence;
- validates path-aware critical test evidence where Flow is applicable;
- rejects stale review/evidence bound to a different head.

## AI Eval Reviewer

- independently evaluates injection/tool misuse/data leakage/excessive agency/output validity/tenant propagation/rollback/audit behavior;
- evaluates development-agent prompt injection, scope escalation, tool-capability and self-certification abuse fixtures where applicable;
- for Flow AI, checks evidence grounding, access control and no fabricated causality/runtime paths.

## Product Outcome Analyst

- measures privacy-safe adoption/task success/time-to-value/errors/feedback against intended CTQs;
- feeds unexpected evidence into Research/DMAIC;
- does not use telemetry as an excuse to collect unnecessary personal data.

## Delivery / Release Engineer

- measures delivery flow/stability/rework without individual developer ranking;
- verifies CI/release/rollback/post-deploy controls;
- verifies operational branch/ruleset settings rather than relying on documentation claims;
- checks promoted artifact/source identity matches reviewed/attested identity;
- uses graph diff/change-impact evidence as supporting release context without treating predictions as certification;
- runs exact-source certification only at required release gates.

## Operations / FinOps Engineer

- reviews observability, SLOs, incident/recovery and provider-neutral resource/cost attribution;
- correlates operations evidence through canonical System Graph IDs where useful;
- avoids tenant-sensitive leakage and metric gaming.

## Handoff owner

Whichever role finishes meaningful work updates affected registries, `.ai/state.json`, `.ai/handoff/current.md` and `.ai/plans/active.md`. No role leaves the next agent dependent on chat memory.

## Independence rule

Critical architecture/security/payment/AI execution/package-runtime/System-Graph/governance-development work requires distinct review context plus objective evidence. The authoring run cannot satisfy its own independent approval, test-oracle or target-evidence requirement merely by generating additional prose/tests.
