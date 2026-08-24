# Nexora Canonical Stage Graph

Stable semantic stage IDs are canonical. Historical `N0.x`, `N1.x`, `DEV-x` labels are aliases/context only.

## Execution rules

1. Only one implementation stage is active at a time unless the user explicitly authorizes a non-conflicting parallel track.
2. Every system/module/feature/package/AI tool must be registered through `.ai/governance/development-intake.md` before implementation.
3. Every substantial active stage must use `.ai/plans/active.md` based on `.ai/plans/plan-template.md`.
4. New roadmap work is inserted before coding begins; AI must not create hidden work outside the plan.
5. A stage cannot become `SOURCE_DONE` until its Definition of Done and source/static evidence pass.
6. A target-dependent stage cannot become `TARGET_VERIFIED` without real-target evidence.
7. Existing downstream code does not allow prerequisite stages to be skipped.
8. Architecture, security, data/migrations, permissions/tenancy, API/SDK, theme/Studio, AI exposure, observability, performance/code quality, tests and rollback are part of feature scope when applicable.
9. Security is continuous. `SECURITY-BASELINE-200` is an early mandatory gate; `SENTINEL-200` is later advanced runtime/package hardening.
10. Historical `N1.x` labels must be resolved through `.ai/roadmap/legacy-aliases.md` before use.

## Release-train intent

- **Builder Beta** — prove Nexora as a secure extensible CMS/site builder first, with performance attribution/budgets built into the platform rather than added after launch.
- **Pro** — add AI-native design/content/development, AEO, migration, optimization and PageSpeed/GTmetrix-class Performance Intelligence.
- **Platform** — productize marketplace, commerce, portals, enterprise/cloud and advanced security/operations.
- **Production** — final performance/accessibility/release certification.

See `.ai/roadmap/release-trains.md` for commercial gates.

## Track A — control plane, runtime and core verification

| # | Stage | Scope | Train | Status | Depends on |
|---:|---|---|---|---|---|
| 0 | `AI-GOV-001` | deterministic `.ai` control plane, registry, capability map, handoff and planning rules | Builder Beta | SOURCE_DONE on AI branch | — |
| 1 | `RUNTIME-CLOSURE-001` | rc.93 live repair, compatibility/readiness proof, rc.94 clean-runtime closure | Builder Beta | ACTIVE / BLOCKED ON REAL TARGET | AI-GOV-001 |
| 2 | `CORE-QA-001` | login/session, direct routes, Admin boot, users/roles/settings/media/core CRUD | Builder Beta | PLANNED | RUNTIME-CLOSURE-001 |
| 3 | `AI-GOV-AUTOMATION-100` | machine-check `.ai` registry/stages/plans/DoD/state/handoff so CI rejects unplanned/inconsistent work | Builder Beta | NEW_REQUIRED | CORE-QA-001 |
| 4 | `ADMIN-UX-CLOSURE-001` | shared Admin design system, responsive behavior, loading/error/destructive states, accessibility baseline | Builder Beta | PLANNED | CORE-QA-001 |
| 5 | `SECURITY-BASELINE-200` | MFA/passkeys plan, CSP/browser hardening, continuous AppSec CI, secrets/dependency scanning, authorization/tenancy matrices, early threat-model program | Builder Beta | NEW_REQUIRED / CRITICAL | AI-GOV-AUTOMATION-100, CORE-QA-001 |
| 6 | `ARCH-BOUNDARY-100` | reconcile architecture constitution with implementation; repository/persistence boundaries; broaden architecture tests | Builder Beta | NEW_REQUIRED | SECURITY-BASELINE-200 |

## Track B — close existing website-platform foundations

