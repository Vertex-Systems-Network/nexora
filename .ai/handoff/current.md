# Nexora Current AI Handoff

## Resume instruction

Begin with `AGENTS.md`, `.ai/README.md`, `.ai/state.json`, this handoff, `.ai/roadmap/legacy-aliases.md`, `.ai/registry/development-units.json`, `.ai/plans/active.md`, `NEXORA_PROGRESS.md`, and the current GitHub PR/head state. Never resume from historical chat or stale target prose when repository evidence is available.

## Current source / governance context

- Long-lived engineering branch: `dev/n1-0b-core-functional-qa` / PR #1, DRAFT + OPEN.
- N1.12 accepted frozen source: `78976e9f44e290155b119ae722a77fb37fd59018`.
- That source passed exact-head GitHub-hosted governance via run `33553824752` / job `100009648131`; development-readiness artifact `9818655365`, digest `sha256:471949869471e6db95dcf0fd0da0f47b65e062ce8f196f2105145741fd8a8fa4`.
- Current source release: `1.0.0-rc.94`; installer protocol `v5.29`; generation `n1-v5.29`.
- The N1.12 closure/N1.13 cursor commit is control-plane/documentation only. Its resulting exact head must pass `governance` before it becomes the N1.13 frozen source.

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

Issue #39 CLOSED completed; diagnostic PR #40 CLOSED + UNMERGED. Frozen source `2f5eb3b9dcf1c146f4e647fb3441318c4bf2c829`; carrier `b1481c4064bbad96b79cf13d40ac5d07be10a434`; run `33550851207` / job `99999628418`; artifact `9817460169`; independently verified digest `sha256:b973ffdc424daf3e5b0987d75bff2c6a79b94f1c5e3530a7058091b1af08e1c8`.

### N1.12 Search 2.0

**BOUNDED TARGET VERIFIED.** Issue #41 CLOSED completed; diagnostic PR #45 CLOSED + DRAFT + UNMERGED.

- Frozen product source: `78976e9f44e290155b119ae722a77fb37fd59018`.
- Exact-head governance run: `33553824752` / job `100009648131` PASS.
- Certified carrier head: `449ea9b3d5c0d2ad176031a1849ed80c349e0023`.
- Target run: `33563871904` / job `100042466730` SUCCESS.
- Artifact: `9822383456`.
- GitHub digest and independently downloaded ZIP SHA-256 both: `6d298f5d60c5dc7a0423aec8d73d107de79888662cc919d62d53824b0e723731`.
- Fresh disposable rc.94 install + guarded post-install reconcile PASS.
- Search product-contract verifier and warning-hard `SearchProductIsolationTest` PASS.
- Installed-target document/media index lifecycle, SEO refresh, stale rebuild cleanup, repeat rebuild/idempotency, deterministic exact-title ranking and tenant isolation PASS.
- Product dual authorization path was exercised: global RBAC `Role` plus tenant-local `EnterpriseRole`; no super-admin bypass was used.
- Real HTTP: public Search 200; protected document/media public non-disclosure; disabled Search 404; guest Admin Search 302; authorized Admin Search 200; missing `search.use` 403; document/media result visibility follows respective permissions; missing `search.index.manage` reindex 403; authorized reindex succeeds and records audit evidence.
- GPC and DNT requests were served normally but did not persist Search query-log evidence.
- Server evidence contained no hidden fatal/uncaught Search runtime failure in the accepted flow.

Only carrier SHA `449ea9b3d5c0d2ad176031a1849ed80c349e0023` / run `33563871904` is certified. Later PR #45 tree-equivalent/no-content heads are explicitly excluded from the acceptance record.

Explicit non-claims: external Search providers, distributed indexing/replication/HA, unimplemented facets, Search performance/scale/relevance SLOs, broader `PORTAL-200`, five-engine DB matrix, N1.13+, HA/recovery/C5/C6/final release.

## Current active stage

Stable semantic stage: `COLLAB-200`.

Registered unit: `SYS-COLLABORATION`.

Legacy execution alias: `N1.13 — Collaboration`.

Status: **PARTIAL — source exists; bounded real-target acceptance pending.**

Canonical roadmap dependencies are `SITE-BUILDER-200`, `RELEASE-WORKFLOW-200`, and `PORTAL-200`. They remain separate product/evidence claims. The user-authorized N1.9–N1.26 target-QA order may continue but does not silently satisfy, skip or certify those dependencies.

Registry source truth identifies Collaboration local contracts including `App\Nexora\Collaboration\CollaborationRepository`, `ApprovalWorkflow`, and `LockManager`. Distributed coordination semantics, if source-implemented/enabled, require explicit evidence; they are not inferred from stage naming.

## Current blocker

The N1.12 closure and N1.13 cursor are being applied as one state-only canonical dev commit. Before any N1.13 target carrier:

1. obtain exact-head GitHub-hosted `governance` PASS on that resulting dev head;
2. audit current Collaboration comments, locks, approvals, history, routes/controllers/models, tenant/permission boundaries, migrations, tests and registered local contracts;
3. freeze one exact-source bounded acceptance tracker from source truth;
4. only then execute the disposable real-target carrier.

Do not weaken governance, tests, authorization, tenant scopes, lock semantics or approval rules to move the cursor.

## N1.13 target-QA intent

- verify only Collaboration behavior already implemented on the exact governed source;
- verify comment/history ownership and visibility boundaries;
- verify lock acquire/conflict/refresh/release/expiry semantics only where implemented, including concurrency behavior without lock theft or stale-writer acceptance;
- verify approval state transitions, authorization and invalid-transition rejection where implemented;
- verify tenant/organization isolation across collaboration records and actor/member references;
- verify history/audit provenance and fail-closed behavior;
- verify retry/idempotency only where the source claims it;
- exercise existing real HTTP/API/UI boundaries where exposed;
- independently download and hash the evidence artifact before acceptance;
- keep any diagnostic carrier DRAFT + DO NOT MERGE and close it unmerged.

## Remaining boundaries after N1.13

Continue explicit legacy target-QA order N1.14–N1.26 while keeping canonical semantic dependency/product-expansion claims separate. Still required globally: five-engine DB matrix; controlled provider/identity/API/import/observability/Sentinel/Marketplace evidence; HA; backup/restore + upgrade rehearsal; C5 browser/W3C/WAVE/AT/HTTP/Web-Vitals evidence; C6 reviewed dependency locks/provenance/final operations/release evidence.

Project/Source/Target/Release power remains `76.5% / 99.0% / 50.0% / 25.0%` until broader evidence explicitly justifies a change.

## Exact next action

**Require exact-head governance on this synchronized N1.12 closure/N1.13 cursor commit. Then audit and freeze the bounded Collaboration acceptance contract; do not start target execution before that PASS.**
