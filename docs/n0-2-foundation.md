> Historical N0.2 record. N0.3 supersedes current implementation guidance; see `docs/n0-3-identity-access.md`.

# N0.2 — Runnable Admin Foundation

## Included
- Laravel 13 foundation with Inertia v3 server integration.
- React 19 + TypeScript strict + Vite 8 admin entry point.
- Nexora Admin UI abstraction designed around Untitled UI React's source-owned component model.
- Responsive admin shell, auth pages, route progress, skeleton/error/empty primitives and action loading states.
- Token-driven theme, primary color, density and radius settings.
- Users read view, settings view and runtime health view.
- Core users/roles/permissions/settings/modules/audit/health migrations.
- Production-safe core seeding and local/testing-only demo seeding.
- PHPUnit unit/feature/architecture suites and Vitest component test foundation.

## Required local bootstrap
1. `composer install` (this regenerates `composer.lock` because Inertia was introduced in N0.2).
2. `npm install` (generates `package-lock.json`).
3. `cp .env.example .env` and `php artisan key:generate`.
4. `php artisan migrate:fresh --seed`.
5. `npm run dev`.

Local demo admin defaults to `admin@nexora.test` / `password`. The demo seeder refuses to create this account outside `local` or `testing` environments.

## Quality gate
Run `./scripts/quality-check.sh` after dependencies are installed. A feature is not complete if migrations, tests, typecheck or production build fail.
