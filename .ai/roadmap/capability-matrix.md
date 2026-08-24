# Nexora Master Capability Matrix

## Status vocabulary

- `FOUNDATION` — meaningful implementation exists, but product-grade closure/verification may still be incomplete.
- `SOURCE_DONE` — source contract is implemented and passes applicable source checks.
- `TARGET_VERIFIED` — behavior has real-target evidence.
- `LEGACY_PLANNED` — already approved in the historical Nexora roadmap.
- `NEW_REQUIRED` — added by the Phase 2 platform-gap audit; not previously explicit enough in the roadmap.
- `EXTERNAL` — intentionally delivered as an installable package rather than Nexora Core.

No item may silently move from `FOUNDATION` or `SOURCE_DONE` to `TARGET_VERIFIED`.

## 1. Platform kernel, contracts and architecture

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Kernel / lifecycle primitives | FOUNDATION | stable generic core | existing closure |
| Public Contracts | FOUNDATION | versioned public contracts | `ARCH-BOUNDARY-100` |
| Module Registry | FOUNDATION | deterministic registration | existing closure |
| Capability Runtime | FOUNDATION | least-privilege runtime identity | existing closure |
| Persistence boundary enforcement | GAP / partially enforced | domain contracts/repositories or explicitly documented allowed boundary | `ARCH-BOUNDARY-100` |
| Architecture tests covering all core domains | GAP | automated boundary coverage beyond `app/Nexora/Modules` | `ARCH-BOUNDARY-100` |
| Stable semantic stage/AI execution control | SOURCE_DONE on AI branch | deterministic continuation | `AI-GOV-001` |

## 2. Content model / CMS kernel

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Universal Document Engine | FOUNDATION | production content primitive | existing closure |
| Runtime document type registry | FOUNDATION | complete content-type contract | `CONTENT-MODEL-200` |
| User-created custom content types | GAP | Admin/API/extension definable | `CONTENT-MODEL-200` |
| Typed custom fields | LEGACY_PLANNED | rich schema field system | `CONTENT-MODEL-200` |
| Field groups / reusable schemas | GAP | reusable field contracts | `CONTENT-MODEL-200` |
| One-to-one relations | GAP | typed relations | `CONTENT-MODEL-200` |
| One-to-many relations | GAP | typed relations | `CONTENT-MODEL-200` |
| Many-to-many relations | GAP | typed relations | `CONTENT-MODEL-200` |
| Hierarchical content types | GAP | parent/child policy | `CONTENT-MODEL-200` |
| Content capabilities per type | GAP | create/read/update/delete/publish policy | `CONTENT-MODEL-200` |
| Per-type API exposure policy | GAP | explicit API contract | `CONTENT-MODEL-200` |
| Per-type search/index policy | GAP | explicit search contract | `CONTENT-MODEL-200` |
| Per-type Studio support | GAP | explicit builder binding | `CONTENT-MODEL-200` |
| Code-defined vs site-defined schemas | GAP | deterministic ownership + migration rules | `CONTENT-MODEL-200` |
| Schema migration/versioning | GAP | non-destructive upgrade path | `CONTENT-MODEL-200` |

## 3. Taxonomy, query, archives and routing

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Taxonomy terms + document relations | FOUNDATION | generic taxonomy runtime | `TAXONOMY-200` |
| Publishing categories/tags/topics | FOUNDATION | retained as first-party definitions | `TAXONOMY-200` |
| Generic taxonomy definition registry | GAP | code/site/extension-defined taxonomies | `TAXONOMY-200` |
| Hierarchical taxonomies | PARTIAL | generic policy | `TAXONOMY-200` |
| Taxonomy-to-content-type binding | GAP | explicit many-type binding | `TAXONOMY-200` |
| Taxonomy capabilities | GAP | permission-aware term management | `TAXONOMY-200` |
| Query builder / typed content query | GAP | filtering/sorting/pagination/relations/taxonomy/date/author | `QUERY-ENGINE-200` |
| Saved queries / reusable query definitions | GAP | Studio/theme/API reusable queries | `QUERY-ENGINE-200` |
| Archive query engine | PARTIAL | generic archives for every public type/taxonomy | `QUERY-ENGINE-200` |
| Permalink manager | GAP | deterministic configurable route patterns | `ROUTING-200` |
| Rewrite/slug collision policy | GAP | validation + conflict diagnostics | `ROUTING-200` |
| Canonical route resolver | PARTIAL | content/type/taxonomy/author/search/archive resolution | `ROUTING-200` |
| Redirect manager | GAP | managed 301/302/410 + migration redirects | `ROUTING-200` |

