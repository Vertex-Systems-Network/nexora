# N0.4 — Kernel, Contracts and Runtime

N0.4 introduces the first real Nexora modular runtime. It deliberately separates **human authorization** from **code capabilities**.

## Runtime flow

```text
Laravel service container
        ↓
NexoraServiceProvider
        ↓
CapabilityRegistry + ModuleRegistry
        ↓
Dependency validation / boot ordering
        ↓
Module register phase
        ↓
Module boot phase
```

No request-time filesystem scan is used. Core module classes are explicit in `config/nexora/modules.php`.

## Module contract

Every Nexora module implements `ModuleContract` and returns a typed `ModuleManifest` containing:

- stable identifier
- name/version/description
- core/trust classification
- load-order hint
- requested runtime capabilities
- required/optional module dependencies
- metadata

Dependencies are resolved before boot. Missing dependencies, circular dependencies and incompatible supported version constraints fail fast.

## Runtime capabilities

Runtime capabilities describe what **code** may access, for example:

```text
identity.users.read
identity.users.write
admin.navigation.register
system.runtime.sync
```

They are not user permissions such as `users.view` or `roles.update`.

`RuntimeContext` identifies the currently executing module. `CapabilityGuard` can authorize a platform API against the module's declared capabilities. N0.4 establishes this boundary; later Sentinel/Extension blocks will force third-party package calls through capability-aware brokers.

## Runtime synchronization

The in-memory/configured runtime is authoritative for code boot. Database metadata exists for administration, audit, compatibility and future package lifecycle operations.

Run:

```bash
php artisan nexora:runtime:sync
```

This synchronizes:

- `nx_modules`
- `nx_module_versions`
- `nx_capabilities`
- `nx_module_dependencies`
- `nx_module_capabilities`

The operation is idempotent and does not delete unknown future extension records.

## Runtime CLI

```bash
php artisan nexora:module:list
php artisan nexora:capability:list
php artisan nexora:capability:list --risk=critical
php artisan nexora:runtime:sync
php artisan nexora:runtime:cache
php artisan nexora:runtime:clear
```

`runtime:cache` writes a deterministic metadata snapshot under `bootstrap/cache/nexora/` for deployment verification and later compiled package lifecycle work.

## Admin UI

Users with the relevant permissions receive:

```text
System → Modules
System → Capabilities
```

The Modules screen exposes dependency/boot order, runtime-sync state, and same-version manifest integrity. The first checksum seen for a module version is preserved; if code later presents a different manifest without a version change, the UI flags `Version changed` instead of silently rewriting the historical checksum. The Capabilities screen exposes risk and which modules request each capability.
