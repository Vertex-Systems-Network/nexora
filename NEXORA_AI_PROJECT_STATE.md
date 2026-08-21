# Nexora AI Project State & Execution Ledger

> **AI START HERE**
> This is the canonical cross-chat handoff file for Nexora. Any AI/agent working on Nexora must read this file before planning, modifying, auditing, packaging, or certifying the project, and must update it after every meaningful implementation/audit/release pass.
>
> This is operational documentation and is intentionally outside the immutable source-attestation roots so history/status updates do not create deployment/runtime drift.
>
> `NEXORA_PROGRESS.md` is the mandatory human-readable weighted progress dashboard. Every meaningful apply must update it as required by `AGENTS.md`; it complements this ledger and never replaces SOURCE DONE vs TARGET VERIFIED semantics.

---

## 0. Ledger metadata

- Ledger schema: `1`
- Ledger revision: `2.4`
- Project: `Nexora`
- Product class: advanced extensible web platform / CMS / site builder / application ecosystem
- Current development source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Ledger last updated: `2026-08-21`
- GitHub canonical repository: `Vertex-Systems-Network/nexora`
- GitHub default branch: `main`
- Active development branch: `dev/n1-0b-core-functional-qa`
- Active GitHub pull request: `#1` — draft, mergeable; title to synchronize as `DEV-4/DEV-5 + N1.9-N1.17: product QA, Commerce, CRM/Membership, Search, Collaboration, Automation, AI, Multisite and Enterprise Governance source closure`
- Current branch head before this ledger-only commit: `76b8d077eb5f8abd3e3c623ecc652c13ade555e2`
- Latest complete green source-certification run before this ledger-only commit: `32508900897` on source head `1b86f3975438e1ba8eb7ede0f7f54fe9e6e088e3`
- Current weighted Project Power before this ledger-only commit: `76.5%` (`Source 99.0%`, `Target 50.0%`, `Release 25.0%`)
- Open GitHub issues at this checkpoint: `#2 Nexora runtime identity mismatch`
- GitHub `main` protection state at this checkpoint: `protected=false` (server-side branch/ruleset protection requested but not yet writable through the connected GitHub action surface)
- Current target environment: Windows + Laragon (real-target development test environment)
- Current target path: `D:\laragon\www\nexora`
- Final target: portable/self-hostable beyond Laragon; Laragon is a real development target, not an architectural dependency.

---

## 1. Product definition — what Nexora is

Nexora is a modern extensible platform intended to combine and advance the strongest ideas from WordPress, Webflow, Wix, Shopify and enterprise SaaS systems without inheriting their architectural limitations.

Intended user experience:

```text
Install Nexora
  -> create/manage site or workspace
  -> upload/install/activate themes
  -> upload/install/activate plugins/apps
  -> build pages visually in Studio
  -> manage CMS/content/media/SEO/forms/users
  -> enable optional modules such as commerce, LMS, booking, CRM, membership, etc.
  -> publish, operate, update and scale
```

### Core architecture principle

Themes/extensions operate through Nexora public contracts, capabilities and security/trust boundaries. They must not bypass core security, tenancy, admin, publishing semantics, deployment/runtime rules or platform contracts.

### Product-family principle

Vertical products such as Books, CV/Profile, LMS, Booking, Projects and future verticals should be independent modules/apps/extensions rather than permanently hard-coded into Core.

---

## 2. Non-negotiable architecture rules

1. **Core remains stable and generic.** Product-specific features should be modules/extensions where practical.
2. **Themes are presentation systems, not unrestricted core overrides.**
3. **Plugins/apps use contracts + capabilities + lifecycle controls.**
4. **Admin UI uses shared Nexora UI/design-system components.** Avoid ad-hoc raw controls where a library component exists.
5. **Security is fail-closed.** Never solve blockers by silently disabling runtime, tenancy, auth, source-integrity or update-security boundaries.
6. **Runtime certification is not the product.** It supports the platform and must not permanently block product development.
7. **SOURCE DONE != TARGET VERIFIED.** Always report them separately.
8. **No cross-project files/code.**
9. **No stale generated/runtime/certification state in clean source packages.**
10. **Migrations are portable and fresh-install safe.**
11. **Real target evidence is required before marking runtime/product behavior complete.**
12. **Final dependency/release certification remains late, after usability is proven.**
13. **Ledger/history updates must not mutate deployed source identity.**
14. **GitHub is the canonical source-control workflow.** Meaningful source changes go through a development branch/PR; do not push unverified runtime changes directly to `main`.
15. **GitHub issues are an execution input, not a separate backlog.** At the start and end of every meaningful pass, inspect open repository issues. Fix applicable source defects alongside roadmap work, add regression protection, and do not close runtime/environment issues until the required real-target evidence exists.
16. **Disposable DB verification is mandatory for cross-engine claims.** Source contracts prove source alignment only; a database engine becomes TARGET VERIFIED only after the guarded real target matrix passes on that engine.
17. **Final PRs merge automatically once genuinely final.** When the required source CI, target/runtime/product QA and applicable issue gates all pass, mark the PR Ready for review and merge it without waiting for another user confirmation. Never auto-merge a draft, red, stale-head or target-unverified PR.
18. **Weighted progress is evidence-based.** Read and update `NEXORA_PROGRESS.md` after every meaningful apply. Never increase Target Power from source/static CI alone and never inflate progress because file/change volume is large.
19. **`main` must be server-protected.** Desired repository rule is PR-only changes, required Source certification, stale-review dismissal, review/conversation resolution where supported, force-push/delete denial and administrator enforcement. Do not claim this rule active until GitHub reports direct server-side protection/ruleset evidence.

---

## 3. Platform architecture map

| Layer / subsystem | Purpose | Current state |
|---|---|---|
| Kernel / Core | Boot, lifecycle, shared primitives | Foundation implemented |
| Public Contracts | Stable APIs for modules/plugins/themes | Foundation implemented; N1.18 next expansion |
| Module Registry | Discover/register/activate modules | Foundation implemented |
| Capability Runtime | Controlled extension permissions | Foundation implemented |
| Admin Platform / Design System | Shared UI/navigation/forms/selects/themes/tooltips | Strong source closure; responsive/mobile/accessibility contract green; app-wide target QA pending |
| Document Engine | Structured page/content documents | Writer CRUD/revisions/autosave/concurrency/Media reuse + generic Collections SOURCE DONE; target execution pending |
| SEO Core | Metadata/canonical/schema/social/sitemap/publishing semantics | Publishing + SEO workflow SOURCE DONE; target execution pending |
| Theme Engine | Package/install/activate/render | Product workflow SOURCE DONE; target execution pending |
| Plugin / Extension Engine | Lifecycle/capabilities/migrations | Product workflow SOURCE DONE; target execution pending |
| Studio | Visual page/site builder | First create/edit/publish/public-render workflow SOURCE DONE; target execution pending; advanced builder features remain |
| Media / DAM | Upload/inspect/variants/folders/collections/reuse | Foundation + reusable picker SOURCE DONE; target execution pending |
| Forms / Data / Workflows | Tenant forms, public submissions, Automation bridge | SOURCE DONE for current product workflow; target execution pending |
| Data Connections | Auxiliary Mongo/Redis/AWS data-service handles | Product/security + DEV-5 portability source closure green; live connector target tests pending |
| Primary SQL Portability | MySQL/MariaDB/PostgreSQL/SQLite/SQL Server + managed aliases | Source/harness closure green; real engine matrix evidence pending |
| Search / Discovery | Public content search, Admin global search, query analytics | N1.12 Search 2.0 first workflow SOURCE DONE; target execution pending |
| Collaboration | Document assignment/review and Admin notifications | N1.13 first workflow SOURCE DONE; target execution pending |
| Automation | Event-driven workflows, actions, inbound/outbound webhooks | N1.14 first workflow SOURCE DONE; target execution pending |
| AI Platform | Tenant AI connections, provider-neutral text generation, privacy-minimal run metadata | N1.15 first workflow SOURCE DONE; target execution + real provider-adapter evidence pending |
| Multisite / Organizations | Tenant root, organization switching, member/domain/identity governance boundaries | N1.16 first workflow SOURCE DONE; target execution pending |
| SSO / Enterprise Governance | Identity adapter lifecycle, enforced login, SCIM, roles/invitations/impersonation governance | N1.17 SOURCE DONE; enforced-login, SCIM tenant lifecycle and governed impersonation source-gated; target execution pending |
| Public APIs / Webhooks / SDK | External API authentication/versioning, webhook/public contracts, SDK capability surface | Foundation/partial; N1.18 NEXT SOURCE BLOCK |
| Forge / SDK | Developer extension tooling | Foundation/planned expansion |
| Sentinel | Theme/plugin trust/security | Foundation implemented; 2.0 later |
| Marketplace | Theme/app/extension catalog, trusted staging and promotion | N1.9 first workflow SOURCE DONE; target execution pending; Marketplace 2.0 later |
| Commerce | Catalog/orders/invoices/provider-neutral billing | N1.10 first workflow SOURCE DONE including provider payments/refunds/subscriptions; target execution pending |
| CRM / Membership / Customer Portal | Business/customer/member capabilities | N1.11 first product workflow SOURCE DONE; target execution pending |
| Helpdesk | Support/customer service | Foundation exists; later product closure |
| Cloud / HA Runtime | Distributed workers/storage/deployments | Later roadmap |
| Installer / Deployment / Recovery | Zero-state install/update/recovery/runtime handoff | rc.94 source stabilization + DB UX closure green; live rc.93 recovery evidence pending |

---

## 4. Completion semantics

- `SOURCE DONE` — code/static contract exists and source/static checks pass.
- `TARGET VERIFIED` — behavior executed successfully on the real target.
- `PARTIAL` — meaningful implementation exists but end-to-end closure is missing.
- `BLOCKED` — known blocker prevents next gate.
- `PLANNED` — not yet implemented.
- `DEFERRED CERTIFICATION` — intentionally postponed until product/runtime usability closes.

Never report a feature as simply “100% complete” when only source/static verification exists.

