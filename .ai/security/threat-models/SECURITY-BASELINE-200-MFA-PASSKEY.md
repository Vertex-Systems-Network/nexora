# Threat Model — SECURITY-BASELINE-200 / SYS-MFA-PASSKEY (increment: passkeys)

## Unit

- Development unit ID: `SYS-MFA-PASSKEY`
- Parent stage: `SECURITY-BASELINE-200`.
- Risk class: critical. This adds a new way to authenticate.
- Owner/reviewer: authoring AI run. An independent review on the exact head SHA is required before merge.

## Scope

Users can register WebAuthn passkeys from their profile and sign in with one without a password.

- **Registration** (`/admin/profile/passkeys/options`, `/admin/profile/passkeys`): requires the current password to start. It uses discoverable credentials (`residentKey=required`) with `userVerification=required` and `"none"` attestation only. A user may have at most 10 passkeys.
- **Sign-in** (`/passkeys/login/options`, `/passkeys/login`): discoverable-credential flow, where the authenticator chooses the account. After a valid assertion, the same `completeLogin` as password login runs.
- **Removal** (`DELETE /admin/profile/passkeys/{passkey}`): requires the current password and ownership.
- **Out of scope:** passkeys as a second factor after a password, attestation/MDS trust policies, the mobile API token exchange, and policy that enforces passkeys by role or organization.

## Dependency (supply-chain record)

| Item | Value |
|---|---|
| Purpose | WebAuthn ceremony verification: CBOR/COSE parsing, signature, origin, rpIdHash, counter and UV/UP checks. Implementing this by hand was judged riskier than a maintained library. |
| Direct package | `web-auth/webauthn-lib` 5.3.9 (MIT), constraint `^5.3` |
| New transitive packages (15, all MIT) | `doctrine/deprecations`, `paragonie/constant_time_encoding`, `phpdocumentor/reflection-common`, `phpdocumentor/reflection-docblock`, `phpdocumentor/type-resolver`, `phpstan/phpdoc-parser`, `spomky-labs/cbor-php`, `spomky-labs/pki-framework`, `symfony/polyfill-php81`, `symfony/property-access`, `symfony/property-info`, `symfony/serializer`, `symfony/type-info`, `web-auth/cose-lib`, `webmozart/assert` |
| Existing packages changed | none |
| Advisories | `composer audit --locked`: none. `npm audit`: 0. |
| Install scripts | Composer packages have no install-time scripts in this flow; the lock was produced with `--no-scripts`. |
| Owner approval | Approved by the repository owner in session on 2026-10-23. |
| Rollback | Revert the merge commit and drop the `nx_passkeys` table (the migration `down()` does this). No other table is touched. |

## Assets

Passkey public keys and signature counters, authenticated sessions, and the audit trail. No private key material ever reaches the server.

## Trust boundaries

- Unauthenticated browser → `/passkeys/login/options` → `/passkeys/login`.
- Authenticated user → profile passkey endpoints.
- Browser ↔ platform authenticator: outside the server's control. The server trusts only signed `authenticatorData` and `clientDataJSON`.

## Threats and controls

| Threat | Control |
|---|---|
| Phishing / relay from another origin | Origin is checked for an exact scheme+host+port match against the `APP_URL` origin (`CheckAllowedOrigins`), and rpIdHash must equal `sha256(rp id)`. A credential registered for one host cannot sign for another. Tested with an attacker origin. |
| Forged assertion for a known credential id | The signature is verified with the stored public key. Tested with the same credential id and a different private key. |
| Challenge replay | Options are stored in the session and pulled (removed) before verification, so each challenge is single-use and expires after 5 minutes. Tested. |
| Signing in as another user | The assertion's `userHandle` must equal the owner's HMAC handle, and the library's `CheckUserHandle` also applies. Tested. |
| Weak "user present only" assertions | `userVerification=required` is set in the options and enforced by the ceremony. A UP-only assertion is rejected (tested). A passkey therefore counts as MFA on its own, so passkey sign-in does not additionally require TOTP. |
| Cloned authenticator | Signature counter checks use the library's default `ThrowExceptionIfInvalid`. Counter updates are serialized with `lockForUpdate` per credential. |
| Session-hijack adds an attacker passkey | Starting registration requires the current password. If the account has TOTP enabled it also requires a current authenticator or recovery code, because passkey sign-in skips TOTP and adding one must not be weaker. Registration options are bound to the requesting user id. Tested. |
| Wrong origin behind a TLS-terminating proxy | The relying-party ID and the single allowed origin come from the configured `APP_URL`, not from the request, so an `http://` view of an `https://` site still matches. The request is used only when `APP_URL` has no host. Tested (mutation-checked). One consequence is that passkeys work only on the `APP_URL` host; `www.` and the apex domain are not interchangeable. |
| Session-hijack removes passkeys | Removal requires the current password and ownership; another user gets 404. Tested. |
| Account or SSO policy bypass | Passkey sign-in applies the same `status === active` and `SsoEnforcementPolicy::requiresSso` checks as password login. Inactive accounts are tested. |
| Credential brute force / enumeration | Verification shares the `auth:` throttle (5/min). Options are throttled at 20/min. Every failure returns the same generic message and is audited as `auth.passkey.login_failed`. |
| PII disclosure to authenticators | The user handle is `HMAC-SHA256(app key, user id)`, not the email or database id. Tested. |
| Credential record exposure | `credential_record` is `$hidden` on the model and is never sent to the frontend. Tested. |
| Unbounded registrations | At most 10 per user, checked under a row lock. Duplicate credential ids are rejected by a unique index and an explicit check. |

## Verification

- `tests/Feature/PasskeyAuthenticationTest.php`: 14 tests (including TOTP-gated registration, expired ceremony and the proxy/`APP_URL` origin) against a real software authenticator (`tests/Support/SoftwareAuthenticator.php`) using P-256 keys, CBOR attestation and ECDSA signatures.
- Mutation checks: with assertion verification removed, the forged-signature and UV/origin tests fail; with the origin taken from the request instead of `APP_URL`, the proxy test fails.
- `resources/js/admin/tests/passkeys.test.ts`: base64url and options conversion.
- Full PHPUnit suite: 831/831 (SQLite, local). `tsc --noEmit` clean. Production build passes. Source certification passes.

## Residual risk

- Attestation is not verified, so authenticator make/model is not trusted. This is acceptable for consumer passkeys; an enterprise attestation policy is a later increment.
- Synced passkeys (backup-eligible) inherit the security of the platform account (iCloud/Google). This is accepted and recorded.
- No live-browser evidence yet; target/browser acceptance remains with RUNTIME-TARGET-ACCEPTANCE.
