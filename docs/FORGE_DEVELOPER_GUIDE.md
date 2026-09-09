# Nexora Forge Developer Guide

Nexora Forge is a **source scaffolding** workflow for extension developers. It does not install packages, grant capabilities, mark code trusted, sign artifacts, or bypass Sentinel.

## Create a scaffold

```bash
php artisan nexora:make:extension vendor.package --name="Vendor Package" --type=extension
```

Supported first-flow package types:

- `extension`
- `app`
- `integration`
- `studio-pack`

The identifier must be namespaced and lowercase, for example `vendor.package` or `vendor.my-package`.

Forge writes source scaffolds under:

```text
<project>/extensions/<identifier>
```

The generated file contract is deterministic:

```text
.nexora-forge.json
README.md
composer.json
database/migrations/.gitkeep
nexora.json
resources/.gitkeep
src/.gitkeep
tests/.gitkeep
```

`nexora.json` is validated by the same `ExtensionManifestValidator` used by the extension lifecycle before Forge writes the scaffold.

## Dry run

Preview the exact destination and managed files without changing the filesystem:

```bash
php artisan nexora:make:extension vendor.package --dry-run
```

Dry run performs the same identifier, type, path and manifest validation as a real scaffold.

## Existing directories and `--force`

Forge refuses an existing destination by default.

```bash
php artisan nexora:make:extension vendor.package --force
```

`--force` is intentionally narrow. It is accepted only when the destination contains `.nexora-forge.json` with:

```json
{
  "schema": "nexora.forge.scaffold.v1",
  "identifier": "vendor.package"
}
```

This prevents Forge from clobbering arbitrary source directories. A force refresh overwrites only deterministic Forge-managed files. Developer-created files such as `src/MyFeature.php` remain untouched because Forge never deletes the scaffold directory.

Symbolic-link traversal and file-vs-directory path conflicts are refused.

## Capabilities

New scaffolds request **zero capabilities**. Add only the capabilities your package genuinely requires. Capability grants remain an installation/runtime decision and are not granted by Forge.

## Runtime and migrations

The first scaffold defaults to:

```json
{
  "runtime": { "mode": "declarative" },
  "migrations": {
    "policy": "none",
    "schema_compatible_rollback": false
  }
}
```

Changing a package to trusted PHP or forward-only migrations does not make it trusted. Normal Nexora validation, package policy and Sentinel review still apply.

## Package and install

Forge does not create a trusted install state. The intended path is:

```text
Forge source scaffold
  -> developer implementation/tests
  -> build/package outside runtime installation storage
  -> sign/provenance as applicable
  -> upload package through Sentinel
  -> Sentinel scan/policy decision
  -> ALLOW
  -> normal Extension lifecycle install/enable
```

Never copy Forge source directly into `storage/app/nexora/extensions/...` and treat that as an installed extension. The install directory is owned by the verified package lifecycle.

## Stable developer contract

For N1.21 the supported developer-facing contract is:

- Artisan command `nexora:make:extension`
- package manifest `nexora.json`
- Forge ownership marker `.nexora-forge.json`
- deterministic generated-file list above
- `--dry-run` is zero-write
- `--force` requires same-identifier Forge ownership
- Forge never installs, enables, signs, trusts, or grants capabilities

Future Forge features should extend this contract without making internal Core service classes or Eloquent models public SDK APIs.
