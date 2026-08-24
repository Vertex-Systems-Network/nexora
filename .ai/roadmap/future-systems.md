# Nexora Future Systems Registry

Planning commitments only; not implementation claims. Use the canonical stage graph for order/dependencies and main/domain registries for development-unit detail.

## Governance / Quality Engineering

- `AI-GOV-AUTOMATION-100` — machine-enforced registry/stage/plan/state/DoD consistency **plus governed AI-development orchestration**: run manifests, instruction trust, scope leases, least-privilege dev tools, scope-delta checks, protected-governance self-modification guard, test/evidence integrity, exact-head review, multi-agent coordination, bounded retries, dependency intake, waivers and promotion/provenance gates.
- `RESEARCH-DISCOVERY-100` — ResearchBrief/VOC/problem validation/current market/standards/baseline/CTQ inputs.
- `QUALITY-GOVERNANCE-100` — closed-loop Quality OS, proportional DMADV/DMAIC, FMEA/root-cause/control-plan traceability.
- `SECURITY-BASELINE-200` — early identity/browser/AppSec/tenancy/secrets/threat-model baseline.

AI-development orchestration details live in `.ai/governance/ai-development-orchestration.md`, `.ai/registry/ai-development-units.json`, `.ai/schemas/ai-development-run.schema.json` and the Phase 7 capability addendum. It intentionally matures `AI-GOV-AUTOMATION-100` rather than adding another top-level roadmap stage.

## Architecture / Data / website-platform primitives

- `ARCH-BOUNDARY-100`
- `CONTENT-MODEL-200`
- `DATA-GOVERNANCE-200` — formal DataFlow/classification/authority/lineage/retention/export/delete and package/API/AI data policy.
- `TAXONOMY-200`
- `QUERY-ENGINE-200`
- `ROUTING-200`
- `NAVIGATION-100`
- `THEME-CONTRACT-200`
- `EXT-SDK-200`
- `SITE-BUILDER-200`
- `THEME-STUDIO-200`
- `RELEASE-WORKFLOW-200`
- `TEMPLATE-ECOSYSTEM-100`

## Delivery / performance / content operations / system topology

- `I18N-200`
- `FRONTEND-RUNTIME-200`
- `PERFORMANCE-FOUNDATION-200`
- `CODE-QUALITY-200`
- `SYSTEM-GRAPH-100` — canonical machine-readable topology/evidence substrate across Core/Theme/Extension/module/runtime/data/security/error/state/events/permissions/packages; separates declared/static/observed/tested/production-observed/AI-inferred truth and provides a safe basic Flow Explorer.
- `MEDIA-DAM-200`
- `SEARCH-200`
- `FORMS-WORKFLOW-200`
- `PRIVACY-CONSENT-100`
- `SEO-AI-200`

System Graph architecture and pre-planned units live in `.ai/flow/system-graph.md`, `.ai/registry/flow-units.json` and the Phase 6 capability addendum.

## AI / API / interoperability

- `AI-KERNEL-100`
- `API-PLATFORM-100`
- `CONFIG-AS-CODE-100`
- `AGENT-INTEROP-100`
- `AI-CONTENT-100`
- `AI-DESIGN-100`
- `DESIGN-IMPORT-100`
- `AI-DX-100`

Product AI and development AI are separate trust domains. Product AI uses Nexora typed public tools/capabilities; development AI uses repository/run/tool/evidence/review governance. Neither is allowed to infer privilege from natural-language intent.

## Performance / Reliability / Flow / Product improvement

