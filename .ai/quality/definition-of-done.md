# Nexora AI Definition of Done

No stage/unit advances because code exists or tests were added. Completion requires every applicable gate and recorded evidence. `NOT_APPLICABLE` is explicit, never inferred from omission.

## Universal preconditions before substantial implementation

- canonical parent stage + registered unit ID(s);
- dependencies mapped;
- active plan complete at proportional depth;
- new/redesigned work has ResearchBrief/problem/VOC/baseline/CTQs where applicable;
- DMADV or DMAIC method selected where substantial;
- architecture/DataFlow decisions recorded;
- high/critical security work has threat model;
- high/critical/complex material failure modes have FMEA where applicable;
- runtime-affecting work has performance budget/profile or explicit N/A;
- critical stateful/provider work has reliability/idempotency/recovery plan;
- payment work has payment-provider profile and payment security plan before code.

## Universal completion gates

### 1. Research / scope / outcome integrity

- problem is distinguished from a prescribed solution;
- VOC/market/standards/baseline claims cite real evidence or are labeled assumption/UNKNOWN;
- CTQs and intended user/product outcome are objective;
- existing Nexora capability was inspected before adding new architecture;
- optional AI-discovered work was not silently promoted;
- out-of-scope remains out-of-scope until plan/registry/roadmap changes first.

### 2. Architecture

- public contracts/capabilities used where required;
- no first/third-party private Core shortcut;
- ADR/review exists for public-contract, data-authority, tenancy, security, execution, payment or protocol changes;
- persistence/domain/runtime/package boundaries remain fail-closed;
- migrations remain additive/portable/fresh-install safe where applicable;
- backward compatibility/deprecation path is explicit.

### 3. Data architecture / governance

When material data is affected:

- authoritative source/store identified;
- data classification recorded;
- tenant/site/user ownership and access path explicit;
- transformations and derived stores mapped;
- cache/search/analytics/vector/export are not silently authoritative;
- package/API/webhook/AI exposure is least-purpose/minimized;
- retention/export/delete propagation defined;
- migration/backfill/backup/restore/recovery behavior verified;
- logs/telemetry/error reports follow classification/redaction policy.

### 4. Security / privacy

- risk class recorded;
- required threat model complete;
- auth/tenancy negative tests pass;
- secrets never leak to logs/source/AI;
- network/filesystem/package/AI powers are least privilege;
- privacy/consent/retention/export/delete addressed;
- controls are not weakened to make tests/scores pass;
- residual high/critical risk has explicit acceptance authority rather than implicit silence.

### 5. FMEA / failure control

For applicable high/critical/complex flows:

- important failure modes/effects/causes identified;
- critical severity receives mitigation even if believed rare;
- prevention + detection + recovery/reconciliation defined;
- residual risk recorded;
- regression/control evidence added after fixes/incidents.

### 6. Functional implementation / UX

- happy path + permissions + validation + failure states;
- empty/loading/error/destructive states;
- accessibility/responsive/i18n as applicable;
- upgrade/backward compatibility;
- install/enable/disable/update/rollback/uninstall for packages;
- release/preview/staging implications for publishable work.

### 7. Public surfaces

Applicable Admin/public/API/webhook/SDK/theme/Studio/package/AI/import-export surfaces are intentionally handled and versioned.

### 8. Code quality / performance

- static/type/lint/build checks pass;
- complexity/duplication/dead-code/bundle regressions handled where in scope;
- frontend/Admin/backend/query/cache/network/memory/package attribution measured where applicable;
- budget/profile baseline comparison recorded;
- performance security/privacy overhead is considered;
- quality/performance/Sentinel security verdicts remain separate.

### 9. Reliability

For critical recurring/stateful/provider flows:

- timeout/retry/backoff/idempotency/concurrency policy verified;
- failure isolation/degradation/fallback defined;
- SLI/SLO/error budget defined where meaningful;
- provider/partial/ambiguous failure scenarios tested;
- rollback/recovery/reconciliation exists;
- non-idempotent financial/destructive mutation is never blindly retried after ambiguous outcome.

