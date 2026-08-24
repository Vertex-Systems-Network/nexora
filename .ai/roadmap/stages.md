# Nexora Canonical Stage Graph

This file controls forward execution. Stable semantic stage IDs are canonical; historical `N0.x`, `N1.x`, `DEV-x` names are aliases only.

## Rules

1. Only one execution stage is active at a time unless the user explicitly authorizes a parallel non-conflicting track.
2. A stage cannot move to `SOURCE_DONE` until its source Definition of Done is met.
3. A stage cannot move to `TARGET_VERIFIED` without real-target evidence where behavior is target-dependent.
4. A later stage may already contain source code, but that does not allow the execution cursor to skip unmet prerequisites.
5. New roadmap items are inserted explicitly with dependencies; agents must not invent hidden work.
6. The active cursor lives in `.ai/state.json`.
7. Historical milestone names are context only. Never execute a bare ambiguous `N1.x` instruction without resolving it through `.ai/roadmap/legacy-aliases.md`.
8. A broad product stage must be broken into executable tasks before coding begins.
9. A stage closes only when its capability lifecycle is usable end-to-end, not merely because a database table/controller/screen exists.
10. Architecture, security, migration, tests, docs, API/AI exposure and rollback/recovery requirements are part of feature work when applicable, not optional later cleanup.

## Track A — AI control plane + existing platform closure

This track verifies and closes the substantial platform foundations already present in source before large 2.0 expansion work begins.

| Order | Stable stage ID | Scope | Imported source | Status | Depends on |
|---:|---|---|---|---|---|
| 0 | `AI-GOV-001` | `.ai` control plane, deterministic handoff, system registry, capability matrix, stage graph | AI governance baseline | SOURCE_DONE on this branch | — |
| 1 | `RUNTIME-CLOSURE-001` | rc.93 live identity repair, compatibility PASS, post-install readiness, rc.94 clean-runtime closure | legacy `N1.0A`, `DEV-3` | ACTIVE / BLOCKED ON REAL TARGET | AI-GOV-001 |
| 2 | `CORE-QA-001` | Super Admin login/session, direct routes, Admin boot, auth/users/roles/settings/media/core CRUD | legacy `N1.0B`, `DEV-4` | PLANNED | RUNTIME-CLOSURE-001 |
| 3 | `ADMIN-UX-CLOSURE-001` | shared Admin design-system consistency, responsive behavior, appearance, errors, accessibility baseline | legacy AI roadmap `N1.1` | PLANNED | CORE-QA-001 |
| 4 | `THEME-CLOSURE-001` | current theme upload → Sentinel → install → preview → activate → switch → rollback → render | N0.20 foundation | PLANNED | ADMIN-UX-CLOSURE-001 |
| 5 | `EXTENSION-CLOSURE-001` | current extension/app/integration/studio-pack lifecycle, capabilities, dependencies, enable/disable/rollback/uninstall | N0.29 foundation | PLANNED | THEME-CLOSURE-001 |
| 6 | `STUDIO-CLOSURE-001` | current Studio first real visual editing flow, responsive tree, dynamic bindings, revisions, publish/render | N0.21 foundation | PLANNED | EXTENSION-CLOSURE-001 |
| 7 | `CMS-PUBLISHING-CLOSURE-001` | current Documents, Writer, revisions, editorial, Blog/Article, authors, publishing taxonomies, series, archives | N0.16–N0.22 | PLANNED | STUDIO-CLOSURE-001 |
| 8 | `MEDIA-DISTRIBUTION-CLOSURE-001` | current media upload/select/use, newsletter, RSS, distribution adapters | N0.25 | PLANNED | CMS-PUBLISHING-CLOSURE-001 |
| 9 | `SEO-SEARCH-CLOSURE-001` | current SEO Core, Schema Graph, sitemap, internal links, search, analytics, SEO crawler | N0.19/N0.26 | PLANNED | MEDIA-DISTRIBUTION-CLOSURE-001 |
| 10 | `AUTOMATION-CLOSURE-001` | current triggers, conditions, actions, inbound/outbound signed webhooks, retries/evidence | N0.27 | PLANNED | SEO-SEARCH-CLOSURE-001 |
| 11 | `MARKETPLACE-CLOSURE-001` | current catalogs, publisher trust, package staging → quarantine → Sentinel → install | N0.29 | PLANNED | AUTOMATION-CLOSURE-001 |
| 12 | `COMMERCE-CLOSURE-001` | current product/price/tax/customer/order/invoice/payment/refund/subscription foundation workflows | N0.30 | PLANNED | MARKETPLACE-CLOSURE-001 |
| 13 | `CRM-MEMBERSHIP-HELPDESK-CLOSURE-001` | current CRM, membership entitlement/access, tickets/SLA/customer-domain workflows | N0.31/N0.32 | PLANNED | COMMERCE-CLOSURE-001 |
| 14 | `ENTERPRISE-CLOUD-CLOSURE-001` | current organizations/tenancy/SSO/SCIM/governance plus distributed runtime/HA foundation verification | N0.33/N0.34 | PLANNED | CRM-MEMBERSHIP-HELPDESK-CLOSURE-001 |