---

## 5. Current development checkpoint

### 5.1 Development source

- Source release: `1.0.0-rc.94`
- Protocol: `v5.29`
- Generation: `n1-v5.29`
- Active branch: `dev/n1-0b-core-functional-qa`
- PR `#1` is draft and mergeable; it must remain draft until the required real-target gates pass.
- Latest complete green source CI before this ledger-only commit: `32508900897` on source head `1b86f3975438e1ba8eb7ede0f7f54fe9e6e088e3`.
- That run passed:
  - Certification preflight
  - Source Guard
  - Post-install runtime convergence contract
  - DEV-4 core functional source contract
  - Theme product source contract
  - Extension product source contract
  - Studio product source contract
  - Document product source contract
  - Content collection product source contract
  - Publishing + SEO product source contract
  - Admin UX product source contract
  - Forms + Data + Workflows product source contract
  - Data Connections product source contract
  - Primary SQL portability source contract
  - Installer database UX source contract
  - Development target QA source contract
  - Marketplace product source contract
  - Commerce product source contract
  - Customer Portal product source contract
  - CRM + Membership product source contract
  - Search 2.0 product source contract
  - Collaboration product source contract
  - Automation product source contract
  - AI Platform product source contract
  - Multisite / Organizations product source contract
  - SSO / Enterprise Governance product source contract
  - Unified source certification
- Source/static gates are green through N1.17 SSO / Enterprise Governance.
- `composer.lock` and `package-lock.json` are not committed; deterministic dependency/release certification remains deferred.

### 5.2 Open GitHub issue status

#### Issue #2 — Nexora runtime identity mismatch

Live rc.93 reports only:

```text
environment
activation
service
process
```

as mismatches. Version, generation, deployment/source, database, storage, host, resources, policy, Laravel framework and runtime dependencies match.

Permanent rc.94 source fix is present and CI-guarded through `scripts/post-install-runtime-convergence-contract-verify.php`. The contract guarantees fresh `/install/runtime-handoff`, an exact mutable-plane reconciliation allow-list, immutable mismatch rejection, service/process health gates, one-time post-install identity finalization and post-write compatibility reassessment.

Issue #2 remains **OPEN** because the existing rc.93 Laragon target still needs real recovery verification. The latest N1.11-N1.17 source passes do not change that live evidence.

### 5.3 Current live Laragon installation

The live target was installed from **rc.93** before rc.94 fresh-request stabilization existed.

Latest live evidence:

```text
Version                     MATCH
Generation                  MATCH
Deployment/source           PASS
Database data plane         MATCH
Storage                     MATCH
Host                        MATCH
Resources                   MATCH
Policy                      MATCH
Laravel framework           MATCH
Runtime dependencies        MATCH

Mismatches:
- environment
- activation
- service
- process
```

Do not overwrite installed rc.93 with rc.94 merely to repair this state.

### 5.4 Dependency review state

Runtime dependencies match the live installed lock state, but formal reviewed-lock attestation is missing. This is not the current usability blocker and remains final N1.0/C1-C6 certification work.

### 5.5 DEV-5 target-evidence state

The real target matrix harness exists, but no cross-engine result is allowed to be inferred from source CI.

Canonical runner:

```text
scripts/database-target-matrix.php
```

Durable secret-free evidence path when `--evidence` is used:

```text
storage/app/nexora/qa/database-target-matrix.json
```

No MySQL/MariaDB/PostgreSQL/SQL Server or managed AWS engine is TARGET VERIFIED from current source-only CI. SQLite also requires an actual target run for the current branch generation.

### 5.6 GitHub `main` branch protection state

GitHub branch metadata currently reports:

```text
main protected=false
required status checks: off
```

The authenticated repository user has admin permission, but the connected GitHub action surface available in this execution context does not expose branch-protection/ruleset mutation. This is not treated as applied. Desired server policy remains PR-only + required Source certification + stale-review dismissal + review/conversation resolution + no force push/delete + admin enforcement.

---

## 6. Work completed so far

### Installer / bootstrap / runtime stabilization

- Composer availability/bootstrap handling and Windows npm execution bridge.
- npm bundled/inBundle integrity handling.
- Development-first workflow; final audit moved later.
- Installer Requirements -> Database -> Application + Super Admin -> Review + Install flow with progress, logs and cancellation.
- Existing DB detection, backup/destructive-consent path, password-strength flow and first-admin verification.
- System/Light/Dark appearance and shared-select direction.
- Primary SQL support: MySQL/MariaDB/PostgreSQL/SQLite/SQL Server + SQL-compatible managed aliases.
- Auxiliary Mongo/Redis/AWS services modeled separately.
- Pre-install web-pipeline isolation and installer diagnostics.
- Browser/CLI deployment identity convergence.
- rc.94 fresh-request post-install runtime handoff and source regression gate.
- Historical TypeScript blockers remediated at source level (`76/76`); real Laragon frontend build was previously reported clean.

### GitHub-first DEV-4 foundation

- Root `AGENTS.md` requires agents to read/update the canonical ledger and `NEXORA_PROGRESS.md` after each meaningful apply.
- Product-facing source contracts run in development readiness and GitHub Actions.
- PR #1 remains draft until target evidence is available.

### Site identity / regional settings

- Application/site name, logo, display timezone and default language are validated and shared through Admin/Auth/runtime locale handling.
- Tenant context resolves before locale middleware.
- Settings validation covers unsafe logos, invalid timezones and unsupported locales.

### Media reusable-selection source closure

- Media Library supports upload, inspection, variants, folders, collections, metadata, usage tracking, Trash/restore and guarded permanent delete.
- Shared searchable `MediaPicker` is reused by Settings, Writer, Publishing and SEO.
- Writer persists canonical `media_asset_id`; Publishing/SEO support canonical media with controlled external fallback.

### Theme Engine product workflow source closure

- Theme ZIP upload enters Sentinel quarantine before promotion.
- Install requires completed `ALLOW` + immutable approved digest.
- Safe engine boundary, required slots, preview-without-activation, activation/version/design tokens/rollback and visible errors are source-gated.
- Real-ZIP acceptance source covers scan -> install -> preview -> activate -> public render -> rollback.

### Plugin / Extension product workflow source closure

- Upload goes to Sentinel quarantine; direct upload UI requires owning install + Sentinel permissions.
- Capability grants are deny-by-default; dependency/trusted-PHP/migration/dependent-uninstall guards remain enforced.
- Uninstall requires confirmation; acceptance source covers Sentinel -> artifact -> install -> enable -> disable -> uninstall.

### Studio first product workflow source closure

- Typed visual editor, layers, responsive editing, drag/drop, undo/redo, bindings, design tokens and reusable components exist.
- Row locks + lock versions reject stale writes; renderer safely integrates published canvases with Document fallback.
- Acceptance source proves public render, responsive CSS, unpublish fallback, stale-write rejection and unsafe-link normalization.

### Document / Writer + Content Collections source closure

- Writer CRUD, revisions, autosave, optimistic concurrency, structured block validation and Media reuse are source-gated.
- Generic tenant-native Content Collections support typed custom fields, document membership/per-entry data, permissions, audit and non-destructive lifecycle.
- Historical tenant baseline remains immutable while forward tenant-native models are recognized.

### Publishing + SEO end-to-end source closure

- Publishing/SEO use canonical Media Library assets for hero/social images.
- Public metadata emits route-correct canonical/robots/Open Graph/Twitter output.
- `PublicDocumentVisibility` centralizes anonymous exclusion for membership-protected documents.
- Home/blog/taxonomy/author/series/related/sitemap discovery excludes protected published documents.

### Application-wide Admin UX source closure pass

- Responsive tenant/language switching, accessible toast/select behavior, route-progress cleanup and shared responsive/accessibility source gates are in place.

### Forms + Data + Workflows source closure

- Tenant-native forms/submissions, schema-derived validation, privacy-minimal storage, CSRF/honeypot/throttle/status guards and stable `form.submitted` Automation bridge are source-gated.

### Data Connections product + auxiliary portability source closure

- Tenant-scoped encrypted connection records, non-disclosing Admin payloads, safe secret/health lifecycle and driver-aware Mongo/Redis/AWS rules are source-gated.
- Dynamo IAM/static-key and Redis TLS behavior have regression coverage.

### DEV-5 Primary SQL portability + installer DB UX source closure

- Native/managed SQL registry, version/config/backup policy and migration portability analyzer are source-gated.
- Installer DB test invalidates after connection changes and requires fresh verification.
- Managed database creation remains disabled.

### DEV-5 guarded real database target matrix

- `scripts/database-target-matrix.php` only accepts disposable `nexora_matrix_*` targets.
- Non-empty databases are refused, `.env` is not rewritten and database containers are never dropped.
- `--evidence` writes a secret-free schema-2 result artifact.
- Target matrix source exists; real engine evidence remains pending.

### N1.9 Marketplace first workflow source closure

- Marketplace source lifecycle, authoritative bounded sync, stale/withdrawn retirement and owning-engine permissions are fail-closed.
- Theme/extension/app/integration/Studio-pack packages stage through Sentinel; promotion reuses Theme/Extension installers.

### N1.10 Commerce 2.0 first workflow source closure

- Bounded money/tax arithmetic, active price windows, serialized order/invoice lifecycle and tenant-scoped SKU/slug identity are source-gated.
- Provider payment/refund/subscription create/cancel uses capability/health admission, locks, idempotency and failure-state preservation.
- Billing Admin surfaces expose controlled provider actions; real provider target verification remains pending.

### N1.11 CRM / Membership / Customer Portal source closure

