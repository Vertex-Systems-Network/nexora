# Threat model — SEC-PR152-SOURCE-INTEGRATION

## Context and assets

Protected main, governed Composer/npm locks, review identity, source/target/release evidence and native GitHub checks. No provider, target, secret, deployment or runtime mutation is authorized. Source integration is not target acceptance.

## Trust boundaries and threats

| Threat | Control |
|---|---|
| Scope drift or implicit waiver extension | Exact main and original #152, immutable minimal warning cut, allowlisted governance/evidence paths; scope/base/candidate drift invalidates authorization. Earlier review waiver is historical for this combined scope. |
| Self-approval / protected verifier weakening | Independent review of exact combined published head, including exception and verifier. Existing checks/assertions preserved; review identity/decision/head external binding. |
| Green job hides warning | Inspect actual strict PHPUnit log for zero warnings/errors/failures; preserve older warning; rerun all required CI at new head. |
| Lock drift / insecure promotion | Match original reviewed-promoted Composer/npm blobs and recorded hashes, unchanged root manifests; fresh locked audits and source/toolchain/provenance controls. |
| Source merge misrepresented as target acceptance | Stage ACTIVE; target BLOCKED; #74 OPEN; no Target Power increase or deployment/release. Live target evidence still mandatory for target acceptance. |
| Race, native protection bypass, replay | Normal expected-head publication and merge only after fresh current-base checks, no force/bypass/auto-merge. Exception expires on one merge, closure, date, revocation or scope/base/candidate drift. |
| Regression after integration | Verify exact resulting main tree and push certification; revert through governed PR if required, never rewrite main. |

## Verification and residual risk

Independent protected-change review pending, fresh exact combined-head CI pending. Owner approval is not review. Prior CI certifies only its measured SHA. No actual target proof; residual risk of target-specific behavior remains explicitly blocked. No broad rule or test relaxation.
