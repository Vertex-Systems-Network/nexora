# N0.7 — Deployment Bootstrap & Runtime Repair (historical; URL flow superseded by N0.8)

N0.7 removes a clean-ZIP bootstrap failure class and extends the installer so Nexora can be deployed on hosting where the customer does not have interactive shell access.

## Runtime repair before Laravel

Both `public/index.php` and `artisan` execute `bootstrap/nexora-runtime-bootstrap.php` before Composer/Laravel boot. It creates and validates:

- `bootstrap/cache`
- `storage/framework/views`
- `storage/framework/sessions`
- `storage/framework/cache/data`
- `storage/logs`
- Nexora installation/security runtime directories

`config/view.php` explicitly binds compiled views to `storage/framework/views`. Empty-directory `.gitkeep` markers are also retained, but runtime repair is authoritative because ZIP tools can omit empty directories.

## Two browser installation layers

### 1. Standalone Deployment Bootstrap

`public/nexora-bootstrap.php` has no Laravel/Composer dependency. In N0.8 it is included internally by the canonical `/` entry point when `vendor/autoload.php` or the production Vite manifest is missing; the filename is no longer exposed in the browser flow.

Production release mode: a prebuilt Nexora release already contains `vendor/` and `public/build/`, so the page immediately passes dependency readiness and continues to `/install`. If a source package was uploaded first, the authorized bootstrap can also accept a prebuilt production release ZIP in the UI, validate archive paths, reject symlinks/environment state, verify `nexora-release.json` artifact hashes, stage it outside the web root and then deploy it.

Source/server-build fallback (updated by N0.13): when the server exposes `proc_open`, Composer, Node.js and npm, the bootstrap may run only a fixed allow-list. Database credentials are no longer accepted as bootstrap authorization; local development can be auto-authorized and remote source builds use the file-backed deployment access key. Application database settings are collected later in `/install`:

- `composer install --no-interaction --prefer-dist --optimize-autoloader`
- `npm ci` (or `npm install` when no lock exists)
- `npm run build`

There is no arbitrary command field and request input is never passed to `proc_open`.

### 2. Main Nexora Installation Wizard

After dependency/build readiness, `/install` handles MySQL/database creation, environment settings, forward migrations, core seed, first Super Admin, runtime compilation and `installed.lock`.

## Prebuilt production release

Customer/shared-hosting distribution should use the production release artifact. The release build includes Composer dependencies and frontend build output so the customer server needs no Composer, Node.js or npm.

A trusted build/CI machine can generate it after all quality gates pass:

```bat
scripts\build-production-release.bat
```

The builder requires `composer.lock`, `package-lock.json`, `vendor/autoload.php` and `public/build/manifest.json`, then emits a production ZIP plus SHA-256 sidecar. The ZIP contains `nexora-release.json` with lock/build hashes. The standalone bootstrap verifies those hashes before treating a prebuilt release as ready.

## Zero test on Laragon

```bat
scripts\setup-zero.bat
```

This now resets Nexora state/MySQL only. It intentionally does not run Composer or npm. Open `http://nexora.test/`; if build dependencies are missing N0.8 renders deployment preparation at the same site URL. With the current local convention use MySQL `root / root`, click **Prepare everything automatically**, then continue through the main installer.
