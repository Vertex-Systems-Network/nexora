# N0.10 Verification

Source verification for N0.10 covers the former deployment UX where a browser could remain on `Working…` while a synchronous Composer/npm/build request blocked.

Required source gates:

- deployment bootstrap exposes `application/x-ndjson`
- long commands use `nxStreamFixedCommand`
- `Prepare everything` uses the streaming browser form
- real command output is surfaced through the stream
- one-second server heartbeat exists while child commands run
- browser cancellation is wired to `AbortController`
- deployment concurrency lock exists
- main Laravel installer exposes `/install/stream`
- main installer reports migrations/seed/admin/runtime/lock progress
- ordinary non-JavaScript install POST remains available
- source guard remains green

## Local source verification performed for this artifact

- 187 PHP files linted with PHP 8.4: no syntax errors
- standalone deployment JavaScript parsed by Node.js: PASS
- main installer JavaScript parsed after resolving Blade route literals: PASS
- Nexora source guard `--source-only`: PASS
- clean-domain bootstrap smoke test: HTTP 200 at `/`
- direct `/nexora-bootstrap.php`: HTTP 302 back to `/`
- authorized streamed `npm_build` smoke request emitted valid NDJSON `start → stages → complete` events and failed visibly/cleanly because `node_modules` was intentionally absent

Full dependency-backed Laravel/MySQL tests are still the responsibility of the zero test on the target Laragon environment, because this source artifact intentionally contains neither `vendor/` nor `node_modules/` nor a production build.