## Track B — platform primitives required for WordPress/Webflow/Wix-class extensibility

These stages were normalized from the existing roadmap plus the Phase 2 capability-gap audit. They must be implemented before Nexora is called a mature extensible CMS/site-builder platform.

| Order | Stable stage ID | Scope | Status | Depends on |
|---:|---|---|---|---|
| 15 | `ARCH-BOUNDARY-100` | reconcile architecture constitution with implementation; persistence/domain boundaries; broaden architecture tests; document deliberate exceptions | NEW_REQUIRED | ENTERPRISE-CLOUD-CLOSURE-001 |
| 16 | `CONTENT-MODEL-200` | custom content types, typed fields, field groups, relations, hierarchy, schema versioning, permissions, API/search/Studio policies | LEGACY_PLANNED + EXPANDED | ARCH-BOUNDARY-100 |
| 17 | `TAXONOMY-200` | generic taxonomy definitions/registry, hierarchy, content-type bindings, capabilities, extension registration | NEW_REQUIRED | CONTENT-MODEL-200 |
| 18 | `QUERY-ENGINE-200` | typed content queries, filters/sort/pagination/relations/taxonomies, saved queries, generic archive queries | NEW_REQUIRED | CONTENT-MODEL-200, TAXONOMY-200 |
| 19 | `ROUTING-200` | permalink patterns, route resolver, archives, rewrite/slug collision policy, redirects, canonical route integration | NEW_REQUIRED | CONTENT-MODEL-200, TAXONOMY-200, QUERY-ENGINE-200 |
| 20 | `NAVIGATION-100` | public menus, nested items, menu locations, content/taxonomy/custom targets, conditions, API/AI contract | NEW_REQUIRED | ROUTING-200 |
| 21 | `THEME-CONTRACT-200` | deterministic template hierarchy, template parts, manifest 2.0, tokens, menu locations, slots/support flags | LEGACY THEME-STUDIO PRECONDITION + NEW_REQUIRED | NAVIGATION-100 |
| 22 | `EXT-SDK-200` | typed events/actions, filters/transforms, Admin/UI/theme slots, admin pages, runtime APIs, content/taxonomy/Studio registration, jobs/commands | LEGACY_PLANNED + EXPANDED | ARCH-BOUNDARY-100, CONTENT-MODEL-200, TAXONOMY-200, THEME-CONTRACT-200 |
| 23 | `SITE-BUILDER-200` | visual AST 2.0, reusable components/instances/overrides, dynamic templates/bindings, responsive layout, interactions, history, preview/publish | LEGACY_PLANNED + EXPANDED | QUERY-ENGINE-200, THEME-CONTRACT-200, EXT-SDK-200 |
| 24 | `THEME-STUDIO-200` | global design system, visual tokens, global sections/template parts, theme-level visual editing | LEGACY_PLANNED | SITE-BUILDER-200 |

## Track C — content delivery, localization, discovery and workflow platform

