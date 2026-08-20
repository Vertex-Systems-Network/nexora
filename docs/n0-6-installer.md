# N0.6 — Installation Wizard

N0.6 fixes the PHP readonly-controller inheritance failure and introduces a two-stage Nexora installer.

## Why installation is two-stage

A Laravel source checkout cannot safely use a Laravel browser page to install Composer itself because Laravel's autoloader must already exist before the application can boot. Frontend source also needs Node/NPM before Vite can create production assets. Nexora therefore separates trusted build/bootstrap work from public HTTP configuration.

### Source / developer distribution

Run one trusted local bootstrap:

```bat
scripts\bootstrap-installer.bat
```

It performs only source preparation:

1. source guard
2. Composer install / package discovery
3. APP_KEY generation
4. npm install
5. production frontend build
6. Laravel cache clear

It does **not** migrate or seed the application database.

Then open `/install`.

### Prebuilt customer release

Release artifacts should already contain:

- `vendor/`
- `public/build/manifest.json` and compiled assets
- immutable application source

The customer can open `/install` directly without Composer or Node on the server.

## Browser wizard responsibilities

The browser wizard:

- validates PHP version and required extensions
- validates writable storage/cache paths
- verifies Composer dependencies and production assets exist
- tests MySQL credentials without exposing the password in logs
- optionally creates the selected database
- blocks non-empty databases by default
- writes `.env` using locked writes
- configures production-safe app/session/cache/queue defaults
- runs forward migrations (never `migrate:fresh`)
- seeds deterministic core roles/permissions/settings only
- creates or resumes creation of the first Super Admin
- synchronizes and compiles the Nexora runtime
- writes an installation lock only after successful completion
- clears caches

## Concurrency and retry safety

`storage/app/nexora/installing.lock` uses an exclusive file lock so two installation requests cannot run concurrently. Core seeding is idempotent and first-admin creation uses `updateOrCreate`, allowing a reviewed interrupted install to resume.

The persistent marker is `storage/app/nexora/installed.lock`. Once present, `/install` redirects to login and normal application routes no longer pass through installation mode.

## Zero test on Laragon

```bat
scripts\setup-zero.bat
```

This intentionally drops local MySQL database `nexora`, removes local installation state, restores `.env.example`, bootstraps source dependencies/build, then stops. Open `http://nexora.test/install`, finish the UI wizard, then run:

```bat
scripts\quality-check.bat
```

Quality tests destructively refresh only `nexora_testing`.
