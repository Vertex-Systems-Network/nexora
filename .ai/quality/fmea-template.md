# Nexora FMEA Template

Use for high/critical systems and for complex flows where failure can cause security, financial, data-integrity, reliability, legal/compliance or major customer impact.

## Context

- Stage ID:
- Development unit IDs:
- System/flow:
- Owner/reviewer:
- Date/version:
- Related ResearchBrief:
- Related threat model:
- Related DataFlow:
- Related CTQs/SLOs:

## Failure-mode table

| Failure mode | Effect | Cause | Severity 1-10 | Occurrence 1-10 | Detectability 1-10 | Existing controls | Required action | Evidence/owner |
|---|---|---|---:|---:|---:|---|---|---|
|  |  |  |  |  |  |  |  |  |

## Rules

- A high numeric score is a prioritization signal, not a substitute for judgment.
- Critical-severity failures must receive explicit treatment even when occurrence is believed low.
- Security threats remain covered by the threat model; FMEA complements rather than replaces it.
- Financial/data-loss/cross-tenant/destructive failure modes require rollback/reconciliation/recovery analysis.
- After mitigation, record residual risk and verification evidence.

## Control evidence

For each accepted high/critical failure mode record:

- prevention control;
- detection control;
- automated regression test;
- monitoring/alert when applicable;
- recovery/reconciliation path;
- residual risk/acceptance authority.
