# N1.0 RC5 — Database / Migration / Seeder / Tenant Certification

RC5 is a stabilization block, not a new product-domain release. It converts database assumptions into repeatable source and dependency-backed certification gates.

## Database contract gate

`scripts/database-contract-verify.php` analyzes the entire migration/seeder/tenant surface before Laravel boots. It rejects duplicate table creation, missing FK targets, FK targets created too late, missing rollback coverage, identifiers beyond the 63-character portability ceiling, phase/milestone table naming, and DB-specific schema primitives that bypass the portability policy.

The same analyzer verifies a 1:1 mapping between the 51 `BelongsToTenant` model roots and the 51 tables listed by the enterprise tenancy migration. A future tenant-aware model without a `tenant_id` migration (or a tenant table without the shared scope) therefore fails source certification.

## Seeder repeatability

Demo users are deterministic (`demo-user-01@nexora.test` through `demo-user-12@nexora.test`) rather than random factory rows on every seed. Helpdesk SLA defaults use `updateOrCreate` independently, so an existing partial SLA configuration does not suppress missing defaults. Full certification runs `db:seed` repeatedly and then performs a complete migration reset/rebuild/seed round-trip.

## Portable nullable uniqueness

Seven nullable unique business keys now use `PortableNullableUnique`. MySQL/MariaDB/PostgreSQL/SQLite receive a normal unique index. SQL Server receives a filtered unique index over non-null rows so optional values can remain null in multiple records while non-null values stay unique.

Covered keys:

- Commerce product SKU
- Commerce payment idempotency key
- Commerce refund idempotency key
- Supply-chain scan link
- Automation event idempotency key
- Membership-plan Commerce price link
- Membership Commerce subscription link

## Compatibility matrix

The database matrix now performs: database preparation, fresh migration + seed, repeated seed, Compatibility tests, complete `migrate:reset`, full migration rebuild, seed rebuild, and Compatibility tests again. The Compatibility suite verifies tenant columns/orphan protection, deterministic seed counts, exactly one default enterprise organization, and nullable-unique NULL semantics on every configured database driver.
