# Nexora AI Roles

Role separation prevents one agent from silently inventing, planning, implementing, approving and certifying its own work without the right controls.

## Product / Intake Planner

- reads current state, release trains, stage graph and development-unit registry;
- classifies every requested/discovered unit using `.ai/governance/development-intake.md`;
- reuses an existing registered unit where possible;
- creates a stable unit ID and registry entry before new implementation work;
- maps parent stage, release train, dependencies, conflicts and acceptance criteria;
- may register AI-discovered optional ideas as `PROPOSED` but may not silently promote them to implementation.

## Stage Planner

- decomposes only the active stage into ordered tasks/chunks;
- fills `.ai/plans/active.md` from the canonical plan template;
- identifies existing implementation to preserve, prerequisites, architecture/data/security/privacy/API/theme/Studio/AI impacts, tests and rollback;
- does not mark implementation complete.

## Architect

- protects `ARCHITECTURE.md`, public contracts, capability boundaries and trust model;
- decides Core vs package boundaries;
- writes/updates ADRs when public contracts, tenancy, execution models, storage or protocol boundaries change;
- prevents first-party shortcuts that third-party packages could not use;
- normally does not certify its own architecture change.

## Security Architect / Threat Modeler

- classifies high/critical attack surfaces;
- completes/updates threat models;
- reviews auth/tenancy, upload/parser, network/SSRF, secrets, package runtime, supply chain, payment, destructive operations and AI execution risks;
- maps required tests and incident/rollback controls;
- must distinguish policy restrictions from real runtime isolation.

## Developer

- implements only registered development units in the approved active plan;
- makes the smallest architecture-correct change;
- adds contracts/migrations/tests/security controls in the same slice where applicable;
- does not introduce hidden/unplanned product work;
- does not advance stage/unit status without evidence.

## AI / Tool Developer

- builds AI tools/agents only through the governed Tool Registry/capability architecture;
- never grants AI unrestricted shell/database/filesystem/secret/network access as a convenience;
- adds structured schemas, identity propagation, approval policy, audit, rate/budget limits and misuse/injection/leakage evals;
- treats external retrieved/tool content as untrusted input.

## Reviewer

- checks scope/registry/active-plan alignment;
- checks architecture drift, security boundaries, regressions, hidden coupling and backward compatibility;
- verifies source claims match changed code/tests;
- challenges unsupported completion claims.

## Security Reviewer

- independently reviews high/critical changes where required;
- verifies threat-model mitigations, negative authorization/tenancy tests, supply-chain and AI/tool risks;
- may block `SOURCE_DONE` when required security evidence is absent.

## QA / Verifier

- runs required source, integration, browser and real-target checks;
- records PASS/FAIL/NOT_RUN/NOT_APPLICABLE separately;
- distinguishes `SOURCE_DONE` from `TARGET_VERIFIED`;
- verifies acceptance criteria rather than generic “tests pass” claims.

## AI Eval Reviewer

- independently evaluates agent/tool behavior for prompt injection, tool misuse, data leakage, excessive agency, output validity, tenant/permission propagation and rollback/audit correctness;
- does not rely only on the prompts/evals authored by the implementing AI.

## Release / Certification

- runs exact-source production gates only when roadmap reaches `RELEASE-CERT-100`;
- verifies performance, accessibility, dependency/security, database, backup/restore, HA and packaging evidence for the intended release source;
- does not pull deep final certification forward in a way that blocks builder usability unless a safety/runtime gate requires it.

## Handoff owner

Whichever role finishes a meaningful pass must update affected development-unit entries, `.ai/state.json`, `.ai/handoff/current.md` and `.ai/plans/active.md`. No role may leave the next agent dependent on chat memory.

## Independence rule

One model/session may perform multiple roles for practical development, but high-risk architecture/security/AI execution/package-runtime work still requires a distinct review pass/context and objective automated/target evidence. Self-asserted correctness is never sufficient certification.
