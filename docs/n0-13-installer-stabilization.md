# Nexora N0.13 — Installer Stabilization

## Goals

N0.13 makes the next zero-install test fail earlier and more clearly while removing the build/runtime defects reported during N0.12 testing.

## Frontend stabilization

- Inertia v3 owns page resolution and mounting through the Vite plugin.
- `resources/js/app.tsx` uses `pages`, `strictMode`, and `withApp`.
- The optional input leading icon is normalized to a boolean before it enters the class-name helper.
- Existing Nexora error boundaries remain, but they are no longer masking a legacy/v3 bootstrap mismatch.
- Theme/Toast providers no longer call `usePage()` while wrapping the Inertia app from the outside; `withApp` supplies their shared props explicitly.

## Identity step

- aligned password and confirmation fields
- show/hide controls
- live required-pattern checklist
- Low / Medium / Strong score
- explicit consent for valid Low/Medium choices
- live confirmation match state
- default Language selector with flags, country names and supported-language allow-list

The strength score never relaxes the hard password minimum.

## Existing database safety

A non-empty target database requires this sequence:

1. Test the connection.
2. Create a protected SQL backup.
3. Observe backup progress.
4. Download the SQL file.
5. Explicitly confirm that the backup was saved and authorize destructive reset.
6. Start installation.
7. The server revalidates session, database fingerprint, backup expiry and recorded download.
8. Existing views/tables are removed with progress.
9. Fresh Nexora migrations run.

No successful backup/download/consent means no wipe.

## Separation of stages

The pre-Laravel deployment screen handles source dependencies/build only. Database host, database name, username and password are requested exclusively by `/install`. Remote source builds use a deployment access key instead of abusing DB credentials as an authorization secret.

## Zero test

Use a fresh extraction, run `scripts\setup-zero.bat`, then open `https://nexora/`. Complete deployment preparation first. Enter MySQL credentials only after Nexora transitions to `/install`.
