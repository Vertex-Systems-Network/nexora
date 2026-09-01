# rc.93 Post-Install Identity Repair Pack

## Purpose

This is a one-purpose external recovery utility for the already-installed Nexora `1.0.0-rc.93` Windows/Laragon target whose final post-install runtime identity was sealed before the installed environment fully stabilized.

It is **not** an upgrade utility and must not be used on rc.94 or any other release.

Known permitted stale planes:

- `environment`
- `activation`
- `service`
- `process`

Any other runtime-compatibility mismatch causes a fail-closed refusal before mutation.

## Safety model

The executable is `scripts/rc93-post-install-identity-repair.php` with a PowerShell wrapper at `scripts/rc93-post-install-identity-repair.ps1`.

The repair utility:

1. requires an explicit target path;
2. boots the **target's own** `vendor/autoload.php` and `bootstrap/app.php`;
3. requires both running and installed version to equal exactly `1.0.0-rc.93`;
4. verifies the sealed installation lock;
5. verifies current source activation and deep deployment/source identity;
6. permits only the four known stale compatibility planes;
7. requires healthy current service and process identity before mutation;
8. defaults to dry-run;
9. requires `--apply --confirm=REPAIR-RC93` for mutation;
10. creates and verifies a protected backup of the sealed installation lock;
11. updates only runtime-identity metadata through the target `InstallationState::updateMetadata()` contract;
12. requires convergence to `compatible=true`, `mismatches=[]`, `mode=installed-data-plane`;
13. atomically restores the original sealed lock if convergence fails;
14. writes a protected repair receipt after convergence.

The receipt is repair evidence only. It does **not** replace the required independent target commands or browser check.

## Dry-run first

From a checkout containing this repair pack:

```powershell
php scripts/rc93-post-install-identity-repair.php --target="D:\laragon\www\nexora"
```

or:

```powershell
.\scripts\rc93-post-install-identity-repair.ps1 -Target "D:\laragon\www\nexora"
```

Expected dry-run result is `status=pass`, `mode=dry-run`, the known mismatch list, the pre-repair sealed-lock SHA-256 and the bounded metadata fields that would be updated. Dry-run performs no mutation.

## Apply

Only after dry-run preflight passes:

```powershell
php scripts/rc93-post-install-identity-repair.php --target="D:\laragon\www\nexora" --apply --confirm=REPAIR-RC93
```

or:

```powershell
.\scripts\rc93-post-install-identity-repair.ps1 -Target "D:\laragon\www\nexora" -Apply -Confirm REPAIR-RC93
```

A successful apply records protected backup/receipt paths and the before/after installation-lock hashes.

## Mandatory target verification after apply

Run from the installed rc.93 target:

```powershell
cd D:\laragon\www\nexora
php artisan nexora:runtime:compatibility-status --deep
```

Require:

```text
status=pass
mismatches=[]
compatible=true
mode=installed-data-plane
```

Then:

```powershell
php artisan nexora:runtime:post-install-status --assert-ready
```

Only if both gates pass, open:

```text
http://nexora/login
```

`RUNTIME-CLOSURE-001` remains BLOCKED until those real-target checks pass. Source CI, the repair receipt or this document must never be interpreted as `TARGET_VERIFIED` evidence.

## Forbidden recovery shortcuts

Do not:

- overwrite the rc.93 target with rc.94 files;
- run a version upgrade as part of this repair;
- edit the sealed installation lock manually;
- relax runtime compatibility/readiness checks to obtain PASS;
- repair any mismatch outside the four permitted planes;
- treat source/CI success as live-target success.