| # | Stage | Scope | Train | Status | Depends on |
|---:|---|---|---|---|---|
| 7 | `THEME-CLOSURE-001` | current theme upload → Sentinel → install → preview → activate → switch → rollback → render | Builder Beta | PLANNED | ADMIN-UX-CLOSURE-001, ARCH-BOUNDARY-100 |
| 8 | `EXTENSION-CLOSURE-001` | current extension/app/integration/studio-pack lifecycle, capability grants, dependencies, rollback/uninstall | Builder Beta | PLANNED | THEME-CLOSURE-001, SECURITY-BASELINE-200 |
| 9 | `STUDIO-CLOSURE-001` | current Studio edit/responsive/dynamic/revision/publish/render workflow | Builder Beta | PLANNED | EXTENSION-CLOSURE-001 |
| 10 | `CMS-PUBLISHING-CLOSURE-001` | Documents, Writer, revisions, editorial workflow, Blog/Article, authors, current publishing taxonomies/series/archives | Builder Beta | PLANNED | STUDIO-CLOSURE-001 |
| 11 | `MEDIA-DISTRIBUTION-CLOSURE-001` | media upload/select/use, newsletter, RSS and current distribution adapters | Builder Beta | PLANNED | CMS-PUBLISHING-CLOSURE-001 |
| 12 | `SEO-SEARCH-CLOSURE-001` | current SEO Core, Schema Graph, sitemap, internal links, search, analytics and crawler | Builder Beta | PLANNED | MEDIA-DISTRIBUTION-CLOSURE-001 |
| 13 | `AUTOMATION-CLOSURE-001` | current triggers, conditions, actions, signed inbound/outbound webhooks, retry/evidence | Builder Beta | PLANNED | SEO-SEARCH-CLOSURE-001 |

## Track C — mature CMS/site-builder kernel + performance foundation

