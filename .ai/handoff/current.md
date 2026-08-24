# Nexora Current AI Handoff

## Resume instruction

Read, in order:

1. `AGENTS.md`
2. `.ai/README.md`
3. `.ai/state.json`
4. `.ai/roadmap/stages.md`
5. `.ai/plans/master-execution-plan.md`
6. `.ai/roadmap/capability-matrix.md`
7. `.ai/roadmap/systems.md`
8. `.ai/quality/definition-of-done.md`
9. relevant architecture/design documents for the active stage.

## Current source context

- Repository baseline branch: `main`
- Baseline SHA when the control plane was created: `f854c50c0f7687fc87fdfab01b49562392af4ef4`
- Documented development source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Control-plane branch: `ai/control-plane-phase-1`
- Control-plane revision: `2`

The baseline SHA is a reference point, not a self-referential requirement. Always inspect current HEAD. If HEAD moved, reconcile the diff before trusting this handoff.

## What the AI control plane now contains

### Phase 1 — imported repository truth

- deterministic `AGENTS.md` startup entrypoint;
- `.ai` authority/precedence rules;
- machine-readable current state;
- imported registry of documented built/foundation systems;
- imported registry of already approved planned systems;
- stable semantic stage IDs;
- explicit alias mapping for conflicting historical `N1.x` roadmaps;
- Definition of Done and verification matrix.

### Phase 2 — accepted capability-gap planning

- WordPress/Webflow/Wix/Shopify-class competitive capability benchmark;
- master capability matrix covering existing foundations, planned work and missing required platform primitives;
- explicit `ARCH-BOUNDARY-100` architecture reconciliation stage;
- complete content-model expansion;
- generic taxonomy platform;
- typed query engine;
- permalink/router/redirect platform;
- public navigation/menu engine;
- Theme Contract 2.0/template hierarchy;
- expanded Extension SDK 2.0 typed events/filters/UI slots/runtime APIs;
- Site Builder/Theme Studio 2.0 requirements;
- API/headless/config-as-code planning;
- governed AI-native product architecture (`AI-KERNEL-100`);
- AI content, AI Design Professional and AI developer-experience stages;
- expanded marketplace, commerce, migration, Sentinel, operations and release requirements;
- zero-skip master execution protocol;
- active plan file for the current runtime stage.

This planning pass changed documentation/control-plane state only. It did not claim that the new capabilities are implemented.

## Important new stable stages

The following were previously implicit, fragmented or absent and are now canonical roadmap items:

- `ARCH-BOUNDARY-100`
- `TAXONOMY-200`
- `QUERY-ENGINE-200`
- `ROUTING-200`
- `NAVIGATION-100`
- `THEME-CONTRACT-200`
- `AI-KERNEL-100`
- `AI-CONTENT-100`
- `AI-DESIGN-100`
- `AI-DX-100`

Use `.ai/roadmap/stages.md` for their exact dependencies and order.

## Active stage

`RUNTIME-CLOSURE-001 — Installation + Runtime Closure`

Status: `BLOCKED` pending real-target execution.

The Phase 2 roadmap expansion does not change this cursor.

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

Detailed checkboxes are in `.ai/plans/active.md`.

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

The capability matrix is now also authoritative for omission prevention: if a required capability has no implementation yet, it stays visible as a gap until its canonical stage closes.
