# N1.0 RC13 — Existing-Install Upgrade / Rollback Safety

RC13 closes a release-candidate gap that fresh-install certification cannot cover: an already-installed Nexora site must be able to move to the current source tree without assuming that database migrations, enabled extensions, themes or rollback are automatically safe.

## Operator flow

1. Deploy the exact RC13 source tree while preserving `.env` and persistent storage.
2. Run `php artisan nexora:upgrade:preflight`.
3. Create/verify a source-version backup. Native Nexora runtime backup IDs can be supplied with `--backup=<id>`; externally managed PostgreSQL/SQL Server/provider snapshots use a private evidence JSON via `--external-backup-evidence=<path>`.
4. Run `php artisan nexora:upgrade:plan ...`. The resulting plan is fail-closed, target-version-bound and expiring.
5. Review extension/theme compatibility and forward-only migration rollback barriers.
6. Run `php artisan nexora:upgrade:apply --yes`.
7. If protected execution succeeds, the installed lock is atomically updated with `previous_version`, current `version`, `last_upgrade_id` and `upgraded_at`, while original `installation_id` and `installed_at` are preserved.
8. If a protected migration/runtime step fails, Nexora deliberately remains in maintenance mode. Restore the verified backup and the matching source artifact; do not blindly run migration reset/rollback commands.

## Safety boundaries

- Supported in-place source window defaults to `>=0.34 <2.0` and is explicit in `config/nexora-upgrade.php`.
- Downgrades are blocked by the upgrade workflow.
- Enabled extensions are evaluated against their persisted `requires.nexora` constraint.
- Active themes are evaluated against their persisted `requires.nexora` constraint.
- Forward-only extension migrations without `schema_compatible_rollback` are surfaced as rollback barriers.
- A verified backup is mandatory by default.
- Upgrade plans expire and must match the exact deployed target version.
- Automatic database rollback/reset/fresh is forbidden inside the core upgrade manager.
- Production `nexora-release.json` publishes upgrade compatibility and rollback policy.
- Runtime upgrade plans/history/locks are forbidden from certified customer ZIPs and are removed during true-zero rehearsals.

RC13 does not replace the later N1.26 Update/Rollback/Disaster-Recovery product platform. It establishes a conservative release-candidate operational path for existing installs.
