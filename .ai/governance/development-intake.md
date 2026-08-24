# Nexora Development Intake & Pre-Planning Protocol

This protocol applies to every new Core system, module, feature, extension, app, integration, studio pack, theme, AI tool/agent, API surface, migration adapter, security control and operational capability.

## Non-negotiable rule

**No implementation begins from an idea alone.**

A requested or discovered change must be represented in the AI control plane before code is written.

The minimum sequence is:

```text
request / discovered gap
    -> classify development unit
    -> search registry
    -> register or reconcile unit
    -> map parent stage + dependencies
    -> architecture/data/security/API/AI impact
    -> acceptance criteria + tests + rollback
    -> active plan
    -> implementation
    -> source verification
    -> real-target verification where applicable
    -> state/handoff/evidence update
```

## Development unit types

Every planned unit uses one type:

- `core-system` — platform-wide subsystem such as routing, search or security.
- `module` — bounded first-party domain module operating through public platform boundaries.
- `feature` — capability added inside an existing system/module.
- `extension` — installable `extension` package.
- `app` — installable `app` package.
- `integration` — installable `integration` package/provider adapter.
- `studio-pack` — installable Studio component/template pack.
- `theme` — installable presentation system managed by Theme Engine.
- `ai-tool` — typed AI tool callable through the AI Tool Registry.
- `ai-agent` — bounded planner/assistant/agent using approved AI tools.
- `migration-adapter` — source-platform import/migration adapter.
- `ops-capability` — deployment, observability, backup, DR or cloud operational capability.
- `security-control` — preventive/detective/response security capability.

## Unit lifecycle

Allowed planning lifecycle:

`IDEA -> PROPOSED -> PLANNED -> ACTIVE -> SOURCE_DONE -> TARGET_VERIFIED`

Additional terminal/special states:

- `EXTERNAL` — intentionally outside Core and delivered as package.
- `DEFERRED` — accepted but intentionally delayed.
- `BLOCKED` — cannot proceed because prerequisite/evidence is missing.
- `REJECTED` — evaluated and intentionally not accepted.
- `DEPRECATED` — superseded/retired with migration path when required.

## Intake behavior

### When the user explicitly requests a new capability

The AI may plan it immediately. Before implementation it must:

1. create or update its registry entry;
2. assign a stable unit ID;
3. map it to a canonical stage/release train;
4. record dependencies;
5. create/update the active execution plan;
6. perform mandatory architecture/security/data/API/AI impact review;
7. define verification and rollback;
8. only then write implementation code.

The user does not need to repeat the request after the planning gate unless the planned approach materially changes product scope, security/trust boundaries or destructive data behavior.

### When AI discovers an unrequested opportunity or gap

The AI may register it as `PROPOSED`, explain why it matters and place it in the roadmap, but it must not silently implement it unless:

- it is necessary to correctly complete the user-requested active unit; or
- the user approves/promotes it; or
- an already-approved parent stage explicitly includes that capability.

## Stable unit ID format

Use semantic IDs, not historical milestone numbers:

- `SYS-*` for Core systems.
- `MOD-*` for first-party modules.
- `FEAT-*` for features.
- `EXT-*` for installable extensions.
- `APP-*` for installable apps.
- `INT-*` for integrations.
- `SPK-*` for Studio packs.
- `THM-*` for themes.
- `AIT-*` for AI tools.
- `AIA-*` for AI agents.
- `MIG-*` for migration adapters.
- `OPS-*` for operations capabilities.
- `SEC-*` for security controls.

IDs are immutable after code or external artifacts reference them. Rename display names, not IDs.

## Required planning fields

Every unit must record:

1. stable ID and type;
2. name and problem statement;
3. intended users/use cases;
4. parent stage and release train;
5. dependencies and conflicts;
6. Core vs external-package decision;
7. architecture/contracts impacted;
8. persistence/data/migration impact;
9. permission/capability/tenancy impact;
10. security risk class and threat-model requirement;
11. privacy/compliance impact;
12. UI/UX and accessibility impact;
13. API/webhook/SDK surface;
14. theme/Studio surface;
15. extension/package surface;
16. AI read/draft/execute exposure;
17. observability/audit requirements;
18. performance/cache considerations;
19. testing/eval strategy;
20. target verification steps;
21. rollback/recovery/update compatibility;
22. documentation/migration notes;
23. explicit acceptance criteria;
24. explicit out-of-scope items.

## Package planning rule

Before AI creates an Extension/App/Integration/Studio Pack/Theme, the plan must additionally define:

- package family;
- manifest identity/version policy;
- minimum/maximum Nexora compatibility;
- required public contracts;
- declared runtime capabilities;
- runtime mode (`declarative` preferred; `trusted-php` exceptional);
- migration policy (`none` or `forward-only` under current contracts);
- required UI/theme/Studio slots;
- network/filesystem/secret access;
- Sentinel/Supply Chain expectations;
- install/activate/deactivate/update/rollback/uninstall behavior;
- compatibility and regression tests.

No package may rely on private Core shortcuts merely because it is first-party.

## New feature planning rule

A new feature inside an existing system is still a unit. Do not bypass planning by calling it a 'small change' when it changes behavior, data, permissions, public contracts, package capability, security or UX semantics.

Trivial typo/copy/style-only fixes may remain inside the active unit without a new registry item when they do not alter behavior or contracts.

## Architecture decision rule

Create or update an ADR before implementation when a unit:

- changes a public contract;
- introduces a new runtime execution model;
- changes tenancy/security boundaries;
- introduces a new database/storage architecture;
- changes extension/theme isolation;
- introduces a new external protocol/provider abstraction;
- changes the canonical content/routing/query model;
- gives AI a new execution capability.

## Security rule

Security is continuous, not a late stage. Every unit receives a security impact classification. `high` and `critical` units require an explicit threat model before implementation.

## AI development rule

An AI agent may plan, implement and test work, but it may not use its own claims as sufficient certification. High-risk/security/architecture work requires independent review evidence or a separate review pass, plus automated checks and real-target evidence where applicable.

## Enforcement destination

`AI-GOV-AUTOMATION-100` will add machine validation so CI can reject inconsistent/unplanned work. Until that stage is implemented, `AGENTS.md`, this protocol, the development-unit registry, the active plan and review rules are mandatory procedural controls.
