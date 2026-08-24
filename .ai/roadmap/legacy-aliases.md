# Legacy Roadmap Alias Map

Nexora historically accumulated two different forward-roadmap naming schemes. The same `N1.x` label can mean different work depending on which document an agent reads. This file prevents silent ambiguity.

Stable semantic IDs in `.ai/roadmap/stages.md` are canonical. Legacy aliases are context only.

## Legacy AI ledger aliases

Source: `NEXORA_AI_PROJECT_STATE.md` product-oriented roadmap.

| Legacy alias | Historical meaning | Canonical stable stage(s) |
|---|---|---|
| N1.0A | Installation + Runtime Closure | `RUNTIME-CLOSURE-001` |
| N1.0B | Super Admin + Core Application QA | `CORE-QA-001` |
| N1.1 | Admin Design System / UX Closure | `ADMIN-UX-CLOSURE-001` |
| N1.2 | Theme Engine Product Closure | `THEME-CLOSURE-001` |
| N1.3 | Plugin / Extension Product Closure | `EXTENSION-CLOSURE-001` |
| N1.4 | Studio / Visual Builder | `STUDIO-CLOSURE-001` |
| N1.5 | CMS / Documents / Collections | `CMS-PUBLISHING-CLOSURE-001` |
| N1.6 | Media / DAM | `MEDIA-DISTRIBUTION-CLOSURE-001` |
| N1.7 | SEO / Publishing | `SEO-SEARCH-CLOSURE-001` plus `CMS-PUBLISHING-CLOSURE-001` |
| N1.8 | Forms / Data / Workflows | `FORMS-WORKFLOW-200` plus current `AUTOMATION-CLOSURE-001` |
| N1.9 | Marketplace | `MARKETPLACE-CLOSURE-001`, later `MARKETPLACE-200` |
| N1.10 | Commerce 2.0 | `COMMERCE-CLOSURE-001`, later `COMMERCE-200` |
| N1.11 | CRM / Membership / Customer Portal | `CRM-MEMBERSHIP-HELPDESK-CLOSURE-001`, later `PORTAL-200` |
| N1.12 | Search 2.0 | `SEARCH-200` |
| N1.13 | Collaboration | `COLLAB-200` |
| N1.14 | Automation | `AUTOMATION-CLOSURE-001` plus workflow expansion in `FORMS-WORKFLOW-200` |
| N1.15 | AI Platform Capabilities | `AI-KERNEL-100` foundation plus `AI-CONTENT-100`, `AI-DESIGN-100`, `AI-DX-100` product layers |
| N1.16 | Multisite / Organizations | `ENTERPRISE-CLOUD-CLOSURE-001`, later `ENTERPRISE-GOV-200` |
| N1.17 | SSO / Enterprise Governance | `ENTERPRISE-CLOUD-CLOSURE-001`, later `ENTERPRISE-GOV-200` |
| N1.18 | Public APIs / Webhooks / SDK | `API-PLATFORM-100` plus `DX-200` |
| N1.19 | Import / Export / WordPress migrations | `CONFIG-AS-CODE-100` plus `MIGRATION-CENTER-100` |
| N1.20 | Observability | `OBSERVABILITY-200` |
| N1.21 | Developer Experience / Forge | `DX-200` plus AI developer assistance `AI-DX-100` |
| N1.22 | Sentinel 2.0 | `SENTINEL-200` |
| N1.23 | Marketplace 2.0 | `MARKETPLACE-200` |
| N1.24 | Cloud / HA / Distributed Runtime | `ENTERPRISE-CLOUD-CLOSURE-001`; final evidence `RELEASE-CERT-100` |
| N1.25 | Backup / DR / Upgrade Certification | `DR-PLATFORM-100`, then `RELEASE-CERT-100` |
| N1.26 | Performance + Accessibility + Release | `PERF-CWV-CERT-100`, `A11Y-CERT-100`, `RELEASE-CERT-100` |

## Legacy master-plan aliases

Source: `docs/NEXORA_PLAN_STATUS.md`.