- Customer Portal first workflow is source-gated: regular verified customer users route to `/account`; administrator `/admin` flow remains intact; portal ownership is explicit across user/customer/membership.
- Forward migration `2026_08_21_000500_scope_crm_membership_identity_to_tenant.php` tenantizes CRM Commerce links and scopes CRM pipeline/custom-field plus Membership plan/access-policy identities to tenant.
- Historical inconsistent cross-tenant CRM Commerce relationships fail closed rather than being silently reassigned.
- `CrmCommerceLink` uses `BelongsToTenant`; service writes assert current-tenant customer/contact/organization ownership.
- `MembershipManager` enforces tenant/customer/subscription consistency and Membership Commerce synchronization executes inside `TenantExecutionScope`.
- Shared `TenantMemberDirectory` drives Membership member selection and CRM Organization/Contact/Lead/Opportunity owner selection.
- CRM owner writes use `TenantMemberExists`; platform-wide `exists:users,id` owner validation was removed from those CRM flows.
- `CrmLeadConversionService` re-resolves externally supplied pipelines in the active tenant and re-resolves stages against that pipeline.
- CRM/Membership tenant-isolation acceptance source and `scripts/crm-membership-product-contract-verify.php` cover these boundaries.
- N1.11 full source run `32480925507` PASS; later N1.12 run `32481766814` reconfirmed the same gate.
- Real Customer Portal/CRM/Membership browser/runtime execution remains TARGET PENDING.

### N1.12 Search 2.0 first workflow source closure

- Existing document/media/SEO observers were verified as the correct index lifecycle foundation; no unnecessary rewrite was made.
- Public search now composes with `PublicDocumentVisibility`, searches public documents only and excludes active membership-protected published document IDs from anonymous results.
- Search index/query-log models remain tenant-scoped through `BelongsToTenant`.
- Admin global user search no longer uses platform-wide `User::query()`; it uses `TenantMemberDirectory::search()` and discloses active users only from the current organization.
- Admin indexed content search derives independent searchable types from `documents.view` and `media.view`; document permission no longer implicitly controls media discovery.
- Search resource types and limits are bounded; public search remains throttled/noindex and privacy-aware query-demand recording is preserved.
- `tests/Feature/Discovery/SearchProductIsolationTest.php` adds protected-document and cross-tenant-user non-disclosure acceptance coverage.
- `scripts/search-product-contract-verify.php` is required by GitHub Actions and development readiness.
- GitHub Actions run `32481766814` PASS on source head `4ddc9f56c0cbfc3d5fe828687724bcc496af3cad`, including Search 2.0 Product Contract and Unified Source Certification.
- Real Search browser/runtime/PHPUnit execution remains TARGET PENDING.

### N1.13 Collaboration first workflow source closure

- Writer assignee/reviewer discovery now uses shared `TenantMemberDirectory::activeUsers()` instead of platform-wide user discovery.
- `assigned_to` and `reviewer_id` writes use `TenantMemberExists`; platform-wide `exists:users,id` collaboration validation was removed.
- `DocumentReviewComment` and `AdminNotification` now use `BelongsToTenant`; review comment creation explicitly inherits the parent Document tenant.
- Forward migration `2026_08_21_000600_scope_collaboration_identity_to_tenant.php` adds tenant identity to review comments and Admin notifications without mutating historical migrations.
- Historical review comments backfill from their parent Document tenant. Historical notifications backfill only when the user has exactly one active organization membership; zero/multi-organization rows remain `tenant_id = null` and therefore fail closed under tenant scope.
- `tests/Feature/Collaboration/CollaborationTenantIsolationTest.php` covers collaborator discovery/write isolation, direct review/notification tenant isolation and ambiguous legacy notification non-disclosure.
- `scripts/collaboration-product-contract-verify.php` is required by development readiness and GitHub Actions.
- First source run `32489523189` correctly failed because the forward migration used non-portable `->after()` column placement. That cosmetic placement was removed without changing tenant semantics; run `32489614837` then passed Collaboration Product Contract, Unified Source Certification and every prior source gate on head `9c254e551fb8346eb553e67403fc7baeb09fe53e`.
- Real Collaboration PHPUnit/browser/runtime execution remains TARGET PENDING.

### N1.14 Automation first workflow source closure

- Existing Automation foundations were retained: trigger/action registries, transactional event bus, condition evaluator, inbound webhook signature/replay protection, outbound URL policy/signing, run claims and queue tenant restoration were already strong.
- Automation Admin user discovery now uses `TenantMemberDirectory`; platform-wide active-user disclosure was removed.
- `AutomationDefinitionValidator` requires `admin.notification` targets to be active members of the current organization. `WorkflowActionExecutor` rechecks membership again at execution time so stale workflow definitions fail closed after a membership is removed/deactivated.
- Workflow slug validation and database uniqueness are tenant-scoped rather than platform-global.
- Automation-event nullable idempotency uniqueness is tenant-scoped, so the same external idempotency key may safely exist in different organizations without suppressing each other.
- `WorkflowStepRun` now uses `BelongsToTenant`; forward migration `2026_08_21_000700_scope_automation_identity_to_tenant.php` backfills every step from its parent run and aborts if no trustworthy parent tenant exists.
- `DeliverWebhookJob::failed()` restores the delivery tenant through `TenantExecutionScope` before persisting terminal failure state.
- `PortableNullableUnique::drop()` centralizes SQL Server/non-SQL Server index-drop behavior so migrations remain free of raw DB statements while preserving SQL Server filtered-index semantics.
- `tests/Feature/Automation/AutomationTenantIsolationTest.php` covers cross-tenant notification target non-disclosure/rejection, same workflow slug + event idempotency key across tenants and runtime stale-membership rejection.
- `scripts/automation-product-contract-verify.php` is required by development readiness and GitHub Actions and guards queue tenant restoration, idempotency, step ownership and inbound/outbound webhook safety.
- First N1.14 run `32492812146` correctly failed preflight because the initial migration contained raw SQL for SQL Server index drop and the portability analyzer still expected the old nullable-unique declaration count. The drop behavior moved into the shared portable helper and analyzer accounting was updated without weakening the raw-SQL migration prohibition.
- GitHub Actions run `32493091576` then passed Certification preflight, Primary SQL Portability, Automation Product Contract, Unified Source Certification and every prior source gate on head `4689abd4b91a4a293bfbf4dc365befb56a2cc04e`.
- Real Automation PHPUnit/browser/queue/inbound-outbound webhook execution remains TARGET PENDING.

### N1.15 AI Platform first workflow source closure

- Existing Core had no dedicated AI module/provider/runtime surface, so the first workflow was intentionally scoped to tenant AI connections, provider-neutral bounded text generation and privacy-minimal generation metadata rather than claiming a complete vendor AI suite.
- `AiConnection` and `AiGenerationRun` are tenant-owned through `BelongsToTenant`; connection credentials use Laravel `encrypted:array` casting and are hidden from serialization.
- Core defines `AiTextProviderContract`, `AiProviderRegistry` and `AiGenerationService`; no OpenAI/Anthropic/vendor AI SDK was added to Core. Verified extensions can register adapters without changing the Core generation contract.
- Generation re-resolves the selected connection inside the active tenant, requires an enabled/healthy registered provider, enforces per-connection input/output bounds and reserves the daily request quota under a concurrency mutex before calling the provider. Failed provider attempts therefore also consume admission budget instead of enabling retry-based quota bypass.
- Generation history has no raw `prompt` or `output` columns. It stores SHA-256 digests, lengths, requested/observed token counts, status/timestamps and a strictly validated provider request identifier. Provider failure messages persisted by Core are generic.
- Raw generated text is returned by a direct JSON response and held only in browser-local React state. It is not placed in Laravel session flash, preventing DB-backed session persistence of generated content.
- Provider health diagnostics are reduced to generic Core messages so an adapter cannot persist/echo secret or prompt content through health text.
- Provider settings reject secret-like keys; secrets must use encrypted Credentials JSON. Changing provider requires an explicit new credentials payload (including `{}` for no-secret adapters) so old-provider credentials cannot silently cross provider boundaries.
- Admin routes are authenticated, verified, admin-only, tenant route-bound and permission/throttle gated. AI capabilities are registered in the runtime graph and `AiServiceProvider` is bootstrapped explicitly.
- `tests/Feature/Ai/AiPlatformIsolationTest.php` covers cross-tenant connection isolation, encrypted-at-rest credentials, metadata-only generation history, quota admission and cross-tenant execution rejection.
- `scripts/ai-platform-product-contract-verify.php` is required by development readiness and GitHub Actions and guards provider neutrality, credential privacy, raw-content non-persistence, health/quota/bounds admission and route/UI privacy semantics.
- GitHub Actions run `32501783846` SUCCESS on source head `3b9eb2d1012336b43aa06a2d01841f7fc9d19b5e`, including AI Platform Product Contract, Unified Source Certification and every prior source gate.
- Real Laragon PHPUnit/browser AI execution and controlled real provider-adapter verification remain TARGET PENDING.

### N1.16 Multisite / Organizations first workflow source closure

- Existing Enterprise foundations already provided organization models, memberships, domains, roles, SSO/SCIM primitives, tenant context and tenant-role authorization; the audit therefore focused on cross-organization route and identity boundaries rather than rewriting the subsystem.
- Root cause closed: `RequirePermission` authorized the active organization, but `EnterpriseOrganization` is the tenant root and has no `tenant_id`, so generic route binding did not reject a different `{organization}` route root after current-tenant permission success.
- `EnsureTenantRouteBinding` now treats `EnterpriseOrganization` specially and requires its primary key to match the active `TenantContext`; current-tenant permissions cannot be replayed against another organization's route and mismatches return 404.
- Organization management navigation switches the session tenant before visiting a different organization's management route.
- Organization switching now validates UUID shape, resolves active organizations, verifies access and returns 404 for inaccessible/nonexistent IDs without a platform-wide `exists` validation disclosure.
- Enterprise Admin action props now compose global RBAC with `TenantAuthorizationService`, matching route authorization instead of displaying controls from global permission state alone.
- Ordinary organization admins no longer receive a platform-wide user directory. Direct attachment of an existing platform identity is server/UI restricted to Super Admin; ordinary organization admins retain invitation-by-email.
- Direct attachment accepts only active users. Impersonation validation is scoped to active organization membership and its UI picker is derived from the organization's own member list.
- SSO adapter health output is generic/fail-closed so arbitrary adapter diagnostic text is not flashed to Admin.
- `tests/Feature/Enterprise/MultisiteOrganizationIsolationTest.php` adds six source acceptance regressions for cross-org route replay, hidden switching, platform-user non-disclosure/direct-attach denial, invitation preservation, member-scoped impersonation and nested-resource rejection.
- `scripts/multisite-organizations-product-contract-verify.php` is required by development readiness and GitHub Actions. It also guards repository-level mandatory weighted progress tracking through `AGENTS.md` + `NEXORA_PROGRESS.md`.
- Integrated run `32504705855` passed Multisite / Organizations Product Contract, Unified Source Certification and every prior gate on implementation/progress head `e6c884f714e6419794b1c11566e978987a73ecad`; progress-only head `9f26b27b48e55a1d5f7f7ef2b3d7b210b2adb29f` was reconfirmed green by run `32504935527`.
- Real Laragon/browser/PHPUnit organization switching, invitation, domain, SSO/SCIM and impersonation execution remains TARGET PENDING.

