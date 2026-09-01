# Nexora Lean Six Sigma / Quality Improvement Model

## Intent

Nexora uses selected Lean Six Sigma methods as engineering tools, not as manufacturing ceremony or as a promise of a universal 3.4-defects-per-million software metric.

The goal is to make decisions measurable, root-cause analysis evidence-based, and improvements durable.

## Method selection

### DMADV / Design for Six Sigma

Use for a new system, new module, new extension capability, major redesign or materially new user workflow.

- **Define** — VOC, problem, business/user outcome, scope, constraints.
- **Measure** — current baseline, CTQs, target metrics, risks and assumptions.
- **Analyze** — alternatives, trade-offs, architecture/data/security/design analysis, FMEA.
- **Design** — implementable contracts/flows/controls/tests/budgets/recovery.
- **Verify** — source + target evidence against CTQs, security, reliability and outcome.

### DMAIC

Use for an existing defect, incident, performance regression, reliability problem, security recurrence or process bottleneck.

- **Define** — exact defect and affected customer/system outcome.
- **Measure** — establish reproducible baseline.
- **Analyze** — identify root cause and contributing factors.
- **Improve** — implement the smallest robust correction.
- **Control** — add regression prevention, telemetry, budgets/SLOs/alerts or process guard.

## Approved tools

### Voice of Customer (VOC)

Capture what the user/operator/developer actually needs, not only the requested implementation.

### Critical to Quality (CTQ)

Translate needs into measurable or objectively verifiable properties.

Example:

```text
Need: checkout must feel safe and reliable
CTQs:
- card data never enters Nexora application runtime under default payment profile
- duplicate webhook cannot duplicate capture/order transition
- payment state is reconciled after ambiguous provider timeout
- unauthorized scripts cannot execute on protected payment surface
```

### SIPOC

Use for complex cross-system flows:

`Supplier → Input → Process → Output → Customer`

Useful for payments, publishing, imports, integrations, AI tools, workflows and data pipelines.

### FMEA

Use for high/critical systems to identify non-security and security failure modes before implementation.

Record:

- failure mode;
- effect;
- cause;
- severity;
- occurrence likelihood;
- detectability;
- existing control;
- required mitigation;
- owner/evidence.

Numeric risk-priority scores may be used as prioritization aids but never override a critical-severity control requirement.

### Root-cause analysis

Approved techniques:

- 5 Whys;
- cause-and-effect / fishbone;
- fault tree where appropriate;
- trace/event/query evidence;
- controlled reproduction;
- dependency/version bisection.

### Pareto analysis

Use defect/incident/support/performance evidence to focus improvement effort on the few causes creating the largest impact. Do not use counts alone when severity differs materially.

### Control charts / baselines

Use only for stable repeatable metrics such as latency, error rate, job duration, build time, failure rate or support volume where statistical monitoring is meaningful.

## Software metrics that matter more than generic DPMO

Prefer evidence such as:

- escaped defect rate;
- regression recurrence;
- change failure rate;
- deployment rework rate;
- rollback/hotfix rate;
- SLO/error-budget consumption;
- performance-budget failures;
- security finding recurrence;
- cross-tenant/auth failures;
- flaky-test rate;
- incident recovery time;
- AI eval failure rate;
- customer task-success/error/abandonment rate.

## Control plan

Every resolved critical/repeated defect asks:

1. What detects recurrence?
2. What prevents recurrence?
3. What metric/budget/SLO changes?
4. What automated test exists?
5. Who/what is alerted?
6. What rollback/recovery action exists?
7. Is the lesson encoded into templates, static rules, architecture tests or Sentinel?

## AI behavior

AI may use Lean Six Sigma tools to structure analysis, but it must not invent VOC, baseline measurements, statistical significance or root-cause evidence. Hypotheses remain hypotheses until verified.
