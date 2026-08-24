# Nexora System Registry

This file is the AI-readable inventory of systems already documented in Nexora. Phase 1 imports repository facts only; it does not add new feature ideas.

Two statuses are kept deliberately separate:

- **Legacy plan status** is the status written in `docs/NEXORA_PLAN_STATUS.md`.
- **AI execution status** uses `.ai` completion semantics and does not treat source implementation as real-target product verification.

## Implemented / existing foundations

| Legacy milestone | System | Legacy plan status | AI execution status | Current interpretation |
|---|---|---|---|---|
| N0.0 | Architecture constitution / boundaries / engineering rules | DONE | SOURCE_DONE | Architecture constitution exists; changes must preserve it. |
| N0.1 | Repository, database, testing and Admin standards | DONE | SOURCE_DONE | Foundational engineering standards exist. |
| N0.2 | Laravel + Inertia + React + TypeScript Admin foundation | DONE | PARTIAL | Strong foundation; application-wide functional/UX QA remains. |
| N0.3 | Identity, users, roles, permissions, audit, notifications, tables | DONE | PARTIAL | Source exists; live auth/role/profile/admin QA is part of the active product closure path. |
| N0.4 | Kernel, Contracts, Module Registry, Capability Runtime | DONE | SOURCE_DONE | Core extensibility foundation exists. |
| N0.5 | Sentinel quarantine + static/package security scanning | DONE | SOURCE_DONE | Security inspection foundation exists; later Sentinel 2.0 remains planned. |
| N0.6 | Secure installer | DONE | SOURCE_DONE | Source-complete; current real-target post-install convergence still gates product flow. |
| N0.7 | Deployment bootstrap + runtime/cache hardening | DONE | PARTIAL | Runtime closure remains active. |
| N0.8 | Clean-domain / zero-CLI deployment | DONE | PARTIAL | Implemented foundation; final real-target certification remains. |
| N0.9 | Portable Composer/Node toolchain | DONE | PARTIAL | Implemented; final dependency certification deferred. |
| N0.10 | Observable deployment/install progress + cancellation | DONE | SOURCE_DONE | Observable workflow foundation exists. |
| N0.11 | Installer branding + resilient environment persistence | DONE | SOURCE_DONE | Implemented source foundation. |
| N0.12 | Deployment recovery + localization/RTL + file picker | DONE | PARTIAL | Source foundation exists; broader multilingual 2.0 is planned. |
| N0.13 | Installer stabilization / password / backup consent | DONE | SOURCE_DONE | Implemented source foundation. |
| N0.14 | Relational DB portability + reset/recovery | DONE | PARTIAL | MySQL/MariaDB/PostgreSQL/SQLite/SQL Server support exists; target matrix certification remains. |
| N0.15 | Data Connections / auxiliary MongoDB, Redis and AWS services | DONE | PARTIAL | Connection foundation exists; live portability/product verification remains. |
| N0.16 | Shared UI governance + universal Document Engine + revisions | DONE | PARTIAL | Core document foundation exists; Dynamic Content Model 2.0 remains planned. |
| N0.17 | Admin shell + Writer block editor | DONE | PARTIAL | Foundation exists; Admin UX consistency and product-grade editing remain. |
| N0.18 | Editorial workflow / comments / revisions / autosave | DONE | PARTIAL | Implemented foundation requiring product workflow verification. |
| N0.19 | SEO Core / Schema Graph / canonical / robots / sitemap / internal links | DONE | PARTIAL | Central theme-independent SEO foundation exists. |
| N0.20 | Theme Engine / design tokens / install / preview / switch / rollback | DONE | PARTIAL | Theme lifecycle exists; real end-to-end theme workflow/product closure remains. |
| N0.21 | Nexora Studio visual builder foundation | DONE | PARTIAL | Validated visual-tree foundation exists; Visual Site Builder 2.0 is planned. |
| N0.22 | Blog & Article publishing / authors / taxonomy / series / scheduling / archives | DONE | PARTIAL | Publishing foundation exists; generic dynamic-content/taxonomy expansion remains future work. |
| N0.25 | Media / newsletter / syndication / distribution | DONE | PARTIAL | First-party media/distribution foundation exists; DAM 2.0 remains planned. |
| N0.26 | Search / content analytics / SEO crawler | DONE | PARTIAL | Search projection, privacy-aware analytics and evidence-based SEO crawler exist. |
| N0.27 | Automation / workflow engine / webhooks | DONE | PARTIAL | Trigger/condition/action/webhook foundation exists. |
| N0.28 | Supply-chain security / SBOM / signing / provenance / sandbox adapter | DONE | SOURCE_DONE | Trust foundation exists; it does not claim OS/container/process isolation. |
| N0.29 | Extensions lifecycle / Forge SDK / Marketplace | DONE | PARTIAL | Package lifecycle exists; Extension SDK 2.0 hooks/UI slots/runtime APIs remain planned. |
| N0.30 | Commerce + Billing foundation | DONE | PARTIAL | Provider-neutral primitives exist; Storefront/Checkout 2.0 remains planned. |
| N0.31 | CRM foundation | DONE | PARTIAL | Domain foundation exists; product closure remains. |
| N0.32 | Membership + Helpdesk foundations | DONE | PARTIAL | Domain foundations exist; portal/product closure remains. |
| N0.33 | Multisite / tenancy / organizations / SSO / enterprise controls | DONE | PARTIAL | Source foundations exist; enterprise product/governance closure remains. |
| N0.34 | Cloud / HA / distributed runtime / queues / object storage / operations | DONE | PARTIAL | Source foundations exist; real multi-node/production evidence remains certification work. |