| # | Stage | Scope | Train | Status | Depends on |
|---:|---|---|---|---|---|
| 14 | `CONTENT-MODEL-200` | custom content types, typed fields/groups, relations, hierarchy, schema versions, permissions, API/search/Studio policies | Builder Beta | LEGACY_PLANNED + EXPANDED | ARCH-BOUNDARY-100, CMS-PUBLISHING-CLOSURE-001 |
| 15 | `TAXONOMY-200` | generic taxonomy definitions/registry, hierarchy, content-type binding and extension registration | Builder Beta | NEW_REQUIRED | CONTENT-MODEL-200 |
| 16 | `QUERY-ENGINE-200` | typed filters/sort/pagination/relations/taxonomies, saved queries and generic archive queries | Builder Beta | NEW_REQUIRED | CONTENT-MODEL-200, TAXONOMY-200 |
| 17 | `ROUTING-200` | permalink patterns, route resolver, archives, slug collision policy, rewrites, redirects and canonical integration | Builder Beta | NEW_REQUIRED | CONTENT-MODEL-200, TAXONOMY-200, QUERY-ENGINE-200 |
| 18 | `NAVIGATION-100` | public menus, nested items, menu locations, content/taxonomy/custom targets, conditions and API/AI contract | Builder Beta | NEW_REQUIRED | ROUTING-200 |
| 19 | `THEME-CONTRACT-200` | deterministic template hierarchy/parts, manifest 2.0, tokens, menu locations, slots and support flags | Builder Beta | NEW_REQUIRED + LEGACY EXPANSION | NAVIGATION-100, THEME-CLOSURE-001 |
| 20 | `EXT-SDK-200` | typed events/actions, filters/transforms, Admin/UI/theme slots, pages, runtime APIs, content/taxonomy/Studio registration, jobs/commands | Builder Beta | LEGACY_PLANNED + EXPANDED | ARCH-BOUNDARY-100, CONTENT-MODEL-200, TAXONOMY-200, THEME-CONTRACT-200, EXTENSION-CLOSURE-001 |
| 21 | `SITE-BUILDER-200` | visual AST 2.0, reusable components/instances/overrides, dynamic templates/bindings, responsive layout, interactions, history and publish | Builder Beta | LEGACY_PLANNED + EXPANDED | QUERY-ENGINE-200, THEME-CONTRACT-200, EXT-SDK-200, STUDIO-CLOSURE-001 |
| 22 | `THEME-STUDIO-200` | global design system, visual tokens, global sections/template parts and theme-level visual editing | Builder Beta | LEGACY_PLANNED | SITE-BUILDER-200 |
| 23 | `RELEASE-WORKFLOW-200` | preview/staging, page/content branches, conflict/merge, preview URLs, scheduled/multi-page/single-page publish, promotion and rollback history | Builder Beta | NEW_REQUIRED / CRITICAL | SITE-BUILDER-200, THEME-STUDIO-200, ROUTING-200 |
| 24 | `TEMPLATE-ECOSYSTEM-100` | site/page/section/component/theme/commerce starter kits, dependency-aware one-click install, safe customization/update model | Builder Beta | NEW_REQUIRED / COMMERCIAL | THEME-STUDIO-200, EXT-SDK-200 |
| 25 | `I18N-200` | site locales, localized static/CMS/component content, localized SEO/hreflang and translation workflow | Builder Beta | LEGACY_PLANNED + EXPANDED | CONTENT-MODEL-200, THEME-STUDIO-200, RELEASE-WORKFLOW-200 |
| 26 | `FRONTEND-RUNTIME-200` | frontend cache/CDN/image pipeline, render budgets, invalidation and delivery runtime | Builder Beta | LEGACY_PLANNED | THEME-CONTRACT-200, SITE-BUILDER-200 |
| 27 | `PERFORMANCE-FOUNDATION-200` | provider-neutral lab/RUM/server profiling primitives, Admin/backend traces, DB/cache/network/memory metrics, Theme/Extension/asset attribution, budgets and regression baselines | Builder Beta | NEW_REQUIRED / EXPANDS LEGACY PERFORMANCE | FRONTEND-RUNTIME-200, THEME-CONTRACT-200, EXT-SDK-200, SECURITY-BASELINE-200 |
| 28 | `CODE-QUALITY-200` | Core/Theme/Extension/App static/type/lint/complexity/duplication/dead-code/bundle quality with runtime-cost correlation and package quality profiles | Builder Beta | NEW_REQUIRED | ARCH-BOUNDARY-100, EXT-SDK-200, PERFORMANCE-FOUNDATION-200 |
| 29 | `MEDIA-DAM-200` | asset organization/usage graph, transforms, dedupe, metadata/rights and external storage product closure | Builder Beta | LEGACY_PLANNED + EXPANDED | FRONTEND-RUNTIME-200, MEDIA-DISTRIBUTION-CLOSURE-001 |
| 30 | `SEARCH-200` | facets, provider abstraction and advanced indexing/querying for content/commerce | Builder Beta | LEGACY_PLANNED | CONTENT-MODEL-200, TAXONOMY-200, QUERY-ENGINE-200 |
| 31 | `FORMS-WORKFLOW-200` | form builder, validation, spam/rate controls, lead capture and visual workflow integration | Builder Beta | LEGACY_PLANNED | SITE-BUILDER-200, EXT-SDK-200, AUTOMATION-CLOSURE-001 |
| 32 | `PRIVACY-CONSENT-100` | consent manager, cookie categories, analytics/marketing consent, GPC/DNT, retention/export/delete hooks and regional policy integration including RUM telemetry policy | Builder Beta | NEW_REQUIRED | SECURITY-BASELINE-200, I18N-200, FORMS-WORKFLOW-200, PERFORMANCE-FOUNDATION-200 |

## Track D — AI-native Pro platform, discoverability and Performance Intelligence

