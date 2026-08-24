# Nexora Verification Matrix

Minimum evidence classes before a stage/unit advances. Source, target, provider, graph, AI-development-run and post-release evidence remain distinguishable.

## Builder Beta / foundation stages

| Stage | Source/static evidence | Integration evidence | Target/evidence | Special evidence |
|---|---|---|---|---|
| `RUNTIME-CLOSURE-001` | runtime/source guards | compatibility/readiness | real target required | exact identity-plane output |
| `CORE-QA-001` | tests/typecheck | auth/roles/settings/media CRUD | browser/target required | direct URL/session persistence |
| `AI-GOV-AUTOMATION-100` | schema/registry/stage/run-manifest/protected-policy validators | invalid governance + prompt-injection + stale-run + race + scope-delta + test/evidence/review/waiver/dependency fixtures rejected | CI/repository-rules evidence required where accessible | unregistered/stale/inconsistent work fails; AI cannot self-weaken governance/test oracle, forge target evidence or self-approve critical work; reviewed/promoted exact-head identity enforced |
| `RESEARCH-DISCOVERY-100` | ResearchBrief schema/policy tests | request -> evidence -> unit/CTQ mapping | representative workflow | AI cannot fabricate VOC/baselines/sources |
| `QUALITY-GOVERNANCE-100` | DMADV/DMAIC/FMEA/control validators | representative new-unit + regression workflow | process evidence | proportionality and traceability |
| `ADMIN-UX-CLOSURE-001` | component/type tests | Admin shell flows | browser required | responsive/appearance/a11y |
| `SECURITY-BASELINE-200` | SAST/dependency/secret/security | auth/tenancy/browser-policy | target as applicable | threat model/MFA/CSP/security-CI |
| `ARCH-BOUNDARY-100` | architecture tests | boundary integration | target if runtime affected | ADR/exception evidence |
| `THEME-CLOSURE-001` | manifest/security/tests | full theme lifecycle | required | preview/activate/rollback/render |
| `EXTENSION-CLOSURE-001` | manifest/capability/security | full package lifecycle | required | family/runtime/migration policy |
| `STUDIO-CLOSURE-001` | visual-tree/schema | document/theme bindings | required | responsive/revision/publish |
| `CMS-PUBLISHING-CLOSURE-001` | publishing tests | CRUD/revisions/archive | required | permalink/render/SEO |
| `MEDIA-DISTRIBUTION-CLOSURE-001` | media/distribution | upload/use/RSS/newsletter | required | media delivery policy |
| `SEO-SEARCH-CLOSURE-001` | SEO/search/crawler | schema/sitemap/search | required | rendered metadata/crawler |
| `AUTOMATION-CLOSURE-001` | trigger/action/webhook | retry/idempotency/signature | target where network matters | replay/private-network protections |
| `CONTENT-MODEL-200` | schema/field/relation | Admin/API/package/Studio | required | install/upgrade/schema versions |
| `DATA-GOVERNANCE-200` | data-flow/classification/lineage policy | source -> derived store -> API/package/AI/delete | representative target | tenant/access/deletion/redaction evidence |
| `TAXONOMY-200` | definition/hierarchy | content/package registration | required | permission/routing |
| `QUERY-ENGINE-200` | typed query/parser | content/relation/archive | required | authorization/performance |
| `ROUTING-200` | resolver/collision/redirect | canonical routes | required | permalink migration/redirect |
| `NAVIGATION-100` | menu tests | theme/Studio/API/AI | required | nested/conditional/location |
| `THEME-CONTRACT-200` | manifest/hierarchy | theme/Studio/content | required | deterministic fallback/slots |
| `EXT-SDK-200` | SDK/capability tests | reference package | required | no private Core shortcut |
| `SITE-BUILDER-200` | AST/component/binding | theme/query/content/package | required | responsive/a11y/history |
| `THEME-STUDIO-200` | token/global-template | site-wide visual | required | inheritance/overrides |
| `RELEASE-WORKFLOW-200` | branch/release state | preview/merge/publish/rollback | required | selective/scheduled publish |
| `TEMPLATE-ECOSYSTEM-100` | package/dependency/update | starter install/customize/update | required | derived customization |
| `I18N-200` | locale/translation | CMS/theme/SEO/release | required | hreflang/RTL/locale publish |
| `FRONTEND-RUNTIME-200` | cache/image/CDN | delivery | required | CWV/cache/security headers |
| `PERFORMANCE-FOUNDATION-200` | profile/budget/attribution | browser/Admin/server/DB/package | required | profiler overhead/redaction/regression detection |
| `CODE-QUALITY-200` | analyzers/findings | Core/package source/build + runtime | representative | reproducible quality findings |
| `SYSTEM-GRAPH-100` | node/edge/evidence schemas; identity/provider/static/drift tests | registry + static + runtime + data + security + package correlation | representative Core/Theme/Extension real-target paths required | declared/static/observed/tested separation; unexpected edge/drift detection; sensitive access/redaction/export/deep-trace tests; measured collector overhead |
| `MEDIA-DAM-200` | asset/transform/dedupe | content/Studio/storage | required | usage/rights/delivery |
| `SEARCH-200` | index/facet/provider | content/commerce | required | ranking/privacy/performance |
| `FORMS-WORKFLOW-200` | validation/abuse | lead/provider/automation | required | rate/consent evidence |
| `PRIVACY-CONSENT-100` | consent/policy | analytics/forms/RUM/System-Graph telemetry | required | GPC/DNT/export/delete/retention |

