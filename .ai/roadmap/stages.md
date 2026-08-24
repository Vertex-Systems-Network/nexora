# Nexora Canonical Stage Graph

This file controls forward execution. Stable semantic stage IDs are canonical; historical `N0.x`, `N1.x`, `DEV-x` names are aliases only.

## Rules

1. Only one stage is active at a time.
2. A stage cannot move to `SOURCE_DONE` until its source Definition of Done is met.
3. A stage cannot move to `TARGET_VERIFIED` without real-target evidence where behavior is target-dependent.
4. A later stage may have existing source code, but that does not allow the execution cursor to skip unmet prerequisites.
5. New roadmap items are inserted explicitly with dependencies; agents must not invent hidden work.
6. The active cursor lives in `.ai/state.json`.

## Track A — AI control + existing platform closure

| Order | Stable stage ID | Scope | Imported source | Status | Depends on |
|---:|---|---|---|---|---|
| 0 | `AI-GOV-001` | `.ai` control plane, deterministic handoff, system registry, stage graph | user-approved AI-native governance baseline | SOURCE_DONE on this branch | — |
| 1 | `RUNTIME-CLOSURE-001` | rc.93 live identity repair, compatibility PASS, post-install readiness, rc.94 clean-runtime closure | legacy `N1.0A`, `DEV-3` | ACTIVE / BLOCKED ON REAL TARGET | AI-GOV-001 |
| 2 | `CORE-QA-001` | Super Admin login/session, direct routes, Admin boot, auth/users/roles/settings/media/core CRUD | legacy `N1.0B`, `DEV-4` | PLANNED | RUNTIME-CLOSURE-001 |
| 3 | `ADMIN-UX-CLOSURE-001` | shared Admin design-system consistency, responsive behavior, appearance, errors, accessibility baseline | legacy AI roadmap `N1.1` | PLANNED | CORE-QA-001 |
| 4 | `THEME-CLOSURE-001` | upload → Sentinel → install → preview → activate → switch → rollback → render | legacy AI roadmap `N1.2`; N0.20 foundation | PLANNED | ADMIN-UX-CLOSURE-001 |
| 5 | `EXTENSION-CLOSURE-001` | extension/app/integration/studio-pack lifecycle, capabilities, dependencies, enable/disable/rollback/uninstall | legacy AI roadmap `N1.3`; N0.29 foundation | PLANNED | THEME-CLOSURE-001 |
| 6 | `STUDIO-CLOSURE-001` | first real visual editing flow, responsive visual tree, dynamic bindings, revisions, publish/render | legacy AI roadmap `N1.4`; N0.21 foundation | PLANNED | EXTENSION-CLOSURE-001 |
| 7 | `CMS-PUBLISHING-CLOSURE-001` | Documents, Writer, revisions, editorial, Blog/Article, authors, taxonomies, series, archives | legacy AI roadmap `N1.5/N1.7`; N0.16–N0.22 | PLANNED | STUDIO-CLOSURE-001 |
| 8 | `MEDIA-DISTRIBUTION-CLOSURE-001` | media upload/select/use, newsletter, RSS, distribution adapters | legacy AI roadmap `N1.6`; N0.25 | PLANNED | CMS-PUBLISHING-CLOSURE-001 |
| 9 | `SEO-SEARCH-CLOSURE-001` | SEO Core, Schema Graph, sitemap, internal links, search, analytics, SEO crawler | legacy AI roadmap `N1.7/N1.12`; N0.19/N0.26 | PLANNED | MEDIA-DISTRIBUTION-CLOSURE-001 |
| 10 | `AUTOMATION-CLOSURE-001` | triggers, conditions, actions, inbound/outbound signed webhooks, retries/evidence | legacy AI roadmap `N1.14`; N0.27 | PLANNED | SEO-SEARCH-CLOSURE-001 |
| 11 | `MARKETPLACE-CLOSURE-001` | catalogs, publisher trust, package staging → quarantine → Sentinel → install | legacy AI roadmap `N1.9`; N0.29 | PLANNED | AUTOMATION-CLOSURE-001 |
| 12 | `COMMERCE-CLOSURE-001` | product/price/tax/customer/order/invoice/payment/refund/subscription foundation workflows | legacy AI roadmap `N1.10`; N0.30 | PLANNED | MARKETPLACE-CLOSURE-001 |
| 13 | `CRM-MEMBERSHIP-HELPDESK-CLOSURE-001` | CRM, membership entitlement/access, tickets/SLA/customer-domain workflows | legacy AI roadmap `N1.11`; N0.31/N0.32 | PLANNED | COMMERCE-CLOSURE-001 |
| 14 | `ENTERPRISE-CLOUD-CLOSURE-001` | organizations/tenancy/SSO/SCIM/governance plus distributed runtime/HA foundation verification | legacy AI roadmap `N1.16/N1.17/N1.24`; N0.33/N0.34 | PLANNED | CRM-MEMBERSHIP-HELPDESK-CLOSURE-001 |

## Track B — already approved 2.0 / expansion roadmap

Track B imports the approved items from `docs/NEXORA_PLAN_STATUS.md`. It does not yet add audit-derived new features.

