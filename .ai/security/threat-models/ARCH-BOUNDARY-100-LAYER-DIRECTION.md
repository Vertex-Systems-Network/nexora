# Threat Model — ARCH-BOUNDARY-100 / SYS-ARCH-BOUNDARIES (layer direction)

## Unit

- Development unit ID: `SYS-ARCH-BOUNDARIES`; stage `ARCH-BOUNDARY-100`; risk high (architecture).

## Scope

Architecture tests that enforce the dependency direction, plus one refactor: the request-id attribute name moves from `App\Http\Middleware\AssignRequestId::ATTRIBUTE` to `App\Nexora\Http\RequestContext::REQUEST_ID` (the middleware constant aliases it, so the value `nexora_request_id` is unchanged). No runtime behaviour, data, permission or network change.

## Threats and controls

| Threat | Control |
|---|---|
| Refactor changes the request-id attribute value and breaks audit/observability correlation | The middleware constant is defined as `RequestContext::REQUEST_ID`, so both names resolve to the same string; existing Observability and audit tests still pass. |
| Boundary test silently stops checking (e.g. scans the wrong directory) | Mutation check: re-adding a `use App\Http\...` import to a domain file makes the test fail. Allowlist entries are verified to still exist and still need the exception. |
| Allowlist used to hide new violations | Each entry is an exact file or class with a reason, reviewed in the PR; stale entries fail the test. |
| Comment text mistaken for a dependency | Comments and docblocks are stripped with the PHP tokenizer before matching. |

## Residual risk

Rules match imports and fully-qualified names; dynamic class strings (e.g. `'App\\Http\\...'` built at runtime) are not detected.
