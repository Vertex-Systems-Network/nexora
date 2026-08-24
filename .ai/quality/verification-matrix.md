# Nexora Verification Matrix

Minimum evidence classes before a stage/unit advances. Source, target, provider and post-release evidence remain distinguishable.

## Builder Beta / foundation stages

| Stage | Source/static evidence | Integration evidence | Target/evidence | Special evidence |
|---|---|---|---|---|
| `RUNTIME-CLOSURE-001` | runtime/source guards | compatibility/readiness | real target required | exact identity-plane output |
| `CORE-QA-001` | tests/typecheck | auth/roles/settings/media CRUD | browser/target required | direct URL/session persistence |
| `AI-GOV-AUTOMATION-100` | schema/registry/stage validators | broken fixtures rejected | CI required | unregistered/stale/inconsistent work fails |
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
| `MEDIA-DAM-200` | asset/transform/dedupe | content/Studio/storage | required | usage/rights/delivery |
| `SEARCH-200` | index/facet/provider | content/commerce | required | ranking/privacy/performance |
| `FORMS-WORKFLOW-200` | validation/abuse | lead/provider/automation | required | rate/consent evidence |
| `PRIVACY-CONSENT-100` | consent/policy | analytics/forms/RUM | required | GPC/DNT/export/delete/retention |

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
| `AI-DX-100` | scaffold/review/quality | generated package -> SDK/Sentinel | representative | independent review |
| `PERFORMANCE-INTELLIGENCE-200` | report/runner/provider/alert | lab + RUM + backend + package | required | waterfall/flow/history/secure URL runner |
| `RELIABILITY-ENGINEERING-200` | SLI/SLO/retry/idempotency contracts | fault/provider/degradation/recovery | representative targets | error-budget/fault/reconciliation evidence |
| `EXPERIMENTATION-100` | assignment/statistics/goals | release/privacy/performance | required | deterministic rollout/rollback |
| `PRODUCT-OUTCOMES-100` | outcome schema/privacy | CTQ -> adoption/task-success/feedback | observation evidence | no fabricated future outcome; privacy-safe |
| `PERSONALIZATION-100` | segment/rule/fallback | query/content/privacy | required | no tenant/segment leakage |
| `APP-RUNTIME-100` | capability/broker | functions/jobs/network/secrets | required | isolation/egress/tenant abuse |
| `MIGRATION-CENTER-100` | adapter/idempotency | dry-run/import | required | loss/redirect/retry |
| `DX-200` | CLI/SDK/reference | clean external workflow | representative | docs/compatibility |
| `DELIVERY-EXCELLENCE-100` | metric definitions/collectors | CI/PR/release events | observed pipeline | change lead/failure/recovery/rework + anti-gaming |

## Platform / payments / operations stages

| Stage | Source/static evidence | Integration evidence | Target/evidence | Special evidence |
|---|---|---|---|---|
| `MARKETPLACE-CLOSURE-001` | catalog/stager/security | quarantine/install | required | publisher/signature/digest |
| `MARKETPLACE-200` | license/compat/update/profile | publisher/package lifecycle | required | revocation + reproducible quality/security/perf |
| `COMMERCE-CLOSURE-001` | domain/payment-adapter foundation | order/invoice/refund/subscription | required | minor-unit money/idempotency/provider boundary |
| `PAYMENT-SECURITY-200` | payment manifest/capability/state/data/leak tests | Secret/Network Broker + webhook + surface + sandbox provider | real provider sandbox required | threat model + FMEA + independent review + raw-account-data exclusion + forged/replay/duplicate/out-of-order/tamper/timeout/reconcile/3DS/SCA tests |
| `COMMERCE-200` | catalog/cart/checkout/functions | payment-secure provider/fulfillment | required | cannot bypass Payment Security gate |
| `CRM-MEMBERSHIP-HELPDESK-CLOSURE-001` | domain/auth | business workflows | required | entitlement/SLA/history |
| `PORTAL-200` | account/auth | commerce/CRM/member | required | customer/tenant isolation |
| `COLLAB-200` | concurrency/locks | presence/comments/approval | required | conflict/audit |
| `MANAGED-CLOUD-100` | provisioning/policy | domain/SSL/CDN/backup/deploy | managed target | isolation/metering/restore/scaling |
| `ENTERPRISE-CLOUD-CLOSURE-001` | tenancy/HA | org/SSO/runtime | required | multi-node/failover |
| `SENTINEL-200` | advisory/isolation/revocation | marketplace/app/payment package | required | real isolation + emergency revoke |
| `ENTERPRISE-GOV-200` | policy/SSO/SCIM | org/governance | required | privilege/impersonation |
| `OBSERVABILITY-200` | logs/metrics/traces | platform/AI/security/perf/reliability | required | secret-safe incident diagnostics |
| `EFFICIENCY-FINOPS-100` | cost/resource schema | telemetry/provider attribution | representative | tenant-safe budgets/anomalies |
| `DR-PLATFORM-100` | update/backup/restore | rollback/restore rehearsal | required | integrity/recovery |

## Production

| Stage | Required evidence |
|---|---|
| `PERF-CWV-CERT-100` | exact release/profile CWV/frontend/backend/package/code-quality regression evidence |
| `A11Y-CERT-100` | keyboard/screen-reader/contrast/RTL/international evidence |
| `RELEASE-CERT-100` | exact-source dependency/browser/DB/security/reliability/backup/restore/HA/package evidence; payment-enabled releases also require current payment-provider evidence |
| `N2-STABLE-100` | all applicable prior production gates PASS for intended release/deployment capabilities |

## Evidence naming

Use explicit categories: `research_evidence`, `ctq_evidence`, `development_units`, `architecture_checks`, `data_flow_checks`, `source_checks`, `integration_checks`, `target_checks`, `security_checks`, `fmea_checks`, `privacy_checks`, `payment_checks`, `performance_checks`, `code_quality_checks`, `reliability_checks`, `ai_evals`, `outcome_checks`, `known_gaps`.

Unexecuted evidence is `NOT_RUN`, `NOT_APPLICABLE` or `UNKNOWN`, never omitted as if PASS.
