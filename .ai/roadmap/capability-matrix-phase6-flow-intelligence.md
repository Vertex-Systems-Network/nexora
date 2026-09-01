# Nexora Capability Matrix — Phase 6 System Graph & Flow Intelligence

This addendum defines accepted capability gaps for an evidence-backed platform graph and GUI Flow Intelligence system. It does not claim implementation.

| Capability | Existing foundation | Gap before Phase 6 | Accepted target | Stage |
|---|---|---|---|---|
| Canonical system topology | Architecture/contracts/registries exist | no unified queryable node/edge/evidence model | versioned provider-neutral graph schema across architecture/runtime/data/security/packages/tests/releases/infra | `SYSTEM-GRAPH-100` |
| Evidence truth separation | source vs target and performance evidence already separated | graph assertions could otherwise look equally certain | explicit `declared/static/observed/tested/production-observed/ai-inferred` evidence classes with provenance/confidence | `SYSTEM-GRAPH-100` |
| Theme flow | Theme lifecycle/template contracts exist/planned | no unified visual render/data/asset/slot/security path | request→resolver→query→template→Theme→component/slot/assets graph with package/version/source evidence | `SYSTEM-GRAPH-100` |
| Extension/module flow | Extension lifecycle/capabilities/SDK planned | no single view of contracts/hooks/data/network/secrets/errors/performance/lifecycle | package-centric flow profile + declared-vs-observed drift | `SYSTEM-GRAPH-100` |
| Runtime code flow | Performance Foundation plans route/service/package spans | profiler output not a system relationship graph | correlate request/middleware/service/hook/DB/cache/network/job/package edges without duplicate telemetry | `SYSTEM-GRAPH-100` |
| Data lineage GUI | Data Governance planned | no interactive data-path projection | source→validation→transform→authoritative/derived stores→API/package/AI/external/retention view | `SYSTEM-GRAPH-100` |
| Security flow | threat modeling/Sentinel/security controls exist/planned | no unified trust-boundary/authz/source-to-sink GUI | entry/trust/auth/authz/capability/secret/broker/taint/attack-path graph with evidence | `SYSTEM-GRAPH-100` + `FLOW-INTELLIGENCE-200` |
| Permission explanation | RBAC/capabilities exist | difficult to explain why access/package execution was allowed/denied | `Why allowed?` / `Why denied?` chain for role/tenant/policy/capability/approval | `SYSTEM-GRAPH-100` |
| Conditions/gateways | code/workflows contain decisions | condition meaning hidden in code | diamond/gateway node with human-readable meaning, inputs, branches, source, tests, security/state significance | `SYSTEM-GRAPH-100` |
| Error propagation | logs/exceptions/observability planned | root and cascade failures visually mixed | root→propagated→secondary/retry/recovery/dead-letter/outcome graph | `SYSTEM-GRAPH-100` + `FLOW-INTELLIGENCE-200` |
| State machines | payment/package/publishing states exist | no cross-platform visual state model | allowed/invalid transitions with actor/capability/precondition/idempotency/audit | `SYSTEM-GRAPH-100` |
| Transaction/concurrency | row locks/idempotency exist in selected systems | no platform-wide race/transaction visibility | transaction/lock/idempotency/concurrent path/race-window/reconciliation lens | `SYSTEM-GRAPH-100` |
| Retry/resilience flow | Reliability stage planned | retry/fallback semantics scattered | timeout/retry/backoff/circuit/fallback/dead-letter/reconciliation projection | `SYSTEM-GRAPH-100` + `FLOW-INTELLIGENCE-200` |
| Events/queues/hooks | Extension SDK/Automation planned | async boundaries hard to debug | event/listener/hook/filter/queue/job priority/sync-async/retry/recursive-loop graph | `SYSTEM-GRAPH-100` |
| Deployment topology | enterprise/cloud/managed deployment planned | application graph lacks infrastructure context | DNS/CDN/WAF/LB/app/cache/DB/queue/storage/provider environment graph | `FLOW-INTELLIGENCE-200` |
| Configuration topology | config-as-code planned | runtime path depends on hidden flags/config/provider selection | conditional graph by tenant/site/env/feature flag/locale/consent/package version | `FLOW-INTELLIGENCE-200` |
| Supply-chain paths | Sentinel/SBOM/provenance planned | advisories/dependencies not connected to runtime/package graph | direct/transitive dependency + artifact/publisher/advisory/license/runtime-reachability lens | `FLOW-INTELLIGENCE-200` |
| Ownership/accountability | package publishers/code ownership exist separately | incident routing requires manual discovery | node/edge owners, publisher, reviewer/support/security ownership and release identity | `SYSTEM-GRAPH-100` |
| Path-aware test evidence | verification matrix exists | line/percentage coverage does not prove critical flow | overlay unit/integration/auth/security/E2E/perf/fault/production evidence on graph paths | `FLOW-INTELLIGENCE-200` |
| Expected vs actual drift | architecture/source guards exist | runtime drift is hard to see | declared vs static vs observed comparison and deterministic policy findings | `SYSTEM-GRAPH-100` |
| Graph history/diff | release/version data exists | no relationship time travel | release/package/branch/environment graph diff + first/last seen | `FLOW-INTELLIGENCE-200` |
| Runtime visual replay | traces planned | raw trace not intuitive | read-only visual playback of recorded request/async/error flow | `FLOW-INTELLIGENCE-200` |
| Change impact/blast radius | dependencies/tests spread across systems | change risk manually reconstructed | potential vs tested/observed impact traversal across contracts/packages/data/security/tests/perf/SLO/deployments | `FLOW-INTELLIGENCE-200` |
| Incident flow | security/reliability/observability planned | incident evidence fragmented | root/affected paths/containment/SLO/recovery/reconciliation/version timeline view | `FLOW-INTELLIGENCE-200` |
| What-if analysis | no formal platform scenario graph | failure/package/config impact guessed manually | modelled/predicted scenario traversal using registered dependencies/state/fallbacks; never presented as verified | `FLOW-INTELLIGENCE-200` |
| Payment flow GUI | Payment Security architecture planned | controls difficult to review as end-to-end path | Payment Surface→provider→Webhook Gateway→security checks→Commerce state plus sensitive-data assertions | `FLOW-INTELLIGENCE-200` |
| AI action flow | AI Kernel pipeline planned | operator cannot inspect tool decision path visually | request→planner→tool→capability→approval→contract→validation→mutation→audit graph | `FLOW-INTELLIGENCE-200` |
| Performance overlay | Performance Intelligence planned | metrics not visually tied to entire business/security flow | per-node/edge duration/query/cache/network/CPU/memory/asset/package regression overlay | `FLOW-INTELLIGENCE-200` |
| Cost/SLO overlay | reliability/FinOps planned | operational impact isolated from flow | node/edge SLI/SLO/error-budget and resource/cost attribution where available | `FLOW-INTELLIGENCE-200` |
| Sensitive graph access | RBAC/tenancy baseline | architecture graph can become attacker reconnaissance | dedicated `flow.*` permissions, tenant scope, redaction, export/deep-trace audit/re-auth policy | both |
| Graph storage | relational platform foundation | temptation to add graph DB prematurely | storage abstraction; use relational node/edge/evidence model initially unless measured need justifies graph DB ADR | `SYSTEM-GRAPH-100` |
| AI flow explanation | AI Kernel planned | AI could invent paths | typed read-only explain/root-cause/security/impact tools grounded in authorized graph evidence classes | `FLOW-INTELLIGENCE-200` |

## Product acceptance principle

A pretty diagram does not satisfy this capability matrix. A capability is complete only when the graph can state **where an assertion came from and how certain it is**.

## Integration principle

- Performance owns performance evidence.
- Observability owns broad operational telemetry.
- Data Governance owns data classification/lineage policy.
- Sentinel/Security owns security enforcement/findings.
- Payment Security owns payment-specific controls.
- Release Workflow owns release state.
- System Graph references/correlates those facts.
- Flow Intelligence projects, queries, compares and explains them.

No second authoritative telemetry/security/data model may be created merely for the GUI.
