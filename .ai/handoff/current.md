# Nexora Current AI Handoff

## Resume instruction

Begin with `AGENTS.md`, `.ai/README.md`, `.ai/state.json`, this handoff, `.ai/roadmap/legacy-aliases.md`, `.ai/registry/development-units.json`, `.ai/plans/active.md`, `NEXORA_PROGRESS.md`, and the current GitHub PR/head state. Never resume from historical chat or stale target prose when repository evidence is available.

## Current source / governance context

- Long-lived engineering branch: `dev/n1-0b-core-functional-qa` / PR #1.
- Current product source before the state-only N1.11 closure apply: `2f5eb3b9dcf1c146f4e647fb3441318c4bf2c829`.
- That source is exact-head governance-green via Development Execution QA run `33545705598`.
- Current source release: `1.0.0-rc.94`; installer protocol `v5.29`; generation `n1-v5.29`.
- The N1.11 closure/N1.12 cursor commit is control-plane/documentation only. Its resulting exact head must pass `governance` before it becomes the N1.12 frozen source.

## Evidence precedence

`.ai/state.json` is the canonical active stage/unit cursor. `NEXORA_PROGRESS.md` is the detailed live target/release evidence dashboard and must agree with it. `NEXORA_AI_PROJECT_STATE.md` is historical evidence.

`SOURCE_DONE != TARGET_VERIFIED`. A bounded target PASS does not imply broader provider, dependency-graph, database, HA, recovery, accessibility or final-release certification.

## Accepted bounded target evidence

### Runtime replacement recovery

Issue #2 CLOSED via approved disposable current-source rc.94 replacement acceptance. PR #17 CLOSED + UNMERGED. Run `32667462959`; artifact `9500449768`; digest `sha256:1ac7ccf409181322e74ca1444bfd2ed3cca1539875eba398ad0d98a06e7e4aba`.

### N1.9 Marketplace

Issue #20 CLOSED completed; PR #21 CLOSED + UNMERGED. Source `8e359f07dc6b608b0d09468386fca13f066337a1`; run `32671245015`; artifact `9501470648`; digest `sha256:b26036aa0ad8c7ac075f1a60e213163ce10121e2ff3f606cdd06406ce3fb6aed`.

### N1.10 Commerce

Issue #32 CLOSED completed; PR #33 CLOSED + UNMERGED. Frozen source `43314a111405245f151ec66c01e9261af675c992`. Primary run `33540575198`, artifact `9813554570`, digest `sha256:68e8e9cefcb32a49a6d9912b5a3b1a4f7eaf0b3ac94850ec8418262c82cad882`. Provider supplement run `33540575159`, artifact `9813440996`, digest `sha256:1af3fd58308e92e1f90431588a045ecc7810d1de96bc7152bed4e9a0c0bcd330`.

### N1.11 Customer Portal / CRM / Membership

**BOUNDED TARGET VERIFIED.** Issue #39 CLOSED completed; diagnostic PR #40 CLOSED + UNMERGED.

- Frozen product source: `2f5eb3b9dcf1c146f4e647fb3441318c4bf2c829`.
- Governance run: `33545705598` PASS.
- Carrier head: `b1481c4064bbad96b79cf13d40ac5d07be10a434` (workflow-only instrumentation; never product source).
- Target run `33550851207` / job `99999628418`: SUCCESS.
- Artifact `9817460169`.
- GitHub digest and independently downloaded ZIP SHA-256 both: `b973ffdc424daf3e5b0987d75bff2c6a79b94f1c5e3530a7058091b1af08e1c8`.
- Frozen feature suite: `10 tests / 64 assertions PASS` with warning-hard execution.
- Real HTTP: guest `/account` 302→`/login`; verified user login 302→`/account`; authenticated `/account` 200; ordinary user `/admin` 403.
- Portal/tenant isolation: current linked customer + membership visible; other tenant/customer/member undisclosed; cross-tenant CRM Commerce link, Membership Commerce reference and lead-pipeline conversion fail closed.
- Fresh installer/reconcile and both Customer Portal + CRM/Membership product-contract verifiers PASS.

Carrier failures were harness-only defects (temporary generated-PHP BOM, login CSRF extraction, verified-user seed mass assignment) and were fixed without changing frozen product/runtime source or weakening assertions.

Explicit non-claims: Helpdesk, `PORTAL-200`, external providers/connectors, remaining five-engine DB matrix, N1.12+, HA/recovery/C5/C6/final release.

## Current active stage

Stable semantic stage: `SEARCH-200`.

Registered unit: `SYS-SEARCH`.

Legacy execution alias: `N1.12 — Search 2.0`.

Status: **PARTIAL — source exists; bounded real-target acceptance pending.**

Legacy N1.12 resolves directly to `SEARCH-200`. The registry defines Search 2.0 as facets, provider abstraction and advanced indexing/querying, while canonical data remains authoritative. Its canonical dependencies remain separate product/verification claims; the user-authorized legacy target-QA order does not silently certify them.

## Current blocker

The N1.11 closure and N1.12 cursor are being applied as a state-only canonical dev commit. Before any N1.12 target carrier:

1. obtain exact-head GitHub-hosted `governance` PASS on that resulting dev head;
2. audit current Search implementation, routes/services/models/provider/index abstractions, tenant/permission boundaries, migrations, tests and `scripts/search-product-contract-verify.php`;
3. freeze one exact-source acceptance tracker from source truth;
4. only then execute the disposable real-target carrier.

Do not weaken governance, tests, tenant scopes or product contracts to move the cursor.

## N1.12 target-QA intent

- verify only Search behavior already implemented on the exact governed source;
- prove canonical data remains authoritative over any index/projection;
- verify create/update/delete/reindex convergence, replay/idempotency, query/filter/facet/sort/pagination behavior where implemented;
- verify tenant/private-resource isolation and applicable auth/permission boundaries;
- verify provider default/selection/fail-closed behavior only where an abstraction is actually implemented;
- do not fabricate an external-provider certification if current source has no real provider integration;
- capture exact source/carrier/toolchain binding, installer/reconcile evidence, executable verifier/tests, HTTP/state/database/log evidence and explicit exclusions;
- independently download and hash the artifact before acceptance;
- keep any diagnostic carrier DRAFT + UNMERGED;
- distinguish product defects from carrier defects and fix only bounded prerequisites.

## Remaining boundaries after N1.12

Continue explicit legacy target-QA order N1.13–N1.26 while keeping canonical semantic dependency/product-expansion claims separate. Still required globally: five-engine DB matrix; controlled provider/identity/API/import/observability/Sentinel/Marketplace evidence; HA; backup/restore + upgrade rehearsal; C5 browser/W3C/WAVE/AT/HTTP/Web-Vitals evidence; C6 reviewed dependency locks/provenance/final operations/release evidence.

Project/Source/Target/Release power remains `76.5% / 99.0% / 50.0% / 25.0%` until broader evidence explicitly justifies a change.

## Exact next action

**Require exact-head governance on the synchronized N1.11 closure/N1.12 cursor commit. Then audit and freeze the bounded Search 2.0 acceptance contract; do not start target execution before that PASS.**
