# N0.17 Verification

Verification performed in the packaging environment:

- PHP source lint: required for all PHP files.
- TypeScript/TSX parser diagnostics: required for all Admin/source TS files.
- Local import graph: required.
- Nexora Source Guard `--source-only`: required.
- Admin raw interactive controls outside `@nexora/admin-ui`: zero allowed.
- Direct Inertia `Link` outside `@nexora/admin-ui`: zero allowed.
- Writer/Admin shell required artifacts: required.
- Runtime-generated `.env`, sessions, private tool caches, deployment state, `vendor`, `node_modules` and `public/build`: excluded from clean source package.

The packaging environment does not have the project's npm dependencies installed, so dependency-backed `npm run build` is not falsely reported as PASS. The known `ButtonLink` TypeScript collision is fixed by omitting `size` from `InertiaLinkProps` before declaring `ButtonSize`. The user's Laragon `npm run build` remains the final dependency-backed integration gate.