| Order | Stable stage ID | Scope | Status | Depends on |
|---:|---|---|---|---|
| 25 | `I18N-200` | site locales, localized static/CMS/component content, localized SEO, hreflang, locale publishing, translation workflow | LEGACY_PLANNED + EXPANDED | CONTENT-MODEL-200, THEME-STUDIO-200 |
| 26 | `FRONTEND-RUNTIME-200` | frontend cache, CDN integration, image pipeline, rendering budgets, invalidation and delivery runtime | LEGACY_PLANNED | THEME-CONTRACT-200, SITE-BUILDER-200 |
| 27 | `MEDIA-DAM-200` | DAM organization, asset usage graph, transformations, dedupe, metadata/rights, external storage product closure | LEGACY_PLANNED + EXPANDED | FRONTEND-RUNTIME-200 |
| 28 | `SEARCH-200` | facets, provider abstraction, advanced indexing/querying for content/commerce | LEGACY_PLANNED | CONTENT-MODEL-200, TAXONOMY-200, QUERY-ENGINE-200 |
| 29 | `AI-KERNEL-100` | model gateway, agent runtime, tool registry, AI capability gate, context engine, structured executor, approval, audit, prompt registry, evals, telemetry | NEW_REQUIRED / legacy AI platform foundation | CONTENT-MODEL-200, EXT-SDK-200 |
| 30 | `SEO-AI-200` | SEO product 2.0 + AI visibility intelligence, expanded resource adapters, crawler workflows, schema/entity intelligence | LEGACY_PLANNED + EXPANDED | ROUTING-200, SEARCH-200, AI-KERNEL-100 |
| 31 | `FORMS-WORKFLOW-200` | form builder, validation, spam/rate-limit, lead capture, visual workflows, current automation integration | LEGACY_PLANNED | SITE-BUILDER-200, EXT-SDK-200 |

## Track D — API, configuration and AI product experiences

| Order | Stable stage ID | Scope | Status | Depends on |
|---:|---|---|---|---|
| 32 | `API-PLATFORM-100` | versioned REST, GraphQL, OAuth/scopes, webhook subscriptions, headless delivery, extension API surfaces | LEGACY_PLANNED | CONTENT-MODEL-200, TAXONOMY-200, QUERY-ENGINE-200, EXT-SDK-200 |
| 33 | `CONFIG-AS-CODE-100` | import/export, site/schema/config serialization, validation, diff/apply, environment-safe configuration | LEGACY_PLANNED | API-PLATFORM-100 |
| 34 | `AI-CONTENT-100` | governed AI content/CMS/media/SEO assistance through typed tools and evidence | NEW_REQUIRED | AI-KERNEL-100, SEO-AI-200 |
| 35 | `AI-DESIGN-100` | AI Design Professional: brief → IA → tokens → components → visual AST → responsive/a11y validation → draft/publish workflow | NEW_REQUIRED | AI-KERNEL-100, SITE-BUILDER-200, THEME-STUDIO-200, NAVIGATION-100 |
| 36 | `AI-DX-100` | SDK-aware extension/app/theme scaffolding, architectural review, tests/evals and package-development assistance | NEW_REQUIRED | AI-KERNEL-100, EXT-SDK-200, API-PLATFORM-100 |

## Track E — marketplace, commerce and business application productization

| Order | Stable stage ID | Scope | Status | Depends on |
|---:|---|---|---|---|
| 37 | `MARKETPLACE-200` | publisher economy, discovery, ratings/reviews, compatibility checks, licensing/subscriptions, safe update channels | LEGACY_PLANNED | EXT-SDK-200, SENTINEL foundation |
| 38 | `COMMERCE-200` | catalog/variants/inventory, discounts, cart, secure checkout, commerce functions, checkout/account extension surfaces, providers, fulfillment | LEGACY_PLANNED + EXPANDED | EXT-SDK-200, SITE-BUILDER-200, API-PLATFORM-100 |
| 39 | `PORTAL-200` | authenticated customer/member portal builder, CRM/membership/helpdesk integration, account extension slots | LEGACY_PLANNED | COMMERCE-200, SITE-BUILDER-200 |
| 40 | `COLLAB-200` | presence, locks, comments, approvals, collaborative editing and workflow-aware publishing | LEGACY_PLANNED | SITE-BUILDER-200, PORTAL-200 |

## Track F — migration, developer ecosystem, security and enterprise closure

