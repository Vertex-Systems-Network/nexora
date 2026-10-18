# Threat Model — SECURITY-BASELINE-200 / SYS-MFA-TOTP (increment 1)

## Unit

- Development unit ID: `SYS-MFA-TOTP`
- Parent stage: `SECURITY-BASELINE-200`, owner-prioritised on 2026-10-18.
- Risk class: critical. This changes authentication for every account.
- Owner/reviewer: authoring AI run. An independent review on the exact head SHA is required before merge.

## Scope

Users can enrol in authenticator-app MFA following RFC 6238 (TOTP, SHA-1, 6 digits, 30-second step). It is implemented natively with `hash_hmac`, with **no new dependency**.

- **Enrolment, on the profile page:**
  - Start enrolment: a pending secret is generated.
  - Confirm with a valid code: the secret is activated and 8 recovery codes are shown once.
  - Regenerate recovery codes.
  - Disable MFA, which requires the current password.
- **Web login:** if the user has confirmed MFA, a correct password does **not** create a session. A pending challenge is stored in the session instead, and the user must then pass `POST /two-factor-challenge` with a TOTP code or a recovery code.
- **Mobile token exchange** (`POST /api/v1/auth/token`, from MOBILE-AUTH-API-100): users with confirmed MFA must send a `code` field. Without it the response is `422 two_factor_required`; with a wrong code it is the identical `invalid_credentials`.
- **Out of scope:** passkeys/WebAuthn, enforcing MFA by role or organization policy, and SMS or email OTP. These are later SECURITY-BASELINE-200 increments. SSO sign-ins are also out of scope, because the identity provider owns MFA for them.

## Assets

TOTP shared secrets, recovery codes, authenticated sessions, API tokens, and the audit trail.

## Trust boundaries

- Unauthenticated browser → `/login` → pending-challenge session → `/two-factor-challenge`.
- Unauthenticated mobile client → `/api/v1/auth/token`.
- Authenticated user → profile MFA endpoints.

## Threats and controls

| Threat | Control |
|---|---|
| Password-only bypass of MFA (web) | Login checks `two_factor_confirmed_at` before `Auth::login`. A pending state stores only the user id, the remember flag and an expiry (5 minutes); it is not an authenticated session. Admin and portal routes stay behind `auth`, so a pending user is still a guest. |
| Password-only bypass of MFA (API) | Token exchange requires a valid `code` for confirmed-MFA users, and this check runs before any token is issued. A test asserts that no token row exists. |
| Brute-forcing 6-digit codes | The challenge route uses the shared `auth:` throttle (5/min per IP). The challenge itself allows 5 failed attempts and then clears the pending state, so the user must re-enter the password. The API path counts toward the existing per-email, per-IP and per-account buckets. |
| Code replay within its window | `two_factor_last_used_step` stores the last accepted time-step, and codes at or before that step are rejected. |
| Clock drift | ±1 step of tolerance (90 seconds total), and no more. |
| Secret disclosure at rest | The secret and recovery codes use Laravel's `encrypted` cast with APP_KEY. They are hidden from model serialization and never sent to the frontend after enrolment. |
| Recovery-code theft or reuse | 8 random 10-character codes, stored as SHA-256 hashes inside the encrypted column. They are single-use (removed when consumed) and can be regenerated. Plaintext is shown only once. |
| Enrolment hijack or lockout | Enrolment is confirm-before-enforce: an unconfirmed secret never gates login. Disabling or re-enrolling requires the current password. |
| Unauthenticated MFA disable | The profile routes require `auth` plus the existing `permission:profile.manage`; disabling also requires `current_password`. |
| Timing differences | Codes are compared with `hash_equals`. API failures return the same body as a wrong password. |
| Audit gaps | Audit events: `auth.two_factor.enabled`, `.disabled`, `.recovery_regenerated`, `.challenge_failed`, `.challenge_passed` and `.recovery_used`. |

## Abuse cases

