# Phase 1 — Portable source bootstrap origin preflight

The Windows PowerShell, Windows CMD and Bash bootstrap entrypoints validate configured URL and cookie policy before Composer/npm installs. The doctor is read-only: no .env values or key material are printed or changed.

Before running setup, copy .env.example to .env and replace the historical nexora.test sample with the origin where the Nexora site will actually be opened.

For a local loopback HTTP installation set APP_ENV=local, APP_URL=http://127.0.0.1:8000 (adjust port), and SESSION_SECURE_COOKIE=false. Non-loopback HTTP is rejected unless APP_ENV is nonproduction and NEXORA_ALLOW_INSECURE_HTTP=true has explicitly been set. For all production hosts use an actual HTTPS origin with APP_ENV=production and SESSION_SECURE_COOKIE=true. Production HTTP is rejected even when insecure HTTP is opted into.

Execute the URL-only check with: php scripts/bootstrap-origin-doctor.php

Then run the platform's bootstrap-installer.sh, bootstrap-installer.bat or bootstrap-installer.ps1. For a local PHP development server using Vite assets, run php -S 127.0.0.1:8000 -t public without an explicit router script so static compiled JS/CSS files are served correctly.

This preflight is not evidence of ownership of a live target, a current source-to-web challenge, target readiness, TLS acceptance, or retirement of the historically exposed application key. Use the governed Phase 1 target operator handoff for those separately required gates. Do not commit .env or rotate APP_KEY during source bootstrap.
