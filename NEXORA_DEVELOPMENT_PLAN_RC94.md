# Nexora Development Plan — rc.94 / v5.29

## Development-first status

| Phase | Scope | Progress | Status |
|---|---|---:|---|
| DEV-0 | Package/bootstrap determinism | 90% | In progress; final dependency review remains last-stage work |
| DEV-1 | Installer functional closure | 100% source | Fresh-request post-install identity handoff implemented |
| DEV-2A | Historical TypeScript remediation | 100% | Done |
| DEV-2B | Target TypeScript/Vite build | 100% target-reported | Clean on current Laragon run |
| DEV-3 | Laravel runtime/install closure | 75% | Install committed; rc.94 fixes one-time post-install fingerprint stabilization |
| DEV-4 | Login/admin/core functional QA | 30% | Static Laravel/security/browser/database gates PASS; live login next |
| DEV-5 | DB/services portability | 60% | SQL primary + auxiliary services implemented; broader matrix remains |
| DEV-6 | Final C1–C6 certification | 10% | Intentionally last |

## rc.94 closure

The installer no longer seals install-sensitive environment, activation, service, and process fingerprints inside the same long-lived request that wrote `.env` and created `installed.lock`.

After the durable install commit, the client is sent to `/install/runtime-handoff`. That fresh HTTP request loads the committed environment and installed-state-sensitive Laravel configuration, then Nexora may finalize only the one-time allowed planes: environment, activation, service, and process.

Finalization is fail-closed. Version, source/deployment generation, engine, database, storage, host, resource, policy, framework, and dependency identity must already match. Once `post_install_identity_finalized=true` is sealed, later runtime drift is never silently adopted.

## Next development gate

1. Repair the already-installed rc.93 instance without changing source.
2. Verify `nexora:runtime:compatibility-status --deep` PASS.
3. Verify `nexora:runtime:post-install-status --assert-ready` PASS.
4. Open Super Admin login and begin DEV-4 functional QA.
