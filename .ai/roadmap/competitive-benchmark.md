# Nexora Competitive Capability Benchmark

## Purpose

This document is an architecture benchmark, not a cloning specification. Nexora may borrow proven product concepts from WordPress, Webflow, Wix and Shopify while implementing them through Nexora's own typed contracts, capability runtime, Sentinel trust model, structured document engine and AI control plane.

The benchmark is used to prevent capability omissions during planning. A competitor feature is not automatically required merely because it exists; every adopted capability must fit Nexora's product definition and architecture constitution.

## Benchmark rules

1. Prefer durable platform primitives over one-off feature copies.
2. Do not reproduce unrestricted WordPress-style global mutation when a typed event/filter/slot contract can solve the same problem.
3. Do not couple content ownership to themes.
4. Do not let extensions bypass capabilities, Sentinel, tenancy, auth or source-integrity boundaries.
5. Builder output must remain structured, portable and renderable without editor-only runtime assumptions.
6. AI must call public platform tools/contracts; it must not become a privileged database bypass.
7. Every benchmark-derived capability must have a stable Nexora stage ID before implementation.

## WordPress-class benchmark

Nexora must equal or exceed the useful platform primitives behind WordPress rather than copy WordPress internals.

Required capability families:

- custom content/post types;
- custom taxonomies and term relationships;
- custom fields/meta and typed relations;
- hierarchical and non-hierarchical content;
- archive/single/search/author/taxonomy routing;
- permalink/rewrite management;
- public navigation menus and theme menu locations;
- reusable blocks/components/patterns/template parts;
- deterministic template hierarchy;
- theme-level global style/tokens;
- plugin extension points comparable in usefulness to actions/filters, but typed and capability-gated;
- admin extension surfaces;
- REST/headless access with explicit capability/security policy;
- revisions, drafts, scheduling and editorial workflows;
- media management;
- migration/import/export ecosystem.

Official benchmark references:

- https://developer.wordpress.org/reference/functions/register_post_type/
- https://developer.wordpress.org/block-editor/reference-guides/filters/
- https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-theme/
- https://developer.wordpress.org/block-editor/reference-guides/block-api/block-patterns/

## Webflow-class benchmark

Required capability families:

- visual site/page building;
- reusable component definitions and instances;
- instance properties/overrides;
- CMS collections with dynamic bindings;
- dynamic collection/archive/detail pages;
- responsive layout control and breakpoint behavior;
- global design tokens/styles;
- reusable sections/components;
- design-safe publishing and preview;
- localization of static pages, CMS content and component content;
- clean generated frontend output;
- interaction/animation architecture without corrupting content ownership.

Official benchmark references:

- https://developers.webflow.com/data/docs/working-with-localization/localize-components
- https://developers.webflow.com/data/docs/working-with-the-cms/localization

## Wix-class benchmark

Required capability families:

- user-created data collections;
- typed fields, references, queries, filters and aggregation;
- dynamic pages bound to collection data;
- data lifecycle hooks/events;
- reusable custom apps/widgets/dashboard surfaces;
- backend service-extension points;
- scheduled jobs and automations;
- external data/service connections;
- REST/SDK/GraphQL-style developer access where appropriate;
- role/permission-aware collection access.

Official benchmark references:

- https://dev.wix.com/docs/velo/apis/wix-data-v2/introduction
- https://dev.wix.com/docs/develop-websites/articles/databases/wix-data/data-api/working-with-the-data-api
- https://dev.wix.com/docs/build-apps/develop-your-app/api-integrations/about-wix-apis
- https://dev.wix.com/docs/develop-websites/articles/coding-with-velo/packages/about-custom-apps

## Shopify-class benchmark

Required capability families:

- robust product/variant/catalog model;
- pricing, tax, discount, cart, checkout, orders, fulfillment, refunds and subscriptions;
- storefront themes and reusable commerce blocks;
- targeted application extension points instead of arbitrary template mutation;
- checkout UI extension surfaces;
- server-side commerce functions/rules;
- customer-account extension surfaces;
- analytics/web-pixel style integrations through privacy/security controls;
- payment/provider integrations;
- event/workflow integrations;
- versioned app extensions;
- app/theme distribution and update lifecycle.

Official benchmark references:

- https://shopify.dev/docs/apps/build/app-extensions/list-of-app-extensions
- https://shopify.dev/docs/apps/build/online-store/theme-app-extensions
- https://shopify.dev/docs/apps/build/checkout/index

## Nexora differentiation target

Nexora should exceed the benchmark through these cross-cutting properties:

- one typed content/data model shared by CMS, Studio, API and AI;
- typed extension surfaces with least-privilege capabilities;
- signed/quarantined/Sentinel-scanned package lifecycle;
- deterministic theme/template resolution;
- first-class structured SEO and Schema Graph;
- portable visual AST rather than opaque page blobs;
- AI as a governed platform participant with plan/dry-run/approval/validation/audit stages;
- machine-readable project development state under `.ai`;
- source-complete and target-verified statuses kept separate;
- self-hostable architecture without making one hosting vendor the platform dependency.

## Benchmark acceptance principle

Nexora is not considered platform-complete merely because a screen exists. For every adopted capability, the acceptance test must prove the complete lifecycle: define/configure -> permission -> create/use -> edit -> preview -> publish/execute -> consume/render -> update -> rollback/recover where applicable -> API/AI accessibility where declared -> security/audit evidence.