### N1.17 SSO / Enterprise Governance source closure

- `enforce_for_members` is now a real authentication policy. Active ordinary organization members cannot bypass an enabled enforced provider using a local password; Super Admin retains explicit break-glass local access.
- Login UI surfaces only enabled, registered and protocol-compatible providers, with generic required/unavailable messaging.
- SSO start/callback state is one-time, expiring and bound to organization + provider; callback rechecks adapter/protocol, validates bounded absolute HTTP(S) redirects and normalized provider identity email, requires active user + active membership, rotates the authenticated session and selects the organization.
- Adapter exceptions and diagnostics remain behind generic Core failure messages.
- `EnterpriseSsoProvider` recursively rejects secret-like keys from public unencrypted `configuration`; secret payload remains encrypted/hidden.
- `ScimTokenManager` requires active organizations, canonical token prefix, enabled/non-revoked/unexpired state and fails closed after organization suspension.
- SCIM lifecycle is tenant-local: membership carries active/suspended state; existing foreign platform identities cannot be silently attached; same-tenant privileged roles are preserved; owner/admin deactivation through SCIM is denied.
- Invitation creation supersedes stale pending tokens for the same organization/email; acceptance requires active account/organization, preserves existing owner/admin authority, supersedes stale tokens and selects the accepted organization in session.
- `ImpersonationManager` rejects nested sessions and unauthorized/inactive actors/targets; stop validates active session/current target/original actor authority before restoration and otherwise fails closed.
- Source Guard SSO/SCIM security checks were made semantic/whitespace-tolerant rather than formatting dependent; the Multisite progress contract was made section-number independent.
- `tests/Feature/Enterprise/EnterpriseIdentityGovernanceTest.php` covers enforced password denial + Super Admin break-glass, SSO provider-state/protocol binding, public secret-config denial, suspended-tenant SCIM token rejection, cross-tenant identity attach denial, tenant-local SCIM lifecycle/privilege preservation, invitation replay/role/session behavior and nested impersonation denial.
- `scripts/enterprise-governance-product-contract-verify.php` is required by development readiness and GitHub Actions.
- GitHub Actions run `32508900897` SUCCESS on source head `1b86f3975438e1ba8eb7ede0f7f54fe9e6e088e3`, passing every prior gate, Multisite / Organizations Product Contract, SSO / Enterprise Governance Product Contract and Unified Source Certification.
- Real Laragon/browser/PHPUnit SSO adapter login enforcement, SCIM provisioning/revocation, invitation and impersonation execution remains TARGET PENDING.

---

## 7. Current progress dashboard

### Weighted Project Power

```text
PROJECT POWER   76.5%  ███████████████░░░░░
SOURCE POWER    99.0%  ████████████████████
TARGET POWER    50.0%  ██████████░░░░░░░░░░
RELEASE POWER   25.0%  █████░░░░░░░░░░░░░░░
```

Detailed weights, per-block status and every-apply history are maintained in `NEXORA_PROGRESS.md`.

### Platform implementation

```text
████████████████████  ~99.0%
```

### Real functional verification

```text
██████████░░░░░░░░░░  ~50%
```

Source implementation increased through N1.17; real verification intentionally did not rise because N1.11-N1.17 and prior DEV-4/DEV-5 product work have not yet been executed across the required real targets.

| Phase | Progress | Status |
|---|---:|---|
| DEV-0 Package/bootstrap | 90% | PARTIAL — final dependency certification deferred |
| DEV-1 Installer | 100% source | SOURCE DONE — clean live rc.94 install confirmation still required |
| DEV-2A Historical TypeScript remediation | 100% | SOURCE DONE |
| DEV-2B TypeScript/Vite target build | 100% reported | TARGET VERIFIED for the previously reported Laragon build |
| DEV-3 Laravel/install runtime | 80% source / 75% live | PARTIAL — source convergence gate green; live rc.93 repair evidence pending |
| DEV-4 Login/admin/core functional QA | 98% source / 30% live | PARTIAL — major product workflows source-gated; broad target QA pending |
| DEV-4A Site settings + media reuse | 100% source / target pending | SOURCE DONE |
| DEV-4B Theme workflow | 100% source contract / target pending | SOURCE DONE |
| DEV-4C Extension workflow | 100% source contract / target pending | SOURCE DONE |
| DEV-4D Studio first publish workflow | 100% source contract / target pending | SOURCE DONE |
| DEV-4E Documents + Collections | 100% source contract / target pending | SOURCE DONE |
| DEV-4F Publishing + SEO | 100% source contract / target pending | SOURCE DONE |
| DEV-4G Admin UX source pass | 100% source contract / target pending | SOURCE DONE for current pass |
| DEV-4H Forms + Data + Workflows | 100% source contract / target pending | SOURCE DONE |
| DEV-4I Data Connections product | 100% source contract / target pending | SOURCE DONE |
| DEV-5 DB/services portability | ~95% source / real matrix pending | PARTIAL — source/harness green, TARGET VERIFIED evidence pending |
| N1.9 Marketplace first workflow | 100% source contract / target pending | SOURCE DONE |
| N1.10 Commerce 2.0 first workflow | 100% source contract / target pending | SOURCE DONE |
| N1.11 CRM / Membership / Customer Portal | 100% source contract / target pending | SOURCE DONE |
| N1.12 Search 2.0 | 100% source contract / target pending | SOURCE DONE |
| N1.13 Collaboration | 100% source contract / target pending | SOURCE DONE |
| N1.14 Automation | 100% source contract / target pending | SOURCE DONE |
| N1.15 AI Platform Capabilities | 100% source contract / target pending | SOURCE DONE |
| N1.16 Multisite / Organizations | 100% source contract / target pending | SOURCE DONE |
| N1.17 SSO / Enterprise Governance | 100% source contract / target pending | SOURCE DONE |
| N1.18 Public APIs / Webhooks / SDK | Foundation/partial | NEXT SOURCE BLOCK |
| DEV-6 Final C1-C6/release certification | 10% | DEFERRED CERTIFICATION |

---

## 8. NEXT ACTION — exact execution order

### Immediate live-target gate

Do **not** overwrite installed rc.93 with rc.94 merely to repair four fingerprints.

1. Run the prepared rc.93 Post-Install Identity Repair Pack externally against `D:\laragon\www\nexora`.
2. Verify:

```bat
php artisan nexora:runtime:compatibility-status --deep
```

Expected:

```text
status: pass
mismatches: []
compatible: true
mode: installed-data-plane
```

3. Verify:

```bat
php artisan nexora:runtime:post-install-status --assert-ready
```

4. If both pass, test:

```text
http://nexora/login
http://nexora/admin
```

5. Only after those pass may GitHub issue #2 be closed.
6. Keep the installed rc.93 recovery target separate from the rc.94 development checkout.

### Development-target verification after live recovery

Use a separate development checkout of `dev/n1-0b-core-functional-qa`, then run:

```bat
scripts\development-readiness.bat --full
php artisan test
npm run build
php scripts\database-target-matrix.php --list
php scripts\database-target-matrix.php --drivers=sqlite,mysql,mariadb,pgsql,sqlsrv --evidence
```

At minimum explicitly exercise Settings, Media, Theme, Extensions, Studio, Documents, Collections, Publishing, SEO, Forms, Data Workflows, Data Connections, Marketplace, Commerce, Customer Portal, CRM, Membership, Search, Collaboration, Automation, AI Platform, Multisite / Organizations, SSO / Enterprise Governance and responsive Admin navigation on the real target.

For network engines use only disposable databases whose names start with `nexora_matrix_`. For auxiliary connectors/provider actions, use controlled target services and record separate evidence. Never infer target verification from source fixtures. For AI, a provider becomes TARGET VERIFIED only after a controlled registered adapter is exercised without exposing credentials/raw generation history. For SSO/SCIM, target evidence requires a controlled registered identity adapter and SCIM token lifecycle exercise.

### Remaining source/target sequence

```text
N1.18 Public APIs / Webhooks / SDK source audit + closure
  ||
Live rc.93 runtime recovery evidence
  -> separate dev checkout full PHPUnit/build/product browser QA
  -> real SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix evidence
  -> optional managed AWS/auxiliary connector/provider/AI/SSO target evidence where services exist
  -> close remaining target defects
  -> final DEV-6 reviewed locks + C1-C6/release certification
  -> mark PR Ready + merge automatically when all required gates are genuinely final
```

During every block, inspect GitHub open issues first and again before final handoff.

### GitHub main branch protection

Desired GitHub server-side rule remains:

```text
require pull request before merge
require Source certification status check
require stale review dismissal on new commits
require conversation/review resolution where supported
block force pushes
block branch deletion
enforce for administrators
```

Current branch API reports `protected=false`. Apply only through an authorized GitHub branch/ruleset settings mutation; do not substitute CI/source files for actual branch protection.

---

## 9. Product roadmap

