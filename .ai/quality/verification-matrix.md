# Nexora Verification Matrix

This matrix defines minimum evidence classes before a stage/unit can advance. It guards against skipped product workflows and source-only completion claims.

## Core / Builder Beta stages

| System / stage | Source/static evidence | Integration evidence | Real-target/browser evidence | Special evidence |
|---|---|---|---|---|
| `RUNTIME-CLOSURE-001` | runtime/source guards | compatibility/readiness commands | required | exact identity-plane output |
| `CORE-QA-001` | tests/typecheck as affected | auth/roles/settings/media CRUD | required | direct URL + session persistence |
| `AI-GOV-AUTOMATION-100` | schema/registry/stage validators | intentionally broken control-plane fixtures rejected | CI required | unregistered/stale/inconsistent work must fail |
| `ADMIN-UX-CLOSURE-001` | component/type tests | shared Admin shell flows | required | responsive + appearance + accessibility baseline |
| `SECURITY-BASELINE-200` | SAST/dependency/secret/security tests | authorization/tenancy/browser-policy integration | required on disposable/real targets as applicable | threat-model evidence, MFA/CSP/security-CI evidence |
| `ARCH-BOUNDARY-100` | architecture tests | boundary integration | source evidence + target where runtime affected | ADR/exception evidence |
| `THEME-CLOSURE-001` | manifest/security/tests | full theme lifecycle | required | preview/activation/rollback/public render |
| `EXTENSION-CLOSURE-001` | manifest/capability/security/tests | full extension lifecycle | required | package family + runtime mode + migration policy |
| `STUDIO-CLOSURE-001` | visual-tree/schema/tests | document/theme bindings | required | responsive + revision/publish |
| `CMS-PUBLISHING-CLOSURE-001` | document/editorial/publishing tests | CRUD/revisions/taxonomy/archive | required | permalink/render/SEO integration |
| `MEDIA-DISTRIBUTION-CLOSURE-001` | media/distribution tests | upload/use/newsletter/RSS | required | public-media policy/delivery |
| `SEO-SEARCH-CLOSURE-001` | SEO/search/crawler tests | schema/sitemap/index/search | required | rendered metadata + crawler evidence |
| `AUTOMATION-CLOSURE-001` | trigger/action/webhook tests | retries/idempotency/signatures | target where network/runtime matters | replay/private-network protections |
| `CONTENT-MODEL-200` | schema/field/relation tests | Admin/API/extension/Studio integration | required | fresh install/upgrade/schema-version evidence |
| `TAXONOMY-200` | definition/hierarchy tests | content binding + extension registration | required | permission/routing integration |
| `QUERY-ENGINE-200` | typed query/parser tests | content/taxonomy/relation/archive queries | required | authorization + performance evidence |
| `ROUTING-200` | resolver/collision/redirect tests | content/taxonomy/archive/canonical routes | required | permalink migration/redirect evidence |
| `NAVIGATION-100` | menu definition tests | theme/Studio/API/AI bindings | required | nested/conditional/location behavior |
| `THEME-CONTRACT-200` | manifest/template hierarchy tests | theme/Studio/navigation/content integration | required | deterministic fallback/slot behavior |
| `EXT-SDK-200` | SDK contract/capability tests | first-party reference package integration | required for full closure | no private Core shortcut, compatibility evidence |
| `SITE-BUILDER-200` | AST/component/binding tests | theme/query/content/extension integration | required | responsive/interactions/history/accessibility |
| `THEME-STUDIO-200` | token/component/global-template tests | site-wide visual integration | required | inheritance/override consistency |
| `RELEASE-WORKFLOW-200` | branch/release state tests | preview/staging/merge/publish/rollback | required | conflict/selective/scheduled publish evidence |
| `TEMPLATE-ECOSYSTEM-100` | package/dependency/update tests | one-click starter install/customize/update | required | derived customization/upstream update behavior |
| `I18N-200` | locale/translation tests | CMS/theme/SEO/release integration | required | hreflang/RTL/per-locale publish |
| `FRONTEND-RUNTIME-200` | cache/image/CDN/invalidation tests | render/delivery integration | required | CWV/cache correctness/security headers |
| `PERFORMANCE-FOUNDATION-200` | result/profile/budget/attribution contract tests | browser + Admin + server + DB/cache/package correlation | required | intentional frontend/backend/package regressions detected; profiler overhead/redaction evidence |
| `CODE-QUALITY-200` | analyzer/provider/finding tests | Core/Theme/Extension/App source/build analysis + runtime correlation | representative source/build evidence required | complexity/duplication/dead-code/bundle findings reproducible; security verdict kept separate |
| `MEDIA-DAM-200` | asset/transform/dedupe tests | content/Studio/storage integration | required | usage graph/rights/delivery evidence |
| `SEARCH-200` | index/facet/provider tests | content/commerce provider integration | required | ranking/facet/privacy/performance |
| `FORMS-WORKFLOW-200` | validation/spam/workflow tests | lead/provider/automation integration | required | abuse/rate-limit/consent evidence |
| `PRIVACY-CONSENT-100` | consent/policy tests | analytics/forms/experiment/RUM integration | required | GPC/DNT/export/delete/retention evidence |

## Pro / AI-native stages

