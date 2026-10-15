# Phase 1 — Portable source bootstrap origin preflight

The Windows PowerShell, Windows CMD and Bash bootstrap entrypoints validate configured URL and cookie policy before Composer/npm installs. The doctor is read-only: no .env values or key material are printed or changed.

Before running setup, copy .env.example to .env and replace the historical nexora.test sample with the origin where the Nexora site will actually be opened.

For a local loopback HTTP installation set APP_ENV=local, APP_URL=http://127.0.0.1:8000 (adjust port), and SESSION_SECURE_COOKIE=false. Non-loopback HTTP is rejected unless APP_ENV is nonproduction and NEXORA_ALLOW_INSECURE_HTTP=true has explicitly been set. For all production hosts use an actual HTTPS origin with APP_ENV=production and SESSION_SECURE_COOKIE=true. Production HTTP is rejected even when insecure HTTP is opted into.

Execute the URL-only check with: php scripts/bootstrap-origin-doctor.php

Then run the platform's bootstrap-installer.sh, bootstrap-installer.bat or bootstrap-installer.ps1. For a local PHP development server using Vite assets, run php -S 127.0.0.1:8000 -t public without an explicit router script so static compiled JS/CSS files are served correctly.

When Docker, a service manager, or a hosting panel injects APP_URL, APP_ENV, SESSION_SECURE_COOKIE or NEXORA_ALLOW_INSECURE_HTTP through the process environment, the preflight checks those effective values in preference to the file. It does not rewrite .env. The same effective settings must be supplied to the PHP web runtime; otherwise the command-line preflight is not a valid assertion about the running server. A production HTTPS origin must never opt into NEXORA_ALLOW_INSECURE_HTTP=true.

This preflight is not evidence of ownership of a live target, a current source-to-web challenge, target readiness, TLS acceptance, or retirement of the historically exposed application key. Use the governed Phase 1 target operator handoff for those separately required gates. Do not commit .env or rotate APP_KEY during source bootstrap.

## Protected fallback environment and IPv6 hosts

If the installer has an active protected environment marker under `storage/app/nexora/environment/active`, the preflight inspects the selected `root` or `fallback` `.env` instead of blindly validating the root file. An invalid active marker or a missing selected environment is rejected without falling back to a stale source; only fixed diagnostic messages are emitted. Explicit process environment overrides retain precedence. Local IPv6 loopback origins such as `http://[::1]:8000` are supported when `SESSION_SECURE_COOKIE=false`, while zero/empty ports and malformed DNS labels are rejected. This is configuration consistency evidence, not hosted-target identity attestation.

## Bind addresses are not browser origins

`0.0.0.0` and `[::]` are wildcard server bind addresses and must never be configured as `APP_URL`, even when the web process listens on all interfaces. Use the actual browser-reachable HTTPS hostname instead. Non-loopback HTTP with `NEXORA_ALLOW_INSECURE_HTTP=true` is only considered when `APP_ENV` is explicitly classified as nonproduction; a blank environment cannot silently authorize it. Production HTTP remains rejected.

## Browser session cookie compatibility

The origin doctor now also validates effective `SESSION_DOMAIN`, `SESSION_SAME_SITE` and `SESSION_PARTITIONED_COOKIE` values (including explicit hosting process overrides). A nonempty session domain must be an exact or parent DNS domain of `APP_URL`; omit `SESSION_DOMAIN` when using `localhost`, IPv4 or IPv6 loopback. An unrelated cookie domain causes successful login redirects to lose their authenticated session.

`SESSION_SAME_SITE` supports `lax`, `strict` or `none` (default `lax`); `none` requires Secure cookies. Partitioned session cookies likewise require `SESSION_SECURE_COOKIE=true`. Invalid cookie-policy values fail closed instead of silently generating unusable browser cookies. Non-loopback HTTP opt-in is limited to explicitly recognized nonproduction modes (`local`, `development`, `dev`, `testing`, `test`, `staging`, `preview`, `demo`, `ci`); an unknown or `prod` environment cannot enable insecure hosting through the opt-in. This is a configuration preflight, not live browser or target acceptance.