```text
N1.0A  Installation + Runtime Closure
N1.0B  Super Admin + Core Application QA
N1.1   Admin Design System / UX Closure
N1.2   Theme Engine Product Closure
N1.3   Plugin / Extension Product Closure
N1.4   Studio / Visual Builder
N1.5   CMS / Documents / Collections
N1.6   Media / DAM
N1.7   SEO / Publishing
N1.8   Forms / Data / Workflows
N1.9   Marketplace
N1.10  Commerce 2.0
N1.11  CRM / Membership / Customer Portal
N1.12  Search 2.0
N1.13  Collaboration
N1.14  Automation
N1.15  AI Platform Capabilities
N1.16  Multisite / Organizations
N1.17  SSO / Enterprise Governance
N1.18  Public APIs / Webhooks / SDK
N1.19  Import / Export / WordPress migrations
N1.20  Observability
N1.21  Developer Experience / Forge
N1.22  Sentinel 2.0
N1.23  Marketplace 2.0
N1.24  Cloud / HA / Distributed Runtime
N1.25  Backup / DR / Upgrade Certification
N1.26  Performance + Accessibility + Release
N2.0   Stable Production
```

---

## 10. AI execution protocol

### Before work

1. Read this entire file.
2. Read `NEXORA_PROGRESS.md` in full.
3. Inspect current GitHub branch/source before trusting old claims.
4. Query open GitHub issues for `Vertex-Systems-Network/nexora`; classify each as source, target/runtime, UX, data or certification.
5. Identify dev source version, installed target version, blocker class, current PR/branch and latest CI state.
6. Do not repeat completed work without regression evidence.
7. Prefer the next roadmap gate while solving applicable open issues in the same pass.
8. Inspect `main` branch protection state; do not call repository governance protected unless GitHub reports it protected.

### During work

1. Make the smallest architecture-correct root-cause fix.
2. Add regression protection for repeated blocker classes.
3. Preserve trust boundaries.
4. Distinguish development convenience from release security.
5. Never mark target PASS from static checks alone.
6. Use development branch + PR for meaningful source changes.
7. Do not close runtime/environment GitHub issues from source CI alone.
8. For DB portability, never point the target matrix at customer/staging/production/shared data; use the guarded disposable naming contract only.
9. After every meaningful apply, update `NEXORA_PROGRESS.md` with exact head/evidence, weighted progress where justified, blockers, next action and a new Apply Log row.

### After work

1. Re-query open GitHub issues and update issue comments/state with source/target evidence.
2. Update `NEXORA_PROGRESS.md` for the final apply/checkpoint.
3. Update this file before final response/merge/package: metadata/branch/PR/release/CI, open issue state, checkpoint/live target state, completed work, progress dashboard, `NEXT ACTION`, and append history.
4. Keep PR #1 draft until the real-target gate is satisfied.
5. Once all required source CI, target/runtime/product QA, DB evidence and applicable issue gates pass, mark the PR Ready and merge automatically without requesting another user confirmation.

Never delete prior history entries. Corrections are appended and explain what changed.

---

## 11. Required history-entry format

```markdown
### YYYY-MM-DD — rc.xx / vx.xx — Short title

- Trigger / observed blocker:
- Root cause:
- Changes applied:
- Verification completed:
- Real-target evidence:
- Remaining blocker:
- Next exact action:
```

Use `No release` when no rc release was produced.

---

## 12. Change History

### 2026-08-21 — No release — Canonical AI project-state ledger introduced

- Trigger / observed blocker: project knowledge, completion claims, roadmap and live state were spread across long chat/release history.
- Root cause: no single project-local append-only AI handoff source.
- Changes applied: added `NEXORA_AI_PROJECT_STATE.md` with product definition, architecture rules, completion semantics, current source/live state, roadmap, next action, AI protocol and history.
- Verification completed: ledger updates were proven outside source-attestation roots.
- Real-target evidence: documentation-only change; no target mutation.
- Remaining blocker: live rc.93 post-install convergence.
- Next exact action: repair/verify runtime, then DEV-4.

### 2026-08-21 — rc.94 / v5.29 — Post-install runtime stabilization architecture

- Trigger / observed blocker: live rc.93 failed only `environment`, `activation`, `service`, `process` while immutable/core runtime planes matched.
- Root cause: final runtime fingerprints could be sealed in the long-running installer request before final `.env`/cache/session/deployment context loaded.
- Changes applied: rc.94 moved final reconciliation/sealing to a fresh `/install/runtime-handoff` request; immutable planes remain fail-closed; separate safe rc.93 repair pack prepared.
- Verification completed: rc.94 installer/runtime source contracts PASS; package zero-state.
- Real-target evidence: rc.93 immutable/runtime core planes matched; four post-install planes stale.
- Remaining blocker: safe rc.93 repair + compatibility/post-install PASS.
- Next exact action: repair live rc.93, then login/admin QA.

### 2026-08-21 — rc.93 / v5.28 — Browser/CLI deployment identity convergence

- Trigger / observed blocker: browser reported source mismatch while CLI install-readiness was 8/8 PASS.
- Root cause: memoized browser `source-fallback` identity vs fresh CLI process.
- Changes applied: refresh deployment identity + controlled source-fallback re-attestation; persisted identities still hard-fail on mismatch.
- Verification completed: installer/runtime source contracts PASS.
- Real-target evidence: CLI source/deployment PASS.
- Remaining blocker: post-install stabilization.
- Next exact action: stabilize post-install planes.

### 2026-08-21 — rc.92 / v5.27 — Installer Blade component closure

- Trigger / observed blocker: `/install` failed on missing `lucide-circle` component.
- Root cause: invalid per-icon Blade alias.
- Changes applied: shared Lucide component pattern + component-resolution regression check.
- Verification completed: unresolved installer Blade components reduced to zero.
- Real-target evidence: installer progressed to next exact blocker.
- Remaining blocker: deployment identity readiness.
- Next exact action: browser/CLI identity convergence.

### 2026-08-21 — rc.91 / v5.26 — Installer bootstrap isolation closure

- Trigger / observed blocker: `/install` returned HTTP 500 after Composer/build.
- Root cause: pre-install requests still traversed middleware touching tenant/auth/DB/runtime state.
- Changes applied: installer routes isolated; pre-install DB/auth touches reduced; installer-specific error surface added.
- Verification completed: bootstrap isolation/static contracts PASS.
- Real-target evidence: browser reached installer Blade and exposed next error.
- Remaining blocker: invalid Blade icon component.
- Next exact action: Blade component closure.

### 2026-08-21 — rc.90 / v5.25 — Runtime bootstrap fence closure

- Trigger / observed blocker: CLI compatibility PASS in bootstrap mode while browser readiness failed.
- Root cause: global heartbeat/readiness enforcement before installation.
- Changes applied: pre-install/bootstrap bypasses installed-runtime heartbeat fences; installed runtime keeps enforcement.
- Verification completed: bootstrap contracts PASS.
- Real-target evidence: CLI bootstrap compatibility PASS.
- Remaining blocker: broader installer middleware isolation.
- Next exact action: isolate installer web pipeline.

### 2026-08-21 — rc.89 / v5.24 — Development Closure Batch A

- Trigger / observed blocker: certification-heavy workflow before usability closure.
- Root cause: development and final audit mixed.
- Changes applied: development-first plan, installer UX/auxiliary service/package hygiene/dependency bootstrap/theme/select/cancel/429 improvements.
- Verification completed: source/static + PHP lint PASS; real build delegated to Laragon.
- Real-target evidence: user later reported clean build and bootstrap compatibility PASS.
- Remaining blocker: installer/browser runtime errors.
- Next exact action: close real installer blockers.

### 2026-08-21 — No release — GitHub canonical workflow activated

- Trigger / observed blocker: source moved to GitHub; future work needs central push/pull instead of ZIP-only handoffs.
- Root cause: archive-only iteration made branch history/review harder.
- Changes applied: verified `Vertex-Systems-Network/nexora` `main` as rc.94/v5.29; created `dev/n1-0b-core-functional-qa`; added `AGENTS.md`; established GitHub as canonical source workflow.
- Verification completed: repository access includes admin/push; `main` commit `f555fe396cda0e82efd4445ba016f709de3398c8`; repository ledger matched local rc.94 ledger.
- Real-target evidence: no Laragon mutation in this synchronization pass.
- Remaining blocker: live rc.93 post-install convergence.
- Next exact action: continue DEV-4 source audit while obtaining live convergence/login evidence.

### 2026-08-21 — No release — DEV-4 GitHub source gate and draft PR opened

- Trigger / observed blocker: GitHub workflow needed an enforceable product-facing DEV-4 source gate rather than chat/package claims alone.
- Root cause: auth/admin/core surfaces existed, but no single DEV-4 contract ran both locally and in CI; source-only CI referenced absent `package-lock.json` for npm caching.
- Changes applied: added `scripts/dev4-core-functional-contract-verify.php`; wired it into development readiness and GitHub Actions; removed lockfile-dependent npm cache; opened PR #1.
- Verification completed: certification preflight, Source Guard, DEV-4 contract and unified source certification PASS.
- Real-target evidence: no new Laragon target execution in this source pass.
- Remaining blocker: live rc.93 convergence.
- Next exact action: verify/repair rc.93 -> `/login` + `/admin`; continue product-facing source work.

### 2026-08-21 — No release — DEV-4 site identity and regional settings source closure

- Trigger / observed blocker: global Settings only exposed application name and appearance tokens; platform-level logo, default display timezone and default language were incomplete.
- Root cause: settings keys, validation, shared Inertia props, branding and installed default-locale resolution were incomplete; locale middleware also ran before tenant context resolution.
- Changes applied: added validated `app.logo_url`, `app.default_timezone`, `app.default_locale`; expanded Settings UI; shared props; Admin/Auth logo; installed default locale; tenant-before-locale ordering; tests/contracts.
- Verification completed: PHP syntax, Laravel Runtime, Security, Frontend, Inertia, Source Guard, preflight and unified source certification PASS; GitHub Actions source run successful.
- Real-target evidence: no Laragon execution for this branch batch.
- Remaining blocker: live rc.93 convergence + target settings execution.
- Next exact action: runtime recovery then target DEV-4 tests.

### 2026-08-21 — No release — Runtime issue #2 source regression gate

