# N0.8 — Clean-domain Zero-CLI Deployment

N0.8 removes the deployment implementation filename from the customer flow. A customer opens the normal domain only. If `vendor/autoload.php` or `public/build/manifest.json` is missing, `public/index.php` renders the framework-independent deployment preparation internally while the browser URL remains `/`.

## Deployment modes

### Preferred production release

A trusted CI/build machine produces a Nexora production release containing Composer dependencies and built frontend assets. The customer uploads/extracts it and proceeds directly to the main browser installer. No Composer, Node.js, npm, SSH or terminal is required on the customer host.

### Source-package browser preparation

When a source package is deployed, the browser preparation can:

1. verify MySQL credentials to authorize deployment actions for the current browser session;
2. discover PHP CLI, Composer, Node.js and npm from the web process PATH;
3. discover Laragon installations from the project path and common Laragon locations on Windows;
4. install a private Composer PHAR after verifying the official installer signature;
5. install a private Node.js v24 LTS runtime from an official checksum-published archive on supported platforms;
6. run the fixed Composer dependency install;
7. run the fixed npm install/ci task;
8. build Vite production assets;
9. hand off to `/install` for DB/site/admin/migrations/runtime configuration.

No arbitrary command field exists and request input is never forwarded to a process-execution primitive.

## Private tools

Fallback tools live outside the public document root under:

```text
storage/app/nexora/tools/
├── composer.phar
└── node/
```

They are bootstrap tooling, not application plugins, and are excluded from repository/release state unless a release process explicitly bundles them.

## Canonical URL

`public/nexora-bootstrap.php` remains an internal implementation file because it must be usable before Composer/Laravel exists. Direct HTTP access to that filename redirects to `/`. Only `public/index.php` defines the internal bootstrap constant and includes it.

## Failure policy

If process execution is disabled, PHP CLI cannot be resolved, outbound HTTPS is unavailable, or the private tool verification fails, source-build mode stops. The operator can still use a verified prebuilt production release. Nexora never falls back to unverified downloads or arbitrary shell input.
