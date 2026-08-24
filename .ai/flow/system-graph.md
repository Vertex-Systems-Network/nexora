# Nexora System Graph & Flow Intelligence Architecture

## Objective

Nexora must make its ecosystem understandable, inspectable and explainable without forcing operators/developers to reconstruct behavior from source files, logs, profilers and package manifests manually.

The product is **not a manually drawn flowchart editor**. The source of truth is a machine-readable **canonical system graph plus evidence**. GUI diagrams are projections/lenses over that evidence.

Core principle:

> **Graph + evidence is truth. Diagram is a view of truth.**

The system must answer:

- what is connected to what;
- why a path exists;
- which Theme/Extension/module/service registered it;
- where data travels and changes classification/ownership;
- where authentication, authorization, capability or trust-boundary checks occur;
- which condition selected a branch and what the condition means;
- where errors originate, propagate, retry, recover or cascade;
- which code/source/package/version executed;
- what DB/cache/network/queue/AI/payment/deployment dependencies exist;
- which paths are declared, statically inferred, runtime-observed, tested and production-observed;
- where declared architecture differs from static/runtime reality;
- what a change, outage or compromise can affect.

## Two-stage architecture

### `SYSTEM-GRAPH-100` — Builder Beta foundation

Owns the canonical graph/evidence model, collectors, identity, basic query contract and safe Admin explorer needed for architecture/package/data/security/runtime visibility.

### `FLOW-INTELLIGENCE-200` — Pro product intelligence

Owns the advanced user-facing Flow Center: multi-lens analysis, deep runtime replay, root-cause/cascade visualization, graph diff/history, blast-radius/change-impact, incident views, attack/data paths, test/evidence coverage, model-based scenario analysis and AI explanations grounded in graph evidence.

Do not duplicate Performance, Observability, Sentinel, Data Governance or Payment Security collectors. Flow Intelligence consumes their canonical evidence through provider contracts.

## Evidence model — three truths plus verification maturity

Every graph assertion must carry evidence class and provenance.

Primary evidence classes:

1. `declared` — architecture contracts, manifests, registries, configuration or explicit design model says the relationship should exist;
2. `static` — source/build/static/data-flow analysis infers the relationship can exist;
3. `observed` — runtime trace/event/query/network/package telemetry proves the path executed;
4. `tested` — a controlled test exercised or asserted the path/control;
5. `production-observed` — production telemetry observed the path under the configured retention/privacy policy;
6. `ai-inferred` — AI hypothesis/explanation only; never silently promoted to runtime truth.

Each assertion should retain:

- provider/source;
- source/build/package/deployment identity;
- environment;
- first/last seen;
- confidence;
- tenant/site scope when permitted;
- evidence IDs;
- redaction/classification;
- expiry/retention rules.

### Drift detection

High-value comparisons include:

- declared but never observed;
- observed but not declared;
- static path violating architecture policy;
- package requesting/using undeclared capability;
- runtime network destination not present in manifest;
- Theme/Extension bypassing required public contract/broker;
- payment/data/AI flow crossing an unexpected trust boundary;
- test suite not covering a critical declared/observed path.

## Canonical graph model

### Node families

The graph must support typed, versionable nodes such as:

- actor/user/service identity;
- site/tenant/environment/deployment;
- entry point and route;
- middleware;
- authentication control;
- authorization policy/permission/capability;
- condition/gateway/state transition;
- controller/action/command;
- service/use case;
- public contract/interface;
- registry/provider;
- Core module;
- Theme/template/layout/component/asset;
- Extension/App/Integration/Studio Pack and package version;
- hook/event/filter/slot/listener;
- job/queue/schedule;
- database/table/record class;
- transaction/lock boundary;
- cache/index/search store;
- file/object storage;
- data object/classification;
- secret reference/broker;
- external API/network origin/provider;
- AI agent/tool/model/context boundary;
- payment provider/webhook/surface/state;
- security control/trust zone/trust boundary;
- error/exception/retry/fallback/circuit-breaker;
- test/eval/control evidence;
- SLI/SLO/alert/incident;
- build/artifact/release/version;
- infrastructure node such as DNS/CDN/WAF/load balancer/app node/Redis/DB/worker/object store;
- owner/publisher/CODEOWNER/support owner.

Node types are extensible through versioned public graph schemas; third-party packages cannot invent executable privilege by adding a visual node.

### Edge families

Examples:

- `CALLS`
- `READS`
- `WRITES`
- `TRANSFORMS`
- `VALIDATES`
- `AUTHENTICATES`
- `AUTHORIZES`
- `REQUIRES_PERMISSION`
- `REQUIRES_CAPABILITY`
- `EMITS`
- `LISTENS`
- `QUEUES`
- `RETRIES`
- `FALLS_BACK_TO`
- `RECONCILES_WITH`
- `THROWS`
- `CATCHES`
- `CAUSES`
- `RENDERS`
- `LOADS_ASSET`
- `DEPENDS_ON`
- `SENDS_TO`
- `RECEIVES_FROM`
- `CROSSES_TRUST_BOUNDARY`
- `USES_SECRET_REF`
- `LOCKS`
- `TRANSITIONS_TO`
- `INVALIDATES`
- `DEPLOYED_ON`
- `OWNED_BY`
- `TESTS`
- `OBSERVED_BY`
- `AFFECTS`

Every edge is directional unless its schema explicitly says otherwise.

## Hierarchy / zoom model

Do not render the entire platform as one unreadable graph.

Use progressive views:

- **L0 Ecosystem** — Nexora, major modules, packages, data stores, external providers and infrastructure;
- **L1 System** — Commerce/CMS/Auth/Theme/AI/etc.;
- **L2 Feature** — checkout/refund/login/publish/search/package activation/etc.;
- **L3 Execution** — route/middleware/service/hook/query/network/error/state detail;
- optional source/function depth only when requested and evidence permits.

The graph query engine selects relevant subgraphs and can collapse/expand groups.

## GUI notation

Use familiar flow semantics without making diagram shapes authoritative.

Recommended projection semantics:

- oval — start/end/event;
- rectangle — process/action;
- diamond — condition/gateway/decision;
- cylinder — database/data store;
- rounded component — module/service/package/component;
- cloud/external — remote provider/network system;
- queue symbol — async queue/job;
- shield marker — security control;
- key marker — secret/capability;
- dotted or bounded container — trust zone/deployment/transaction boundary;
- directed edge — flow/relationship;
- distinct error/retry/fallback/state-transition markers.

Never rely on color alone. Icons, labels, patterns and accessible textual equivalents are required.

## Condition / gateway explanation

A decision node must be understandable without opening source.

Clicking a condition should show, where evidence exists:

- human-readable question;
- source expression/policy identifier;
- inputs;
- expected data types/classification;
- TRUE branch meaning;
- FALSE branch meaning;
- permission/capability implications;
- state/precondition requirements;
- observed branch frequency only when privacy-safe and statistically meaningful;
- source file/symbol/package/version;
- related tests;
- failure/security significance.

AI may explain a condition, but the underlying expression/policy/evidence remains visible.

## Required GUI lenses

The UI should expose grouped top-level categories, not dozens of permanent tabs. Suggested top navigation:

- Overview
- Architecture
- Runtime
- Data
- Security
- Quality
- Operations
- Packages

Sub-lenses can include:

### Ecosystem / architecture

- system landscape;
- module/contracts/dependencies;
- deployment/infrastructure;
- configuration/feature-flag topology;
- ownership/accountability;
- release/artifact/version identity.

### Runtime / code

- route/request execution;
- code/call paths;
- events/hooks/filters/slots;
- queue/jobs/schedules;
- cache/invalidation;
- database queries;
- network/external providers;
- transactions/locks/concurrency;
- retry/timeout/backoff/circuit-breaker/fallback;
- state machines.

### Data

- data lineage;
- authoritative vs derived stores;
- classification;
- transformations;
- tenant/user ownership;
- retention/export/delete propagation;
- AI/package/external exposure;
- compliance/residency/consent where known.

### Security

- trust zones and boundaries;
- entry points;
- authentication;
- authorization/permission/capability paths;
- secrets/brokers;
- source-to-sink/taint paths;
- architecture violations;
- attack paths;
- package trust/Sentinel/supply-chain evidence;
- payment-security flow;
- AI tool/approval path.

### Errors / reliability

- root cause vs propagated/cascade failures;
- exception path;
- retry/fallback/reconciliation;
- queue/dead-letter flow;
- provider outage/degradation;
- SLI/SLO/error-budget impact;
- incident/blast radius.

### Quality / tests / performance / cost

- test/eval coverage by critical path;
- static quality findings linked to runtime;
- frontend/backend/package performance overlays;
- cost/resource attribution;
- expected vs observed architecture;
- change impact;
- version/deployment diff;
- regression history.

## Theme lens

Show:

`request → route resolver → content/query → template resolver → Theme → layout/component → extension slot → assets → rendered response`

