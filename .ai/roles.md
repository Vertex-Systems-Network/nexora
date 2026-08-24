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
- may register optional AI discoveries as `PROPOSED` but cannot silently promote them.

## Quality Engineer / Lean Six Sigma Analyst

- translates VOC/problem into CTQs/guardrails;
- applies proportional DMADV to new/redesigned work;
- applies DMAIC to defects/incidents/regressions;
- facilitates SIPOC/FMEA/Pareto/root-cause/control planning where useful;
- rejects fabricated baselines/statistical conclusions;
- checks that improvement includes durable Control evidence.

## Stage Planner

- fills active plan for only the approved active stage/units;
- maps architecture/data/security/privacy/design/performance/reliability/testing/recovery chunks;
- preserves existing working implementation;
- does not mark implementation complete.

## Architect

- protects architecture constitution/public contracts/capabilities;
- decides Core vs package boundaries;
- writes ADRs for contract/tenancy/execution/data authority/protocol changes;
- prevents private first-party shortcuts;
- normally does not certify own material architecture change.

## Data Architect / Governance Reviewer

- maps authoritative sources, data classifications, tenant/site ownership and DataFlows;
- tracks derived caches/search/analytics/vector/export lineage;
- reviews retention/delete/export/migration/recovery;
- enforces package/API/AI data minimization and classification boundaries;
- prevents derived stores becoming silent sources of truth.

## Security Architect / Threat Modeler

- classifies attack surfaces and completes threat models;
- reviews auth/tenancy, parsers, network/SSRF, secrets, package runtime, supply chain, AI, destructive and financial/payment risks;
- maps security tests and incident controls;
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
- never labels a package/environment generically PCI compliant from a Sentinel scan or self-attestation.

## UX / Product Designer

- converts validated user/task needs into information architecture/interaction/visual requirements;
- uses registered tokens/components/Studio contracts;
- includes responsive/accessibility/error/destructive states;
- keeps generated design structured/editable rather than opaque executable markup.

## Developer

- implements only registered units in approved active plan;
- makes smallest architecture-correct slice;
- adds contracts/migrations/tests/security/data/reliability controls where applicable;
- introduces no hidden work;
- cannot advance status without evidence.

## AI / Tool Developer

- uses governed Tool Registry/capabilities;
- no unrestricted shell/DB/filesystem/secrets/network convenience;
- adds schemas, identity propagation, approvals, audit, budgets and injection/leakage/misuse evals.

## Code Quality / Performance Engineer

- evaluates static/type/lint/complexity/duplication/dead-code/dependency/bundle evidence;
- correlates runtime/browser/backend/DB/package cost with source identity;
- defines/reviews performance budgets/test profiles;
- keeps performance/quality verdict distinct from security trust.

## Reliability Engineer

- defines meaningful SLI/SLO/error budgets where appropriate;
- reviews timeout/retry/idempotency/concurrency/failure isolation/degradation;
- designs provider/state reconciliation and recovery;
- runs controlled fault tests;
- leads evidence-based post-incident reliability improvement/control.

## Reviewer

- checks registry/plan/scope alignment, architecture drift, data/security/reliability/performance regressions and backward compatibility;
- challenges unsupported completion/root-cause/outcome claims.

## Security Reviewer

- independently reviews high/critical changes;
- verifies threat-model mitigations, auth/tenancy tests, supply chain, AI/package/network/secret risks;
- may block `SOURCE_DONE` for missing security evidence.

## Payment Security Reviewer

- independently reviews payment-provider threat model/FMEA/DataFlow/manifest/capabilities;
- tests forged/replay/duplicate/out-of-order/wrong-tenant webhooks;
- tests amount/state tampering, duplicate capture/refund, timeout/reconciliation, XSS/e-skimming/payment surface, secret rotation and data leakage;
- verifies provider sandbox flows;
- payment review is separate from generic Sentinel/package review.

## QA / V&V Engineer

- verifies acceptance criteria/CTQs across source/integration/browser/target/provider evidence;
- records PASS/FAIL/NOT_RUN/NOT_APPLICABLE/UNKNOWN;
- distinguishes source from target and future outcome evidence.

## AI Eval Reviewer

- independently evaluates injection/tool misuse/data leakage/excessive agency/output validity/tenant propagation/rollback/audit behavior.

## Product Outcome Analyst

- measures privacy-safe adoption/task success/time-to-value/errors/feedback against intended CTQs;
- feeds unexpected evidence into Research/DMAIC;
- does not use telemetry as an excuse to collect unnecessary personal data.

## Delivery / Release Engineer

- measures delivery flow/stability/rework without individual developer ranking;
- verifies CI/release/rollback/post-deploy controls;
- runs exact-source certification only at required release gates.

## Operations / FinOps Engineer

- reviews observability, SLOs, incident/recovery and provider-neutral resource/cost attribution;
- avoids tenant-sensitive leakage and metric gaming.

## Handoff owner

Whichever role finishes meaningful work updates affected registries, `.ai/state.json`, `.ai/handoff/current.md` and `.ai/plans/active.md`. No role leaves the next agent dependent on chat memory.

## Independence rule

Critical architecture/security/payment/AI execution/package-runtime work requires distinct review context plus objective evidence. Self-asserted correctness is never sufficient certification.
