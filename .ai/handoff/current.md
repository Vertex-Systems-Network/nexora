# Nexora Current AI Handoff

## Resume instruction

Begin with `AGENTS.md`, `.ai/README.md`, `.ai/state.json`, this handoff, `.ai/roadmap/legacy-aliases.md`, the relevant registry entries, `.ai/plans/active.md`, `NEXORA_PROGRESS.md`, and the current GitHub PR/head state. Never resume from historical chat or stale runtime-closure prose when current repository evidence is available.

## Current source / integration context

- Long-lived engineering branch: `dev/n1-0b-core-functional-qa` / PR #1.
- Current certified/main control-plane lineage integrated from: `6d0bb2cf7f92777b8f5f7f4f84ae0f041069124a`.
- Last exact dev source already proven by hosted `governance`: `43314a111405245f151ec66c01e9261af675c992` via run `32672492494`.
- N1.10 state-only dev head before main-control-plane reconciliation: `f4b8daa94781907ae78649cbc3ac1bfe26380803`.
- Resolved true two-parent integration commit: `1e5994362720b2d4ec17b003af305335b44d05e5`, with parents `f4b8daa94781907ae78649cbc3ac1bfe26380803` and `6d0bb2cf7f92777b8f5f7f4f84ae0f041069124a`.
- Current integration branch includes the merged `.ai/**` governance system, rc.93 repair-pack lineage, dev W3C/Web Standards commands, `dev:target-qa`, and the main `repair:rc93` alias.
- `.ai/state.json` has been reconciled after the integration to control-plane revision 8. Exact-head governance on the final reconciled integration head is still required before N1.11 execution.

## Evidence precedence

`.ai/state.json` is the canonical active stage/unit cursor. `NEXORA_PROGRESS.md` is the detailed live target/release evidence dashboard for the long-lived dev program and must agree with it. `NEXORA_AI_PROJECT_STATE.md` is historical evidence. If active cursor and detailed dashboard conflict, stop and reconcile rather than silently choosing one.

`SOURCE_DONE != TARGET_VERIFIED`. Bounded target acceptance does not imply broader provider, database, HA, recovery, accessibility or final-release certification.

## Accepted bounded target evidence

### Runtime replacement recovery

Issue #2 is CLOSED via the approved **separate disposable current-source rc.94 replacement recovery acceptance**. The preserved rc.93 installation remains historical evidence and is not reclassified as an in-place PASS.

Accepted run: `32667462959`; exact dev source `a6b6462954edddbe138bc26577625bac2a8bddd2`; carrier PR #17 remains CLOSED + UNMERGED; artifact `9500449768`; digest `sha256:1ac7ccf409181322e74ca1444bfd2ed3cca1539875eba398ad0d98a06e7e4aba`.

The newly integrated rc.93 repair pack from main is preserved as historical/control tooling. Its older `.ai` cursor that described runtime closure as BLOCKED is superseded by the later accepted replacement evidence; do not reopen Issue #2 merely because that historical control-plane lineage was integrated.

### N1.9 Marketplace

Issue #20 CLOSED completed; PR #21 CLOSED + UNMERGED. Accepted run `32671245015` on source `8e359f07dc6b608b0d09468386fca13f066337a1`; artifact `9501470648`; digest `sha256:b26036aa0ad8c7ac075f1a60e213163ce10121e2ff3f606cdd06406ce3fb6aed`.

This certifies only the bounded current Marketplace source → sync → stage/Sentinel → owning Extension-engine workflow. It does not certify later `MARKETPLACE-200` hardening.

### N1.10 Commerce

Issue #32 CLOSED completed; PR #33 CLOSED + UNMERGED. Frozen source `43314a111405245f151ec66c01e9261af675c992`.

Primary Windows real-target run `33540575198` PASS; artifact `9813554570`; digest `sha256:68e8e9cefcb32a49a6d9912b5a3b1a4f7eaf0b3ac94850ec8418262c82cad882`.

