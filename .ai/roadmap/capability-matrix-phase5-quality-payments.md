# Nexora Capability Matrix — Phase 5 Quality Engineering & Payment Security

This addendum extends the canonical capability registry for lifecycle quality, data/reliability/delivery intelligence and critical payment-provider security.

## Research / discovery / product value

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| ResearchBrief before substantial new systems | GAP / chat-dependent | structured evidence-backed artifact | `RESEARCH-DISCOVERY-100` |
| Voice of Customer / stakeholder evidence | implicit | source/confidence-aware VOC | `RESEARCH-DISCOVERY-100` |
| Problem vs requested-solution distinction | implicit | mandatory discovery decision | `RESEARCH-DISCOVERY-100` |
| Competitor/standards/current-practice research | manual | freshness/source-aware research | `RESEARCH-DISCOVERY-100` |
| Baseline before change | partial/performance-specific | explicit baseline or `UNKNOWN` | `RESEARCH-DISCOVERY-100` |
| CTQs / measurable quality requirements | GAP | linked to unit acceptance/outcome | `QUALITY-GOVERNANCE-100` |

## Quality Engineering / Lean Six Sigma

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Full lifecycle Quality OS | partial across existing docs | closed-loop lifecycle from research to control/evolution | `QUALITY-GOVERNANCE-100` |
| DMADV for new/redesigned capabilities | NEW_REQUIRED | proportional DFSS evidence | `QUALITY-GOVERNANCE-100` |
| DMAIC for defects/incidents/regressions | NEW_REQUIRED | root-cause + control evidence | `QUALITY-GOVERNANCE-100` |
| FMEA for high/critical failure modes | NEW_REQUIRED | complements threat model | `QUALITY-GOVERNANCE-100` |
| VOC / CTQ / SIPOC / Pareto / root-cause toolbox | NEW_REQUIRED | evidence-based use, no fabricated statistics | `QUALITY-GOVERNANCE-100` |
| Traceability from research -> code -> evidence -> outcome | partial | durable machine-verifiable links | `QUALITY-GOVERNANCE-100` + AI governance |
| Regression control plan | partial tests/DoD | required for critical/repeated defect classes | `QUALITY-GOVERNANCE-100` |

## Data architecture / governance

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Formal DataFlow artifacts | subsystem-specific only | common flow contract | `DATA-GOVERNANCE-200` |
| Data classification | partial security/privacy | public/internal/personal/sensitive/secret/financial/payment/audit classes | `DATA-GOVERNANCE-200` |
| Authoritative-source declaration | implicit | explicit per material resource | `DATA-GOVERNANCE-200` |
| Derived-store lineage | GAP | cache/search/analytics/vector/API/export lineage | `DATA-GOVERNANCE-200` |
| Data owner/tenant/site scope | partial | explicit access/ownership metadata | `DATA-GOVERNANCE-200` |
| Retention/export/delete propagation | partial | authoritative + derived-store lifecycle | `DATA-GOVERNANCE-200` |
| AI data exposure classification | planned AI policy | explicit lineage/context exclusion | `DATA-GOVERNANCE-200` + `AI-KERNEL-100` |
| Package data-purpose declaration | partial capabilities | fields/purpose/copy/external-transfer/uninstall policy | `DATA-GOVERNANCE-200` + `EXT-SDK-200` |

## Reliability engineering

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| SLI/SLO policy | GAP | meaningful service/workflow objectives | `RELIABILITY-ENGINEERING-200` |
| Error-budget policy | GAP | burn-rate/action policy where meaningful | `RELIABILITY-ENGINEERING-200` |
| Timeout/retry/idempotency design | subsystem-specific | common reliability contracts | `RELIABILITY-ENGINEERING-200` |
| Circuit breaker/failure isolation | partial/unknown | provider/package failure containment | `RELIABILITY-ENGINEERING-200` |
| Graceful degradation | partial | deterministic fallback policy | `RELIABILITY-ENGINEERING-200` |
| Fault/chaos testing | GAP | controlled failure scenarios | `RELIABILITY-ENGINEERING-200` |
| Incident -> postmortem -> control loop | partial security/ops | blameless evidence-based closed loop | `RELIABILITY-ENGINEERING-200` |
| Provider reconciliation after ambiguous mutation | payment-specific gap | mandatory financial reliability pattern | `PAYMENT-SECURITY-200` |

## Delivery excellence

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Change lead time | GAP | provider-neutral engineering-flow metric | `DELIVERY-EXCELLENCE-100` |
| Deployment frequency | GAP | trend/context metric | `DELIVERY-EXCELLENCE-100` |
| Failed deployment recovery time | partial ops evidence | measured consistently | `DELIVERY-EXCELLENCE-100` |
| Change failure rate | GAP | measured consistently | `DELIVERY-EXCELLENCE-100` |
| Deployment rework rate | GAP | measured consistently | `DELIVERY-EXCELLENCE-100` |
| AI-generated regression/rework evidence | GAP | distinguish throughput from quality | `DELIVERY-EXCELLENCE-100` |
| Anti-gaming metric definitions | GAP | transparent/versioned definitions | `DELIVERY-EXCELLENCE-100` |