Also show Theme registration, templates, menu locations, Studio bindings, asset/network ownership, extension slots and violations such as Theme → direct DB/secret/private Core access.

## Extension/module/package lens

For an installable package show:

- package/publisher/version/security profile;
- public contracts registered/consumed;
- hooks/events/filters/slots/routes/jobs/components;
- requested and used capabilities;
- permissions/human approvals;
- DB/data/cache/network/secret access through approved brokers;
- data classifications handled;
- frontend assets/scripts/origins;
- runtime cost/errors;
- lifecycle states;
- dependencies/SBOM/advisories/provenance;
- test/security/performance evidence;
- install/enable/update/rollback/uninstall paths.

## Package lifecycle flow

Visualize at least:

`upload → quarantine → signature/publisher → Sentinel/Supply Chain → manifest → compatibility → dependencies → capabilities → approval → install → migration → enable → runtime → update → rollback → disable → uninstall`

The exact failing gate and remediation/evidence must be clickable.

## Data flow / lineage view

A data edge can expose:

- field/data object;
- classification;
- source/actor;
- validation/transformation;
- encryption in transit/at rest policy where known;
- authoritative store;
- derived stores;
- tenant/site/user scope;
- external recipients/processors;
- AI exposure policy;
- logging/telemetry policy;
- consent/residency;
- retention/export/delete behavior.

Do not show raw sensitive values by default.

## Security attack/source-to-sink paths

The graph should ingest approved static/taint-analysis findings and runtime evidence to highlight paths such as:

`untrusted source → transformation/validation → trust boundary → sensitive sink`

Example categories:

- SSRF/network destination;
- SQL/query sink;
- filesystem write;
- executable content;
- DOM/script injection;
- secret access;
- cross-tenant data;
- unsafe redirect;
- payment mutation;
- AI tool side effect.

A path finding must retain analyzer/provider evidence. Flow Intelligence does not invent a vulnerability merely because nodes are adjacent.

## Permission/capability explanation

The GUI must support `Why allowed?` and `Why denied?` views showing:

- actor/role;
- tenant/site;
- human permission;
- policy/preconditions;
- runtime package capability;
- approval/re-auth step;
- final allow/deny decision;
- source/registration evidence.

Human permissions and executable-code capabilities remain distinct.

## Error and root-cause flow

Error visualization distinguishes:

- origin/root failure;
- propagated failure;
- secondary/cascade failure;
- recovered error;
- retry attempt;
- timeout;
- reconciliation;
- dead-letter/failed job;
- customer-visible outcome.

Correlate through trace/correlation IDs without exposing secrets or sensitive payloads.

## State machine lens

Represent valid/invalid transitions for stateful domains including payments, orders, publishing, package lifecycle, workflows, subscriptions and deployment/release state.

Show:

- current state;
- allowed next states;
- actor/capability/precondition;
- event/source;
- invalid attempted transitions;
- idempotency/concurrency guard;
- audit evidence.

## Transaction / concurrency lens

Model:

- transaction boundaries;
- row/advisory/distributed locks where applicable;
- optimistic version checks;
- concurrent paths;
- idempotency keys;
- race windows/findings;
- retry after rollback/timeout;
- provider reconciliation.

Do not infer race safety from a single successful trace.

## Retry / timeout / circuit-breaker lens

Show per dependency/operation:

- timeout;
- retry eligibility;
- max attempts;
- backoff/jitter;
- idempotency requirement;
- circuit state;
- fallback;
- dead-letter path;
- ambiguous-outcome reconciliation.

Financial/destructive paths follow Payment/Reliability rules and are not blindly retried.

## Deployment / infrastructure flow

Where deployment metadata exists, show:

`user → DNS → CDN/WAF → load balancer → app node → cache/DB/queue/object store/external provider`

Support environment comparison such as dev/staging/production and version/deployment identity. Do not expose sensitive internal topology to unauthorized roles.

## Configuration / conditional topology

Graph conditions may depend on:

- tenant/site configuration;
- provider selection;
- feature flags;
- locale;
- environment;
- package activation/version;
- consent profile;
- role/capability;
- release branch.

The graph must represent conditional topology without pretending a path is universally active.

## Supply-chain lens

Connect package → direct/transitive dependency → artifact/build/publisher/provenance/advisory/license evidence.

Show whether a vulnerable dependency is reachable/observed on a runtime path where such evidence exists, but do not claim non-reachability proves safety.

## Ownership lens

Nodes/edges can map to:

- Core/module team;
- CODEOWNER/reviewer;
- package publisher;
- support owner;
- security owner;
- last material release/change.

