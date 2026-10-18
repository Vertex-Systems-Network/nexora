# Threat Model — MOBILE-AUTH-API-100 (SYS-MOBILE-AUTH-API)

## Unit

- Development unit ID: `SYS-MOBILE-AUTH-API`
- Parent stage: `MOBILE-AUTH-API-100`
- Risk class: critical (new unauthenticated credential-exchange entry point)
- Owner/reviewer: authoring AI run (2026-10-18); independent review pass on the exact head SHA required before merge.

## Scope

Three routes on the existing `nxapi_` token layer (`ApiTokenManager`, `AuthenticateApiToken`, `RequireApiAbility`). No new auth package, no new table, no change to token hashing or resolution:

- `POST /api/v1/auth/token`: email + password + device name (+ optional organization) → one bearer token.
- `GET /api/v1/me`: the presenting user's own profile (requires `profile.read`).
- `POST /api/v1/auth/logout`: revokes the presenting token only.

## Assets

User credentials (in transit only), issued bearer tokens, user profile PII (name, email), tenant/organization boundary, audit trail, availability of the login path.

## Trust boundaries

Unauthenticated internet → `/api/v1/auth/token`. Bearer-authenticated mobile client → `/api/v1/*`. Tenant context is installed per request by `AuthenticateApiToken` and restored afterwards.

## Entry points

The three HTTP routes above. There are no webhooks, uploads, jobs or CLI entry points.

## Threats and controls

| Threat | Control |
|---|---|
| Credential stuffing / brute force | Per-(email-hash + IP) limiter of 5 attempts/min and per-IP 20/min on the exchange route. Failures are audited (`api.auth.token_failed`, email stored only as a SHA-256 hash). |
| User enumeration | Every credential failure returns one identical 422 message. Inactive and SSO-required accounts are rejected only *after* the password is verified, so their states are not revealed to unauthenticated callers. |
| Bypassing web-login policy | The same rules as `AuthenticatedSessionController` apply: an `active` status is required and `SsoEnforcementPolicy::requiresSso` blocks password exchange. Unverified email is refused (stricter than web, which redirects to verification). |
| Tenant escape | The organization comes from the user's own active membership or an explicitly requested org. `ApiTokenManager::issue()` re-checks membership or super-admin and org `active`. `resolve()` re-checks on every request. |
| Privilege escalation via token abilities | Exchanged tokens receive only `profile.read` (self). They never receive admin, document or other abilities, and the caller cannot choose abilities. |
| Token theft / replay | Tokens are stored only as SHA-256 hashes and expire after 30 days. Logout revokes server-side and the revocation is audited. Admins can revoke from the Developer › API tokens page. Clients must use Keychain/Keystore (APP-MOBILE-CLIENT criterion). |
| Logout revoking another user's or tenant's token | Logout acts only on the `ApiAccessToken` that `AuthenticateApiToken` attached to the request. There is no id parameter. |
| Token sprawl / DoS by issuing many tokens | Throttles as above. Device name is capped at 80 characters. Per-user active token cap: the oldest exchanged token beyond 10 is revoked. |
| Profile data over-exposure | `/me` returns an explicit allow-list (id, name, email, verified, organization id/name/slug, token abilities and expiry). There is no password hash, remember token or role internals. |
| Injection / unsafe output | Validated scalar inputs, Eloquent bindings only, JSON responses. |
| Audit gaps | Issue and revoke go through the existing `EnterpriseAuditRecorder`. Failed or blocked exchanges are recorded through `AuditManager`. |

## Abuse cases

1. An attacker sprays passwords across emails. Mitigated by the per-email and per-IP throttles and audited failures.
2. A suspended employee tries to keep mobile access. The status check runs at exchange time and in `resolve()` on every call, so suspension cuts access immediately.
3. A member of org A requests org B. Rejected, because `issue()` requires active membership in the requested org.
4. A stolen device. The admin revokes the token, or it expires within 30 days at most.

