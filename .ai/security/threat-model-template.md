# Nexora Threat Model Template

Use this template before implementing any development unit whose registry entry marks `threat_model_required=true`.

## Unit

- Development unit ID:
- Parent stage:
- Risk class:
- Owner/reviewer:

## Assets

List data, identities, secrets, money, content, tenant boundaries, execution privileges and availability guarantees that matter.

## Trust boundaries

Identify crossings between browser/admin/public API, tenant contexts, Core/modules, extension runtime, filesystem/storage, queues, databases, external providers, AI/model/tool boundaries and deployment infrastructure.

## Entry points

List HTTP/API routes, webhooks, uploads, package manifests, background jobs, CLI, extension hooks, Studio bindings, AI tools, migration/import inputs and admin actions.

## Threats

At minimum evaluate:

- broken access control / IDOR / tenant escape;
- authentication/session abuse;
- injection and unsafe output handling;
- SSRF/network pivoting;
- upload/archive/parser abuse;
- deserialization/dynamic execution;
- secret exposure;
- supply-chain/package compromise;
- dependency/provenance tampering;
- privilege/capability escalation;
- data corruption/race/idempotency failures;
- denial of service/resource exhaustion;
- unsafe rollback/update/restore;
- audit/log tampering or evidence gaps;
- privacy/retention/consent failure;
- prompt injection/tool misuse/excessive agency when AI is involved.

## Abuse cases

Describe realistic attacker/user/extension/agent abuse stories, including cross-tenant and compromised-admin scenarios.

## Controls

For every material threat, record preventive, detective and recovery controls. Prefer default-deny and bounded brokered capabilities.

## Residual risk

Record risks that remain after controls and why they are accepted/deferred.

## Verification

List automated security tests, fuzzing/corpus cases, manual review, target checks and evidence required before `SOURCE_DONE`/`TARGET_VERIFIED`.

## Rollback / incident response

Describe how to disable, quarantine, revoke, restore or roll back the unit if exploitation or regression is discovered.

## Review outcome

- [ ] Threat model complete.
- [ ] Critical/high threats have mitigations.
- [ ] Required tests added to active plan.
- [ ] Residual risk accepted/documented.
- [ ] Independent security review required/completed where applicable.