## Extension and package system currently documented

The N0.29 extension lifecycle recognizes exactly these package families:

- `extension`
- `app`
- `integration`
- `studio-pack`

Themes are intentionally managed by the separate Theme Engine lifecycle.

Extension runtime modes:

- `declarative` — default structured/non-arbitrary runtime model.
- `trusted-php` — executable PHP mode subject to Sentinel/Supply Chain execution policy.

Extension migration policies:

- `none`
- `forward-only`

Rollback never implies automatic destructive/down migrations; schema-changing rollback must be explicitly schema-compatible.

## SEO systems currently documented

### SEO Core — N0.19

Existing foundation includes:

- search title and description;
- canonical URL and public URL fallback;
- index/noindex and follow/nofollow;
- advanced robots directives;
- sitemap inclusion policy and `/sitemap.xml`;
- semantic schema type;
- central JSON-LD Schema Graph with stable `@id` nodes;
- WebSite, optional Organization, WebPage/subtype and extension-contributed graph nodes;
- social-preview metadata foundation;
- durable internal-link suggestions;
- evidence/remediation-oriented audit issues rather than a synthetic SEO score.

### Search / Analytics / SEO Crawler — N0.26

Existing foundation includes:

- canonical Document/Media search projections;
- public and Admin search;
- privacy-aware first-party analytics;
- search-demand and zero-result analysis;
- SEO crawling of owned public URLs;
- HTTP/title/description/canonical/noindex/H1/schema/duplicate observations;
- persisted page evidence/issues rather than an invented aggregate score.

## Current certification / closure system

| Legacy milestone | System | Legacy plan status | AI execution status |
|---|---|---|---|
| N1.0 | RC certification: zero-install, migration, build, security, accessibility, performance, backup/restore, HA, packaging | CERTIFYING | DEFERRED_CERTIFICATION / PARTIAL |

The legacy AI ledger deliberately prioritizes runtime usability and DEV-4 product QA before returning to deep final certification. The `.ai` stage graph preserves that product-first execution cursor.

## Already approved but not yet implemented 2.0 / expansion systems

