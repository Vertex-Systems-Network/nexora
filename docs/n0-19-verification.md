# N0.19 Verification Gates

The N0.19 source guard verifies that SEO Core cannot silently disappear or regress.

Required checks include:

- SEO module registration and runtime capabilities.
- New forward-only SEO migration.
- SEO Admin pages/controllers/routes.
- public sitemap route.
- Schema Graph conflict unit coverage.
- theme-independent `SeoManagerContract`.
- no raw interactive controls in Admin feature surfaces.
- no return of Books/CV/LMS/Booking/Projects to the internal roadmap.
- platform version consistency at `0.19.0`.

Dependency-backed Laravel tests, migrations and the Vite production build remain part of `scripts/quality-check.*` and must run on the actual development/CI machine.