| # | Stage | Scope | Train | Status | Depends on |
|---:|---|---|---|---|---|
| 33 | `AI-KERNEL-100` | model gateway, agent runtime, Tool Registry, capability gate, context engine, structured executor, approvals, audit, prompt registry, evals and telemetry | Pro | NEW_REQUIRED / LEGACY AI FOUNDATION | CONTENT-MODEL-200, EXT-SDK-200, SECURITY-BASELINE-200 |
| 34 | `SEO-AI-200` | SEO 2.0 + AEO/AI visibility: expanded resource adapters, entity/schema intelligence, AI-readable representations, crawler/citation visibility workflows | Pro | LEGACY_PLANNED + EXPANDED | ROUTING-200, SEARCH-200, AI-KERNEL-100, SEO-SEARCH-CLOSURE-001 |
| 35 | `API-PLATFORM-100` | versioned REST/GraphQL, OAuth/scopes, webhook subscriptions, headless delivery and extension APIs | Pro | LEGACY_PLANNED | CONTENT-MODEL-200, TAXONOMY-200, QUERY-ENGINE-200, EXT-SDK-200, SECURITY-BASELINE-200 |
| 36 | `CONFIG-AS-CODE-100` | import/export, schema/site/config serialization, validate/diff/apply and environment-safe configuration | Pro | LEGACY_PLANNED | API-PLATFORM-100 |
| 37 | `AGENT-INTEROP-100` | external AI agent gateway with OAuth/scoped identity, capability negotiation, typed tools, read/draft/execute policies and audit; protocol adapters remain replaceable | Pro | NEW_REQUIRED / FUTURE-CRITICAL | AI-KERNEL-100, API-PLATFORM-100, SECURITY-BASELINE-200 |
| 38 | `AI-CONTENT-100` | governed AI CMS/media/SEO/AEO assistance through typed tools and evidence | Pro | NEW_REQUIRED | AI-KERNEL-100, SEO-AI-200 |
| 39 | `AI-DESIGN-100` | brief → IA → content model → navigation → tokens → components → visual AST → responsive/a11y validation → draft/publish | Pro | NEW_REQUIRED | AI-KERNEL-100, SITE-BUILDER-200, THEME-STUDIO-200, NAVIGATION-100, RELEASE-WORKFLOW-200 |
| 40 | `DESIGN-IMPORT-100` | Figma/design-source import into tokens/components/layout AST with responsive inference and validation, never raw trusted HTML | Pro | NEW_REQUIRED | THEME-STUDIO-200, SITE-BUILDER-200 |
| 41 | `AI-DX-100` | SDK-aware app/extension/theme scaffolding, architecture/security/code-quality review, tests/evals and package-development assistance | Pro | NEW_REQUIRED | AI-KERNEL-100, EXT-SDK-200, API-PLATFORM-100, AI-GOV-AUTOMATION-100, CODE-QUALITY-200 |
| 42 | `PERFORMANCE-INTELLIGENCE-200` | PageSpeed/GTmetrix-class Performance Center: lab + field Web Vitals, waterfall, filmstrip/video, user-flow profiles, frontend/backend traces, Theme/Extension attribution, code-quality correlation, compare/history/monitoring/alerts and secure external runners | Pro | NEW_REQUIRED / DIFFERENTIATOR | PERFORMANCE-FOUNDATION-200, CODE-QUALITY-200, RELEASE-WORKFLOW-200, API-PLATFORM-100, AI-KERNEL-100 |
| 43 | `EXPERIMENTATION-100` | A/B and multivariate tests, goals, safe rollout, variant analytics and AI-assisted variant generation/analysis including performance impact | Pro | NEW_REQUIRED | RELEASE-WORKFLOW-200, FRONTEND-RUNTIME-200, PRIVACY-CONSENT-100, PERFORMANCE-INTELLIGENCE-200 |
| 44 | `PERSONALIZATION-100` | audience/segment rules, personalized components/content with privacy-safe evaluation and deterministic fallback | Pro | NEW_REQUIRED / FUTURE | EXPERIMENTATION-100, PRIVACY-CONSENT-100, QUERY-ENGINE-200 |
| 45 | `APP-RUNTIME-100` | secure low-code/full-stack app functions, jobs, schedules, data actions, integration calls and secrets through capability-bounded runtime rather than arbitrary Core access | Pro | NEW_REQUIRED / FUTURE | EXT-SDK-200, SECURITY-BASELINE-200, API-PLATFORM-100 |
| 46 | `MIGRATION-CENTER-100` | WordPress/Webflow/Drupal/Shopify adapters, dry-run/loss report, redirects, idempotent retries and SEO-safe migration | Pro | LEGACY_PLANNED + EXPANDED | CONFIG-AS-CODE-100, ROUTING-200, MEDIA-DAM-200 |
| 47 | `DX-200` | CLI/SDK/docs/local dev, reference apps/themes/extensions, package generators, compatibility and performance/quality tooling | Pro | LEGACY_PLANNED | EXT-SDK-200, API-PLATFORM-100, MIGRATION-CENTER-100, CODE-QUALITY-200 |