## 4. Public navigation

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Admin navigation registry | FOUNDATION | retained for Admin only | existing |
| Public menu entities | GAP | multiple menus per site | `NAVIGATION-100` |
| Nested menu items | GAP | arbitrary controlled hierarchy | `NAVIGATION-100` |
| Theme menu locations | GAP | header/footer/mobile/custom locations | `NAVIGATION-100` |
| Document/content links | GAP | dynamic target binding | `NAVIGATION-100` |
| Taxonomy/archive links | GAP | dynamic target binding | `NAVIGATION-100` |
| Custom URL links | GAP | validated external/internal URLs | `NAVIGATION-100` |
| Conditional visibility | GAP | role/device/site/context conditions | `NAVIGATION-100` |
| Menu API + extension/AI tools | GAP | public typed contract | `NAVIGATION-100` |

## 5. Publishing and editorial

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Writer/block editor | FOUNDATION | product-grade editor | existing closure |
| Draft/publish | FOUNDATION | verified workflows | existing closure |
| Scheduling | FOUNDATION | verified timezone-safe publishing | existing closure |
| Revisions / compare / restore | FOUNDATION | verified conflict-safe behavior | existing closure |
| Editorial review/comments | FOUNDATION | collaboration 2.0 | `COLLAB-200` |
| Authors | FOUNDATION | generic author identity integration | existing closure |
| Blog/articles | FOUNDATION | first-class publishing product | existing closure |
| Generic archives | PARTIAL | powered by content/query/router contracts | `QUERY-ENGINE-200` |
| Content locking/presence | LEGACY_PLANNED | real-time collaboration | `COLLAB-200` |
| Approval policies | LEGACY_PLANNED | workflow-based publishing gates | `COLLAB-200` |

## 6. Theme engine / frontend presentation

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Theme install/preview/activate/switch/rollback | FOUNDATION | target-verified lifecycle | existing closure |
| Design tokens | FOUNDATION | global token contract | `THEME-CONTRACT-200` |
| Theme manifest | FOUNDATION | Theme Contract 2.0 schema | `THEME-CONTRACT-200` |
| Template hierarchy | GAP / narrow contract | deterministic hierarchy | `THEME-CONTRACT-200` |
| `home` template | FOUNDATION | hierarchy member | `THEME-CONTRACT-200` |
| `front-page` | GAP | template target | `THEME-CONTRACT-200` |
| `page` | PARTIAL | generic + subtype templates | `THEME-CONTRACT-200` |
| `single` / `single:{type}` | GAP | dynamic content templates | `THEME-CONTRACT-200` |
| `archive` / `archive:{type}` | GAP | dynamic archive templates | `THEME-CONTRACT-200` |
| `taxonomy` / taxonomy-specific | GAP | taxonomy hierarchy | `THEME-CONTRACT-200` |
| `author` / `search` / `404` | GAP | full hierarchy | `THEME-CONTRACT-200` |
| Header/footer/template parts | GAP / Studio foundation | reusable global regions | `THEME-CONTRACT-200` |
| Theme menu locations | GAP | declared in manifest | `NAVIGATION-100` + `THEME-CONTRACT-200` |
| Theme slots/regions | GAP | typed extension placement | `EXT-SDK-200` |
| Theme support flags | GAP | declared feature compatibility | `THEME-CONTRACT-200` |
| Child/derived theme strategy | GAP | optional version-safe inheritance/composition | `THEME-CONTRACT-200` |
| Cache/CDN/image frontend runtime | LEGACY_PLANNED | production frontend pipeline | `FRONTEND-RUNTIME-200` |

