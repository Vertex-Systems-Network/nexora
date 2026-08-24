# Nexora Current AI Handoff

## Resume instruction

Read, in order:

1. `AGENTS.md`
2. `.ai/README.md`
3. `.ai/state.json`
4. `.ai/roadmap/stages.md`
5. `.ai/roadmap/release-trains.md`
6. `.ai/governance/development-intake.md`
7. `.ai/registry/development-units.json`
8. `.ai/plans/master-execution-plan.md`
9. `.ai/plans/plan-template.md`
10. `.ai/plans/active.md`
11. `.ai/roadmap/capability-matrix.md`
12. `.ai/roadmap/systems.md`
13. `.ai/roadmap/future-systems.md`
14. `.ai/security/security-program.md`
15. `.ai/quality/definition-of-done.md`
16. relevant architecture/design/security documents for the active stage/unit.

## Current source context

- Repository baseline branch: `main`
- Baseline SHA when the control plane was created: `f854c50c0f7687fc87fdfab01b49562392af4ef4`
- Documented development source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Control-plane branch: `ai/control-plane-phase-1`
- Control-plane revision: `3`

The baseline SHA is a reference point, not a self-referential requirement. Always inspect current HEAD. If HEAD moved, reconcile the diff before trusting this handoff.

## Core governance rule now in force

**No system, module, feature, extension, app, integration, studio pack, theme, AI tool/agent, migration adapter, operations capability or security control begins implementation until it is pre-planned and registered.**

The required flow is:

```text
request / discovered gap
-> classify unit
-> registry entry + stable ID
-> parent stage + release train + dependencies
-> architecture/data/security/privacy/API/theme/Studio/AI impact
-> acceptance criteria + tests + target verification + rollback
-> active plan
-> implementation
-> evidence
-> state/registry/handoff update
```

AI-discovered optional ideas may be registered as `PROPOSED`, but cannot be silently implemented unless they are required by approved active scope or explicitly promoted.

First-party packages receive no private-Core exemption.

## Control-plane phases

### Phase 1 — imported repository truth

- deterministic startup entrypoints;
- machine-readable execution state;
- imported existing/planned system inventory;
- stable semantic stage IDs and legacy alias mapping;
- Definition of Done and verification matrix.

### Phase 2 — capability-gap + AI-native roadmap

- competitive capability benchmark;
- dynamic content/custom fields/relations;
- generic taxonomy;
- query/archive engine;
- routing/permalink/redirect platform;
- public navigation;
- Theme Contract 2.0;
- Extension SDK 2.0;
- Site Builder/Theme Studio 2.0;
- API/config-as-code;
- AI Kernel, AI Content, AI Design Professional, AI DX;
- migration, marketplace, commerce, enterprise/security/operations expansion.

### Phase 3 — pre-planned development operating model

Added and accepted into the canonical plan:

- mandatory development-unit intake/registry/schema;
- mandatory active-plan template;
- builder-first release trains;
- `AI-GOV-AUTOMATION-100` to make planning consistency CI-enforceable;
- early `SECURITY-BASELINE-200` rather than waiting for late Sentinel work;
- continuous security program + threat-model template;
- `RELEASE-WORKFLOW-200` for preview/staging/branching/merge/selective/scheduled publishing/rollback;
- `TEMPLATE-ECOSYSTEM-100` for site/page/section/component/theme starter kits;
- `PRIVACY-CONSENT-100`;
- expanded `SEO-AI-200` for AEO/AI-readable representations and AI visibility;
- `AGENT-INTEROP-100` for external AI agents through scoped tools;
- `DESIGN-IMPORT-100` for structured Figma/design import;
- `EXPERIMENTATION-100` and `PERSONALIZATION-100`;
- `APP-RUNTIME-100` for capability-bounded full-stack/low-code functions/jobs/actions;
- optional `MANAGED-CLOUD-100`;
- revised sequencing so website-builder kernel closes before deep CRM/enterprise/cloud productization.

The canonical stage graph now contains **62 ordered stages/gates**, ending at `N2-STABLE-100`.

## Release trains

### Builder Beta

Secure extensible CMS/site builder: runtime/core/admin/security, architecture, theme/extension/Studio closure, dynamic CMS kernel, routing/navigation, Theme/Extension contracts, Site Builder/Theme Studio, release workflow, templates, multilingual, delivery/media/search/forms/privacy.

### Pro

AI-native differentiation: AI Kernel, SEO/AEO/AI visibility, APIs/config-as-code, external agent interop, AI Content/Design/DX, design import, experimentation/personalization, App Runtime, migration center and DX.

### Platform

Marketplace, commerce, CRM/membership/helpdesk closure, portals, collaboration, optional Managed Cloud, enterprise/cloud verification, Sentinel 2.0, governance, observability and DR.

### Production

Performance, accessibility/international and final exact-source/target certification.

## Active stage

`RUNTIME-CLOSURE-001 — Installation + Runtime Closure`

Registered unit: `SYS-RUNTIME-IDENTITY`

Status: `BLOCKED` pending real-target execution.

Phase 3 planning does not change this cursor.

### Current target evidence

The live Laragon installation was created from rc.93. Version/generation/source/database/storage/host/resources/policy/framework/runtime-dependency planes were observed matching, while these post-install identity planes remained stale:

- environment
- activation
- service
- process

### Do not do this

Do not overwrite installed rc.93 with rc.94 merely to repair the four stale fingerprints. Repair and upgrade must remain separate.

### Exact next actions

1. Run the prepared rc.93 Post-Install Identity Repair Pack externally against `D:\laragon\www\nexora`.
2. Run `php artisan nexora:runtime:compatibility-status --deep`.
3. Require `status: pass`, `mismatches: []`, `compatible: true`, `mode: installed-data-plane`.
4. Run `php artisan nexora:runtime:post-install-status --assert-ready`.
5. If both gates pass, open `/login` and advance to `CORE-QA-001`.

Detailed execution/DoD is in `.ai/plans/active.md`.

## Immediate sequence after runtime closure

1. `CORE-QA-001`
2. `AI-GOV-AUTOMATION-100`
3. `ADMIN-UX-CLOSURE-001`
4. `SECURITY-BASELINE-200`
5. `ARCH-BOUNDARY-100`
6. close current Theme/Extension/Studio/CMS/Media/SEO/Automation foundations
7. build the mature CMS/site-builder kernel

This sequencing intentionally avoids blocking the website-builder product on later CRM/Helpdesk/enterprise/cloud breadth.

## Completion warning

Historical `DONE` labels are implementation history, not real-target product proof. Use `.ai` stage/unit statuses and evidence.

No new feature should disappear into chat history: if it matters enough to implement, it must first exist in the registry/roadmap/active plan.
