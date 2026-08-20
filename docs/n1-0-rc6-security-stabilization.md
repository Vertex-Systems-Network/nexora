# N1.0 RC6 — Authentication, Session, CSRF and Tenant Security Stabilization

RC6 turns the main authentication and tenant authorization boundaries into release-certification contracts rather than relying on feature-by-feature assumptions.

## Authentication/session hardening

Successful password login, self-registration and enterprise SSO all rotate the session identifier and CSRF token. Logout invalidates the active session. Password changes rotate the remember token, revoke other database sessions and rotate the current session. Password resets rotate the remember token and revoke all database sessions for the account.

Failed-login audit records store a normalized email hash rather than the raw submitted email. Forgot-password requests return the same public response shape whether or not an account exists, reducing account-enumeration leakage.

Session payload encryption is enabled by default, HTTP-only cookies remain mandatory, secure cookies default on when `APP_URL` is HTTPS, SameSite remains `lax` to preserve standards-compliant top-level OIDC/SAML return flows, and session serialization remains JSON.

## Tenant authorization / IDOR boundary

Admin entry now requires both platform `admin.access` and the current enterprise-role authorization key. In addition, `EnsureTenantRouteBinding` checks already-bound Eloquent route parameters carrying `tenant_id` and returns 404 when the model belongs to a different tenant. This creates a second defensive layer even when route model binding occurs before the tenant context middleware.

## Enterprise identity and SCIM

SSO state is short-lived and hash-compared, the resolved user must be globally active and an active member of the requested organization, and SSO no longer silently creates a persistent remember login. The authenticated session is rotated before the tenant selection is persisted.

SCIM bearer tokens remain hash-only at rest. A tenant SCIM token may activate/suspend that organization's membership, but it cannot suspend the shared global user record and thereby disrupt the same identity in another tenant.

## External CSRF exceptions

Only protocol endpoints that authenticate independently remain excluded from browser CSRF validation: signed inbound webhooks, SCIM bearer endpoints and SSO callbacks. Inbound webhooks still require timestamp freshness, HMAC signature verification, payload-size limits, optional source-IP allowlists and idempotency handling.