## Independent review round 1 (reviewed head 8bccb98, CHANGES_REQUIRED)

| Finding | Resolution |
|---|---|
| 1. The per-email limit was keyed per IP, so distributed guessing against one account was uncapped. | Fixed. A third bucket keyed on the email alone allows 30 attempts per 15 minutes regardless of IP. Test: `test_account_limit_holds_across_rotating_ips`. |
| 2. The check-then-hit gap let concurrent requests bypass the limit. | Fixed. All three buckets are hit first, and the decision uses the returned counts. |
| 3. Blocked states (inactive, unverified, SSO) did not count toward the limit. | Fixed. Only a fully successful exchange clears the per-email buckets. Test: `test_blocked_states_count_toward_the_limit`. |
| 4. SSO is evaluated per requested organization. | Accepted by design. The token is scoped to exactly that organization, and the policy is defined per organization, which matches web login. A user in org A (SSO enforced) and org B (no SSO) can obtain a B-only token. |
| 5. The token cap matched on name prefix only. | Fixed. The cap only touches tokens whose abilities are exactly `[profile.read]`, so admin-issued tokens are never revoked. Test: `test_token_cap_never_revokes_admin_issued_tokens_sharing_the_prefix`. |
| 6. Super-admin break-glass; email case sensitivity; timing hash cost. | Accepted. The super-admin token is still `profile.read` only. Email case behaviour matches web login. The dummy hash uses cost 12, the Laravel default; deployments that change `BCRYPT_ROUNDS` should keep it aligned. |

## Independent review round 2 (reviewed head 7aa46d1, APPROVE)

Findings 1, 2, 3 and 5 are verified fixed, the new tests are confirmed meaningful, and the finding 4 decision is accepted. The reviewer asked for the lockout risk below to be documented. This doc-only follow-up does not change the reviewed code.

## Residual risk

- **Targeted mobile sign-in lockout.** Because the per-account bucket ignores IP, anyone who knows an email can send 30 requests every 15 minutes and keep that user's *mobile* exchange blocked, even with the correct password. Web login is unaffected. This is accepted as the price of the brute-force cap. Possible later mitigations: let recognised devices through, or replace the hard block with a CAPTCHA or progressive backoff (SECURITY-BASELINE-200).
- `Retry-After` reports the longest timer of the three buckets, so it can overstate the wait. This is cosmetic.

- No MFA exists platform-wide yet. Mobile matches web here, and MFA is scheduled in `SECURITY-BASELINE-200`.
- Tokens are bearer tokens with no device binding. This is accepted until `SECURITY-BASELINE-200` and passkeys.
- The rate limiter is the application cache, so it is not distributed. Production should use Redis, as the existing stack already supports.

## Verification

Feature tests for:
- a successful exchange, and that the issued token works on `/me`;
- wrong password, unknown email (same message), suspended user, unverified user and SSO-required user;
- requests for a foreign organization;
- that the throttle triggers;
- that the issued token cannot call `documents.read` routes;
- that logout revokes only the presenting token and a second token survives;
- that `/me` is not reachable without a token;
- that the `/me` payload omits sensitive fields.

Also required: the full phpunit suite, source-guard, and the independent review on the exact head SHA. The unit stays at `SOURCE_DONE` at most and never reaches `TARGET_VERIFIED` from CI alone.

## Rollback / incident response

- Remove or disable the `auth/token` route and revert the PR. Already-issued tokens can be bulk-revoked by setting `revoked_at` on tokens whose name starts with `Mobile · `.
- No schema change is involved, so rollback is code-only.

## Review outcome

- [x] Threat model complete.
- [x] Critical/high threats have mitigations.
- [x] Required tests added to active plan.
- [x] Residual risk accepted/documented (MFA deferred to SECURITY-BASELINE-200).
- [x] Independent security review: round 1 CHANGES_REQUIRED (8bccb98) → fixed → round 2 APPROVE on 7aa46d1 (code unchanged since; doc-only follow-up).
