# Nexora N0.29 — Extensions Lifecycle, Forge SDK & Marketplace

N0.29 introduces the first production-oriented extension lifecycle above the N0.28 Sentinel and supply-chain trust pipeline. Extension packages never bypass quarantine, Sentinel or content-integrity verification.

## Supported package families

N0.29 manages `extension`, `app`, `integration` and `studio-pack` package types. Themes continue to use the dedicated Theme Engine lifecycle. Books, CV/Profile, LMS, Booking and Projects remain external package families rather than Nexora Core features.

## Install and activation lifecycle

1. A package enters Nexora quarantine through a direct upload or Marketplace staging.
2. Sentinel performs static/package scanning and the Supply Chain analyzer records artifact/content digests, signature/provenance/SBOM state and trust policy.
3. Only a Supply Chain artifact whose linked Sentinel scan is `ALLOW` may be installed.
4. The extension manifest is validated for identity, version, Nexora compatibility, runtime mode, requested capabilities, dependencies and migration policy.
5. The package is extracted to versioned protected storage. The same identifier/version cannot be replaced by a different content digest.
6. Requested capabilities remain denied until explicitly granted by an authorized administrator. Capabilities missing from the current runtime are shown as unavailable and cannot be granted.
7. Dependencies and version constraints are verified before activation.
8. `trusted-php` activation and forward-only extension migrations require the N0.28 execution policy to allow execution. N0.29 does not automatically load arbitrary third-party PHP into every web request.
9. Activation, disable, version switch, guarded rollback and uninstall are recorded in immutable lifecycle history.

## Migration policy

Extension manifests may declare `none` or `forward-only`. Nexora does not automatically run destructive/down migrations during rollback. If the current version changed schema, rollback is blocked unless the package explicitly declares schema-compatible rollback.

## Marketplace boundary

Marketplace sources are administrator-controlled HTTPS/public-network endpoints. Catalog sync does not install code. A selected catalog package is downloaded without following redirects, optionally SHA-256 verified, then stored in Nexora quarantine and scanned. Sources configured as `trusted_publishers_only` require the catalog item's `publisher_key_id` to match an active publisher verification key before the package can even be staged.

## Forge developer workflow

`php artisan nexora:make:extension vendor.package --name="Package Name"` creates a safe declarative extension skeleton under `extensions/`. `php artisan nexora:extension:list --json` provides machine-readable installed extension inventory. The generated manifest uses the public Nexora compatibility contract rather than private Core imports.

## Shared Admin UI refinements

N0.29 also standardizes table and form interaction behavior across Admin:

- DataTable column headers remain sticky at the top of the table scroll surface.
- Pagination remains visible at the bottom of the table surface.
- Select uses the existing React Aria selection primitives and no longer inherits generic action-button scale/brightness feedback.
- Date, DateTime and Time fields use central Nexora UI components backed by React Aria and `@internationalized/date` rather than browser-native date/time inputs.
- Feature pages continue to consume these primitives only through `@nexora/admin-ui`.
