# Nexora Research / Discovery Brief Template

Use before substantial new capabilities and whenever the requested implementation may not be the best solution to the underlying problem.

## Identity

- Research ID:
- Related development unit/stage:
- Request/source:
- Date:
- Confidence: low / medium / high

## Problem

- Who has the problem?
- What is the observable problem?
- Why does it matter?
- What happens if nothing is changed?
- Is the request describing a problem or prescribing a solution?

## Voice of Customer / stakeholder evidence

- User/operator/developer need:
- Evidence source:
- Frequency/impact:
- Direct quotes or measurements where available:
- Unknowns:

Do not invent VOC. When direct evidence is absent, label the statement as an assumption/hypothesis.

## Existing Nexora capability

- Does current Core already solve this?
- Can an existing extension/theme/app surface solve it?
- Is current behavior defective or merely missing?
- What existing contracts/systems should be preserved?

## Market / competitor / standards research

- Relevant current competitor behavior:
- Proven industry patterns:
- Standards/regulatory/security implications:
- What should Nexora deliberately not copy?
- Expected future pressure / portability concern:

Research freshness/date and source confidence must be recorded for claims that can change.

## Alternatives

| Option | Benefits | Risks/cost | Architecture fit | Decision |
|---|---|---|---|---|
| reuse existing |  |  |  |  |
| Core capability |  |  |  |  |
| first-party package |  |  |  |  |
| public extension surface only |  |  |  |  |
| do nothing/defer |  |  |  |  |

## Baseline

What is measurable before implementation?

- current task success/failure:
- current latency/performance:
- current defects/support burden:
- current security/reliability exposure:
- current cost/resource usage:
- current adoption/usage:

If no baseline exists, define how it will be established or explicitly record `UNKNOWN`.

## CTQs / success outcomes

Translate the need into objective acceptance properties.

- CTQ-1:
- CTQ-2:
- CTQ-3:
- Product/business outcome:
- Guardrail metrics:
- Observation window where applicable:

## Risks and assumptions

- Assumptions requiring validation:
- Security/privacy risk:
- Data impact:
- Reliability risk:
- Compatibility/migration risk:
- Cost/complexity risk:

## Recommendation

- Recommended disposition: reject / defer / reuse / fix / new unit
- Core vs package decision:
- Stable unit/stage mapping:
- Why this is the smallest durable solution:
- Evidence still required before implementation:
