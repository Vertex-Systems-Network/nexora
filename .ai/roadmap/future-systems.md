# Nexora Future Systems Registry

This file lists future platform systems accepted into the canonical roadmap. These are planning commitments, not implementation claims.

Use:

- `.ai/roadmap/stages.md` for canonical execution order/dependencies;
- `.ai/registry/development-units.json` for pre-planned implementation units;
- `.ai/governance/development-intake.md` before adding or starting any new unit;
- `.ai/roadmap/release-trains.md` for Builder Beta / Pro / Platform / Production product gates.

## Governance and secure-development controls

- `AI-GOV-AUTOMATION-100` — machine-enforced registry/stage/plan/state/DoD/handoff consistency checks.
- `SECURITY-BASELINE-200` — early continuous security baseline covering identity/MFA, browser hardening, AppSec CI, tenancy/authorization, secrets and threat modeling.

## Architecture and website-platform primitives

- `ARCH-BOUNDARY-100` — architecture/implementation boundary reconciliation and enforcement.
- `CONTENT-MODEL-200` — dynamic content types, custom fields/groups, relations, hierarchy, schema versioning and policies.
- `TAXONOMY-200` — generic custom taxonomy definitions and bindings.
- `QUERY-ENGINE-200` — typed content/archive query engine.
- `ROUTING-200` — permalink, archive, rewrite, redirect and canonical route platform.
- `NAVIGATION-100` — public menus/navigation and theme locations.
- `THEME-CONTRACT-200` — deterministic theme manifest/template hierarchy and presentation contracts.
- `EXT-SDK-200` — typed events/filters/UI slots/runtime APIs and extension registration surfaces.
- `SITE-BUILDER-200` — structured visual site builder 2.0.
- `THEME-STUDIO-200` — global visual design system and theme-level editing.
- `RELEASE-WORKFLOW-200` — preview/staging/branching/merge/scheduled and selective publishing/rollback workflow.
- `TEMPLATE-ECOSYSTEM-100` — site/page/section/component/theme/starter kits with safe dependency/update/customization model.

## Delivery, performance, content operations and discovery

- `I18N-200` — localization and multilingual publishing 2.0.
- `FRONTEND-RUNTIME-200` — cache/CDN/image/rendering delivery runtime.
- `PERFORMANCE-FOUNDATION-200` — provider-neutral lab/RUM/backend profiling, Admin/server traces, Theme/Extension attribution, performance budgets and regression baselines.
- `CODE-QUALITY-200` — Core/Theme/Extension/App code-quality analysis with static/build findings correlated to runtime cost.
- `PERFORMANCE-INTELLIGENCE-200` — Nexora Performance Center: PageSpeed/GTmetrix-class lab/field reports, waterfall, filmstrip/video, scripted profiles, frontend/backend/package attribution, compare/history/monitoring/alerts and secure external runners.
- `MEDIA-DAM-200` — DAM Studio 2.0 and asset intelligence.
- `SEARCH-200` — search facets and provider abstraction.
- `FORMS-WORKFLOW-200` — forms, lead capture and visual workflow product.
- `PRIVACY-CONSENT-100` — consent categories, GPC/DNT, retention, export/delete and regional policy integration including performance RUM policy.
- `SEO-AI-200` — SEO 2.0 + AEO/AI visibility, AI-readable representations and entity/citation intelligence.

See `.ai/performance/performance-platform.md`, `.ai/performance/performance-budget-template.md` and `.ai/roadmap/capability-matrix-phase4-performance.md` for the accepted performance architecture.

## API, interoperability and configuration

- `API-PLATFORM-100` — REST/GraphQL/OAuth/headless/webhook subscription platform.
- `CONFIG-AS-CODE-100` — import/export and configuration-as-code platform.
- `AGENT-INTEROP-100` — external AI-agent gateway with scoped identity, capability negotiation and typed auditable tools.
- `DESIGN-IMPORT-100` — Figma/design-source import into Nexora tokens/components/layout AST with validation.

## AI-native product systems

- `AI-KERNEL-100` — model gateway, agent runtime, Tool Registry, capability gate, context, structured actions, approvals, audit, prompt registry, evals and telemetry.
- `AI-CONTENT-100` — governed AI content/CMS/media/SEO/AEO workflows.
- `AI-DESIGN-100` — AI Design Professional and structured Studio/site-building workflows.
- `AI-DX-100` — AI-assisted extension/app/theme/SDK development and independent review workflow, consuming code-quality/performance evidence.

## Optimization and app-runtime systems

- `EXPERIMENTATION-100` — A/B and multivariate testing, goals, safe rollout, analytics, performance-impact evidence and AI-assisted variants.
- `PERSONALIZATION-100` — privacy-safe audience/segment personalization with deterministic fallback.
- `APP-RUNTIME-100` — capability-bounded low-code/full-stack functions/jobs/schedules/data actions/integrations/secrets runtime.

## Marketplace and business systems

- `MARKETPLACE-200` — publisher economy, discovery, licensing, compatibility, revocation, safe update channels and reproducible package quality/performance profiles.
- `COMMERCE-200` — Storefront/Checkout 2.0, variants/inventory/discount/cart/functions/provider/fulfillment extension surfaces.
- `PORTAL-200` — customer/member portal builder across commerce, CRM, membership and helpdesk.
- `COLLAB-200` — collaboration, presence, locks, comments and approvals.

## Migration, cloud, security and operations

- `MIGRATION-CENTER-100` — WordPress/Webflow/Drupal/Shopify migration adapters and SEO-safe migration workflows.
- `DX-200` — CLI/SDK/docs/reference package developer experience including performance/quality tooling.
- `MANAGED-CLOUD-100` — optional managed Nexora hosting/provisioning/domains/SSL/CDN/backups/staging/distributed performance runners/monitoring/metering/scaling.
- `SENTINEL-200` — vulnerability intelligence, stronger package policy, emergency revocation and real isolation strategy for executable workloads.
- `ENTERPRISE-GOV-200` — enterprise governance 2.0.
- `OBSERVABILITY-200` — diagnostics and operations center consuming shared performance telemetry rather than duplicating it.
- `DR-PLATFORM-100` — updates, rollback, backup/restore and disaster recovery.
- `PERF-CWV-CERT-100` — final performance/Core Web Vitals/frontend/backend/package/code-quality release gate.
- `A11Y-CERT-100` — accessibility/international certification gate.
- `RELEASE-CERT-100` — final exact-source/target release certification.
- `N2-STABLE-100` — stable production release.

## Planning rule

A future system must not be implemented merely because it appears in this file. Before implementation it must have a registered development unit, mapped parent stage, active plan, dependencies, security classification, acceptance criteria, verification and rollback/recovery plan.

Performance-affecting work must also declare the relevant performance budget/test profile or explicitly state why runtime performance is not applicable.

If a new capability is discovered later, add it to the plan/registry first. Do not hide it inside another stage or start coding it before planning.