## `AI-GOV-AUTOMATION-100` orchestration evidence

The stage must demonstrate representative end-to-end cases, not only schema validation:

- valid run manifest binds exact base SHA, stage/unit, plan/policy digests, scope/capabilities/lease/review profile;
- a material HEAD/plan/policy change makes the run stale;
- issue/source/log/README/web prompt injection cannot override governance or grant secrets/network/tools;
- overlapping writers on the same protected file/migration fail or coordinate explicitly;
- child agent cannot exceed parent scope/capabilities;
- new dependency/permission/network/secret/migration/trust-boundary behavior triggers scope-delta re-plan;
- feature/runtime change cannot weaken/remove/downgrade its own governing check to obtain PASS;
- deleted/skipped/relaxed critical tests are surfaced and cannot silently satisfy verification;
- AI-authored `PASS`/`TARGET_VERIFIED` files cannot satisfy machine/runtime/provider evidence;
- review is bound to exact head SHA and becomes stale after material head change;
- repeated equivalent failure loop trips a bounded circuit instead of disabling controls;
- dependency addition/major upgrade requires intake beyond green typecheck/build;
- expired, self-approved or over-broad material waiver fails;
- promoted source/artifact identity matches reviewed/verified/attested identity;
- operational repository rules/settings are checked where accessible; unavailable settings produce `UNKNOWN`, not PASS.

## Pro stages

| Stage | Source/static evidence | Integration evidence | Target/evidence | Special evidence |
|---|---|---|---|---|
| `AI-KERNEL-100` | tool schema/policy/evals | model/context/tool/approval/audit | execute paths required | injection/leakage/agency evals |
| `SEO-AI-200` | SEO/AEO/entity tests | crawler/search/AI | required | no private-data AI representation |
| `API-PLATFORM-100` | schema/auth/rate | REST/GraphQL/OAuth/webhooks | required | IDOR/fuzz/versioning |
| `CONFIG-AS-CODE-100` | serializer/diff | export/apply/rollback | required | idempotent/env safe |
| `AGENT-INTEROP-100` | protocol/auth/tool | external agent -> gateway | required | scoped identity/approval/audit |
| `AI-CONTENT-100` | prompt/tool/output/evals | CMS/media/SEO | required | schema/review/audit |
| `AI-DESIGN-100` | AST/token/component evals | AI -> Studio/release | required | responsive/a11y/rollback |
| `DESIGN-IMPORT-100` | parser/mapping/security | design -> AST/tokens | required | no trusted executable markup |
| `AI-DX-100` | scaffold/review/quality/graph identity | generated package -> SDK/Sentinel/System Graph | representative | independent review + package Flow visibility |
| `PERFORMANCE-INTELLIGENCE-200` | report/runner/provider/alert | lab + RUM + backend + package + System Graph correlation | required | waterfall/user-flow/history/secure URL runner; Flow overlay references authoritative performance evidence |
| `RELIABILITY-ENGINEERING-200` | SLI/SLO/retry/idempotency contracts | fault/provider/degradation/recovery + graphable state/error paths | representative targets | error-budget/fault/reconciliation evidence |
| `FLOW-INTELLIGENCE-200` | query/lens/diff/impact/replay/permission/AI-tool tests | System Graph + Performance + Reliability + Data + Security + Release + Payment/AI providers | real GUI/runtime evidence required | accessible zoom; root/cascade; source-to-sink with provenance; path-aware tests; history/diff; read-only replay; impact/blast radius; incident view; modelled what-if labels; AI evidence grounding; no duplicate truth store |
| `EXPERIMENTATION-100` | assignment/statistics/goals | release/privacy/performance | required | deterministic rollout/rollback |
| `PRODUCT-OUTCOMES-100` | outcome schema/privacy | CTQ -> adoption/task-success/feedback | observation evidence | no fabricated future outcome; privacy-safe |
| `PERSONALIZATION-100` | segment/rule/fallback | query/content/privacy | required | no tenant/segment leakage |
| `APP-RUNTIME-100` | capability/broker/graph-registration | functions/jobs/network/secrets | required | isolation/egress/tenant abuse + observable allowed paths |
| `MIGRATION-CENTER-100` | adapter/idempotency | dry-run/import | required | loss/redirect/retry |
| `DX-200` | CLI/SDK/reference/Flow tooling | clean external workflow | representative | docs/compatibility/graph inspection |
| `DELIVERY-EXCELLENCE-100` | metric definitions/collectors | CI/PR/release/Flow impact events | observed pipeline | change lead/failure/recovery/rework + anti-gaming |

