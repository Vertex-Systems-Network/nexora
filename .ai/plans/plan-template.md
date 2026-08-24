# Nexora Active Development Plan Template

Every substantial implementation stage/unit must instantiate this template before code changes begin.

## Identity

- Parent stage ID:
- Development unit IDs:
- Release train:
- Status:
- Source baseline SHA:
- Target environment(s):

## Objective

What user/platform outcome will be delivered?

## Scope

### In scope

- 

### Out of scope

- 

## Existing implementation to preserve/reuse

List current code/contracts/migrations/tests/docs that already exist. Do not rebuild working foundations without evidence.

## Dependencies and preconditions

- Required stages:
- Required units/contracts:
- External/provider dependencies:
- Current blockers:

## Architecture

- Public contracts to add/change:
- Module/domain boundaries:
- Persistence/repository boundaries:
- ADR required? Why?
- Backward compatibility:

## Data and migrations

- Tables/storage/indexes:
- Migration required:
- Fresh install impact:
- Upgrade impact:
- Data migration/backfill:
- Rollback/recovery:

## Authorization and tenancy

- Human permissions:
- Runtime capabilities:
- Tenant scoping:
- Sensitive actions requiring re-auth/approval:

## Security and threat model

- Risk class:
- Threat model required:
- Major attack surfaces:
- Required security tests:
- Security reviewer/evidence:

## Privacy/compliance

- Personal/sensitive data:
- Consent impact:
- Retention/deletion/export impact:

## UI / UX / accessibility

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
- Approval policy:
- Prompt-injection/data-leakage considerations:
- Evals required:

## Performance / code quality / cache / delivery

Every unit must fill this section or explicitly write `NOT_APPLICABLE` with reason.

### Performance identity

- Performance-affecting paths/pages/APIs/user flows:
- Budget ID(s) or budget creation required:
- Synthetic test profile(s):
- RUM/field impact:
- Baseline/comparison target:

### Frontend/browser impact

- LCP/INP/CLS implications:
- JS/CSS/image/font/request-size implications:
- Main-thread/long-task impact:
- DOM/component impact:
- Third-party/network impact:
- Cache/CDN/image/render implications:

### Backend/Admin impact

- Route/controller/service execution impact:
- Query count/time/N+1 risk:
- Cache hit/miss/invalidation impact:
- External HTTP/storage impact:
- CPU/wall/peak-memory impact:
- Admin/Studio API/render impact:

### Theme/Extension/App attribution

- Package/runtime spans to attribute:
- Hook/event/filter/slot cost:
- Owned asset/chunk cost:
- Package version/baseline comparison:
- Marketplace/package profile impact:

### Code quality

- Static/type/lint impact:
- Complexity/duplication/dead-code risk:
- Bundle/dependency weight impact:
- Runtime-to-source correlation required:

### Performance regression policy

- Warn threshold:
- Fail threshold:
- Repeated-run/noise policy:
- Release-blocking dimensions:
- Authorized override + audit policy:

Use `.ai/performance/performance-budget-template.md` when explicit budgets are required.

## Observability and audit

- Logs/events:
- Metrics/traces:
- Audit records:
- Failure diagnostics:

## Implementation chunks

### A
- [ ]

### B
- [ ]

### C
- [ ]

## Test matrix

- [ ] unit
- [ ] integration
- [ ] architecture
- [ ] authorization/tenancy
- [ ] security
- [ ] browser/E2E
- [ ] migration/fresh install/upgrade
- [ ] package compatibility
- [ ] AI evals if applicable
- [ ] frontend performance if applicable
- [ ] backend/Admin performance if applicable
- [ ] Theme/Extension/package attribution if applicable
- [ ] code-quality regression if applicable
- [ ] budget/baseline comparison if applicable

## Real-target verification

Exact commands/flows/evidence required before target claims.

Performance claims must record runner/browser/device/network/CPU/cache/profile identity and keep lab/field/backend evidence distinguishable.

## Definition of Done

List objective acceptance criteria. No vague 'works' or 'complete' statements.

## Handoff/update requirements

- [ ] development-unit registry updated
- [ ] `.ai/state.json` updated
- [ ] `.ai/handoff/current.md` updated
- [ ] roadmap/status updated if scope changed
- [ ] ADR/security docs updated if required
- [ ] performance budget/profile docs updated if required
- [ ] user-visible docs/migration notes updated
