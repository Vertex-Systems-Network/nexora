# N0.3 — Identity, Access & Premium Admin Interaction

## Goal
N0.3 makes identity and authorization reusable platform capabilities instead of page-specific conditionals. Backend authorization is authoritative; React only reflects capabilities already granted by the server.

## Identity and access
- `User::hasPermission()` resolves permissions through assigned roles.
- `super-admin` is a controlled bypass role and always retains the complete permission catalog.
- `RequirePermission` protects route actions server-side.
- `EnsureAdminAccess` requires both an active account and `admin.access`.
- Admin navigation is dynamically filtered using the same permission slugs.
- The final Super Admin cannot be deleted, demoted or suspended.
- A signed-in user cannot suspend or delete their own account.

Authentication write endpoints are rate-limited, and authorization denials are recorded in the audit trail.

## Current permission catalog
```text
admin.access
users.view / users.create / users.update / users.delete
roles.view / roles.create / roles.update / roles.delete
settings.manage
system.health.view
audit.view
profile.manage
sessions.manage
search.use
notifications.view
```

Future modules register their own domain permissions through the same model.

## User management
The admin can:
- search/filter users;
- create and edit users;
- assign one or more roles;
- mark email verification state;
- activate/suspend accounts;
- set locale/timezone;
- set or replace passwords;
- safely delete accounts;
- run protected bulk activate/suspend actions.

## Roles
Roles support:
- custom name/slug/description;
- grouped permission selection;
- user and permission counts;
- immutable system-role deletion protection;
- stable slugs for system roles.

## Profile and sessions
The current admin can update profile data, change password and revoke other sessions when `SESSION_DRIVER=database`.

## Audit trail
Every web response receives an `X-Request-Id`; audit events created during that request reuse the same correlation ID. Security-sensitive operations use `AuditManager`. Current events include login/logout, user create/update/delete/status changes, role changes, profile changes, password changes and session revocation.

## Premium DataTable standard
`DataTable` is the shared primitive for high-volume admin lists. N0.3 establishes:
- allowlisted server-side sorting;
- pagination;
- row selection;
- scoped pending/loading feedback while preserving table geometry;
- filter toolbar composition;
- bulk actions;
- column visibility controls;
- persisted saved views through `nx_saved_views`;
- empty state support;
- keyboard/focus-aware controls.

Saved views are per-user and server persisted. Column visibility also persists locally for immediate device-specific ergonomics.

## Admin interaction foundation
- `@nexora/admin-ui` is the only public UI surface.
- Ctrl/Cmd+K opens global admin search when the user has `search.use`.
- The topbar notification entry is capability-aware.
- Destructive actions use Nexora confirmation dialogs.
- Modal behavior includes Escape-to-close and focus restoration.
- Global route progress from N0.2 remains active while tables can also show local pending state.
