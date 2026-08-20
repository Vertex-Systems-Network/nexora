# N0.10 — Observable Deployment & Installation

N0.10 removes blind long-running deployment waits. Composer, npm and Vite tasks now run through a newline-delimited JSON stream that reports stage state, stage-based percentage, elapsed time, real stdout/stderr, heartbeats, failure details and cancellation without navigating away from the deployment page.

## Deployment preparation

The browser uses a `fetch()` streaming request for fixed deployment tasks. The server emits:

- start metadata and the ordered deployment plan
- running/completed/skipped/failed stage states
- stage-based percentage values
- real Composer/npm/Vite output chunks
- a heartbeat at least once per second while a child process is running
- a final verifiable success/failure event

The progress percentage is intentionally stage-based; Nexora does not invent fake per-package percentages when Composer/npm do not expose deterministic progress.

`Prepare everything automatically` uses these checkpoints:

1. preflight/tool validation
2. PHP dependencies
3. frontend dependencies
4. production frontend build
5. artifact verification

Only one deployment build can run at a time. A filesystem lock prevents duplicate browser tabs from starting competing Composer/npm operations.

If the browser cancels the stream, Nexora detects the disconnected client on heartbeat/output writes and terminates the active child process. Each fixed command also has an execution timeout so the UI cannot remain in an endless loading state.

## Main installer

The `/install` wizard also uses an observable stream for the final provisioning operation. It reports:

1. installation preflight
2. database verification
3. environment configuration
4. migrations
5. core seed data
6. Super Admin creation
7. Nexora runtime sync/cache
8. final cache cleanup
9. installation lock

The existing synchronous POST remains as a no-JavaScript fallback, but the premium browser path uses the streamed endpoint.

## Failure behavior

A failed task does not leave the UI on an indefinite spinner. Nexora shows the failed stage, the last server output, elapsed time and a retry-capable action. Deployment preparation stores only a small non-secret final run summary under `storage/app/nexora/deployment-last-run.json`; command output is not persisted there.
