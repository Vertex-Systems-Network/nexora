# N1.0 RC2 — Module Graph Stabilization

RC2 addresses a Composer `post-autoload-dump` failure where Laravel package discovery booted Nexora and the Enterprise module requested a module identifier that did not exist:

```text
Module [nexora.enterprise] requires missing module [nexora.identity].
```

The registered identity module has always been `nexora.identity-access`. `EnterpriseModule` now declares `nexora.identity-access ^0.5`, matching the configured `IdentityAccessModule` manifest.

RC2 also adds a dependency-free static Core module graph gate. It is executed during RC preflight and again as an explicit certification step before Laravel package discovery. It rejects missing required dependencies, duplicate configured classes, duplicate identifiers, unregistered Core modules, self-dependencies, circular graphs, and incompatible internal version constraints.

Historical milestone architecture tests no longer assert the mutable top-level platform version. N1.0 owns the current RC platform identity, while N0.x tests remain focused on their feature boundaries. This prevents each RC bump from creating unrelated false failures.

The clean source package also restores runtime `.gitkeep` markers required by zero-install bootstrap so `storage/framework/cache/data`, `storage/framework/sessions`, `storage/framework/views`, and `storage/logs` survive ZIP extraction.