## Track E — marketplace, commerce and customer platform

| # | Stage | Scope | Train | Status | Depends on |
|---:|---|---|---|---|---|
| 48 | `MARKETPLACE-CLOSURE-001` | verify current catalogs, publisher trust and staging → quarantine → Sentinel → install | Platform | PLANNED | EXTENSION-CLOSURE-001, SECURITY-BASELINE-200 |
| 49 | `MARKETPLACE-200` | publisher economy, discovery, ratings/reviews, compatibility, licensing/subscriptions, safe updates and reproducible package quality/performance profiles | Platform | LEGACY_PLANNED + EXPANDED | EXT-SDK-200, MARKETPLACE-CLOSURE-001, CODE-QUALITY-200, PERFORMANCE-INTELLIGENCE-200 |
| 50 | `COMMERCE-CLOSURE-001` | verify current product/price/tax/customer/order/invoice/payment/refund/subscription foundation | Platform | PLANNED | SECURITY-BASELINE-200 |
| 51 | `COMMERCE-200` | variants/inventory/discounts/cart/secure checkout, functions, checkout/account slots, providers and fulfillment | Platform | LEGACY_PLANNED + EXPANDED | EXT-SDK-200, SITE-BUILDER-200, API-PLATFORM-100, COMMERCE-CLOSURE-001 |
| 52 | `CRM-MEMBERSHIP-HELPDESK-CLOSURE-001` | verify current CRM, entitlement/access and tickets/SLA workflows | Platform | PLANNED | CORE-QA-001, SECURITY-BASELINE-200 |
| 53 | `PORTAL-200` | authenticated customer/member portal builder across commerce/CRM/membership/helpdesk | Platform | LEGACY_PLANNED | COMMERCE-200, SITE-BUILDER-200, CRM-MEMBERSHIP-HELPDESK-CLOSURE-001 |
| 54 | `COLLAB-200` | presence, locks, comments, approvals and workflow-aware collaborative editing | Platform | LEGACY_PLANNED | SITE-BUILDER-200, RELEASE-WORKFLOW-200, PORTAL-200 |

## Track F — managed cloud, enterprise, advanced security and operations

| # | Stage | Scope | Train | Status | Depends on |
|---:|---|---|---|---|---|
| 55 | `MANAGED-CLOUD-100` | optional Nexora Managed Cloud: site provisioning, domains/SSL, CDN, backups, staging/deploy history, distributed performance runners, monitoring, usage/metering and autoscaling policy | Platform | NEW_REQUIRED / COMMERCIAL | FRONTEND-RUNTIME-200, RELEASE-WORKFLOW-200, PERFORMANCE-INTELLIGENCE-200, OBSERVABILITY prerequisites |
| 56 | `ENTERPRISE-CLOUD-CLOSURE-001` | verify existing organizations/tenancy/SSO/SCIM/governance and distributed runtime/HA foundations | Platform | PLANNED | SECURITY-BASELINE-200, CRM-MEMBERSHIP-HELPDESK-CLOSURE-001 |
| 57 | `SENTINEL-200` | advisory/vulnerability intelligence, stronger package policy, emergency revocation and true isolated runtime backend strategy | Platform | LEGACY_PLANNED + EXPANDED | EXT-SDK-200, MARKETPLACE-200, APP-RUNTIME-100 |
| 58 | `ENTERPRISE-GOV-200` | policy center, SSO/SCIM product closure, audit/impersonation controls and tenancy hardening | Platform | LEGACY_PLANNED | ENTERPRISE-CLOUD-CLOSURE-001, SENTINEL-200 |
| 59 | `OBSERVABILITY-200` | operations center for logs/metrics/traces/jobs/queues/health/AI/package/security evidence, consuming shared performance telemetry contracts rather than duplicating them | Platform | LEGACY_PLANNED + EXPANDED | ENTERPRISE-GOV-200, PERFORMANCE-FOUNDATION-200 |
| 60 | `DR-PLATFORM-100` | application/package updates, atomic rollback, backup/restore, DR rehearsal and evidence | Platform | LEGACY_PLANNED | OBSERVABILITY-200 |

