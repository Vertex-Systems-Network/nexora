# Threat model — SEC-REVIEW-OVERRIDE-PR152

## Scope

Owner-authorized exception to independent review for PR #152 and exact candidate only, including governance annotations. High-risk control change; no runtime permission, target or secret access.

## Threats and controls

| Threat | Control |
|---|---|
| AI self-approves a waiver | Record exact current owner instruction and identity separately from author; never label it independent review |
| Broad/persistent review bypass | Exact PR, source/candidate identity, expiry/revocation and scope-change invalidation |
| Green status obtained by disabling checks | No CI/audit/test/assertion/native protection modifications; fresh exact-head checks mandatory |
| Wrong or altered dependency promoted | Existing fail-closed source/manifest/toolchain/hash/provenance/audit promotion validations and rollback |
| Source proof misrepresented as live release | #74 target and production/release authorization remain separate |

## Residual risk

Independent reviewer assurance is intentionally absent under explicit owner instruction. Local tests/audits are compensating controls, not equivalent review. Failure or scope/candidate change stops the exception path. Revert exception/promotion commits to roll back.