## 7. Studio / visual builder

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Structured visual tree | FOUNDATION | stable portable visual AST | `SITE-BUILDER-200` |
| Responsive editing | FOUNDATION/PARTIAL | breakpoint-aware constraints | `SITE-BUILDER-200` |
| Dynamic data bindings | FOUNDATION/PARTIAL | content/query/taxonomy/commerce bindings | `SITE-BUILDER-200` |
| Reusable components | GAP/PARTIAL | definition + instance + properties | `SITE-BUILDER-200` |
| Component instance overrides | GAP | validated override contract | `SITE-BUILDER-200` |
| Global sections/template parts | GAP | shared instances with propagation | `THEME-STUDIO-200` |
| Global design tokens | LEGACY_PLANNED | visual token editor | `THEME-STUDIO-200` |
| Responsive layout constraints | PARTIAL | flex/grid/stack/container primitives | `SITE-BUILDER-200` |
| Interaction/animation model | GAP | structured event/timeline model | `SITE-BUILDER-200` |
| History/undo/redo | GAP/PARTIAL | deterministic editor history | `SITE-BUILDER-200` |
| Preview/publish diff | GAP | safe publish workflow | `SITE-BUILDER-200` |
| Dynamic collection/detail templates | GAP | builder templates bound to content types | `SITE-BUILDER-200` |
| Custom component SDK | GAP | extension-provided Studio components | `EXT-SDK-200` |

## 8. Extension / App platform

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Package family `extension` | FOUNDATION | full SDK surface | existing closure + `EXT-SDK-200` |
| Package family `app` | FOUNDATION | full SDK surface | existing closure + `EXT-SDK-200` |
| Package family `integration` | FOUNDATION | full SDK surface | existing closure + `EXT-SDK-200` |
| Package family `studio-pack` | FOUNDATION | full SDK surface | existing closure + `EXT-SDK-200` |
| Declarative runtime | FOUNDATION | preferred safe runtime | `EXT-SDK-200` |
| Trusted PHP runtime | FOUNDATION / security caveat | explicit trust/isolation policy | `SENTINEL-200` |
| Capability grants | FOUNDATION | typed granular capability catalog | `EXT-SDK-200` |
| Dependencies/version constraints | FOUNDATION | verified lifecycle | existing closure |
| Forward-only migrations | FOUNDATION | schema contract | existing closure |
| Typed events/actions | GAP | extension event bus | `EXT-SDK-200` |
| Typed filters/transforms | GAP | deterministic transformation contracts | `EXT-SDK-200` |
| Admin UI slots | GAP | extension dashboard surfaces | `EXT-SDK-200` |
| Theme/frontend slots | GAP | controlled placement targets | `EXT-SDK-200` |
| Admin pages | GAP | declared route/navigation contracts | `EXT-SDK-200` |
| Content-type registration | GAP | public extension SDK | `EXT-SDK-200` |
| Taxonomy registration | GAP | public extension SDK | `EXT-SDK-200` |
| Studio component registration | GAP | public extension SDK | `EXT-SDK-200` |
| Commands/jobs/schedules | GAP/PARTIAL | declared runtime capabilities | `EXT-SDK-200` |
| Public API endpoint registration | GAP | bounded API extension points | `API-PLATFORM-100` |
| AI tool registration | GAP | capability-scoped AI extension surface | `AI-KERNEL-100` |
| True isolation backend | PLANNED foundation only | process/container/WASM/RPC strategy for untrusted code | `SENTINEL-200` |

## 9. SEO, search, analytics and discovery

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| SEO title/description | FOUNDATION | all resource types | existing closure |
| Canonical policy | FOUNDATION | route-aware generic resources | existing closure + `ROUTING-200` |
| Robots/index/follow policy | FOUNDATION | complete UI/API | existing closure |
| Sitemap | FOUNDATION | generic resources + indexes where scale requires | `SEO-AI-200` |
| Schema Graph | FOUNDATION | extensible validated graph | existing closure |
| Internal link suggestions | FOUNDATION | AI-assisted optional workflow | `SEO-AI-200` |
| Social previews | FOUNDATION | product-grade previews | `SEO-AI-200` |
| SEO crawler | FOUNDATION | scheduled evidence + remediation workflow | existing closure |
| Search index | FOUNDATION | provider abstraction/facets | `SEARCH-200` |
| Faceted search | LEGACY_PLANNED | content/commerce facets | `SEARCH-200` |
| Search provider adapters | LEGACY_PLANNED | local/external providers | `SEARCH-200` |
| First-party analytics | FOUNDATION | dashboards + privacy controls | existing closure |
| AI search visibility | LEGACY_PLANNED | AI crawler/entity/schema/content intelligence | `SEO-AI-200` |
| Redirect/canonical migration intelligence | GAP | migration-safe SEO preservation | `MIGRATION-CENTER-100` |