| Legacy alias | Master-plan meaning | Canonical stable stage(s) |
|---|---|---|
| N1.0 | Release Candidate certification / C1-C6 evidence | `RELEASE-CERT-100` (late execution gate) |
| N1.1 | Admin UX / Design System certification | `ADMIN-UX-CLOSURE-001` |
| N1.2 | Dynamic Content Model / Custom Fields / Relations 2.0 | `CONTENT-MODEL-200` |
| N1.3 | Visual Site Builder 2.0 | `SITE-BUILDER-200` |
| N1.4 | Theme Studio 2.0 / Global Design System | `THEME-STUDIO-200` |
| N1.5 | Extension SDK 2.0 / Hooks / UI Slots / Runtime APIs | `EXT-SDK-200` |
| N1.6 | Frontend Runtime / Cache / CDN / Image Pipeline | `FRONTEND-RUNTIME-200` |
| N1.7 | Localization & Multilingual 2.0 | `I18N-200` |
| N1.8 | SEO + AI Visibility Intelligence | `SEO-AI-200` |
| N1.9 | Forms / Lead Capture / Workflow Builder | `FORMS-WORKFLOW-200` |
| N1.10 | Commerce 2.0 / Storefront / Checkout | `COMMERCE-200` |
| N1.11 | Customer & Member Portal Builder | `PORTAL-200` |
| N1.12 | Search 2.0 / Facets / Index Providers | `SEARCH-200` |
| N1.13 | Media / DAM Studio 2.0 | `MEDIA-DAM-200` |
| N1.14 | Collaboration / Comments / Presence / Approvals | `COLLAB-200` |
| N1.15 | Nexora AI Platform / Agents | `AI-KERNEL-100` + AI product stages |
| N1.16 | Observability / Diagnostics / Operations Center | `OBSERVABILITY-200` |
| N1.17 | Developer Experience / CLI / SDK / Docs | `DX-200` |
| N1.18 | Migration Center | `MIGRATION-CENTER-100` |
| N1.19 | Marketplace 2.0 / Publisher Economy | `MARKETPLACE-200` |
| N1.20 | Sentinel 2.0 / Runtime Security | `SENTINEL-200` |
| N1.21 | Enterprise Governance 2.0 | `ENTERPRISE-GOV-200` |
| N1.22 | Accessibility / International Certification | `A11Y-CERT-100` |
| N1.23 | Performance / Core Web Vitals Certification | `PERF-CWV-CERT-100` |
| N1.24 | Public API Platform / REST / GraphQL / OAuth / Webhooks | `API-PLATFORM-100` |
| N1.25 | Import/Export / Configuration as Code | `CONFIG-AS-CODE-100` |
| N1.26 | Update / Rollback / Disaster Recovery Platform | `DR-PLATFORM-100` |
| N2.0 | Stable Production Platform | `N2-STABLE-100` |

## Phase 2 audit-derived stages with no single legacy alias

These stable IDs were added because the capability audit found that their responsibilities were previously implicit, distributed or absent from the old roadmap:

- `ARCH-BOUNDARY-100` — architecture/implementation boundary reconciliation and enforcement.
- `TAXONOMY-200` — generic custom taxonomy platform.
- `QUERY-ENGINE-200` — typed generic content/archive query system.
- `ROUTING-200` — permalink/rewrite/archive/redirect/canonical route platform.
- `NAVIGATION-100` — public navigation/menu engine and theme locations.
- `THEME-CONTRACT-200` — complete deterministic theme/template hierarchy and manifest contract.
- `AI-KERNEL-100` — governed AI model/tool/agent execution foundation.
- `AI-CONTENT-100` — governed AI content/CMS product layer.
- `AI-DESIGN-100` — AI Design Professional / structured Studio design layer.
- `AI-DX-100` — AI-assisted Nexora SDK/package development layer.

These are not optional side quests. They close capabilities required by the product goal and must follow the dependency graph in `.ai/roadmap/stages.md`.

## Agent rule

Never execute a bare instruction such as `start N1.3` without resolving which historical roadmap the label came from. Prefer stable stage IDs in all new plans, commits, issues, handoffs and AI prompts.
