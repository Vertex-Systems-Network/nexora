# Nexora N1.0 RC6 Verification Results

RC6 is an authentication/session/CSRF/permission/tenant-isolation stabilization pass derived from RC5. It does not introduce a new product domain.

## Source certification — PASS

- Platform version: `1.0.0-rc.6`.
- RC source/runtime preflight: PASS.
- Core module graph: PASS, 24 configured modules.
- Laravel runtime contracts: PASS.
- Database contracts: PASS, 24 migrations, 135 tables, 75 foreign targets, 51 tenant tables/models aligned.
- Security contracts: PASS.
  - explicit CSRF-independent protocol exceptions: 3 (`hooks/*`, `scim/*`, SSO callback)
  - authentication session-rotation paths: 3 direct sign-in paths plus impersonation start/restore coverage
  - external authentication boundaries: SSO, SCIM, inbound webhook
  - Admin tenant route-binding guard: present
  - raw tenant-owned `exists:nx_*` request rules covered by RC6 target list: 0
  - raw tenant user `exists:users,id` rules in tenant-owned Helpdesk/Membership/CRM-owner inputs: 0
- Frontend/Inertia contract gate: PASS.
- Nexora Source Guard: PASS.
- PHP syntax lint: 661 files, 0 syntax errors.
- TypeScript/TSX/config syntax parse: 123 files, 0 parser diagnostics.
- Local TypeScript import graph: 351 imports checked, 0 missing.
- Admin feature raw interactive controls: 0.
- Admin native browser date/time inputs: 0.
- Migration `->after()` modifiers: 0.

## RC6 runtime/security fixes

- Password login rejects every non-active account state and rotates the authenticated session/CSRF token after successful authentication.
- Self-registration rotates the session after `Auth::login`.
- Enterprise SSO validates short-lived state, requires an active global user plus active organization membership, avoids implicit persistent remember-login and rotates the session before tenant selection is persisted.
- Impersonation start and actor restoration rotate sessions; inactive targets cannot be impersonated and an inactive original actor cannot be silently restored.
- Password changes rotate remember credentials, revoke other database sessions and rotate the current session.
- Password resets rotate remember credentials and revoke all database sessions for the account.
- Forgot-password responses use the same public response shape for known and unknown email addresses.
- Failed-login audit metadata stores an email hash rather than the raw submitted address.
- Session payload encryption is enabled by default; HTTP-only remains on; secure cookies default on for HTTPS `APP_URL`; SameSite remains `lax`; serialization remains JSON.
- Admin entry now enforces both platform `admin.access` and enterprise-role authorization.
- `EnsureTenantRouteBinding` returns 404 for route-bound models whose `tenant_id` differs from the active tenant.
- `TenantExists` and `TenantMemberExists` replace raw cross-tenant foreign-ID validation across Commerce, CRM, Publishing, Media, Distribution, Membership, Helpdesk and Studio mutation paths.
- Organization SCIM suspension modifies that organization's membership only; it does not suspend the shared global user account.
- Security feature/architecture tests cover suspended login, password-reset enumeration shape, enterprise admin restriction, cross-tenant route IDOR, tenant-aware validation and SCIM organization-local suspension.

## Dependency-backed gates — NOT claimed as PASS here

Composer is not installed in this execution environment and the clean source tree has no `vendor` directory, so Laravel package discovery, migrations, database-backed security tests and PHPUnit/Pest are not reported as PASS.

`npm install --no-audit --no-fund` was attempted but timed out before dependencies were installed; no `node_modules` directory was produced. A subsequent `npm run build` therefore stopped with:

```text
TS2688: Cannot find type definition file for 'vite/client'.
```

That result reflects the missing dependency tree on this host, not a production TypeScript/Vite build pass.

## Target Laragon certification commands

```bat
composer install
npm install
npm run build
scripts\quality-check.bat
```

The quality runner will execute the module, Laravel runtime, database, security, frontend and Source Guard contracts before package discovery/routes/scheduler/migrations/seeds/tests/build/production packaging.

N1.0 remains **CERTIFYING — RC6** until dependency-backed and operator/browser evidence is green. The next planned stabilization block is RC7 zero-install/deployment/recovery certification; N1.1 remains blocked behind N1.0 PASS.