## 10. Media / DAM

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Media upload/select/use | FOUNDATION | verified workflows | existing closure |
| Metadata/alt/caption | FOUNDATION/PARTIAL | structured media metadata | `MEDIA-DAM-200` |
| Folders/collections/tags | GAP/PARTIAL | DAM organization | `MEDIA-DAM-200` |
| Image transformations | LEGACY_PLANNED | responsive variants/optimization | `FRONTEND-RUNTIME-200` + `MEDIA-DAM-200` |
| Asset usage graph | GAP | where-used / dependency safety | `MEDIA-DAM-200` |
| Duplicate detection | GAP | hash/perceptual policy | `MEDIA-DAM-200` |
| External/object storage | FOUNDATION | production provider adapters | enterprise/cloud closure |

## 11. Forms, automation and integrations

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Automation triggers/conditions/actions | FOUNDATION | visual/product-grade workflow builder | `FORMS-WORKFLOW-200` |
| Signed inbound/outbound webhooks | FOUNDATION | retry/idempotency/observability closure | existing closure |
| Form builder | LEGACY_PLANNED | Studio-integrated form schema | `FORMS-WORKFLOW-200` |
| Lead capture | LEGACY_PLANNED | CRM/workflow integration | `FORMS-WORKFLOW-200` |
| Validation/spam/rate-limit | GAP/PARTIAL | security-aware form runtime | `FORMS-WORKFLOW-200` |
| Scheduled jobs | FOUNDATION/PARTIAL | declared and observable scheduler jobs | `EXT-SDK-200` + `OBSERVABILITY-200` |
| Integration credentials/secrets | FOUNDATION/PARTIAL | scoped encrypted secret vault | `SENTINEL-200` / enterprise security |

## 12. Localization

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Admin localization/RTL foundation | FOUNDATION | full product localization | `I18N-200` |
| Site locales | LEGACY_PLANNED | locale registry/default/fallback | `I18N-200` |
| Localized static page content | GAP | per-locale variants | `I18N-200` |
| Localized CMS records | GAP | linked locale variants | `I18N-200` |
| Localized component overrides | GAP | Studio/component integration | `I18N-200` |
| Localized SEO metadata | GAP | locale-aware canonical/hreflang | `I18N-200` |
| Locale-specific publishing state | GAP | independent draft/publish | `I18N-200` |
| Translation workflow | GAP | manual/provider/AI-assisted | `I18N-200` |

## 13. Commerce

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Products/prices/orders/tax/invoices/payments | FOUNDATION | commerce product closure | existing + `COMMERCE-200` |
| Variants/options | GAP/PARTIAL | robust catalog model | `COMMERCE-200` |
| Inventory | GAP/PARTIAL | stock/location policy | `COMMERCE-200` |
| Discounts/promotions | GAP | rule/function engine | `COMMERCE-200` |
| Cart | GAP/PARTIAL | storefront cart API/runtime | `COMMERCE-200` |
| Checkout | LEGACY_PLANNED | secure extensible checkout | `COMMERCE-200` |
| Checkout extension slots | GAP | targeted typed extension APIs | `COMMERCE-200` + `EXT-SDK-200` |
| Commerce functions/rules | GAP | isolated deterministic business rules | `COMMERCE-200` |
| Fulfillment/shipping | GAP/PARTIAL | provider architecture | `COMMERCE-200` |
| Refunds | FOUNDATION | verified workflow | `COMMERCE-200` |
| Subscriptions | FOUNDATION/PARTIAL | verified lifecycle | `COMMERCE-200` |
| Customer accounts | GAP/PARTIAL | portal builder integration | `PORTAL-200` |
| Payment provider extensions | GAP/PARTIAL | public provider contract | `COMMERCE-200` |
| Tax provider extensions | GAP | public provider contract | `COMMERCE-200` |
| Shipping provider extensions | GAP | public provider contract | `COMMERCE-200` |