| Legacy milestone | Planned system | Legacy status | AI status |
|---|---|---|---|
| N1.1 | Admin UX / Design System certification and consistency audit | NEXT AFTER N1.0 PASS | PLANNED |
| N1.2 | Dynamic Content Model / Custom Fields / Relations 2.0 | PLANNED | PLANNED |
| N1.3 | Visual Site Builder 2.0 | PLANNED | PLANNED |
| N1.4 | Theme Studio 2.0 / Global Design System | PLANNED | PLANNED |
| N1.5 | Extension SDK 2.0 / Hooks / UI Slots / Runtime APIs | PLANNED | PLANNED |
| N1.6 | Frontend Runtime / Cache / CDN / Image Pipeline | PLANNED | PLANNED |
| N1.7 | Localization & Multilingual 2.0 | PLANNED | PLANNED |
| N1.8 | SEO + AI Visibility Intelligence | PLANNED | PLANNED |
| N1.9 | Forms / Lead Capture / Workflow Builder | PLANNED | PLANNED |
| N1.10 | Commerce 2.0 / Storefront / Checkout | PLANNED | PLANNED |
| N1.11 | Customer & Member Portal Builder | PLANNED | PLANNED |
| N1.12 | Search 2.0 / Facets / Index Providers | PLANNED | PLANNED |
| N1.13 | Media / DAM Studio 2.0 | PLANNED | PLANNED |
| N1.14 | Collaboration / Comments / Presence / Approvals | PLANNED | PLANNED |
| N1.15 | Nexora AI Platform / Agents | PLANNED | PLANNED |
| N1.16 | Observability / Diagnostics / Operations Center | PLANNED | PLANNED |
| N1.17 | Developer Experience / CLI / SDK / Docs | PLANNED | PLANNED |
| N1.18 | Migration Center — WordPress / Webflow / Drupal / Shopify | PLANNED | PLANNED |
| N1.19 | Marketplace 2.0 / Publisher Economy | PLANNED | PLANNED |
| N1.20 | Sentinel 2.0 / Runtime Security | PLANNED | PLANNED |
| N1.21 | Enterprise Governance 2.0 | PLANNED | PLANNED |
| N1.22 | Accessibility / International Certification | PLANNED | PLANNED |
| N1.23 | Performance / Core Web Vitals Certification | PLANNED | PLANNED |
| N1.24 | Public API Platform / REST / GraphQL / OAuth / Webhooks | PLANNED | PLANNED |
| N1.25 | Import/Export / Configuration as Code | PLANNED | PLANNED |
| N1.26 | Update / Rollback / Disaster Recovery Platform | PLANNED | PLANNED |
| N2.0 | Stable Production Platform Release | BLOCKED | BLOCKED |

## External package families already approved

These remain outside Nexora Core and must use public Contracts/Capabilities like third-party packages:

| External ID | Package family | Packaging direction | AI status |
|---|---|---|---|
| EXT-B01 | Books / Manuscripts / Chapters / Editions / EPUB / print exports | App/Extension + optional publishing themes | EXTERNAL |
| EXT-P01 | Professional Profile / CV / Resume / Biography / portfolio publishing | App/Extension + profile/CV themes | EXTERNAL |
| EXT-L01 | LMS / Courses / Lessons / Quizzes / Progress | App/Extension | EXTERNAL |
| EXT-BK01 | Booking / Services / Staff / Availability / Appointments | App/Extension | EXTERNAL |
| EXT-PR01 | Projects / Tasks / Boards / Milestones / Time tracking | App/Extension | EXTERNAL |

## Source references

This registry was imported from:

- `NEXORA_AI_PROJECT_STATE.md`
- `docs/NEXORA_PLAN_STATUS.md`
- `docs/n0-19-seo-core.md`
- `docs/n0-26-search-analytics-crawler.md`
- `docs/n0-28-supply-chain-security.md`
- `docs/n0-29-extensions-marketplace.md`
- current N0.29 `ExtensionManifestValidator`

When source behavior contradicts this registry, inspect the implementation and update this file rather than silently trusting stale documentation.