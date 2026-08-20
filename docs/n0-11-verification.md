# N0.11 Verification

N0.11 source verification targets the real zero-install regression that previously failed before the Laravel installer could render.

Verified in the build environment:

- 192 PHP files pass `php -l`
- `php scripts/source-guard.php --source-only` passes
- 48 TypeScript/TSX files report 0 syntax diagnostics through the TypeScript parser
- `.env.example` exists and is non-empty
- an uninstalled GET `/` renders the standalone Deployment Center without requiring a root `.env`
- protected fallback selection was simulated with both a stale root `.env` and an active fallback marker; the fallback remained authoritative
- installer bootstrap supports protected environment fallback and active-location marker
- both HTTP and Artisan load the installer environment bootstrap
- Nexora favicon/brand assets exist and are non-empty
- React admin icon layer uses `lucide-react`
- production release builder excludes protected environment runtime state
- source tree was cleaned of `.env`, bootstrap key, fallback environment, active marker, sessions, private tool cache, `vendor`, `node_modules`, and generated frontend build before packaging

Dependency-backed Laravel tests, Composer package discovery, MySQL migrations and the production Vite build are not claimed as executed in this build environment because Composer/MySQL extensions/tooling were not available here. The Windows/Laragon zero-install run remains the integration source of truth for those gates.