| System / stage | Source/static evidence | Integration evidence | Real-target/browser evidence | Special evidence |
|---|---|---|---|---|
| `AI-KERNEL-100` | tool schema/policy/eval tests | model/context/tool/approval/audit integration | required for execute paths | prompt injection/data leakage/excessive-agency evals |
| `SEO-AI-200` | SEO/AEO/entity/representation tests | crawler/search/AI integration | required | AI-readable output must exclude private data; citation/visibility evidence |
| `API-PLATFORM-100` | schema/auth/rate tests | REST/GraphQL/OAuth/webhooks/headless | required | tenancy/IDOR/fuzzing/versioning evidence |
| `CONFIG-AS-CODE-100` | serializer/schema/diff tests | export/apply/rollback integration | required | environment-safe/idempotent evidence |
| `AGENT-INTEROP-100` | protocol/tool/auth tests | external agent -> gateway -> Tool Registry | required | scoped identity, approval, audit, injection/tool-misuse evidence |
| `AI-CONTENT-100` | prompt/tool/output/eval tests | CMS/media/SEO draft/action integration | required | review/approval/audit/schema validity |
| `AI-DESIGN-100` | design AST/token/component evals | AI -> Studio/theme/release integration | required | responsive/accessibility/review/rollback evidence |
| `DESIGN-IMPORT-100` | parser/mapping/security tests | source design -> tokens/components/AST | required | no trusted raw executable markup |
| `AI-DX-100` | scaffold/review/eval/code-quality tests | generated package -> SDK/Sentinel/quality/performance tests | required for representative package | independent review + no unregistered implementation |
| `PERFORMANCE-INTELLIGENCE-200` | canonical report/runner/provider/monitor/alert tests | lab + RUM + backend + code-quality + release/package integration | required | mobile/desktop reports, waterfall, filmstrip/video or runner equivalent, scripted flow, history/alert, secure external URL test, AI evidence grounding |
| `EXPERIMENTATION-100` | assignment/statistics/goal tests | release/analytics/privacy/performance integration | required | deterministic rollout/rollback/consent + performance impact evidence |
| `PERSONALIZATION-100` | segment/rule/fallback tests | query/content/privacy integration | required | no cross-tenant/segment leakage |
| `APP-RUNTIME-100` | capability/runtime/broker tests | functions/jobs/schedules/network/secrets | required | isolation/egress/secret/tenant abuse evidence |
| `MIGRATION-CENTER-100` | adapter/parser/idempotency tests | source -> Nexora dry-run/import | required | loss report/redirect/SEO/retry evidence |
| `DX-200` | CLI/SDK/reference package/quality tooling tests | clean external developer workflow | required for representative environment | docs/compatibility/reference/performance evidence |

## Platform / operations stages

| System / stage | Source/static evidence | Integration evidence | Real-target/browser evidence | Special evidence |
|---|---|---|---|---|
| `MARKETPLACE-CLOSURE-001` | catalog/stager/security tests | stage -> quarantine -> install | required | publisher/signature/digest evidence |
| `MARKETPLACE-200` | licensing/compat/update/quality-profile tests | publisher/package lifecycle + performance profile | required | revocation/rollback/transparency + reproducible package quality/performance evidence |
| `COMMERCE-CLOSURE-001` | domain/payment adapter tests | order/invoice/refund/subscription | required | money/tax/idempotency/provider boundaries |
| `COMMERCE-200` | catalog/cart/checkout/function tests | storefront/provider/fulfillment integration | required | payment/checkout extension security evidence |
| `CRM-MEMBERSHIP-HELPDESK-CLOSURE-001` | domain/authorization tests | end-to-end business workflows | required | entitlement/SLA/history evidence |
| `PORTAL-200` | authorization/account tests | commerce/CRM/membership/helpdesk | required | customer/tenant isolation evidence |
| `COLLAB-200` | concurrency/lock/permission tests | presence/comments/approval/release | required | conflict/audit evidence |
| `MANAGED-CLOUD-100` | provisioning/policy/IaC tests | domains/SSL/CDN/backups/deploy/monitoring/distributed performance runners | required in managed environment | isolation, metering, restore, scaling and runner-egress evidence |
| `ENTERPRISE-CLOUD-CLOSURE-001` | tenancy/HA/source tests | organization/SSO/runtime integration | required | multi-node/shared-state/failover |
| `SENTINEL-200` | advisory/policy/isolation tests | marketplace/app-runtime integration | required | real isolation + emergency revocation evidence |
| `ENTERPRISE-GOV-200` | policy/SSO/SCIM/audit tests | org/identity/governance integration | required | privilege/impersonation/tenant evidence |
| `OBSERVABILITY-200` | log/metric/trace schema tests | app/jobs/AI/security/performance/ops integration | required | no secret leakage + incident diagnostics + no duplicate telemetry source of truth |
| `DR-PLATFORM-100` | update/backup/restore tests | disposable-target restore/rollback | required | integrity/recovery/rehearsal evidence |

## Production stages

| Stage | Required evidence |
|---|---|
| `PERF-CWV-CERT-100` | exact release/test-profile evidence for field/lab CWV, frontend delivery, backend/DB/cache/memory budgets, Theme/Extension impact, code-quality policy and release-regression comparisons |
| `A11Y-CERT-100` | keyboard/screen-reader/contrast/RTL/international evidence |
| `RELEASE-CERT-100` | exact-source dependency/browser/database/security/backup/restore/HA/package evidence |
| `N2-STABLE-100` | prior production gates PASS for intended release source |

## Evidence naming

Every meaningful handoff should record:

- `development_units`
- `source_checks`
- `integration_checks`
- `target_checks`
- `security_checks`
- `privacy_checks`
- `performance_checks`
- `code_quality_checks`
- `ai_evals`
- `manual_browser_checks`
- `known_gaps`

If a class of evidence was not executed, record `NOT_RUN` or `NOT_APPLICABLE`; never omit it in a way that could be mistaken for PASS.