Provider persistence/idempotency supplement run `33540575159` PASS; artifact `9813440996`; digest `sha256:1af3fd58308e92e1f90431588a045ecc7810d1de96bc7152bed4e9a0c0bcd330`.

Accepted evidence covers fresh rc.94 install/reconcile, real HTTP Commerce workspaces and order/invoice path, historical line-item snapshot integrity, 13 Commerce tests / 91 assertions, deterministic provider-contract payment/refund/subscription persistence and retry/failure behavior. It does **not** certify Stripe/PayPal/live gateways, PCI, external webhooks or provider-specific production behavior.

## Current active stage

Stable semantic stage:

`CRM-MEMBERSHIP-HELPDESK-CLOSURE-001`

Registered active unit:

`SYS-CRM-MEMBERSHIP-HELPDESK`

Legacy execution alias being continued by explicit user priority:

`N1.11 — CRM / Membership / Customer Portal`

Status:

**PARTIAL — source exists; bounded real-target acceptance not yet executed.**

The legacy alias maps to `CRM-MEMBERSHIP-HELPDESK-CLOSURE-001`, with broader Customer/Member Portal product expansion later represented by `PORTAL-200`. The bounded N1.11 acceptance must verify current existing portal/CRM/membership behavior without silently claiming `PORTAL-200` product-expansion certification or satisfying unrelated canonical dependency stages by implication.

## Current blocker

The latest `main` control-plane lineage diverged after prior dev governance, which made PR #1 merge-conflicted and prevented GitHub from creating a new `pull_request` governance run for state-only head `f4b8daa…`. A temporary same-head carrier also could not run for the same reason.

A semantic two-parent integration is now being completed on isolated branch `ops-main-ai-integration-resolved`. Before any N1.11 diagnostic target carrier:

1. finish `.ai` state/handoff/active-plan reconciliation;
2. prove the resolved integration branch is mergeable and run the GitHub-hosted exact-head `governance` workflow;
3. inspect its artifact/result rather than treating workflow existence as PASS;
4. merge the reviewed integration to dev only if governance is green;
5. ensure PR #1 is again mergeable and its resulting exact head is governed.

Do not weaken the workflow or create a fake status to bypass this gate.

## N1.11 target-QA intent

After exact-head governance is green:

- audit current Customer Portal, CRM and Membership source, routes, policies, tenant scoping, tests and existing product-contract scripts;
- freeze a bounded acceptance tracker against that exact governed source;
- use a disposable real target and canonical application routes/services;
- verify guest/auth boundaries, customer/member identity isolation, tenant isolation, CRM lifecycle, membership plan/entitlement lifecycle, Commerce↔CRM/Membership link consistency where already implemented, and current portal data exposure/authorization;
- capture exact-source binding, target/toolchain identity, HTTP evidence, target database state, audit/event evidence and applicable executable tests;
- independently inspect artifact bytes/digests before acceptance;
- keep any diagnostic carrier DRAFT + UNMERGED;
- on failure, distinguish product defect from carrier defect and fix only the bounded prerequisite.

Do not add new portal-builder features to make N1.11 pass. `PORTAL-200` remains a later product stage.

## Remaining boundaries after N1.11

Continue the explicit legacy target-QA order N1.12–N1.26 while keeping canonical semantic dependency/product-expansion claims separate. Still required globally:

- real disposable SQLite/MySQL/MariaDB/PostgreSQL/SQL Server matrix;
- controlled provider/identity/API/import/observability/Sentinel/Marketplace evidence where applicable;
- real HA/multi-node evidence;
- disposable backup/restore + upgrade rehearsal;
- C5 W3C HTML/CSS, WAVE, Chrome/Edge/Firefox, responsive/RTL/themes, assistive technology, HTTP/security/latency and Web Vitals;
- C6 reviewed dependency locks, provenance, final operations and release evidence.

Project/Source/Target/Release power remains `76.5% / 99.0% / 50.0% / 25.0%` until broader evidence explicitly justifies a change.

## Exact next action

**Complete and govern the reconciled main→dev control-plane integration. Only then freeze and execute N1.11 bounded target acceptance.**
