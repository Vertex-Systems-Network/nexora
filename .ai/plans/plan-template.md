# Nexora Active Development Plan Template

Every substantial implementation stage/unit must instantiate this template before code changes begin. Use proportional depth: trivial non-behavioral fixes stay lightweight; high/critical work fills all applicable evidence.

## Identity

- Parent stage ID:
- Development unit IDs:
- Release train:
- Status:
- Source baseline SHA:
- Target environment(s):
- Method: DMADV / DMAIC / lightweight maintenance

## Research / problem / outcome

- ResearchBrief ID/path or `NOT_APPLICABLE` reason:
- Request/source signal:
- User/stakeholder/problem:
- Problem vs requested solution distinction:
- VOC/evidence + confidence:
- Existing Nexora capability to preserve/reuse:
- Market/competitor/standards evidence where relevant:
- Alternatives considered:
- Baseline or explicit `UNKNOWN`:
- CTQs:
- Intended product/user outcome:
- Guardrail metrics:

## Scope

### In scope

-

### Out of scope

-

## Existing implementation to preserve/reuse

List current code/contracts/migrations/tests/docs. Do not rebuild working foundations without evidence.

## Dependencies and preconditions

- Required stages:
- Required units/contracts:
- External/provider dependencies:
- Current blockers:

## Architecture

- Public contracts to add/change:
- Module/domain boundaries:
- Persistence/repository boundaries:
- Core vs package decision:
- ADR required? Why?
- Backward compatibility/deprecation:

## Data architecture / flow / migrations

- DataFlow artifact/path:
- Inputs/sources/actors:
- Authoritative source/store:
- Data classifications:
- Tenant/site/user ownership:
- Validation/transformation:
- Derived stores (cache/search/analytics/vector/export):
- API/webhook/package/AI exposure:
- Retention/export/delete propagation:
- Tables/storage/indexes:
- Migration required:
- Fresh install/upgrade/backfill:
- Backup/restore impact:
- Rollback/recovery:

## Authorization and tenancy

- Human permissions:
- Runtime capabilities:
- Tenant scoping:
- Sensitive actions requiring re-auth/approval:

## Security and threat model

- Risk class:
- Threat model required/path:
- Major attack surfaces:
- Required security tests:
- Security reviewer/evidence:

## FMEA / failure analysis

Required for high/critical or complex material failure modes unless explicitly not applicable.

- FMEA path:
- Highest-severity failure modes:
- Prevention controls:
- Detection controls:
- Residual risk/acceptance:

## Privacy/compliance

- Personal/sensitive/financial data:
- Consent impact:
- Retention/deletion/export impact:
- External processors/regions:

## UI / UX / accessibility

- User/task flow:
- Admin UI:
- Public UI:
- Responsive behavior:
- Keyboard/screen-reader requirements:
- Empty/loading/error/destructive states:

## Public/API/SDK surfaces

- REST/GraphQL:
- Webhooks/events:
- SDK/contracts:
- Versioning/deprecation:

## Theme / Studio / extension surfaces

- Theme locations/templates/slots:
- Studio components/bindings:
- Extension hooks/slots/registration APIs:
- Package compatibility impact:

## AI surfaces

- AI may read:
- AI may draft:
- AI may execute:
- Typed tool IDs:
- Context/data classification exclusions:
- Approval policy:
- Prompt-injection/data-leakage considerations:
- Evals required:

## Payment-provider profile

Fill when work authorizes/captures/refunds payments, handles payment-method refs/provider webhooks, or affects payment-entry UI; otherwise `NOT_APPLICABLE`.

- `security_profile: payment-provider` required?
- Flow type: redirect / hosted iframe/fields / approved tokenized SDK:
- Account-data access: `none` / `token-only`:
- Provider API/frontend origins:
- Secret Broker slots:
- Payment capabilities:
- Core amount/currency/order/state authority:
- State machine / 3DS / SCA / async states:
- Idempotency/concurrency rules:
- Webhook signature/freshness/replay/tenant/reconciliation:
- Payment Surface Guard/CSP/script inventory/tamper detection:
- Session replay/analytics exclusion:
- Sandbox activation tests:
- Test/live separation:
- Kill switch / secret rotation / in-flight reconciliation:
- Payment security independent review:

