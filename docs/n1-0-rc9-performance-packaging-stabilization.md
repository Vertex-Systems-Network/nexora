# N1.0 RC9 — Performance / Production Packaging / Cache & Asset Budget Stabilization

RC9 prevents a release candidate from becoming a production artifact when its frontend build, HTTP delivery, optimized Laravel boot, or package contents violate known safety/performance ceilings. It is a certification block, not the final N1.6 cache/CDN engine or N1.23 Core Web Vitals certification.

## Source contracts

`performance-contract-verify.php` runs without Composer/npm dependencies and verifies:

- centralized `config/nexora-performance.php` budgets;
- centralized `config/nexora-release.php` production package policy;
- `ApplyPerformanceHeaders` registration and sensitive-response no-store/security headers;
- Vite production settings (`sourcemap: false`, CSS splitting, explicit chunk warning ceiling);
- Apache Brotli-or-gzip readiness plus immutable `/build/*` cache policy;
- bounded static public assets and lazy/async non-critical Admin preview images;
- certification runner integration for source + built-asset performance gates;
- production release builder enforcement of build-asset evidence and forbidden archive paths.

## Production build budgets

After `npm run build`, run:

```bat
php scripts\performance-build-verify.php
```

The verifier requires the Vite manifest and both Nexora entry points, rejects source maps and local development paths, requires hashed JS/CSS filenames, checks manifest references, and enforces configurable raw/gzip JS, CSS, font, image, total-build and asset-count ceilings. A PASS report is written to protected certification storage as `build-assets.json`.

The ceilings are RC regression limits, not per-page performance targets. N1.6 will own cache/CDN/image-pipeline behavior and N1.23 will own real Core Web Vitals budgets.

## HTTP delivery policy

`ApplyPerformanceHeaders` emits baseline `nosniff`, referrer, frame and permissions policy headers. Admin/Auth/Installer/SSO/SCIM/webhook/health, authenticated requests, non-GET/HEAD requests and error responses are explicitly `Cache-Control: no-store, private`. Anonymous public dynamic responses remain private/no-cache during RC certification so tenant/auth context is not accidentally cached by intermediaries.

On HTTPS, HSTS is configurable and enabled by default. Static Vite `/build/*` assets bypass Laravel through the web server and receive one-year immutable caching because their filenames are content-hashed. Stable brand/icon assets receive a shorter cache policy. Apache uses Brotli when available and gzip otherwise for compressible text types.

## Optimized Laravel boot

Full certification now runs `artisan optimize` and then boots `artisan about`, `route:list` and `schedule:list` while optimization caches are active. Optional target-server HTTP smoke runs before `optimize:clear`, so cached production boot regressions are visible rather than hidden by immediately clearing caches.

## Production ZIP fail-closed policy

`build-production-release.php` requires all of the following before creating an artifact:

- matching `certification-pass` for the exact platform version;
- matching `build-assets.json` PASS report;
- Composer dependencies + lock file;
- npm lock file + Vite production manifest;
- centralized release policy.

The ZIP is reopened after creation. Required entries must exist and `.env`, `public/hot`, tests, node_modules, Git metadata, logs, runtime session/cache data, installer/deployment journals, database backups and certification evidence must not be present. The release manifest records the certification hash, performance-report hash and release-policy hash.

## Target-server evidence

When `NEXORA_CERT_BASE_URL` is configured, `http-smoke.php` records response time and verifies request ID, cache, nosniff, referrer, frame, permissions and HTTPS HSTS behavior for the home/login/live/ready routes. This is a smoke ceiling, not a lab CWV score.