### 10. Verification & Validation

As applicable:

- unit;
- integration/contract;
- architecture;
- data/migration;
- authorization/tenancy;
- security/adversarial;
- browser/E2E/accessibility;
- package compatibility;
- performance/code quality;
- reliability/fault/recovery;
- AI evals;
- real target/provider sandbox.

Source checks produce `SOURCE_DONE`, not `TARGET_VERIFIED`. Real browser/runtime/DB/provider behavior is executed before target claims.

### 11. Outcome / Control

For outcome-dependent units:

- observation metric/window/trigger recorded;
- CTQ/outcome evidence linked when available;
- future evidence is never fabricated to close a current task;
- repeated/critical defects use DMAIC Control: regression test/budget/SLO/alert/static rule/process guard;
- learning feeds Research/quality docs rather than remaining only in incident/chat history.

### 12. Evidence / state

Record unit IDs, changed files/components, commands/tests, research/architecture/data/security/FMEA review, source/target/provider outcome, residual risk, blocker and exact next action. Update affected registries, state, handoff, active plan and changed governance docs; preserve history.

## Package-specific gates

For Extension/App/Integration/Studio-Pack/Theme verify complete supported lifecycle:

`stage/upload → quarantine → Sentinel/Supply Chain → manifest/compatibility → capabilities/grants → dependencies → install → enable → runtime → disable → version/update/rollback → uninstall`

Also verify data purpose, external destinations, runtime/migration mode, network/filesystem/secrets, package attribution, performance/reliability/code-quality budgets and no private Core shortcut.

## Payment-provider gates

Any provider integration/payment-entry surface must additionally prove:

1. standard profile does not expose raw PAN/CVV/track/PIN to Nexora/generic package runtime/storage/logs/AI;
2. allowed flow is provider-hosted redirect/iframe/fields or approved tokenized SDK; generic raw-card collection remains forbidden by default;
3. package uses `security_profile: payment-provider` and only purpose-specific capabilities;
4. Core validates order/tenant/amount/currency/state; browser values cannot establish financial truth;
5. Secret Broker and Network Broker boundaries are enforced;
6. webhook signature/freshness/tenant binding/replay/deduplication/schema/out-of-order behavior is tested;
7. idempotency + concurrency prevents duplicate capture/refund/transition;
8. ambiguous provider timeout reconciles before retry;
9. browser success/return URL alone cannot mark paid;
10. 3DS/SCA/action-required/asynchronous states are modeled where supported;
11. protected payment surface has approved script inventory/slots, strict CSP/origins, tamper detection and session-replay/analytics exclusion;
12. payment logs/traces/errors/backups/AI are scanned/tested for sensitive-data leakage;
13. sandbox authorization/capture/refund/webhook/reconciliation tests pass;
14. test/live credentials are separated and live enablement is explicit;
15. threat model + FMEA + independent payment security review exist;
16. package kill switch, credential rotation and in-flight reconciliation are tested/planned;
17. generic Sentinel/Marketplace status is not presented as automatic PCI/payment compliance.

## AI-specific gates

AI tools/agents use registered typed tools, normal identity/capabilities, no unrestricted shell/DB/filesystem/secrets/network, prompt/tool-content trust boundaries, approvals, audit, budgets, rollback metadata and independent eval review for critical execution paths.

## Managed/operations gates

Managed/cloud/ops claims require tenant isolation, auditable domain/SSL/secrets/deploy/backup operations, failure/drain/rollback/recovery, secret-safe diagnostics and real HA evidence for HA claims. Cost/resource attribution cannot leak tenant-sensitive data.

## Cursor advancement rule

The cursor advances only when the current stage reaches its required evidence status. Later foundations cannot waive earlier quality/security/data/payment gates. A newly discovered substantial capability is registered/planned first rather than silently pulling implementation outside the current cursor.