## 14. CRM, membership, helpdesk and portal

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| CRM contacts/leads/opportunities/pipelines | FOUNDATION | product closure | existing business closure |
| Membership plans/entitlements/access | FOUNDATION | portal integration | `PORTAL-200` |
| Helpdesk tickets/messages/SLA | FOUNDATION | customer portal integration | `PORTAL-200` |
| Customer/member portal builder | LEGACY_PLANNED | authenticated Studio surface | `PORTAL-200` |
| Portal extension slots | GAP | account/app extensions | `PORTAL-200` + `EXT-SDK-200` |

## 15. Public API / headless / configuration

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Public REST API | LEGACY_PLANNED | versioned resource API | `API-PLATFORM-100` |
| GraphQL | LEGACY_PLANNED | schema/capability-aware graph | `API-PLATFORM-100` |
| OAuth/app auth | LEGACY_PLANNED | scoped third-party access | `API-PLATFORM-100` |
| Webhooks | FOUNDATION | public subscription model | `API-PLATFORM-100` |
| Headless content delivery | GAP | explicit delivery API/cache policy | `API-PLATFORM-100` |
| SDKs | LEGACY_PLANNED | generated/typed clients | `DX-200` |
| Import/export | LEGACY_PLANNED | content/config portability | `CONFIG-AS-CODE-100` |
| Configuration as code | LEGACY_PLANNED | diffable site configuration | `CONFIG-AS-CODE-100` |
| Schema/config validation | GAP | machine-safe deployment | `CONFIG-AS-CODE-100` |

## 16. AI-native product platform

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| AI development control plane `.ai` | SOURCE_DONE on branch | deterministic engineering governance | `AI-GOV-001` |
| Product Model Gateway | GAP | provider-neutral model adapters | `AI-KERNEL-100` |
| Agent Runtime | GAP | Planner/Designer/Developer/Reviewer style product agents | `AI-KERNEL-100` |
| AI Tool Registry | GAP | typed tools over public Nexora contracts | `AI-KERNEL-100` |
| AI Capability Gate | GAP | least-privilege AI actions | `AI-KERNEL-100` |
| Context Engine | GAP | site/content/schema/design context retrieval | `AI-KERNEL-100` |
| Prompt/Instruction Registry | GAP | versioned prompts/policies | `AI-KERNEL-100` |
| Project/Site AI memory | GAP | scoped durable context with privacy controls | `AI-KERNEL-100` |
| Structured action schemas | GAP | schema-validated model outputs | `AI-KERNEL-100` |
| Dry-run/plan/approval execution | GAP | governed mutation pipeline | `AI-KERNEL-100` |
| AI audit trail | GAP | every AI mutation attributable/reversible where possible | `AI-KERNEL-100` |
| Cost/token/model telemetry | GAP | budgets/usage/latency | `AI-KERNEL-100` |
| AI evals | GAP | regression/capability/safety quality gates | `AI-KERNEL-100` |
| AI fallback/provider policy | GAP | resilient provider-independent execution | `AI-KERNEL-100` |
| AI content assistant | GAP | structured CMS tools | `AI-CONTENT-100` |
| AI SEO assistant | LEGACY_PLANNED/PARTIAL direction | evidence-based SEO/AI visibility actions | `SEO-AI-200` |
| AI site planner | GAP | brief -> IA -> content/design plan | `AI-DESIGN-100` |
| AI Design Professional | GAP | brief -> tokens -> components -> visual AST -> responsive/a11y validation | `AI-DESIGN-100` |
| AI builder mutations | GAP | safe Studio AST operations | `AI-DESIGN-100` |
| AI extension developer tools | GAP | SDK-aware scaffolding/review | `AI-DX-100` |