- `PERFORMANCE-INTELLIGENCE-200` — PageSpeed/GTmetrix-class analysis plus Nexora-native source/package attribution.
- `RELIABILITY-ENGINEERING-200` — SLI/SLO/error budgets, timeout/retry/idempotency, failure isolation, fault testing and incident/reconciliation control.
- `FLOW-INTELLIGENCE-200` — advanced GUI Flow Center over the canonical graph: ecosystem/architecture/deployment/runtime/code/data/security/permissions/errors/events/queues/network/DB/cache/state/transactions/retries/packages/supply-chain/tests/performance/reliability/cost/payment/AI/release lenses, visual replay, diff/history, change impact/blast radius, incident view and explicitly modelled what-if analysis.
- `EXPERIMENTATION-100`
- `PRODUCT-OUTCOMES-100` — privacy-aware CTQ/adoption/task-success/time-to-value/error/feedback evidence.
- `PERSONALIZATION-100`
- `APP-RUNTIME-100`
- `MIGRATION-CENTER-100`
- `DX-200`
- `DELIVERY-EXCELLENCE-100` — engineering-flow/DORA-style lead/stability/recovery/rework/AI-quality evidence without individual ranking.

### Flow Intelligence design rules

- graph/evidence is source of truth; diagram is projection only;
- use ecosystem→system→feature→execution zoom, filters and lenses rather than one giant graph;
- every edge/node exposes evidence class/provenance/identity where authorized;
- expected-vs-observed drift is first-class;
- conditions/gateways explain inputs, branch semantics, permissions/state/source/tests;
- root/cascade/recovered errors are distinct;
- data lineage and sensitive classifications are redacted and permission-scoped;
- Theme/Extension/module lifecycle/permissions/network/data/assets/errors/performance are package-centric views;
- state machine, transaction/lock/concurrency and retry/reconciliation semantics are first-class;
- path-aware test/evidence coverage is more valuable than one aggregate coverage percentage;
- deployment/config/feature-flag/supply-chain/ownership evidence is graphable;
- Flow AI explains/ranks/hypothesizes but cannot invent runtime truth;
- what-if results stay labelled predicted until tested/observed;
- production deep tracing is sampled/bounded and profiler overhead measured;
- specialized graph DB requires measured need + ADR.

## Marketplace / Payments / Commerce / customer systems

- `MARKETPLACE-200` — package listings can eventually expose evidence-backed security/performance/code-quality/Flow profiles.
- `PAYMENT-SECURITY-200` — mandatory payment-provider security gate before Commerce 2.0: provider-hosted/tokenized default, raw account-data exclusion, purpose-specific capabilities, Secret/Network Brokers, hardened webhook/payment surface, idempotency/reconciliation, sandbox activation and revocation/recovery.
- `COMMERCE-200` — Storefront/Checkout 2.0 only after payment-security boundary for payment-enabled flows.
- `PORTAL-200`
- `COLLAB-200`

Payment Flow views consume Payment Security evidence and may never expose raw account data or become financial authority.

## Cloud / enterprise / security / operations / efficiency

- `MANAGED-CLOUD-100`
- `SENTINEL-200`
- `ENTERPRISE-GOV-200`
- `OBSERVABILITY-200` — integrates production telemetry with canonical System Graph IDs rather than creating another topology source of truth.
- `EFFICIENCY-FINOPS-100` — provider-neutral request/tenant/storage/bandwidth/DB/AI/media/search/provider cost/resource attribution and budgets, available as Flow overlays where authorized.
- `DR-PLATFORM-100`
- `PERF-CWV-CERT-100`
- `A11Y-CERT-100`
- `RELEASE-CERT-100`
- `N2-STABLE-100`

## Planning rule

Appearing here never authorizes coding by itself. Before implementation every system needs registered unit(s), active plan, proportional research/CTQ evidence, architecture/data/security/privacy/performance/System-Graph/reliability decisions, AI-development execution/evidence controls where applicable, tests/verification and rollback/control evidence.

Material runtime/package/data/security/permission/state/network/event/error/deployment changes declare expected System Graph contribution or explicit `NOT_APPLICABLE`. Material AI-assisted runs cannot use stale context, self-weaken governance/tests, forge evidence or self-approve critical work.

Payment providers additionally require the critical payment security profile and sandbox/security review. Newly discovered capabilities are planned/registered first; they are never hidden inside another stage.
