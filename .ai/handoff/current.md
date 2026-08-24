# Nexora Current AI Handoff

## Resume instruction

Read in order:

1. `AGENTS.md`
2. `.ai/README.md`
3. `.ai/state.json`
4. `.ai/roadmap/stages.md` + release trains
5. `.ai/governance/development-intake.md`
6. `.ai/governance/ai-development-orchestration.md`
7. main + relevant domain registries (`development-units.json`, `performance-units.json`, `quality-payment-units.json`, `flow-units.json`, `ai-development-units.json`)
8. `.ai/plans/master-execution-plan.md`, plan template and active plan
9. `.ai/quality/engineering-lifecycle.md` + `.ai/quality/lean-six-sigma.md`
10. ResearchBrief/DataFlow documents where relevant
11. `.ai/flow/system-graph.md` for material runtime/package/data/security/permission/event/network/state/error/deployment relationships
12. security program; payment work also reads `.ai/security/payment-security.md`
13. performance/reliability/delivery documents where relevant
14. capability matrices/addenda + system registries
15. architecture/AI/design constitutions and current source/tests.

## Current source context

- Canonical branch: `main`
- Current canonical repair-tooling merge: `626c8fc656bc28d23000c3e9e5ed6d220d9804a7` (`PR #26`)
- PR #26 exact certified head: `d664aa1e7639439a5590feeb94c417714e84258e`
- PR #26 release-certification run: `#763` — **PASS**
- Historical control-plane creation baseline: `f854c50c0f7687fc87fdfab01b49562392af4ef4`
- Documented source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Control-plane revision: `7`
- Canonical stage count: `75` (`0` through `74`)

Always inspect current HEAD. Historical SHAs are evidence references, not self-referential requirements.

## Core governance rule

No implementable system/module/feature/package/AI/ops/security capability begins from chat/idea alone. It must be registered, dependency-mapped and represented in the active plan first.

Substantial new/redesigned work uses proportional Research/VOC/baseline/CTQ + DMADV. Existing defects/incidents/regressions use DMAIC and durable Control evidence. High/critical/complex material failures use FMEA where applicable in addition to threat modeling.

Material data changes use formal DataFlow/classification/ownership/lineage/retention/delete/AI/package exposure policy. Critical stateful/provider flows include reliability/idempotency/recovery policy. Material relationship changes require System Graph/Flow planning.

## Phase 7 — AI-Native Development Orchestration

The AI-native development workflow itself is treated as a privileged supply-chain/agentic system. No extra top-level stage was added; `AI-GOV-AUTOMATION-100` is expanded with the governed development-agent layer.

Key planned controls remain:

- instruction trust boundary;
- exact base SHA + active-plan/policy identity;
- stale-context invalidation;
- scope/path leases and parallel-writer safety;
- material scope-delta re-planning;
- least-privilege repository/package/network/secret/governance/target capabilities;
- governance self-modification protection;
- test-oracle integrity;
- evidence authority/attestation;
- exact-head independent review and stale-review invalidation;
- multi-agent DAG + child capability subsets;
- bounded retry/attempt/cost circuit breakers;
- dependency/supply-chain intake;
- scoped expiring waivers;
- adversarial development-agent fixtures;
- reviewed/tested/promoted artifact identity/provenance;
- concise action/evidence audit without private chain-of-thought logging.

Repository-level finding remains unresolved operationally: GitHub reports `main` as protected but required-status-check enforcement is off and required checks are empty. Future `AI-GOV-AUTOMATION-100` must verify real repository rules rather than trust documentation.

## Phase 6 — System Graph & Flow Intelligence

Canonical stages remain:

- `SYSTEM-GRAPH-100` — Builder Beta foundation;
- `FLOW-INTELLIGENCE-200` — Pro advanced Flow Center.

Invariant:

**Graph + evidence is source of truth. Diagram is a projection of that truth.**

Evidence classes remain distinct: `declared`, `static`, `observed`, `tested`, `production-observed`, `ai-inferred`.

Flow Intelligence consumes Data Governance, Security/Sentinel, Performance, Reliability, Payment, Release and Observability evidence rather than replacing those authorities. Sensitive topology remains default-deny/redacted/audited and production tracing remains bounded.

## Phase 5 — Quality Engineering & Payment Security

Accepted layers remain `RESEARCH-DISCOVERY-100`, `QUALITY-GOVERNANCE-100`, `DATA-GOVERNANCE-200`, `RELIABILITY-ENGINEERING-200`, `PRODUCT-OUTCOMES-100`, `DELIVERY-EXCELLENCE-100`, `EFFICIENCY-FINOPS-100` and `PAYMENT-SECURITY-200`.

Payment standard profile continues to forbid raw PAN/CVV/track/PIN in Nexora/generic package runtime/storage/logs/AI, prefers provider-hosted/tokenized flows, and requires purpose-specific capabilities, Core-authoritative financial state, Secret/Network Brokers, hardened webhooks/payment surface, idempotency/reconciliation, provider sandbox and independent payment-security evidence.