- Trigger / observed blocker: GitHub issue #2 records live rc.93 `environment/activation/service/process` identity mismatch.
- Root cause: rc.93 sealed install-sensitive fingerprints before final installed runtime context stabilized.
- Changes applied: added `scripts/post-install-runtime-convergence-contract-verify.php`; wired it into development readiness/CI; exact mutable-plane allow-list is guarded and immutable planes remain fail-closed. A first CI parser bug in the contract itself was fixed.
- Verification completed: GitHub Actions run `32425880362` SUCCESS with post-install convergence contract PASS.
- Real-target evidence: no new rc.93 repair result; source evidence only.
- Remaining blocker: issue #2 stays open until live compatibility + post-install readiness PASS.
- Next exact action: run safe rc.93 repair and the two required Artisan verification commands.

### 2026-08-21 — No release — Reusable Media + Theme product workflow source closure

- Trigger / observed blocker: Media needed central reusable selection and Theme Engine needed a real user workflow/acceptance contract rather than foundation-only claims alone.
- Root cause: Media reuse was not a shared chooser; Theme tests did not prove scan/install/preview/activate/public-render/rollback in one acceptance flow; upload/preview errors were weakly surfaced.
- Changes applied: Media picker JSON mode + shared `MediaPicker`; Settings logo integration; picker feature-test source; accessible shared FilePicker error API; Theme preview error UX; Theme real-ZIP acceptance-test source; Theme Product source contract.
- Verification completed: GitHub Actions run `32426200738` SUCCESS with runtime, DEV-4, Theme and unified source contracts PASS.
- Real-target evidence: no Laragon execution for the new Media/Theme branch work.
- Remaining blocker: target execution pending.
- Next exact action: continue Extension workflow source closure while live rc.93 recovery remains the target gate.

### 2026-08-21 — No release — Extension product workflow source closure

- Trigger / observed blocker: Extensions workspace lacked direct package-upload UX and destructive uninstall happened without explicit confirmation; existing tests did not prove the complete trust/lifecycle chain.
- Root cause: upload existed only via Sentinel workspace; Extensions UI started after verified artifact creation; uninstall UX was too easy to trigger.
- Changes applied: Extensions upload modal sends package to Sentinel quarantine only when operator has extension-install + Sentinel scan/view permissions; invalid summary icon fixed; shared FilePicker errors used; uninstall requires ConfirmDialog; declarative real-ZIP acceptance-test source covers Sentinel -> artifact -> install -> enable -> disable -> uninstall; Extension Product source contract added.
- Verification completed: GitHub Actions run `32426515463` SUCCESS with Extension Product contract PASS alongside previous gates.
- Real-target evidence: no Laragon execution for this branch batch.
- Remaining blocker: target execution pending; issue #2 still open.
- Next exact action: Studio product workflow closure + live rc.93 recovery evidence.

### 2026-08-21 — No release — Studio first publish/public-render workflow source closure

- Trigger / observed blocker: Studio had substantial visual-builder code, but acceptance evidence only proved create/save/status-publish and did not prove actual public rendering, fallback, concurrency or unsafe-link normalization.
- Root cause: product acceptance coverage stopped before ThemePage public integration and critical stale-write/safe-output behavior.
- Changes applied: expanded `StudioFlowTest` source to cover create/save/publish -> public document Studio render, document title binding, responsive CSS, unpublish -> Document Engine fallback, stale lock rejection and `javascript:` URL normalization; added `scripts/studio-product-contract-verify.php`; wired Studio gate into development readiness and CI.
- Verification completed: first Studio contract run correctly failed on a wrong static UI marker; marker was corrected. GitHub Actions run `32427092798` then passed preflight, Source Guard, runtime convergence, DEV-4, Theme, Extension, Studio and unified source certification.
- Real-target evidence: no Laragon execution of the expanded Studio test yet; SOURCE DONE only.
- Remaining blocker: live issue #2 + target execution of current branch workflows.
- Next exact action: recover/verify rc.93 live target; in parallel move source work to CMS/Documents/Collections product closure.

### 2026-08-21 — No release — Documents, Media reuse and Content Collections source closure

- Trigger / observed blocker: Writer media choice had to scale beyond fixed lists and Nexora lacked a generic Webflow/WordPress-class Content Collections workflow.
- Root cause: Writer preview/media ownership was split across caller/static assumptions; no generic tenant-native collection model/schema/routes/admin workflow existed; historical tenant source guard assumed all future tenant models must be in the original 51-table backfill manifest.
- Changes applied: Writer uses shared searchable MediaPicker and canonical `media_asset_id`; added tenant-native Content Collections, typed custom fields, document membership/per-entry data, permissions/audit/non-destructive lifecycle, acceptance tests and product contract; source guard now distinguishes historical manifest roots from forward tenant-native models without mutating historical migration semantics.
- Verification completed: GitHub Actions run `32429295616` SUCCESS with Document + Content Collection gates and unified source certification PASS.
- Real-target evidence: no Laragon execution of these new workflows; SOURCE DONE only.
- Remaining blocker: live issue #2 + target execution.
- Next exact action: Publishing/SEO end-to-end source closure while preserving live target boundary.

### 2026-08-21 — No release — Publishing, SEO and protected public visibility source closure

- Trigger / observed blocker: Article Settings preloaded a fixed 250-image dropdown; social SEO media lacked durable Media Library references; saved social/extra robots fields were not fully emitted in public head; membership-protected documents could surface in anonymous discovery lists.
- Root cause: Publishing/SEO workflows were built from strong foundations but were not fully connected through canonical media, public metadata output and one shared public-visibility policy.
- Changes applied: Publishing hero + SEO social MediaPicker integration; canonical social media IDs + usage tracking; resolved social SEO contract; route-correct canonical fallback; robots/OG/Twitter public tags; `PublicDocumentVisibility`; filtering across home/blog/taxonomy/authors/series/related/sitemap; expanded acceptance tests; added Publishing + SEO product contract.
- Verification completed: GitHub Actions run `32430956498` SUCCESS after performance delegation and contract-marker regressions were corrected; all product gates and unified source certification passed.
- Real-target evidence: no Laragon execution of Publishing/SEO changes; SOURCE DONE only.
- Remaining blocker: live issue #2 + target execution.
- Next exact action: application-wide Admin UX source pass and live recovery evidence.

### 2026-08-21 — No release — Application-wide Admin UX responsive/accessibility source closure pass

- Trigger / observed blocker: desktop-only organization switching made tenant changes impossible from mobile Admin; global toast feedback had no explicit dismiss action/canonical status icons; shared Select did not expose invalid state as strongly as Input/Textarea; route-progress hide timers could outlive navigation lifecycle.
- Root cause: shared primitives were strong but a few cross-app interaction details remained outside the existing browser/UX static gate.
- Changes applied: responsive OrganizationSwitcher/LanguageSwitcher APIs; mobile sidebar tenant/language controls; responsive header selectors; lazy/async flag images; dismissible icon-based toast; Select `isInvalid` + announced error state; route-progress show/hide timer cleanup; dedicated Admin UX product contract composed with existing browser UX analyzer.
- Verification completed: GitHub Actions run `32431449676` SUCCESS — preflight, Source Guard, runtime, all Theme/Extension/Studio/Document/Collection/Publishing+SEO/Admin UX product gates and Unified Source Certification PASS.
- Real-target evidence: no new Laragon execution for this Admin UX pass; SOURCE DONE only.
- Remaining blocker: live rc.93 issue #2 and broad target functional QA.
- Next exact action: safe rc.93 recovery -> compatibility/post-install PASS -> login/admin; continue Forms/Data/Workflows + DEV-5 source review in parallel.

### 2026-08-21 — No release — Forms + Data + Workflows source closure

- Trigger / observed blocker: Nexora needed a complete first forms/data workflow rather than isolated form/schema primitives.
- Root cause: public submission semantics, privacy-minimal storage, Automation bridging, tenant permissions and non-destructive lifecycle needed one enforceable product contract.
- Changes applied: tenant form/submission schema and models, controlled form definition validator, schema-derived public validation/storage, CSRF/honeypot/throttle/status guards, `form.submitted` Automation bridge, Admin workflow and acceptance contract.
- Verification completed: Forms + Data + Workflows product source gate PASS in full source run `32476210643` and subsequent full runs.
- Real-target evidence: no new Laragon execution for this workflow; SOURCE DONE only.
- Remaining blocker: target functional execution.
- Next exact action: include Forms/Data/Workflows in the separate dev-checkout PHPUnit/browser QA pass.

### 2026-08-21 — No release — Data Connections tenancy/product source closure

- Trigger / observed blocker: auxiliary data-service records required enterprise tenant ownership, encrypted credential handling, safe rotation and reusable runtime testers.
- Root cause: the original global connection shape and historical tenant manifest assumptions were insufficient for a multi-organization product workflow.
- Changes applied: tenant-scoped `nx_data_connections`, migration/backfill/quarantine rules, encrypted secrets, non-disclosing Admin payload, fresh-health requirements, test/enable/remove lifecycle, dynamic tenant-root round-trip coverage and dedicated product gate.
- Verification completed: Data Connections product gate PASS; historical 51-root migration metric remains frozen while current tenant-aware model discovery validates the expanded set.
- Real-target evidence: no external connector target execution yet.
- Remaining blocker: connector-specific target evidence + broad target QA.
- Next exact action: continue DEV-5 connector and primary SQL portability review.

### 2026-08-21 — No release — Primary SQL portability and installer database UX source closure

- Trigger / observed blocker: supported SQL labels existed, but DEV-5 needed enforceable native/managed driver mapping, version/config/backup/migration coverage and installer state correctness.
- Root cause: managed aliases had incomplete test coverage; runtime tenant test had a stale historical count; installer could preserve a successful DB test after configuration changes or carry stale defaults between drivers.
- Changes applied: complete registry/version/provisioner unit matrix, dynamic tenant compatibility coverage, Primary SQL source gate, registry-driven installer DB defaults/policy and mandatory re-test invalidation; managed create remains disabled.
- Verification completed: GitHub Actions run `32435144381` first closed the installer DB UX gate; later full runs reconfirmed Primary SQL + Installer Database UX + Unified Source Certification PASS.
- Real-target evidence: no new cross-engine real matrix run yet.
- Remaining blocker: real disposable engine execution.
- Next exact action: run the guarded target matrix on the separate dev checkout.

