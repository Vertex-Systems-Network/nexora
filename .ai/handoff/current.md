# Nexora Current AI Handoff

## Resume instruction

Read `AGENTS.md`, `.ai/README.md`, `.ai/state.json`, `.ai/roadmap/systems.md`, `.ai/roadmap/stages.md`, and `.ai/quality/definition-of-done.md` before changing code.

## Current source context

- Repository baseline branch: `main`
- Baseline SHA when this control plane was created: `f854c50c0f7687fc87fdfab01b49562392af4ef4`
- Documented development source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Control-plane branch: `ai/control-plane-phase-1`

The baseline SHA is a reference point, not a self-referential requirement. Always inspect current HEAD. If HEAD moved, reconcile the diff before trusting this handoff.

## What this Phase 1 added

- deterministic `AGENTS.md` startup entrypoint;
- `.ai` authority/precedence rules;
- stable project metadata;
- machine-readable current state;
- complete imported registry of already documented built/foundation systems;
- complete imported registry of already approved planned systems;
- stable semantic stage IDs;
- explicit legacy alias mapping for conflicting `N1.x` roadmaps;
- stage Definition of Done;
- system verification matrix.

No new product feature roadmap ideas were added in this phase. Audit-derived missing systems/parity improvements are intentionally reserved for the next planning pass.

## Active stage

`RUNTIME-CLOSURE-001 — Installation + Runtime Closure`

Status: `BLOCKED` pending real-target execution.

### Target state imported from the legacy operational ledger

The live Laragon installation was created from rc.93. Version/generation/source/database/storage/host/resources/policy/framework/runtime-dependency planes were observed matching, while these post-install identity planes remained stale:

- environment
- activation
- service
- process

### Do not do this

Do not overwrite the installed rc.93 application with rc.94 merely to repair the four stale fingerprints. Repair and upgrade must remain distinct operations.

### Exact next actions

1. Run the prepared rc.93 Post-Install Identity Repair Pack externally against `D:\laragon\www\nexora`.
2. Run `php artisan nexora:runtime:compatibility-status --deep`.
3. Require `status: pass`, `mismatches: []`, `compatible: true`, `mode: installed-data-plane`.
4. Run `php artisan nexora:runtime:post-install-status --assert-ready`.
5. If both gates pass, open `/login` and advance to `CORE-QA-001`.

## Next stage

`CORE-QA-001 — Super Admin + Core Application Functional QA`

First batch must cover, in order:

1. Super Admin login/logout/session persistence.
2. Direct URL navigation/refresh.
3. Admin shell boot.
4. Sidebar/tooltips/responsive behavior.
5. Light/Dark/System persistence.
6. Users/profile/password reset/self-service auth.
7. Roles/permissions/capabilities/tenant boundaries.
8. Settings.
9. Media Library.
10. Core CRUD/error handling.
11. Theme full lifecycle smoke.
12. Extension full lifecycle smoke.
13. Studio first real page/document editing flow.

## Completion warning

The historical master plan contains many `DONE` labels. Treat those as implementation milestones, not proof of real-target product closure. Use `.ai` statuses and evidence before advancing the cursor.