## Product outcomes / feedback

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Adoption/activation | analytics foundation only | unit/outcome-linked measurement | `PRODUCT-OUTCOMES-100` |
| Task success/error/abandonment | GAP/fragmented | privacy-aware workflow outcomes | `PRODUCT-OUTCOMES-100` |
| Time to value | GAP | workflow-specific outcome | `PRODUCT-OUTCOMES-100` |
| Feedback/support signal clustering | GAP | evidence input to Research/DMAIC | `PRODUCT-OUTCOMES-100` |
| CTQ -> post-release outcome comparison | GAP | close development feedback loop | `PRODUCT-OUTCOMES-100` |

## Cost / resource efficiency

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Cost/resource attribution per service/tenant | GAP | provider-neutral metering view | `EFFICIENCY-FINOPS-100` |
| AI model/token cost | planned AI telemetry | budget/anomaly/control integration | `EFFICIENCY-FINOPS-100` |
| DB/storage/bandwidth/media/search cost | partial metrics | attributable trends/budgets | `EFFICIENCY-FINOPS-100` |
| External provider cost | GAP | operation/provider attribution | `EFFICIENCY-FINOPS-100` |
| Cost regression gates | GAP | warn/block policy by unit/release | `EFFICIENCY-FINOPS-100` |

## Existing payment foundation preserved

| Capability | Current state | Destination |
|---|---|---|
| Provider-neutral `PaymentProviderContract` / registry | FOUNDATION | preserve; providers remain adapters |
| No built-in gateway implementation in Commerce Core | SOURCE_GUARDED | preserve |
| No gateway private-key/API-key DB columns in Commerce migration | SOURCE_GUARDED | preserve |
| Integer minor-unit money | FOUNDATION | preserve |
| Core transaction/refund/subscription/billing-event records | FOUNDATION | expand safely |
| Idempotency/provider-event identity | FOUNDATION | strengthen under payment security |
| Cumulative refund locking/validation | FOUNDATION | preserve/regression test |

## Payment Security 2.0

| Capability | Current state | Required destination | Stage |
|---|---|---|---|
| Payment-provider special security profile | GAP | `security_profile: payment-provider` | `PAYMENT-SECURITY-200` |
| Raw PAN/CVV exclusion by default | architecture intention only | enforce across Core/packages/logs/AI/backups | `PAYMENT-SECURITY-200` |
| Hosted redirect/iframe/hosted fields preference | GAP | explicit flow classes/policy | `PAYMENT-SECURITY-200` |
| Raw/direct card collection | MUST NOT IMPLEMENT by default | separately designed/isolated/assessed profile only if ever approved | payment invariant |
| Purpose-specific payment capabilities | GAP | authorize/capture/refund/reconcile etc., no generic power | `PAYMENT-SECURITY-200` |
| Core-authoritative amount/currency/order/state | foundation partial | typed immutable/state-machine contract | `PAYMENT-SECURITY-200` |
| Payment state machine | partial records | validated transitions incl. async/3DS/SCA | `PAYMENT-SECURITY-200` |
| Payment Secret Broker | GAP | scoped opaque credential references/rotation/revocation | `PAYMENT-SECURITY-200` |
| Payment Network Broker | general security planned | provider-origin allowlist + SSRF/timeout/idempotency policy | `PAYMENT-SECURITY-200` |
| Hardened Webhook Gateway | idempotency foundation | signature/freshness/tenant/replay/schema/reconciliation | `PAYMENT-SECURITY-200` |
| Browser return cannot settle payment | GAP policy | server/provider verified state only | `PAYMENT-SECURITY-200` |
| Payment Surface Guard | GAP | approved scripts/slots, strict CSP, tamper monitoring | `PAYMENT-SECURITY-200` |
| Session replay/analytics exclusion on payment entry | GAP | enforced data/surface policy | `PAYMENT-SECURITY-200` |
| Payment-specific FMEA/threat/adversarial tests | GAP | mandatory before activation | `PAYMENT-SECURITY-200` |
| Sandbox activation harness | health-check foundation | authorize/capture/refund/webhook/reconcile suite | `PAYMENT-SECURITY-200` |
| Live-mode explicit promotion | GAP | separate test/live credentials + approval | `PAYMENT-SECURITY-200` |
| Payment provider emergency kill switch | future Sentinel partial | stop new intents, preserve/reconcile history, rotate secrets | `PAYMENT-SECURITY-200` + `SENTINEL-200` |
| Marketplace payment-specific trust status | GAP | separate from generic Sentinel/rating | `PAYMENT-SECURITY-200` + `MARKETPLACE-200` |
| Generic "PCI compliant" self-badge | MUST NOT IMPLEMENT | evidence/assessment metadata only | payment invariant |

## Phase 5 rule

New quality/payment capabilities are planning commitments, not implementation claims. They must be represented in registered units and the active plan before coding. Payment-enabled releases require payment-specific evidence in addition to generic security/package certification.