This supports incident routing and accountability, not developer surveillance/scoring.

## Test / evidence coverage

Coverage is path-aware, not only line/percentage based.

For a critical path show whether it is:

- declared;
- statically resolved;
- runtime observed;
- unit tested;
- integration tested;
- authorization/tenant tested;
- security tested;
- E2E/browser tested;
- performance tested;
- failure/fault tested;
- production observed.

Missing evidence is displayed as missing/unknown, never inferred PASS.

## Performance overlay

Consume `PERFORMANCE-FOUNDATION-200` / `PERFORMANCE-INTELLIGENCE-200` evidence for nodes/edges:

- duration/inclusive/exclusive time;
- queries/cache/network;
- CPU/memory;
- JS/asset/main-thread impact;
- Theme/Extension/component cost;
- before/after/version regression.

Flow Intelligence must not build a second performance telemetry source of truth.

## Payment lens

Consume `.ai/security/payment-security.md` and provider/runtime evidence to show:

`customer → checkout → Payment Surface Guard → hosted/tokenized provider → provider → signed webhook → Payment Webhook Gateway → replay/tenant/state checks → Commerce Core → order/transaction state`

Expose control assertions such as:

- raw PAN/CVV enters Nexora? expected `NO`;
- Secret Broker used?;
- allowed provider origins only?;
- webhook verified/fresh/deduped?;
- Core amount/currency/state authority?;
- browser return treated as non-authoritative?;
- idempotency/reconciliation path?;
- payment package version/security review status?

Sensitive payment details remain redacted.

## AI lens

Show:

`user → AI request/router → planner → tool selection → AI capability gate → approval → typed tool → public Nexora contract → validation → mutation → audit/postcondition`

Clicking approval/condition nodes explains why confirmation was required. AI never gets a graph-specific private bypass.

## Change-impact / blast-radius analysis

Given a changed unit/file/contract/package/config or failed/compromised node, traverse allowed relationship classes to estimate affected:

- public contracts;
- modules/packages;
- runtime paths;
- data flows;
- permissions/capabilities;
- tests;
- payment/AI/security paths;
- performance/SLOs;
- deployments/tenants/sites where authorized.

The result is an evidence-backed **potential impact graph**, not a certainty claim unless observed/tested evidence proves the effect.

## Graph diff / history / time travel

Persist bounded graph snapshots or reconstructable version metadata so users can compare:

- release A vs B;
- package version A vs B;
- branch/staging vs production;
- environment A vs B;
- first/last seen unexpected edge;
- before/after incident or remediation.

Show added/removed/changed nodes, edges, policies, capabilities, network destinations, data flows, tests and performance/reliability findings.

## Runtime visual replay

A trace can be replayed visually without re-executing the request:

- ordered spans/events;
- async boundaries;
- decisions where captured;
- DB/cache/network calls;
- errors/retries;
- final state/outcome.

Replay is a visualization of recorded evidence, never an executable debugger with production side effects.

## Incident / blast-radius view

During an incident show:

- root/trigger node;
- affected paths/components/packages/providers;
- tenant/site/deployment scope where authorized;
- SLO/error-budget impact;
- security findings;
- active containment/kill switches;
- recovery/reconciliation state;
- change/release that first introduced the path when known.

Preserve forensic evidence according to security/retention policy.

## Model-based scenario / what-if analysis

Future Flow Intelligence may evaluate questions such as:

- what if this Extension is disabled?;
- what if Redis/provider/API is unavailable?;
- what if this permission/capability is removed?;
- what if a package version changes?;

Simulation uses declared dependencies, state machines, fallback policies and observed evidence. Results are labelled `predicted/modelled` and must never be presented as verified runtime outcomes until tested.

## Flow integrity / architecture fitness rules

The graph can feed deterministic policy checks, for example:

- Theme → direct DB/private Core/secret = deny/warn according to constitution;
- generic package → unrestricted filesystem/network/secret = deny/review;
- payment provider → arbitrary network/raw account data = deny;
- AI tool → direct unrestricted DB/shell/secret/network = deny;
- cross-tenant data path = deny;
- required broker/contract bypass = architecture violation;
- undeclared runtime package capability/network destination = security finding;
- critical observed path with no required tests = quality finding.

A GUI warning is not itself enforcement; enforcement belongs to the appropriate architecture/security/runtime gate.

## Security of Flow Intelligence itself

The graph is sensitive reconnaissance material. Access is default-deny and separately scoped.

Candidate permissions/capabilities:

- `flow.overview.read`
- `flow.architecture.read`
- `flow.runtime.read`
- `flow.data.read`
- `flow.security.read`
- `flow.package.read`
- `flow.incident.read`
- `flow.sensitive.read`
- `flow.export`
- `flow.deep-trace.execute`

Rules:

- tenant/site scoping;
- sensitive graph fields redacted by default;
- no raw secrets/passwords/tokens/payment account data;
- no unredacted request bodies/SQL bindings by default;
- security/incident/deployment detail restricted;
- graph exports audited and optionally approval/re-auth protected;
- deep traces are bounded, sampled/on-demand and auditable;
- AI sees only graph fields allowed by its user/tenant/tool capability context.

## Runtime overhead policy

The diagnostic system must not become the performance problem.

### Production

- lightweight always-on topology/metrics where safe;
- sampling;
- higher-priority error/security/payment events where policy permits;
- deep traces on demand or bounded targeted profiles;
- retention/cardinality limits;
- no function-by-function full tracing by default.

### Development/staging

- deeper static/runtime tracing;
- richer source/symbol mapping;
- package debugging;
- synthetic/fault/security flows.

Profiler/collector overhead is itself measured and budgeted.

## Storage design

Do not select a graph database merely because the product is a graph.

Start provider-neutral with canonical contracts and a storage abstraction. A relational implementation may use normalized nodes/edges/evidence/snapshots, for example conceptual stores:

- flow nodes;
- flow edges;
- evidence/provenance;
- trace/run links;
- findings;
- snapshots/diffs;
- ownership/policy mappings.

Adopt a specialized graph store only when measured query scale/latency/operations justify the additional dependency. Storage choice requires ADR.

## Provider integration

Evidence providers may include:

- architecture/public registries/manifests;
- AST/static/code/taint analyzers;
- Performance telemetry;
- OpenTelemetry-compatible traces where adopted;
- logs/errors;
- DB/query/cache/network instrumentation;
- Sentinel/Supply Chain;
- Data Governance;
- authorization/capability registries;
- release/build/deployment metadata;
- test/eval evidence;
- Reliability/SLO/incident evidence;
- Payment Security;
- AI Tool Registry/audit.

Canonical graph schemas remain provider-neutral.

## AI integration

After `AI-KERNEL-100`, typed read/draft tools may include:

- `flow.explain`
- `flow.root_cause`
- `flow.security_path`
- `flow.data_lineage`
- `flow.change_impact`
- `flow.compare`
- `flow.evidence_gaps`
- `flow.what_if`

AI may summarize/rank/hypothesize but cannot fabricate nodes, elevate `ai-inferred` to observed evidence, expose restricted graph data, or execute side effects merely because a graph path exists.

## Definition of Done — SYSTEM-GRAPH-100

At minimum:

- versioned canonical node/edge/evidence schemas;
- stable identity model for Core/Theme/Extension/App/Integration/Studio/package/version/route/service/data/provider/deployment nodes;
- declared/static/runtime/test evidence separation;
- provider ingestion contracts;
- architecture/data/security/package/runtime basic lenses;
- permission/redaction/audit policy;
- graph query/subgraph/collapse/expand contract;
- drift detection for representative declared-vs-observed violations;
- bounded storage/retention/cardinality policy;
- measured collector overhead;
- integration with existing Performance/Data/Security/package evidence rather than duplicate telemetry;
- source tests + real-target evidence for representative Theme, Extension and Core request paths.

## Definition of Done — FLOW-INTELLIGENCE-200

At minimum:

- mature accessible Flow Center GUI;
- ecosystem→system→feature→execution zoom;
- runtime visual replay;
- data/security/permission/error/event/network/DB/cache/state/transaction/retry/deployment/supply-chain/test/performance/reliability/payment/AI/release lenses;
- root-vs-cascade failure visualization;
- source-to-sink and trust-boundary security paths with evidence;
- path-aware test/evidence coverage;
- version/environment graph diff/history;
- change-impact/blast-radius analysis;
- incident view;
- sensitive-access/export auditing;
- optional modelled what-if analysis with prediction labels;
- AI explanations grounded only in authorized graph evidence;
- demonstrated detection/explanation of representative architecture drift, unexpected package network edge, data-lineage path, permission denial, runtime error cascade, performance bottleneck and critical untested path.

## Final invariant

Flow Intelligence must make Nexora easier to understand without creating a second competing architecture, security, performance or observability source of truth. The canonical graph references authoritative evidence produced by those systems and makes the relationships visible, queryable and explainable.