# Nexora AI Definition of Done

No AI agent may advance the execution cursor merely because code was written or tests were added. A stage is complete only when every applicable gate below is satisfied and evidence is recorded.

## Universal gates

### 1. Scope

- Stage ID is named explicitly.
- Stage prerequisites are satisfied.
- Work stays inside the approved stage scope unless the user explicitly changes scope.
- Existing downstream code is not used as justification to skip missing prerequisites.

### 2. Architecture

- Public Contracts/Capabilities are used where required.
- No new third-party dependency reaches private Core implementation without an approved architecture decision.
- Theme, extension, tenancy, publishing, installer and security boundaries remain fail-closed.
- Shared Admin UI primitives are used instead of ad-hoc alternatives where a canonical component exists.
- Data/migrations remain fresh-install and supported-database portable.

### 3. Functional implementation

- Happy path is implemented.
- Permission/authorization path is implemented.
- Validation and failure states are implemented.
- Empty/loading/error states are implemented where applicable.
- Upgrade/backward-compatibility impact is considered.
- Rollback/deactivation/uninstall behavior is considered for installable packages.

### 4. Verification

- Relevant unit tests pass.
- Relevant feature/integration tests pass.
- Architecture/security regression checks pass.
- TypeScript/static analysis/build checks pass where frontend/source is affected.
- Source-only verification is recorded as `SOURCE_DONE`, never `TARGET_VERIFIED`.
- Real browser/runtime/DB behavior is exercised on the required target before `TARGET_VERIFIED`.

### 5. Evidence

The handoff records:

- files/components changed;
- commands/tests executed;
- source result;
- target result;
- known limitations;
- remaining blocker;
- exact next action.

### 6. Documentation and AI state

- `.ai/state.json` is updated.
- `.ai/handoff/current.md` is updated.
- system registry is updated if a system capability/status changed.
- stage graph is updated if scope/dependency/order changed.
- historical evidence is preserved; corrections are appended, not erased.

## Additional gates for CMS/content systems

When applicable, verify:

- content type definition/registration;
- fields and validation;
- relations;
- taxonomy binding;
- permissions/capabilities;
- permalink/routing behavior;
- archive/query behavior;
- revisions/editorial flow;
- theme resolution/rendering;
- Studio/dynamic binding integration;
- SEO resource/schema integration;
- API/import/export behavior if part of the stage;
- extension registration path if the capability is public/extensible.

## Additional gates for Theme systems

When applicable, verify the complete lifecycle:

`upload -> quarantine/Sentinel -> manifest validation -> install -> preview -> activate -> public render -> switch -> rollback -> uninstall/remove where supported`

Also verify template fallback, design-token behavior, SEO/content ownership separation and failure-safe fallback rendering.

## Additional gates for Extension/App/Integration/Studio-Pack systems

When applicable, verify:

`upload/stage -> quarantine -> Sentinel/Supply Chain -> manifest compatibility -> requested capabilities -> admin grants -> dependencies -> install -> enable -> runtime behavior -> disable -> version switch -> guarded rollback -> uninstall`

Runtime mode (`declarative` or `trusted-php`) and migration policy (`none` or `forward-only`) must be verified explicitly.

## Additional gates for SEO/Search systems

When applicable, verify:

- canonical title/description/URL output;
- robots/index/follow policy;
- sitemap inclusion/exclusion;
- Schema Graph output and stable IDs;
- extension/resource contribution behavior;
- public search noindex behavior;
- internal-link/audit evidence behavior;
- crawler host/path boundaries;
- no synthetic score is introduced unless the architecture is explicitly changed.

## Additional gates for security/runtime systems

- No trust gate is bypassed to make development easier.
- Quarantined code is not executed before approval/activation.
- Evidence distinguishes static inspection from execution isolation.
- `trusted-php` is never described as OS/container/process isolated unless such isolation is actually implemented and verified.
- Runtime/source identity claims must use exact evidence and cannot be inferred from documentation alone.

## Cursor advancement rule

The cursor advances only when the active stage's required status is reached. If the next stage requires real-target verification, `SOURCE_DONE` is insufficient.