| Order | Stable stage ID | Planned scope | Legacy master alias | Status | Depends on |
|---:|---|---|---|---|---|
| 15 | `CONTENT-MODEL-200` | Dynamic Content Model / Custom Fields / Relations 2.0 | master N1.2 | PLANNED | ENTERPRISE-CLOUD-CLOSURE-001 |
| 16 | `SITE-BUILDER-200` | Visual Site Builder 2.0 | master N1.3 | PLANNED | CONTENT-MODEL-200 |
| 17 | `THEME-STUDIO-200` | Theme Studio 2.0 / Global Design System | master N1.4 | PLANNED | SITE-BUILDER-200 |
| 18 | `EXT-SDK-200` | Hooks / UI Slots / Runtime APIs / SDK 2.0 | master N1.5 | PLANNED | THEME-STUDIO-200 |
| 19 | `FRONTEND-RUNTIME-200` | Cache / CDN / Image Pipeline / frontend runtime | master N1.6 | PLANNED | EXT-SDK-200 |
| 20 | `I18N-200` | Localization & Multilingual 2.0 | master N1.7 | PLANNED | FRONTEND-RUNTIME-200 |
| 21 | `SEO-AI-200` | SEO + AI Visibility Intelligence | master N1.8 | PLANNED | I18N-200 |
| 22 | `FORMS-WORKFLOW-200` | Forms / Lead Capture / Workflow Builder | master N1.9 | PLANNED | SEO-AI-200 |
| 23 | `COMMERCE-200` | Commerce 2.0 / Storefront / Checkout | master N1.10 | PLANNED | FORMS-WORKFLOW-200 |
| 24 | `PORTAL-200` | Customer & Member Portal Builder | master N1.11 | PLANNED | COMMERCE-200 |
| 25 | `SEARCH-200` | Search 2.0 / Facets / Index Providers | master N1.12 | PLANNED | PORTAL-200 |
| 26 | `MEDIA-DAM-200` | Media / DAM Studio 2.0 | master N1.13 | PLANNED | SEARCH-200 |
| 27 | `COLLAB-200` | Collaboration / Comments / Presence / Approvals | master N1.14 | PLANNED | MEDIA-DAM-200 |
| 28 | `AI-PLATFORM-100` | Nexora AI Platform / Agents | master N1.15 | PLANNED | COLLAB-200 |
| 29 | `OBSERVABILITY-200` | Diagnostics / Operations Center / observability | master N1.16 | PLANNED | AI-PLATFORM-100 |
| 30 | `DX-200` | Developer Experience / CLI / SDK / Docs | master N1.17 | PLANNED | OBSERVABILITY-200 |
| 31 | `MIGRATION-CENTER-100` | WordPress / Webflow / Drupal / Shopify adapters | master N1.18 | PLANNED | DX-200 |
| 32 | `MARKETPLACE-200` | Marketplace 2.0 / Publisher Economy | master N1.19 | PLANNED | MIGRATION-CENTER-100 |
| 33 | `SENTINEL-200` | Sentinel 2.0 / Runtime Security | master N1.20 | PLANNED | MARKETPLACE-200 |
| 34 | `ENTERPRISE-GOV-200` | Enterprise Governance 2.0 | master N1.21 | PLANNED | SENTINEL-200 |
| 35 | `A11Y-CERT-100` | Accessibility / International Certification | master N1.22 | PLANNED | ENTERPRISE-GOV-200 |
| 36 | `PERF-CWV-CERT-100` | Performance / Core Web Vitals Certification | master N1.23 | PLANNED | A11Y-CERT-100 |
| 37 | `API-PLATFORM-100` | REST / GraphQL / OAuth / Webhooks public API platform | master N1.24 | PLANNED | PERF-CWV-CERT-100 |
| 38 | `CONFIG-AS-CODE-100` | Import/Export / Configuration as Code | master N1.25 | PLANNED | API-PLATFORM-100 |
| 39 | `DR-PLATFORM-100` | Update / Rollback / Disaster Recovery Platform | master N1.26 | PLANNED | CONFIG-AS-CODE-100 |
| 40 | `RELEASE-CERT-100` | final C1-C6 exact-source dependency/browser/DB/backup/restore/HA/package certification | master N1.0 certification closure, intentionally late per legacy AI ledger | DEFERRED_CERTIFICATION | DR-PLATFORM-100 |
| 41 | `N2-STABLE-100` | Stable Production Platform Release | master N2.0 | BLOCKED | RELEASE-CERT-100 |

## External product track

External products do not block Core stage progression unless a user explicitly promotes one into the active delivery track.

- `EXT-B01` Books / Manuscripts / Editions / EPUB / print — App/Extension + optional publishing themes.
- `EXT-P01` Professional Profile / CV / Resume / Biography / portfolio — App/Extension + profile/CV themes.
- `EXT-L01` LMS / Courses / Lessons / Quizzes / Progress — App/Extension.
- `EXT-BK01` Booking / Services / Staff / Availability / Appointments — App/Extension.
- `EXT-PR01` Projects / Tasks / Boards / Milestones / Time tracking — App/Extension.

## Current cursor

After `AI-GOV-001` is merged, the execution cursor is `RUNTIME-CLOSURE-001`.

The exact first runtime actions remain:

1. repair the already-installed rc.93 post-install identity state without overwriting it with rc.94;
2. run `php artisan nexora:runtime:compatibility-status --deep`;
3. require `status=pass`, `mismatches=[]`, `compatible=true`, `mode=installed-data-plane`;
4. run `php artisan nexora:runtime:post-install-status --assert-ready`;
5. only then proceed to `/login` and `CORE-QA-001`.
