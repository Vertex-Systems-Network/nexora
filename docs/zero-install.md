# Zero Installation Test — Windows / Laragon

## Local standard

- MySQL host: `127.0.0.1`
- port: `3306`
- development DB: `nexora`
- testing DB: `nexora_testing`
- user: `root`
- password: `root`

## Test the real browser installer from zero

From CMD:

```bat
scripts\setup-zero.bat
```

Type `NEXORA` when prompted. This is destructive **only for local database `nexora`** and removes local installer/runtime state.

The zero runner now intentionally does **not** run Composer or npm. Open:

```text
https://nexora/
```

If Composer dependencies or production assets are missing, Nexora **stays on the same site URL** and renders Deployment Preparation internally. Do not browse to a bootstrap PHP filename.

For the current Laragon convention verify MySQL using `root / root`, then click **Prepare everything automatically**. Nexora first discovers and smoke-tests OS/PATH tools, then ComposerSetup/user installs, Laragon, and finally Nexora-private fallbacks. Apache/FastCGI often lacks `APPDATA`, `HOME` or `COMPOSER_HOME`; N0.10 retains N0.9 portable environment handling and now streams every long-running Composer/npm/Vite stage back to the browser with live output, stage percentage, elapsed time and a server heartbeat. If Composer is genuinely absent it can install a verified private Composer copy; if Node/npm are absent it can install a checksum-verified private Node.js LTS runtime on supported hosts. If process execution is unavailable, upload a prebuilt Nexora production release instead; that release already contains `vendor/` and `public/build/`.

While deployment preparation is running, keep the browser open and watch the live progress panel. If a command fails, the failed stage and output remain visible instead of leaving an indefinite spinner. When deployment readiness is green, continue to `/install` and complete:

1. system/runtime readiness
2. MySQL test/configuration
3. site + Super Admin
4. review/install — final provisioning also shows live stage progress through migrations, seed, Super Admin, runtime and installation lock

Then run developer QA:

```bat
scripts\quality-check.bat
```

That suite uses only `nexora_testing` for destructive database tests.


## N0.11 environment persistence

A clean source package does not need a pre-existing root `.env`. Nexora boots the deployment and installer UI using protected temporary state. During final installation it writes the root `.env` when possible; if project-root ACLs are read-only, the same configuration is stored under protected Nexora storage and an active-location marker makes that fallback authoritative on future requests. Do not loosen the entire project root to world-writable permissions simply to make installation pass.


## N1.0 true-zero correction

The zero-state scripts remove `.env` and no longer copy `.env.example` back before the browser opens. `scripts/zero-state-verify.php` must pass first. This means the browser deployment preparation and installer are tested from the same no-root-`.env` condition supported by the shipped clean source archive.

## N1.0 RC7 true-zero + recovery certification

RC7 makes `setup-zero` destructive to **local dependency/build/bootstrap state as well as installer state**. After explicit `NEXORA` confirmation it removes `vendor`, `node_modules`, `public/build`, protected fallback environment state and private Nexora bootstrap tools, then runs:

```bat
php scripts\zero-state-verify.php --strict-source
```

This is intentional: a zero-install rehearsal must prove the exact browser bootstrap path rather than silently reusing dependencies or environment state from a previous run.

If a deployment worker disappears, the bootstrap compares its persisted `active` state with the real OS deployment lock. A free lock converts the stale state to `interrupted` and allows a retry. If the main Laravel installer stops after entering protected schema provisioning, its database fingerprint/run journal allows the same database target to resume idempotent migrations/seeding without a second destructive wipe. The installer never reuses that recovery record for another database target.