## Active stage

`RUNTIME-CLOSURE-001 — Installation + Runtime Closure`

Registered active unit: `SYS-RUNTIME-IDENTITY`

Status: **BLOCKED pending real-target execution**.

No source CI, repair receipt or planning document moves this cursor.

## Current target blocker

Real target:

`D:\laragon\www\nexora`

Installed release:

`1.0.0-rc.93`

Known stale post-install identity planes:

- environment
- activation
- service
- process

Do **not** overwrite rc.93 with rc.94 merely to repair these fingerprints. Repair and upgrade are separate operations.

## rc.93 repair-tooling gap — SOURCE CLOSED

A fresh audit found that the handoff referred to a prepared external repair pack, but no deterministic executable artifact existed in source. This was a genuine zero-skip continuity gap.

PR #26 closed the source-side gap and is merged at `main@626c8fc656bc28d23000c3e9e5ed6d220d9804a7`.

Canonical artifacts:

- `scripts/rc93-post-install-identity-repair.php`
- `scripts/rc93-post-install-identity-repair.ps1`
- `tests/Unit/Certification/Rc93PostInstallIdentityRepairPackTest.php`
- `docs/runtime/RC93_POST_INSTALL_IDENTITY_REPAIR.md`

Exact PR head `d664aa1e7639439a5590feeb94c417714e84258e` passed release-certification run `#763`, including certification preflight, Source Guard, unified source certification and frontend dependency compatibility.

### Repair safety contract

The canonical tool:

- is pinned to both running and installed version `1.0.0-rc.93`;
- boots the target's own autoloader/container and does not copy rc.94 application source into it;
- verifies the sealed installation lock;
- verifies source activation and deep deployment/source identity;
- permits only `environment`, `activation`, `service`, `process` mismatches;
- refuses any immutable/unrelated mismatch before mutation;
- requires healthy service/process identity;
- defaults to dry-run;
- requires `--apply --confirm=REPAIR-RC93` to mutate;
- creates and verifies a protected sealed-lock backup;
- updates only bounded runtime identity metadata through target contracts;
- requires convergence to `compatible=true`, `mismatches=[]`, `mode=installed-data-plane`;
- restores the original sealed lock and verifies its SHA-256 if convergence fails;
- writes a protected receipt after successful convergence.

A repair receipt remains repair evidence only, never final target certification.

## Exact next actions — REAL TARGET REQUIRED

### 1. Dry-run from canonical source checkout

```powershell
php scripts/rc93-post-install-identity-repair.php --target="D:\laragon\www\nexora"
```

PowerShell wrapper equivalent:

```powershell
.\scripts\rc93-post-install-identity-repair.ps1 -Target "D:\laragon\www\nexora"
```

Require dry-run `status=pass`, `mode=dry-run`, immutable/source/deployment/lock/API prechecks PASS and no mismatch outside the four known stale planes.

### 2. Apply only after dry-run PASS

```powershell
php scripts/rc93-post-install-identity-repair.php --target="D:\laragon\www\nexora" --apply --confirm=REPAIR-RC93
```

PowerShell wrapper equivalent:

```powershell
.\scripts\rc93-post-install-identity-repair.ps1 -Target "D:\laragon\www\nexora" -Apply -Confirm REPAIR-RC93
```

### 3. Independent compatibility evidence from installed target

```powershell
cd D:\laragon\www\nexora
php artisan nexora:runtime:compatibility-status --deep
```

Require:

```text
status=pass
mismatches=[]
compatible=true
mode=installed-data-plane
```

### 4. Post-install readiness

```powershell
php artisan nexora:runtime:post-install-status --assert-ready
```

Must PASS.

### 5. Browser handoff

Open:

```text
http://nexora/login
```

Only when compatibility, readiness and browser handoff pass may `RUNTIME-CLOSURE-001` become `TARGET_VERIFIED` and the cursor advance to `CORE-QA-001`.

## Immediate sequence after runtime closure

`CORE-QA-001 → AI-GOV-AUTOMATION-100 → RESEARCH-DISCOVERY-100 → QUALITY-GOVERNANCE-100 → ADMIN-UX-CLOSURE-001 → SECURITY-BASELINE-200 → ARCH-BOUNDARY-100 → existing website-platform closure → mature builder/data/performance kernel → SYSTEM-GRAPH-100`.

Later Pro includes `PERFORMANCE-INTELLIGENCE-200 → RELIABILITY-ENGINEERING-200 → FLOW-INTELLIGENCE-200` when dependencies are satisfied. Platform payment sequence remains `COMMERCE-CLOSURE-001 → PAYMENT-SECURITY-200 → COMMERCE-200`.

## Completion warning

Historical `DONE` is not target proof. `CI green` is not automatically independent target evidence. Missing research/measurement/graph/provider/outcome evidence is never inferred as PASS. AI-authored PASS files and repair receipts are not machine/runtime target evidence. A Flow diagram is not runtime evidence. Payment/security/compliance claims still require their specific real evidence.
