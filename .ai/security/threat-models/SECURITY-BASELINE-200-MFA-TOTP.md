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

## Residual risk

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