## 17. Marketplace and package economy

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Marketplace source/catalog/staging | FOUNDATION | verified distribution lifecycle | existing closure |
| Publisher identity/signing | FOUNDATION | publisher trust product | `MARKETPLACE-200` |
| Ratings/reviews/discovery | GAP | marketplace product features | `MARKETPLACE-200` |
| Commercial licensing/subscriptions | GAP | publisher economy | `MARKETPLACE-200` |
| Automated compatibility checks | GAP/PARTIAL | package CI/certification | `MARKETPLACE-200` |
| Safe update channels | GAP/PARTIAL | signed staged updates | `DR-PLATFORM-100` |

## 18. Security / Sentinel

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Quarantine/static scanning | FOUNDATION | mature package security | existing |
| Artifact/content SHA-256 | FOUNDATION | retained | existing |
| Ed25519 signatures | FOUNDATION | publisher trust | existing |
| SBOM | FOUNDATION | advisory/vulnerability integration | `SENTINEL-200` |
| Provenance | FOUNDATION | policy-based admission | `SENTINEL-200` |
| Capability review | FOUNDATION | fine-grained manifest policy | `SENTINEL-200` |
| Runtime sandbox contract | FOUNDATION only | real execution isolation backend | `SENTINEL-200` |
| Dependency vulnerability intelligence | GAP | advisory feed + policy | `SENTINEL-200` |
| Secret scanning / malicious package heuristics | FOUNDATION/PARTIAL | expanded rules/evidence | `SENTINEL-200` |
| Runtime behavior monitoring | GAP | optional policy telemetry | `SENTINEL-200` |

## 19. Enterprise / operations / reliability

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Multisite / organizations / tenancy | FOUNDATION | target-verified governance | enterprise closure + `ENTERPRISE-GOV-200` |
| SSO/SCIM | FOUNDATION | target-verified enterprise identity | `ENTERPRISE-GOV-200` |
| Audit/governance | FOUNDATION | policy center | `ENTERPRISE-GOV-200` |
| Observability/diagnostics center | LEGACY_PLANNED | logs/metrics/traces/health/jobs | `OBSERVABILITY-200` |
| HA/distributed runtime | FOUNDATION | real multi-node evidence | enterprise/cloud closure |
| Backup/restore | FOUNDATION/PARTIAL | operator-safe DR product | `DR-PLATFORM-100` |
| Updates/rollback | FOUNDATION/PARTIAL | atomic application/package updates | `DR-PLATFORM-100` |
| Disaster recovery | LEGACY_PLANNED | rehearsed recovery | `DR-PLATFORM-100` |
| Performance/Core Web Vitals | LEGACY_PLANNED | budgets + certification | `PERF-CWV-CERT-100` |
| Accessibility | LEGACY_PLANNED | WCAG-oriented product certification | `A11Y-CERT-100` |

## 20. Migration ecosystem

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| WordPress migration | LEGACY_PLANNED | posts/pages/CPT/taxonomies/media/users/SEO/redirect mapping | `MIGRATION-CENTER-100` |
| Webflow migration | LEGACY_PLANNED | pages/CMS/assets/styles/redirects mapping where feasible | `MIGRATION-CENTER-100` |
| Shopify migration | LEGACY_PLANNED | catalog/customers/orders/content/SEO mapping within legal/API limits | `MIGRATION-CENTER-100` |
| Drupal migration | LEGACY_PLANNED | content types/taxonomy/users/content mapping | `MIGRATION-CENTER-100` |
| Migration dry-run report | GAP | preflight/conflict/loss report | `MIGRATION-CENTER-100` |
| SEO redirect preservation | GAP | route/canonical redirect plan | `MIGRATION-CENTER-100` |
| Rollback/retry/idempotency | GAP | safe migration operations | `MIGRATION-CENTER-100` |

## 21. External vertical packages

These remain deliberately outside Core unless architecture is explicitly changed:

- `EXT-B01` Books / Manuscripts / Editions / EPUB / print.
- `EXT-P01` Professional Profile / CV / Resume / Biography / portfolio.
- `EXT-L01` LMS / Courses / Lessons / Quizzes / Progress.
- `EXT-BK01` Booking / Services / Staff / Availability / Appointments.
- `EXT-PR01` Projects / Tasks / Boards / Milestones / Time tracking.

Their existence is also an acceptance test for the extension platform: these products must be buildable without private Core shortcuts.
