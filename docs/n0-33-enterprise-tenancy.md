# N0.33 — Multisite, Tenancy, Organizations, SSO & Enterprise Governance

N0.33 establishes an explicit enterprise organization boundary across Nexora without merging CRM companies with platform tenants. The tenant key is `tenant_id`; CRM's own `organization_id` remains a customer/company relationship.

## Tenant resolution and isolation

Requests resolve the current organization from a verified domain, an authorized session selection, active user membership, or finally the default organization. Tenant-aware models use a central `BelongsToTenant` global scope and automatically stamp new rows. Existing rows are backfilled into the default organization during the forward migration. Console-created tenant rows also fall back to the default organization when no request context exists.

Settings use an organization overlay: a tenant-specific value wins, while existing global settings remain defaults. This lets theme/appearance/SEO/application settings become tenant-aware without duplicating their public contracts.

## Two-key authorization

Platform RBAC remains authoritative for what a user may ever do. An organization role is a second restriction layer and cannot grant a permission that platform RBAC did not already grant. Organization roles therefore reduce access inside a tenant rather than escalating platform privileges.

## Enterprise identity

OIDC and SAML are represented by `EnterpriseIdentityProviderContract` adapters. Core stores provider records and encrypted secret payloads but ships no vendor-specific IdP SDK. Verified extensions register protocol adapters through `SsoProviderRegistry`.

SCIM foundation exposes organization-scoped bearer-token endpoints for listing, provisioning and activating/suspending users. Nexora stores only the token hash and reports one-time bearer values only when issued. This release provides a provisioning foundation rather than claiming every optional SCIM 2.0 feature.

## Domains, invitations and impersonation

Domain ownership uses DNS TXT proof and stores only a hash of the one-time verification value. Invitations store only token hashes. Impersonation requires an explicit reason, restricts the target to an active organization member, persists actor/target/start/end history and exposes a persistent Admin banner until impersonation is ended.

## Queue/runtime propagation

Tenant-aware queue roots restore their organization context before performing workflow, crawl, newsletter or webhook work. N0.34 will extend this into distributed tenant-aware queue routing and HA operations.