1. **A stolen password with MFA enabled.** The attacker hits the web challenge or `two_factor_required` on the API, and cannot proceed without the device.
2. **An attacker brute-forces the challenge.** After 5 failures the pending state is cleared and the attacker must use the password again, under the existing login throttle, which also generates audit signals.
3. **A shoulder-surfed code replayed seconds later.** It is rejected by the last-used-step check.
4. **A user who lost their device.** They sign in with a single-use recovery code; an admin reset path is a later increment, and an operator can clear the columns.

## Independent review round 1 (head 3f1a20d, CHANGES_REQUIRED; no password-only bypass found)

| Finding | Resolution |
|---|---|
| 1. MEDIUM: no account-wide cap on the challenge; the per-session counter resets on a fresh login, so many IPs could guess codes. | Fixed. A `nexora-2fa-failures:{user}` bucket allows 10 failures per 15 minutes across web, API and disable, regardless of IP or session. Locked challenges end the pending login; the API returns 429. Test: `test_account_lock_survives_fresh_logins_and_new_sessions`. |
| 2. MEDIUM: concurrent requests could accept the same TOTP code or recovery code. | Fixed. The row is re-read under `lockForUpdate` inside a transaction, and the TOTP step is advanced with a conditional `UPDATE … WHERE last_used_step IS NULL OR < step`, where 0 rows means failure. Test: `test_one_code_cannot_be_accepted_twice`. |
| 3. MEDIUM: enabling MFA left pre-MFA sessions and remember-me cookies valid. | Fixed. Confirm now revokes other sessions (database driver) and rotates the remember token. Test: `test_enrolment_rotates_the_remember_token`. |
| 4. LOW: disable required only the password. | Fixed. Disable requires the password plus a current TOTP or recovery code. Test: `test_disabling_requires_the_current_password_and_a_current_code`. |
| 5. LOW: freshly generated recovery codes sit in flash session data. | Accepted. They are flash-only for a single request, and a database or file session store keeps them only until that request completes. |
| 6. LOW: `Auth::validate` skipped password rehashing. | Fixed. `rehashPasswordIfRequired` runs after validation. Test: `test_successful_password_login_still_rehashes_outdated_hashes`. |

All five new regression tests fail against 3f1a20d.

## Independent review round 2 (head 1501531, CHANGES_REQUIRED)

Findings 2, 3, 4 and 6 were verified fixed.

New finding (MEDIUM): the account cap was checked before failures were counted, so a concurrent burst from many pending sessions could overrun it.

Fixed: `verifyForLogin` now increments the account bucket **before** verifying and refuses if the count exceeds the cap; only a success clears it. Test: `test_attempt_is_counted_before_verification_runs`, which asserts the counter is already 1 when the user row is read. It fails against 1501531.

## Residual risk

- Account lockout: anyone who knows a password can trigger the 10-failure, 15-minute MFA lock. This is accepted as the price of the brute-force cap.
- With file or cookie session drivers, other sessions cannot be enumerated for revocation on enrolment; the remember token is still rotated.
- TOTP can be phished in real time. Passkeys, planned for a later increment, are the phishing-resistant fix.
- MFA is opt-in per user; there is no role or organization enforcement yet.
- There is no QR code in v1. The setup key and the `otpauth://` URI are shown instead, so no QR dependency is needed.

## Verification

- Unit tests:
  - RFC 6238 test vectors, reduced to 6 digits from the SHA-1 seed.
  - Window tolerance.
  - Replay rejection.
- Feature tests:
  - The enrolment lifecycle.
  - Login without MFA is unchanged.
  - Login with MFA leads to the challenge; no session exists until a valid code is entered.
  - A wrong code fails, and 5 failures clear the pending state.
  - A recovery code works once.
  - Disabling requires the password.
  - API without a code returns 422; API with a wrong code returns `invalid_credentials`; API with a valid code returns 201.
- The full suite, plus an independent review on the exact head.

## Rollback

- Revert the PR. The migration only adds nullable columns.
- To disable MFA for a user in an emergency, null their `two_factor_*` columns.

## Review outcome

- [x] Threat model complete.
- [x] Critical/high threats have mitigations.
- [x] Required tests planned.
- [x] Residual risk documented.
- [ ] Independent security review on the exact head SHA. Required before merge.