### 2026-08-21 — No release — Guarded real DB matrix + auxiliary connector portability hardening

- Trigger / observed blocker: DEV-5 had source portability checks but no safe reproducible way to prove each real engine; auxiliary connector audit also found Dynamo endpoint validation and Redis TLS behavior inconsistent with their actual deployment models.
- Root cause: no disposable multi-engine runner/evidence artifact; Admin treated every auxiliary service as endpoint/user-password shaped; PhpRedis path did not explicitly preserve TLS transport from `rediss://`.
- Changes applied: `scripts/database-target-matrix.php` with strict `nexora_matrix_*` empty-database safety, real round-trip test/cleanup, no `.env` rewrite/no DB-container drop; secret-free schema-2 `--evidence`; operator runbook; Dynamo driver-aware endpoint/region/IAM/static-key rules; Redis TLS normalization across PhpRedis/Predis; feature/unit regression coverage; contracts strengthened.
- Verification completed: full source runs reconfirmed all DEV-5 source gates and Unified Source Certification PASS.
- Real-target evidence: source/harness only; no current-branch real SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix artifact has been supplied yet.
- Remaining blocker: live rc.93 recovery + separate dev-checkout product/runtime QA + real DB matrix evidence.
- Next exact action: obtain those target results before claiming DEV-5 TARGET VERIFIED or moving PR #1 out of draft.

### 2026-08-21 — No release — N1.9 Marketplace first product workflow source closure

- Trigger / observed blocker: Marketplace had source/catalog/staging foundations but incomplete lifecycle, freshness, type-specific permission and Theme/Extension promotion behavior.
- Root cause: inactive/resumed source semantics, stale catalog retirement, package type trust boundaries and owning-engine permissions were not all enforced end-to-end.
- Changes applied: strict authoritative catalog sync, pause/resume/remove, resume-forces-fresh-sync, inactive/stale staging denial, theme package type, Sentinel promotion surfaces, type-aware server/UI permissions and cross-permission acceptance regressions.
- Verification completed: Marketplace Product Contract + Theme/Extension contracts + Unified Source Certification PASS in full source CI.
- Real-target evidence: no real Marketplace/browser package target execution yet.
- Remaining blocker: target functional execution.
- Next exact action: include Marketplace in separate dev checkout target QA and continue N1.10 Commerce source closure.

### 2026-08-21 — No release — N1.10 Commerce 2.0 first product workflow source closure

- Trigger / observed blocker: Commerce foundations allowed inactive-product price ordering, had global tenant SKU/slug uniqueness, read-only provider billing UI and retry/failure gaps around provider actions.
- Root cause: foundation services were not yet connected into one tenant-safe, bounded and provider-neutral product workflow with explicit idempotency and capability/health admission.
- Changes applied: bounded money/tax arithmetic, active price windows, serialized order/invoice lifecycle, tenant-scoped SKU/slug forward migration, portable scoped nullable uniqueness, modular Commerce routes/provider, provider-backed invoice payment/refund/subscription create/cancel, pre-provider idempotency short-circuits, failure-state preservation, shared Admin billing actions and acceptance/contract coverage.
- Verification completed: Commerce Product Contract + all prior gates + Unified Source Certification PASS in GitHub Actions run `32476210643` on source head `c8c94cd246627595b0be7d1092def034ab77a705` and subsequent full runs.
- Real-target evidence: provider-billing acceptance test source exists, but no real Laragon PHPUnit/browser/provider target run has been supplied; SOURCE DONE only.
- Remaining blocker: live issue #2 + target QA + DB matrix evidence.
- Next exact action: continue N1.11 CRM/Membership/Customer Portal source audit while obtaining required target evidence in parallel.

### 2026-08-21 — No release — N1.11 CRM / Membership / Customer Portal tenant closure

- Trigger / observed blocker: Customer Portal source was green, but CRM Commerce links and several CRM/Membership identities/owner selectors still allowed platform-wide or cross-tenant ambiguity; lead conversion trusted an externally supplied pipeline object.
- Root cause: historical CRM Commerce links lacked tenant ownership, several uniqueness contracts were global, Membership/CRM chooser APIs were not converged on one tenant-member directory, and lead conversion did not independently re-resolve pipeline ownership.
- Changes applied: forward tenantization migration; `BelongsToTenant` CRM Commerce links; fail-closed link/Membership service checks; tenant-scoped Membership Commerce sync; shared `TenantMemberDirectory`; tenant-member owner validation for Organization/Contact/Lead/Opportunity; lead pipeline/stage re-resolution; expanded acceptance and product contracts. Collection contract compatibility was also corrected for the forward-tenantization analyzer without weakening historical migration semantics.
- Verification completed: first analyzer-compatible pass exposed an old Collection contract assumption, which was corrected; GitHub Actions run `32480925507` then passed Customer Portal, CRM + Membership and all prior gates; run `32481766814` reconfirmed them.
- Real-target evidence: no current-branch Laragon Customer Portal/CRM/Membership execution yet; SOURCE DONE only.
- Remaining blocker: live issue #2 + broad target QA + real DB matrix evidence.
- Next exact action: N1.12 Search 2.0 source audit while target recovery proceeds separately.

### 2026-08-21 — No release — N1.12 Search 2.0 visibility and tenant-isolation closure

- Trigger / observed blocker: anonymous search filtered only `status=published`, so membership-protected published documents could leak through search; Admin global user search queried all platform users; indexed document/media discovery did not derive fully independent permission scopes.
- Root cause: Search predated the shared protected-public-visibility boundary and its Admin user/resource discovery still used broader platform assumptions.
- Changes applied: SearchIndexer now composes with `PublicDocumentVisibility`, bounds/allow-lists resource types and excludes protected documents from public search; `TenantMemberDirectory::search()` added; Admin Search uses tenant members and independently derives document/media scopes; acceptance test + `scripts/search-product-contract-verify.php` added; Search contract wired into development readiness and GitHub Actions. Existing document/media/SEO observer lifecycle was verified and retained.
- Verification completed: GitHub Actions run `32481766814` SUCCESS on head `4ddc9f56c0cbfc3d5fe828687724bcc496af3cad`; Search 2.0 Product Contract and Unified Source Certification PASS with every prior gate green.
- Real-target evidence: no current-branch Laragon Search/PHPUnit/browser execution yet; SOURCE DONE only.
- Remaining blocker: live issue #2 + broad target QA + real DB matrix evidence.
- Next exact action: synchronize PR/ledger, then begin N1.13 Collaboration source audit while target recovery remains a parallel gate.

### 2026-08-21 — No release — N1.13 Collaboration tenant-isolation closure

- Trigger / observed blocker: Writer collaborator discovery and assignment/reviewer validation were platform-wide; Admin notifications had only user identity and review comments lacked direct tenant ownership, creating cross-organization disclosure risk for multi-organization users/direct model queries.
- Root cause: collaboration features predated the shared tenant-member boundary and two historical collaboration tables had no explicit tenant identity.
- Changes applied: Writer now uses `TenantMemberDirectory` + `TenantMemberExists`; review comments and Admin notifications use `BelongsToTenant`; forward collaboration migration adds tenant identity and deterministic/fail-closed historical backfill; review comment writes inherit parent Document tenant; acceptance tests and a dedicated Collaboration product contract were added and wired to readiness/CI.
- Verification completed: run `32489523189` caught non-portable `->after()` usage in the new migration; the placement-only call was removed. GitHub Actions run `32489614837` then passed every source gate including Collaboration Product Contract and Unified Source Certification on head `9c254e551fb8346eb553e67403fc7baeb09fe53e`.
- Real-target evidence: no current-branch Laragon Collaboration/PHPUnit/browser execution yet; SOURCE DONE only.
- Remaining blocker: live issue #2 + broad target QA + real DB matrix evidence.
- Next exact action: begin N1.14 Automation source audit while live rc.93 recovery remains a parallel target gate.

### 2026-08-21 — No release — N1.14 Automation tenant and execution hardening closure

- Trigger / observed blocker: Automation Admin exposed platform-wide users; notification definitions/runtime allowed cross-tenant or stale user targets; workflow slug and automation-event idempotency were globally scoped; step runs lacked direct tenant identity; terminal webhook failure persistence did not restore queue tenant context.
- Root cause: mature Automation foundations predated current enterprise tenant-member and tenant-native identity conventions; queue happy paths were tenant-aware but model/validation/idempotency edge boundaries were inconsistent.
- Changes applied: shared `TenantMemberDirectory::contains()` check; tenant-member picker plus definition/runtime notification validation; tenant-scoped workflow slug and event idempotency forward migration; workflow-step tenantization/backfill; tenant-restored webhook failed callback; portable nullable-unique drop helper/analyzer update; tenant-isolation acceptance source; Automation product contract wired into readiness/CI.
- Verification completed: first run `32492812146` correctly failed preflight on migration raw SQL and the old nullable-unique declaration count. Portability logic moved into the shared helper and analyzer was updated without weakening the migration raw-SQL prohibition. Run `32493091576` then passed Certification preflight, Primary SQL Portability, Automation Product Contract, Unified Source Certification and every prior source gate on head `4689abd4b91a4a293bfbf4dc365befb56a2cc04e`.
- Real-target evidence: no current-branch Laragon Automation/PHPUnit/browser/queue/webhook execution yet; SOURCE DONE only.
- Remaining blocker: live issue #2 + broad dev target QA + real DB matrix evidence.
- Next exact action: synchronize PR/ledger, then begin N1.15 AI Platform Capabilities source audit while target recovery remains separate.

### 2026-08-21 — No release — N1.15 AI Platform provider-neutral privacy and tenant closure