Direct/raw card-data handling is forbidden by default under the standard profile; any proposed exception is a separate architecture/compliance program, not a checkbox override.

## Performance / code quality / cache / delivery

Fill or explicit `NOT_APPLICABLE`.

### Performance identity

- Affected paths/pages/APIs/user flows:
- Budget ID(s):
- Synthetic profiles:
- RUM/field impact:
- Baseline/comparison:

### Frontend/browser

- LCP/INP/CLS:
- JS/CSS/image/font/request size:
- main-thread/long tasks:
- DOM/component pressure:
- third-party/network:
- cache/CDN/image/render:

### Backend/Admin

- route/controller/service:
- query count/time/N+1:
- cache/invalidation:
- external HTTP/storage:
- CPU/wall/peak memory:
- Admin/Studio API/render:

### Theme/Extension/App attribution

- package/runtime spans:
- hook/event/filter/slot cost:
- owned asset/chunk cost:
- package-version comparison:

### Code quality

- static/type/lint:
- complexity/duplication/dead code:
- dependency/bundle weight:
- runtime-to-source correlation:

### Regression policy

- warn/fail thresholds:
- noise/repeat policy:
- release-blocking dimensions:
- override/audit policy:

## Reliability / SLO / recovery

Fill for critical recurring/stateful/provider workflows or explicit N/A.

- SLI(s):
- SLO/window/error budget:
- timeout policy:
- retry/backoff policy:
- idempotency:
- concurrency/locking:
- circuit/failure isolation:
- graceful degradation/fallback:
- provider outage/ambiguous response behavior:
- reconciliation:
- fault tests:
- incident/recovery/control evidence:

## Cost / resource efficiency

- Expected DB/storage/bandwidth/CPU impact:
- AI/provider/external-service cost:
- Per-tenant/request/resource attribution needed:
- Budget/anomaly threshold:

## Observability and audit

- Logs/events:
- Metrics/traces:
- Audit records:
- Failure diagnostics:
- Redaction/classification:

## Implementation chunks

### A
- [ ]

### B
- [ ]

### C
- [ ]

## Test / verification matrix

- [ ] unit
- [ ] integration
- [ ] contract/architecture
- [ ] authorization/tenancy
- [ ] data-flow/lineage/migration
- [ ] security
- [ ] FMEA failure scenarios
- [ ] browser/E2E/accessibility
- [ ] fresh install/upgrade/rollback
- [ ] package compatibility
- [ ] payment adversarial/provider sandbox if applicable
- [ ] AI evals if applicable
- [ ] frontend performance
- [ ] backend/Admin performance
- [ ] package attribution/code quality
- [ ] reliability/fault/reconciliation
- [ ] cost/resource regression if applicable

## Real-target verification

Exact commands/flows/evidence required before target claims.

Performance claims record runner/browser/device/network/CPU/cache/profile identity. Payment claims record provider sandbox/live mode, package/provider version, webhook/reconciliation and no-sensitive-data evidence. Lab/field/backend/security/payment evidence remain distinguishable.

## Post-release outcome / Control plan

- Observation window/event:
- CTQ/outcome evidence:
- Alerts/monitors:
- Regression tests/static rules/budgets/SLO controls:
- Support/customer feedback signal:
- DMAIC follow-up trigger:

## Definition of Done

List objective acceptance criteria. No vague `works`/`complete` statements.

## Handoff/update requirements

- [ ] affected development-unit registry updated
- [ ] ResearchBrief/CTQ/FMEA/DataFlow updated where required
- [ ] `.ai/state.json` updated
- [ ] `.ai/handoff/current.md` updated
- [ ] roadmap/status updated if scope changed
- [ ] ADR/security/payment/reliability docs updated if required
- [ ] performance budget/profile updated if required
- [ ] user-visible docs/migration/deprecation notes updated