| Order | Stable stage ID | Scope | Status | Depends on |
|---:|---|---|---|---|
| 41 | `MIGRATION-CENTER-100` | WordPress/Webflow/Drupal/Shopify migration adapters, dry-run/loss report, redirects, retries/idempotency | LEGACY_PLANNED + EXPANDED | CONFIG-AS-CODE-100, ROUTING-200, MEDIA-DAM-200 |
| 42 | `DX-200` | CLI, SDKs, docs, local development, package generators, reference apps/themes/extensions, compatibility tooling | LEGACY_PLANNED | EXT-SDK-200, API-PLATFORM-100, MIGRATION-CENTER-100 |
| 43 | `SENTINEL-200` | advisory/vulnerability intelligence, stronger package policy, true runtime isolation backend strategy, runtime security | LEGACY_PLANNED + EXPANDED | EXT-SDK-200, MARKETPLACE-200 |
| 44 | `ENTERPRISE-GOV-200` | enterprise governance, SSO/SCIM product closure, policy center, audit/impersonation controls, tenancy hardening | LEGACY_PLANNED | SENTINEL-200 |
| 45 | `OBSERVABILITY-200` | diagnostics/operations center, logs/metrics/traces/jobs/queues/health/AI/package evidence | LEGACY_PLANNED | ENTERPRISE-GOV-200 |
| 46 | `DR-PLATFORM-100` | application/package update, atomic rollback, backup/restore, disaster recovery, rehearsal/evidence | LEGACY_PLANNED | OBSERVABILITY-200 |

## Track G — production quality and release

| Order | Stable stage ID | Scope | Status | Depends on |
|---:|---|---|---|---|
| 47 | `PERF-CWV-CERT-100` | performance budgets, Core Web Vitals, caching/render/media/query performance certification | LEGACY_PLANNED | DR-PLATFORM-100, FRONTEND-RUNTIME-200 |
| 48 | `A11Y-CERT-100` | accessibility/international certification, keyboard/screen-reader/contrast/RTL verification | LEGACY_PLANNED | PERF-CWV-CERT-100, I18N-200 |
| 49 | `RELEASE-CERT-100` | final C1-C6 exact-source dependency/browser/DB/backup/restore/HA/security/package certification | DEFERRED_CERTIFICATION | A11Y-CERT-100 |
| 50 | `N2-STABLE-100` | Stable Production Platform Release | BLOCKED | RELEASE-CERT-100 |

## External product track

External products do not block Core stage progression unless the user explicitly promotes one into the active delivery track. They are acceptance tests of the public extension platform and must not require private Core shortcuts.

- `EXT-B01` Books / Manuscripts / Editions / EPUB / print — App/Extension + optional publishing themes.
- `EXT-P01` Professional Profile / CV / Resume / Biography / portfolio — App/Extension + profile/CV themes.
- `EXT-L01` LMS / Courses / Lessons / Quizzes / Progress — App/Extension.
- `EXT-BK01` Booking / Services / Staff / Availability / Appointments — App/Extension.
- `EXT-PR01` Projects / Tasks / Boards / Milestones / Time tracking — App/Extension.

## Stage execution protocol

Before starting any stage, create/update an active plan containing:

1. exact capability IDs/scope;
2. dependencies and preconditions;
3. existing implementation to preserve/reuse;
4. architecture/contracts to add/change;
5. data/migration impact;
6. permission/capability impact;
7. UI/UX work;
8. API/extension/AI surfaces;
9. security/threat considerations;
10. tests/evals;
11. target verification steps;
12. rollback/recovery behavior;
13. documentation/handoff updates;
14. explicit out-of-scope items.

A stage may be subdivided into `-A`, `-B`, `-C` execution chunks, but the canonical stable stage ID remains the parent identity.

## Current cursor

The Phase 2 roadmap expansion does **not** skip the current real-target blocker. After this AI governance branch is merged, the execution cursor remains `RUNTIME-CLOSURE-001`.

The exact first runtime actions remain:

1. repair the already-installed rc.93 post-install identity state without overwriting it with rc.94;
2. run `php artisan nexora:runtime:compatibility-status --deep`;
3. require `status=pass`, `mismatches=[]`, `compatible=true`, `mode=installed-data-plane`;
4. run `php artisan nexora:runtime:post-install-status --assert-ready`;
5. only then proceed to `/login` and `CORE-QA-001`.
