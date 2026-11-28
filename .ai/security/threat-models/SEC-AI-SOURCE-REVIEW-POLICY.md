# Threat model — SEC-AI-SOURCE-REVIEW-POLICY

## Context

Owner-directed change replaces mandatory separate reviewer for source-only integration with recorded AI_SELF_CHECK; no native policy bypass or target acceptance relaxation.

| Risk | Control |
|---|---|
| AI misses its own defect | Explicit residual risk, exact diff review with negative cases, unchanged protected oracles, fresh CI and audits, stop on findings. Optional independent reviewer may be used. |
| Author fabricates approval | Label AI_SELF_CHECK, record exact actor/head and actual findings; no GitHub APPROVE from author. |
| Control weakening / scope drift | Owner explicitly authorizes this policy only; separate registered plan; no gate code/workflow/runtime/lock edits; expected-head publication; all other gates remain. |
| Target/release misclassification | #74 remains BLOCKED, theme ACTIVE, existing target/provider/release rules unchanged; no target state promotion, deployment or release. |
| Prior evidence reused | Fresh exact-head CI and actual logs; prior 856/7369 retained at previous SHA. |
| Expired/overbroad source merge exception | Rebind #152 one-use exception to revised scope, same base/candidate/lock, expiry 2026-12-28, drift/closure/revocation fail closed. |

## Residual risk / rollback

Loss of independent judgment increases correlated error risk; explicit owner choice, not equivalent assurance. Revert policy through governed PR; no main history rewrite. Source review does not certify target or provider correctness.
