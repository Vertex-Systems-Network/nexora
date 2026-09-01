# Nexora AI-Native Product Architecture

## Purpose

This document defines the architecture that must exist before Nexora can truthfully claim to be AI-native at the product level. AI-assisted coding alone does not make the product AI-native.

## Core principle

AI is a governed client of Nexora public contracts. It does not receive a privileged shortcut around auth, tenancy, capabilities, Sentinel, content schemas, validation, audit or rollback rules.

## Architecture stack

```text
User / Admin / Studio intent
        ↓
AI Request Router
        ↓
Context Engine
        ↓
Planner / specialized agent
        ↓
Structured Action Plan
        ↓
AI Capability Gate
        ↓
Tool Registry
        ↓
Dry Run / Validation
        ↓
Approval Policy
        ↓
Execute through public Nexora contracts
        ↓
Post-action validation
        ↓
Audit / telemetry / rollback metadata
```

## 1. Model Gateway

Provider-neutral model access layer.

Must support:

- multiple providers/models;
- model capability metadata;
- structured-output support detection;
- cost/token/latency accounting;
- retries/backoff;
- fallback policy;
- timeout/circuit-breaker policy;
- tenant/site-level provider configuration;
- secret isolation;
- no provider-specific types leaking into domain code.

## 2. Agent Runtime

Initial platform agent roles:

- Planner — decomposes user intent into validated action plans.
- Content Agent — works with documents/content types/taxonomies/media.
- SEO Agent — uses SEO Core, crawler evidence, routes and Schema Graph.
- Design Agent — works only through Design/Studio contracts and structured visual AST.
- Site Builder Agent — coordinates content, navigation, theme and Studio tools.
- Commerce Agent — future capability-gated commerce workflows.
- Developer Agent — future SDK/package scaffolding and extension-development assistance.
- Reviewer Agent — validates proposed mutations against architecture/security/quality policies.

Agent names are product roles, not permission grants. Actual permissions come from the capability gate.

## 3. Tool Registry

AI tools must wrap public platform capabilities rather than database tables.

Initial target tool families:

- site.settings.*
- content.types.*
- content.documents.*
- content.fields.*
- content.relations.*
- taxonomy.definitions.*
- taxonomy.terms.*
- query.*
- navigation.*
- themes.*
- studio.*
- media.*
- seo.*
- search.*
- forms.*
- workflows.*
- extensions.*
- marketplace.*
- commerce.*
- users/roles.* where explicitly allowed
- diagnostics.* read-only by default

Every tool requires:

- stable tool ID;
- JSON schema input;
- JSON schema output;
- required capabilities;
- read/write classification;
- tenant/site scope;
- idempotency semantics where relevant;
- dry-run support where mutation is consequential;
- audit event type;
- failure contract.

## 4. AI Capability Gate

The AI runtime must never infer privilege from conversational intent.

Authorization inputs include:

- authenticated human actor;
- tenant/site/workspace;
- human roles/permissions;
- AI policy profile;
- tool-required capabilities;
- package/runtime identity when an extension contributes a tool;
- environment policy;
- mutation sensitivity.

Default policy is deny.

## 5. Context Engine

Context is assembled deliberately, not by dumping the whole database into prompts.

Context providers:

- project/site metadata;
- content type/taxonomy schemas;
- relevant documents;
- navigation graph;
- active theme/design tokens;
- Studio component contracts;
- SEO evidence;
- media metadata;
- workflow/form schemas;
- current user permissions;
- extension tool manifests;
- recent approved AI actions;
- applicable design/brand guidance.

Context providers must declare freshness, scope and maximum payload behavior.

## 6. Structured plans and actions

Free-form model prose is never executed directly.

A mutation sequence is:

```text
intent
→ proposed plan
→ schema validation
→ dependency validation
→ capability validation
→ dry-run
→ approval decision
→ execution
→ postcondition validation
→ audit record
```

Plans require stable action IDs so retries cannot duplicate unsafe mutations.

## 7. Approval policy

Approval levels:

- `auto-read` — safe read-only operations.
- `auto-low-risk-write` — bounded reversible writes if policy permits.
- `confirm` — meaningful content/design/config mutations.
- `elevated-confirm` — permissions, extensions, payments, destructive actions, production operations.
- `forbidden` — policy/security boundaries that AI cannot override.

Approval decisions must be configurable by tenant/site and capability.

## 8. Memory

Separate memory classes:

- Session context — ephemeral conversation state.
- Site memory — approved durable facts/preferences about the site/project.
- Task memory — active plan/checkpoint.
- Audit history — immutable action/evidence history.

Do not treat vector retrieval as authoritative state. Machine state and canonical schemas remain authoritative.

## 9. Prompt and instruction registry

Prompts are versioned product assets.

Each prompt definition should declare:

- ID/version;
- intended agent;
- required context providers;
- output schema;
- model capability requirements;
- safety/architecture policy version;
- eval suite;
- change history.

Prompt changes are testable changes, not invisible configuration edits.

## 10. AI Evals

Minimum eval families:

- schema-valid output rate;
- tool-selection correctness;
- permission/capability refusal correctness;
- hallucinated entity/tool prevention;
- destructive-action approval compliance;
- content factuality where evidence is available;
- SEO recommendation evidence quality;
- design-token/component adherence;
- responsive/a11y design validity;
- rollback/idempotency behavior;
- provider fallback consistency.

A prompt/model update that materially degrades required evals cannot be promoted silently.

## 11. Telemetry and cost controls

Track by tenant/site/user/agent/tool/model:

- requests;
- tokens;
- estimated/actual provider cost where available;
- latency;
- retries;
- tool calls;
- failures;
- approvals;
- denied actions;
- eval/version metadata.

Budgets and rate limits must be enforceable.

## 12. Extension-contributed AI tools

Extensions may contribute AI tools only through a manifest-declared SDK contract.

Requirements:

- Sentinel admission;
- explicit requested AI-tool capability;
- tool schema validation;
- namespaced tool IDs;
- runtime identity attribution;
- tenant admin approval;
- no hidden prompt injection into global system policy;
- no direct model credential access;
- no capability escalation.

## 13. AI architecture staging

`AI-KERNEL-100` builds the provider gateway, tool registry, capability gate, structured plan executor, context engine, prompt registry, audit and eval foundations.

`AI-CONTENT-100` exposes governed CMS/media/SEO assistance on top of the completed content/query/routing contracts.

`AI-DESIGN-100` exposes governed design/site-building capabilities on top of Theme Contract 2.0 + Studio 2.0.

`AI-DX-100` exposes architecture/SDK-aware development assistance for extension/app/theme creators.

## Non-negotiable acceptance condition

Nexora cannot claim an AI feature complete if the model can produce a plausible response but the action path bypasses typed tools, capabilities, validation, approval policy, audit or applicable evals.
