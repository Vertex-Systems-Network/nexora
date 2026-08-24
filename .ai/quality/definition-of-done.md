# Nexora AI Definition of Done

No AI agent may advance the execution cursor merely because code was written or tests were added. A stage/unit is complete only when every applicable gate below is satisfied and evidence is recorded.

## Universal preconditions before implementation

- Parent stage ID is explicit and matches `.ai/roadmap/stages.md`.
- Development-unit ID(s) exist in `.ai/registry/development-units.json`.
- Requested work is inside the registered unit scope.
- Dependencies are mapped and satisfied or explicitly marked blocking.
- `.ai/plans/active.md` contains the required plan fields before substantial implementation.
- New feature/system/package work is not hidden inside unrelated scope.
- High/critical-risk work has an explicit threat model before implementation.

## Universal completion gates

### 1. Scope / planning integrity

- Stage/unit IDs are named explicitly.
- Acceptance criteria are objective and testable.
- Existing downstream code is not used to skip missing prerequisites.
- AI-discovered optional work is not silently promoted to implementation.
- Out-of-scope items remain out of scope unless plan/registry/roadmap are updated first.

### 2. Architecture

- Public Contracts/Capabilities are used where required.
- No first- or third-party package reaches private Core through an undocumented shortcut.
- Architecture/public-contract/tenancy/security/execution-model changes have ADR/review evidence when required.
- Theme, extension, tenancy, publishing, installer, deployment, API and AI boundaries remain fail-closed.
- Shared Admin UI primitives are used instead of ad-hoc alternatives where canonical components exist.
- Persistence/domain boundaries follow the accepted architecture.
- Data/migrations remain fresh-install safe and portable across supported databases where applicable.

### 3. Security / privacy

- Development-unit risk class is recorded.
- Required threat model is complete for `high`/`critical` work.
- Authorization and tenancy paths are tested, including negative/cross-tenant cases where relevant.
- Secrets are never logged/committed/exposed to AI context without explicit allowed contract.
- New network/filesystem/secret/package/AI execution capability is least-privilege and policy-bounded.
- Privacy/consent/retention/export/delete implications are addressed when personal/behavioral data is affected.
- Security controls are not disabled merely to make tests pass.

### 4. Functional implementation

- Happy path is implemented.
- Permission/authorization path is implemented.
- Validation and failure states are implemented.
- Empty/loading/error/destructive states are implemented where applicable.
- Upgrade/backward-compatibility impact is considered.
- Rollback/deactivation/uninstall behavior is considered for installable packages.
- Release/preview/staging implications are considered for publishable content/design changes.

### 5. Public surfaces

Every applicable surface is intentionally handled:

- Admin UI;
- public UI;
- REST/GraphQL/API;
- webhook/event;
- SDK/public contract;
- theme/template/slot;
- Studio/component/dynamic binding;
- extension registration;
- AI read/draft/execute tool surface;
- import/export/config-as-code.

`Not applicable` must be explicit in the active plan rather than omitted.

### 6. Verification

- Relevant unit tests pass.
- Relevant feature/integration tests pass.
- Architecture/security regression checks pass.
- Authorization/tenancy regression tests pass where applicable.
- TypeScript/static analysis/build checks pass where affected.
- Migration/fresh-install/upgrade tests pass where affected.
- Browser/E2E tests pass where user workflows are affected.
- AI evals pass where AI tools/agents are affected.
- Source-only verification is recorded as `SOURCE_DONE`, never `TARGET_VERIFIED`.
- Real browser/runtime/DB behavior is exercised on the required target before `TARGET_VERIFIED`.

### 7. Evidence

The handoff/registry/plan record:

- development-unit IDs;
- files/components changed;
- commands/tests executed;
- architecture/security review result;
- source result;
- target result;
- known limitations/residual risk;
- remaining blocker;
- exact next action.

### 8. Documentation / AI state

- affected registry entries are updated;
- `.ai/state.json` is updated;
- `.ai/handoff/current.md` is updated;
- `.ai/plans/active.md` is updated;
- system/capability/security docs are updated if behavior/status changed;
- stage/release-train graph is updated if scope/dependency/order changed;
- ADR/threat-model docs are updated if required;
- historical evidence is preserved rather than rewritten to appear cleaner.

## Additional gates for CMS/content systems

When applicable, verify:

- content type definition/registration;
- fields/validation/field groups;
- one-to-one/one-to-many/many-to-many relations;
- taxonomy binding;
- permissions/capabilities;
- permalink/routing behavior;
- archive/query behavior;
- revisions/editorial flow;
- theme/template resolution;
- Studio/dynamic binding integration;
- SEO/AEO resource/schema integration;
- API/import/export behavior;
- extension registration path;
- locale/release/preview behavior where relevant.

## Additional gates for Theme systems

Verify complete lifecycle where supported:

`upload -> quarantine/Sentinel -> manifest validation -> install -> preview -> activate -> public render -> switch -> rollback -> uninstall/remove`

Also verify template hierarchy/fallback, design tokens, menu locations/slots, SEO/content ownership separation, compatibility policy and failure-safe rendering.

## Additional gates for Extension/App/Integration/Studio-Pack systems

Verify:

`upload/stage -> quarantine -> Sentinel/Supply Chain -> manifest compatibility -> requested capabilities -> admin grants -> dependencies -> install -> enable -> runtime behavior -> disable -> version switch -> guarded rollback -> uninstall`

Also verify:

- package family and stable unit ID;
- runtime mode (`declarative` preferred; `trusted-php` exceptional);
- migration policy (`none` or `forward-only` under current contract);
- network/filesystem/secret access;
- public contract/slot usage;
- compatibility matrix;
- no private Core shortcut even for first-party packages.

## Additional gates for Site Builder / release systems

Verify:

- structured/validated visual AST;
- responsive behavior and component inheritance/overrides;
- dynamic bindings/query permissions;
- undo/history/revisions;
- preview/staging isolation;
- branch/merge/conflict behavior;
- scheduled/selective/group publishing;
- rollback/release history;
- accessibility baseline;
- no arbitrary executable markup trusted merely because AI/user generated it.

## Additional gates for SEO/Search/AEO systems

Verify:

- canonical title/description/URL output;
- robots/index/follow policy;
- sitemap inclusion/exclusion;
- Schema Graph output/stable IDs;
- extension/resource contribution behavior;
- public search noindex behavior;
- internal-link/audit evidence behavior;
- crawler host/path/network boundaries;
- AI-readable representations do not expose private/admin data;
- AEO/citation/visibility output remains evidence-based rather than vanity-score driven.

## Additional gates for AI tools/agents

Verify:

- tool/action is represented by registered `AIT-*`/`AIA-*` or parent AI unit;
- no direct unrestricted shell/database/filesystem/secret/network bypass;
- user/tenant identity propagates to tool authorization;
- structured tool schema validates inputs/outputs;
- least-privilege capability scope;
- prompt-injection/tool-output trust boundaries;
- dry-run/approval for high-risk actions where designed;
- output validation before side effects;
- immutable audit of request/approval/execution;
- rate/budget/concurrency controls;
- rollback/recovery metadata;
- evals for misuse, injection, data leakage and excessive agency;
- critical AI execution changes receive independent review evidence.

## Additional gates for security/runtime systems

- No trust gate is bypassed to make development easier.
- Quarantined code is not executed before approval/activation.
- Evidence distinguishes static inspection from real execution isolation.
- `trusted-php` is never described as OS/container/process isolated unless such isolation exists and is target-verified.
- Runtime/source identity claims use exact evidence.
- Incident/disable/quarantine/revoke/recover path is defined for high-risk runtime/package capabilities.

## Additional gates for privacy/experimentation/personalization

- consent requirements are evaluated before collection/assignment;
- GPC/DNT behavior is preserved where applicable;
- audience/segment data does not leak across tenants/users;
- experiment assignment is deterministic and reversible;
- default/fallback experience exists;
- analytics/goals respect consent and retention policy;
- AI-generated variants remain drafts until governed publish flow approves them.

## Additional gates for managed cloud / operations

- self-hostable architecture is not silently broken by managed-only assumptions;
- tenant/site isolation is explicit;
- domains/SSL/secrets/backup/restore/deploy operations are auditable;
- failure/drain/rollback/recovery paths are defined;
- operator health/diagnostics do not leak secrets;
- HA claims require multi-node/shared-state evidence.

## Cursor advancement rule

The cursor advances only when the active stage's required status is reached. If the next stage requires real-target verification, `SOURCE_DONE` is insufficient. A new feature discovered during closure is registered/planned first; it does not silently pull the cursor into unrelated implementation.