## Track G — production certification

| # | Stage | Scope | Train | Status | Depends on |
|---:|---|---|---|---|---|
| 61 | `PERF-CWV-CERT-100` | final performance certification across Core Web Vitals, frontend delivery, backend/query/cache/memory budgets, Theme/Extension impact and release-regression evidence | Production | LEGACY_PLANNED + EXPANDED | DR-PLATFORM-100, FRONTEND-RUNTIME-200, PERFORMANCE-INTELLIGENCE-200, CODE-QUALITY-200 |
| 62 | `A11Y-CERT-100` | accessibility/international certification including keyboard, screen-reader, contrast and RTL | Production | LEGACY_PLANNED | PERF-CWV-CERT-100, I18N-200 |
| 63 | `RELEASE-CERT-100` | final C1-C6 exact-source dependency/browser/DB/security/backup/restore/HA/package certification | Production | DEFERRED_CERTIFICATION | A11Y-CERT-100, SENTINEL-200, DR-PLATFORM-100 |
| 64 | `N2-STABLE-100` | stable production platform release | Production | BLOCKED | RELEASE-CERT-100 |

## External first-party package track

These packages remain outside Core and are acceptance tests of the public extension architecture. They must be separately registered/planned before implementation and may not use private Core shortcuts.

- `EXT-B01` Books / Manuscripts / Editions / EPUB / print.
- `EXT-P01` Professional Profile / CV / Resume / Biography / portfolio.
- `EXT-L01` LMS / Courses / Lessons / Quizzes / Progress.
- `EXT-BK01` Booking / Services / Staff / Availability / Appointments.
- `EXT-PR01` Projects / Tasks / Boards / Milestones / Time tracking.

## Mandatory pre-implementation plan gate

Before coding any stage/unit, the active plan must identify:

1. registered development-unit IDs;
2. exact capability scope;
3. dependencies/preconditions;
4. existing implementation to preserve;
5. architecture/contracts/ADR impact;
6. data/migrations/fresh-install/upgrade impact;
7. permissions/capabilities/tenancy;
8. security risk/threat model;
9. privacy/consent/retention;
10. UI/UX/accessibility;
11. API/webhook/SDK;
12. theme/Studio/extension surfaces;
13. AI read/draft/execute/tool policy;
14. observability/performance/code-quality/budget impact;
15. tests/evals;
16. exact target verification;
17. rollback/recovery/update compatibility;
18. docs/handoff;
19. explicit acceptance criteria;
20. explicit out-of-scope items.

Performance-affecting work must explicitly state its budget/test profile or state `NOT_APPLICABLE` with reason. Theme/Extension/App work must state how its frontend/backend/runtime cost will be measured or why the unit cannot affect runtime performance.

## Current cursor

Planning changes do not bypass the live blocker. The active cursor remains `RUNTIME-CLOSURE-001`.

1. repair the installed rc.93 post-install identity state without overwriting it with rc.94;
2. run `php artisan nexora:runtime:compatibility-status --deep`;
3. require `status=pass`, `mismatches=[]`, `compatible=true`, `mode=installed-data-plane`;
4. run `php artisan nexora:runtime:post-install-status --assert-ready`;
5. proceed to `/login` and `CORE-QA-001` only after those target gates pass.