- Trigger / observed blocker: Nexora had no dedicated AI platform surface, while a safe first workflow required tenant isolation, provider neutrality, encrypted credentials, strict admission controls and a guarantee that raw prompts/generated text would not become durable history.
- Root cause: no Core AI provider contract/registry, no tenant-native AI connection/run schema and no shared generation boundary existed. During implementation, session-flash raw output, arbitrary provider health text/request IDs, secret-like settings and cross-provider credential reuse were also identified as privacy/trust risks.
- Changes applied: added `nexora.ai`, AI capabilities and service-provider/routes; tenant-scoped encrypted AI connections and metadata-only generation runs; provider-neutral `AiTextProviderContract`/registry; bounded `AiGenerationService` with tenant re-resolution, health admission, pre-call daily quota reservation, input/output bounds and generic failures; direct JSON/browser-local raw output; secret-like settings rejection; explicit credentials on provider switch; generic health diagnostics; strict provider request-ID validation; acceptance tests and required AI Platform product source contract.
- Verification completed: GitHub Actions run `32501783846` SUCCESS on head `3b9eb2d1012336b43aa06a2d01841f7fc9d19b5e`; AI Platform Product Contract, Unified Source Certification and every prior source gate passed.
- Real-target evidence: no current-branch Laragon AI PHPUnit/browser execution or controlled real provider-adapter call has been supplied; SOURCE DONE only.
- Remaining blocker: live issue #2 + broad dev target QA + real DB matrix + controlled AI provider-adapter target evidence.
- Next exact action: begin N1.16 Multisite / Organizations source audit while live rc.93 recovery remains a separate target gate.

### 2026-08-21 — No release — N1.16 Multisite / Organizations tenant-root authorization closure

- Trigger / observed blocker: Enterprise organization foundations were substantial, but tenant-role permission checks were resolved from the active organization while route-bound `EnterpriseOrganization` roots had no `tenant_id`; normal organization Admin also received a platform user directory for direct member assignment, and SSO health surfaced adapter-owned diagnostics.
- Root cause: generic `EnsureTenantRouteBinding` skipped tenant-root organization models, creating a current-tenant-vs-route-organization confused-deputy gap. Platform identity attachment and impersonation discovery also needed explicit organization/privacy boundaries.
- Changes applied: organization-root route binding to active `TenantContext`; switch-before-manage UI; non-disclosing active organization switching; tenant-aware UI capability resolution; Super Admin-only direct platform identity attachment; organization-admin invitation preservation; member-scoped impersonation validation/picker; generic SSO adapter health diagnostics; six cross-organization acceptance regressions; required Multisite / Organizations product source contract; repository-governed `NEXORA_PROGRESS.md` weighted progress protocol.
- Verification completed: integrated run `32504705855` passed Multisite / Organizations Product Contract, Unified Source Certification and every prior source gate; progress-only head `9f26b27b48e55a1d5f7f7ef2b3d7b210b2adb29f` was reconfirmed by green run `32504935527`.
- Real-target evidence: no current-branch Laragon browser/PHPUnit execution of organization switching/member/invitation/domain/SSO/SCIM/impersonation workflows; SOURCE DONE only.
- Remaining blocker: live issue #2 + broad dev target QA + real DB matrix/provider evidence.
- Next exact action: synchronize PR/issue, confirm ledger/progress-only CI, then begin N1.17 SSO / Enterprise Governance source audit.

### 2026-08-21 — No release — N1.17 SSO / Enterprise Governance enforcement and tenant-lifecycle closure

- Trigger / observed blocker: `enforce_for_members` was metadata-only for password login; callback trust/state binding was incomplete; public SSO configuration could carry secret-like keys; SCIM active status conflicted with organization membership state and could attach/demote/deactivate identities unsafely; stale invitations and nested impersonation needed stronger governance.
- Root cause: N0.33 Enterprise foundations provided primitives but several identity flows still trusted platform-wide/global account semantics or presentation-only enforcement instead of current-tenant, replay-safe, service-level governance.
- Changes applied: real enforced-SSO password policy with Super Admin break-glass; compatible SSO choices in Login; one-time organization/provider-bound state, protocol/redirect/identity validation and generic adapter failures; secret-like public config rejection; active-tenant/prefix/revocation/expiry SCIM token admission; tenant-local SCIM lifecycle, foreign-identity attach denial, privileged-role preservation/deactivation guard; stale invitation supersession and accepted-tenant session selection; nested/unauthorized impersonation denial and stop integrity checks; executable Enterprise governance acceptance tests; required product contract; semantic Source Guard and section-number-independent progress governance corrections.
- Verification completed: first run `32508054237` exposed old formatting-sensitive Source Guard markers; correction `6856de412a1f483892944b6c91b64e4969506236` made those checks semantic. Run `32508273140` then passed every gate through AI Platform and exposed only a stale `Apply Log` section-number marker in the N1.16 contract; correction `255ed88beb9c2a324408c36eb417c9df244c96f6` made the heading check semantic. Final integrated run `32508900897` passed Certification preflight, Source Guard, every prior product contract, Multisite / Organizations, SSO / Enterprise Governance and Unified Source Certification on source head `1b86f3975438e1ba8eb7ede0f7f54fe9e6e088e3`.
- Real-target evidence: no current-branch Laragon/browser/PHPUnit controlled identity-adapter/SCIM execution has been supplied; SOURCE DONE only.
- Remaining blocker: live issue #2 + broad dev target QA + real DB/provider/identity-adapter evidence; GitHub `main` server protection still reports `protected=false` and requires a settings mutation capability.
- Next exact action: synchronize PR/issue/governance CI, apply `main` server protection when an authorized branch/ruleset mutation is available, then begin N1.18 Public APIs / Webhooks / SDK source audit while target recovery remains separate.

---

## 13. Known deferred work / not the current blocker

- formal reviewed-lock attestation
- C1-C6 final certification
- release signing/provenance finalization
- broad cross-platform matrix
- real cross-database target matrix execution/evidence
- managed AWS SQL target verification where test services are available
- auxiliary Mongo/Redis/AWS connector target verification where adapters/services are available
- real Commerce payment-provider target verification where a controlled test gateway/extension is available
- real AI provider-adapter target verification where a controlled adapter/service is available
- real SSO identity-adapter/SCIM target verification where a controlled adapter/service is available
- GitHub `main` branch protection/ruleset server mutation (current API evidence: protected=false)
- HA/distributed runtime
- final performance/accessibility certification
- Marketplace 2.0 / Sentinel 2.0

Do not let these pull work away from: **live runtime convergence -> login/admin target QA -> current product workflow target tests -> guarded DB target matrix -> final DEV-6 certification**, while source roadmap work may continue in parallel.

---

## 14. AI quick resume card

```text
PROJECT: Nexora
GOAL: Advanced extensible WordPress/Webflow/Wix/Shopify-class platform ecosystem
GITHUB: Vertex-Systems-Network/nexora
DEV SOURCE: rc.94 / v5.29 / n1-v5.29
DEV BRANCH: dev/n1-0b-core-functional-qa
PR: #1 DRAFT + MERGEABLE; SYNC THROUGH N1.17; FINAL GATES PASS => MARK READY + MERGE AUTOMATICALLY
BRANCH HEAD BEFORE LEDGER COMMIT: 76b8d077eb5f8abd3e3c623ecc652c13ade555e2
LATEST GREEN CI BEFORE LEDGER COMMIT: 32508900897 on 1b86f3975438e1ba8eb7ede0f7f54fe9e6e088e3
PROGRESS DASHBOARD: NEXORA_PROGRESS.md — mandatory every-apply update
PROJECT POWER: 76.5% | SOURCE 99.0% | TARGET 50.0% | RELEASE 25.0%
OPEN ISSUE: #2 runtime identity mismatch
MAIN PROTECTION: GitHub reports protected=false; desired PR + Source certification + stale-review dismissal + resolution + no force/delete + admin enforcement; do not claim applied until server evidence changes
LIVE TARGET: rc.93 installed on Laragon
LIVE BLOCKER: post-install environment/activation/service/process fingerprints stale
SOURCE/DEPLOYMENT/DB ON LIVE EVIDENCE: matching
DEPENDENCY RUNTIME: matching
LOCK REVIEW: missing, deferred
SOURCE DONE NOW: runtime convergence regression + settings + reusable Media + Theme + Extension + Studio + Documents + Content Collections + Publishing/SEO + Admin UX + Forms/Data/Workflows + Data Connections + Primary SQL portability + Installer DB UX + guarded real-DB matrix harness + Marketplace N1.9 + Commerce N1.10 + Customer Portal/CRM/Membership N1.11 + Search N1.12 + Collaboration N1.13 + Automation N1.14 + AI Platform N1.15 + Multisite/Organizations N1.16 + SSO/Enterprise Governance N1.17
DEV-5: ~95% SOURCE; real engine TARGET VERIFIED evidence pending
N1.15: SOURCE DONE; target/provider-adapter evidence pending
N1.16: SOURCE DONE; target organization/identity workflow evidence pending
N1.17: SOURCE DONE; target SSO/SCIM/invitation/impersonation evidence pending
DB MATRIX: scripts/database-target-matrix.php; use only empty nexora_matrix_* targets; --evidence -> storage/app/nexora/qa/database-target-matrix.json
NEXT LIVE: safe rc.93 repair -> compatibility PASS -> post-install PASS -> /login -> /admin -> issue #2 close only after evidence
NEXT TARGET TESTS: development-readiness --full + full PHPUnit/build + major product browser QA including AI + Multisite/Organizations + SSO/Enterprise Governance + real DB target matrix on separate dev checkout
NEXT SOURCE: N1.18 Public APIs / Webhooks / SDK source audit and closure
ISSUE RULE: inspect open GitHub issues every pass and solve applicable defects alongside roadmap work
PROGRESS RULE: update NEXORA_PROGRESS.md after every meaningful apply; Target Power only moves on real target evidence
MERGE RULE: when required source + target + issue gates are final, mark Ready and merge automatically without asking again
DO NOT: overwrite installed rc.93 with rc.94 as repair; do not mark PR #1 Ready or claim DB/provider/SSO TARGET VERIFIED from source CI alone
```