## Platform / payments / operations stages

| Stage | Source/static evidence | Integration evidence | Target/evidence | Special evidence |
|---|---|---|---|---|
| `MARKETPLACE-CLOSURE-001` | catalog/stager/security | quarantine/install | required | publisher/signature/digest |
| `MARKETPLACE-200` | license/compat/update/profile | publisher/package lifecycle + Flow profile | required | revocation + reproducible quality/security/perf/graph evidence |
| `COMMERCE-CLOSURE-001` | domain/payment-adapter foundation | order/invoice/refund/subscription | required | minor-unit money/idempotency/provider boundary |
| `PAYMENT-SECURITY-200` | payment manifest/capability/state/data/leak/graph tests | Secret/Network Broker + webhook + surface + sandbox provider + System Graph projection | real provider sandbox required | threat model + FMEA + independent review + raw-account-data exclusion + forged/replay/duplicate/out-of-order/tamper/timeout/reconcile/3DS/SCA tests; Flow exposes no account data |
| `COMMERCE-200` | catalog/cart/checkout/functions | payment-secure provider/fulfillment | required | cannot bypass Payment Security gate |
| `CRM-MEMBERSHIP-HELPDESK-CLOSURE-001` | domain/auth | business workflows | required | entitlement/SLA/history |
| `PORTAL-200` | account/auth | commerce/CRM/member | required | customer/tenant isolation |
| `COLLAB-200` | concurrency/locks | presence/comments/approval | required | conflict/audit |
| `MANAGED-CLOUD-100` | provisioning/policy/graph-provider | domain/SSL/CDN/backup/deploy/topology | managed target | isolation/metering/restore/scaling + restricted deployment Flow |
| `ENTERPRISE-CLOUD-CLOSURE-001` | tenancy/HA | org/SSO/runtime | required | multi-node/failover |
| `SENTINEL-200` | advisory/isolation/revocation | marketplace/app/payment package + Flow findings | required | real isolation + emergency revoke |
| `ENTERPRISE-GOV-200` | policy/SSO/SCIM | org/governance | required | privilege/impersonation |
| `OBSERVABILITY-200` | logs/metrics/traces + canonical graph IDs | platform/AI/security/perf/reliability/Flow | required | secret-safe incident diagnostics; no competing topology truth |
| `EFFICIENCY-FINOPS-100` | cost/resource schema | telemetry/provider/System Graph attribution | representative | tenant-safe budgets/anomalies + Flow overlays |
| `DR-PLATFORM-100` | update/backup/restore | rollback/restore rehearsal + incident/recovery graph | required | integrity/recovery |

## Production

| Stage | Required evidence |
|---|---|
| `PERF-CWV-CERT-100` | exact release/profile CWV/frontend/backend/package/code-quality regression evidence |
| `A11Y-CERT-100` | keyboard/screen-reader/contrast/RTL/international evidence |
| `RELEASE-CERT-100` | exact-source dependency/browser/DB/security/reliability/backup/restore/HA/package evidence; reviewed/promoted source/build identity consistency; release-critical System Graph identities/drift/test-evidence controls; payment-enabled releases also require current payment-provider/Flow evidence |
| `N2-STABLE-100` | all applicable prior production gates PASS for intended release/deployment capabilities |

## Evidence authority examples

AI-development and Flow evidence must preserve producer/provenance distinctions.

Examples that remain separate:

- plan declaration;
- AI hypothesis (`ai-inferred`);
- static analyzer finding;
- controlled test result;
- CI build result on exact SHA;
- target runtime observation;
- provider sandbox evidence;
- production-observed telemetry;
- reviewer approval on exact head;
- artifact provenance/attestation.

A developer/AI-authored Markdown/JSON statement about one of these cannot substitute for the authoritative producer.

## Evidence naming

Use explicit categories: `research_evidence`, `ctq_evidence`, `development_units`, `ai_development_runs`, `scope_leases`, `scope_delta_checks`, `test_oracle_checks`, `evidence_attestations`, `review_checks`, `waiver_checks`, `dependency_intake_checks`, `repository_policy_checks`, `architecture_checks`, `data_flow_checks`, `system_graph_checks`, `flow_checks`, `source_checks`, `integration_checks`, `target_checks`, `security_checks`, `fmea_checks`, `privacy_checks`, `payment_checks`, `performance_checks`, `code_quality_checks`, `reliability_checks`, `ai_evals`, `outcome_checks`, `known_gaps`.

Unexecuted evidence is `NOT_RUN`, `NOT_APPLICABLE` or `UNKNOWN`, never omitted as if PASS.
