# Stable disposable runtime verification — 2026-09-30

Unit SYS-RUNTIME-IDENTITY; RUNTIME-CLOSURE-001 remains globally BLOCKED. Base protected main `186138b0f7b7ca0da1ce3b2bb37b051edc75c855` passed release certification 36735105784. Runtime code candidate `5cfa5a6a54907e85d0cee8e90f04c38e486dd0a3` was exercised by real PHP 8.3.6/SQLite/loopback HTTP processes. This report distinguishes source certification, disposable runtime observation and live acceptance.

## Proxy mismatch diagnosis

The earlier service mismatches in both the baseline and SQLite-fixed fixtures were reproduced by substituting only their original loopback HTTP/HTTPS proxy port into the current service materials. The calculated SHA-256 exactly matched each installed service fingerprint. No other service material required modification. The workspace proxy port changes across tool executions; CLI and web in one shared execution have the same profile. A previously successful fixture also mismatched on a later execution.

This is observed environment drift, not proof that source activation changes service configuration. Original failures remain historical observations. The service guard correctly rejects the changed proxy endpoint and is unchanged. Fresh disposable QA explicitly removed inherited HTTP/HTTPS/ALL proxy variables and used a fixed loopback NO_PROXY profile in all child processes. No outbound provider call is part of this fixture, and this local setup is not advice to bypass a required deployment proxy or TLS verification.

## Actual authentication defect

A stable-proxy baseline installation passed source activation, one-time web acknowledgement, CLI re-verification and governed runtime recovery. However, after public HTTP smoke, login POST returned 429 and admin GET redirected 302. Laravel numeric throttle middleware without a prefix uses the same domain/IP signature across public health and guest authentication routes. Benign public requests therefore exhausted the five-attempt authentication budget.

The existing four guest authentication mutation routes now share `throttle:5,1,auth:`. The combined five-attempt/minute/IP authentication budget is preserved across login, registration, password request and reset. Public counters no longer consume it. No rate increase, identity exclusion, authentication/CSRF bypass, new route or dependency is introduced. The reviewed route hash and manifest binding are refreshed using the canonical manifest generator; all other critical source hashes remain unchanged.

Regression: six public health requests followed by correct credentials failed with 429 before the patch. After the patch it succeeds. Five invalid login mutations are still allowed through validation, the sixth returns 429, and other auth endpoints also return 429 under that exhausted shared budget. Authentication and existing security-boundary suite: 10 tests / 40 assertions PASS.

## Fresh corrected-source observation

Source-only certification exited 0 before installation. A separate clean candidate worktree was used so changes to evidence/documentation cannot mutate an already sealed fixture.

- Real migrations, seeders, administrator creation, permanent install lock: PASS.
- First HTTP runtime handoff: final 200.
- Readiness before and after source activation: exit 0.
- Token-bearing web acknowledgement: 200, acknowledged, status pass, authorized true; token never published.
- Independent CLI --require-web-ack: exit 0.
- Governed runtime recovery --apply --confirm=RECOVER-RUNTIME: exit 0, compatibility pass with no mismatches, fresh exact web identity proof and login GET 200. Its target_verification_complete=true refers only to this disposable loopback target.
- Existing public HTTP smoke: PASS, each public/login/live/ready route 200 under the existing 2000ms ceiling.
- Actual cookie/CSRF login POST: 302 to /admin; authenticated admin GET: 200.
- Final readiness: status pass, ready true, runtime_ready true, receipt_current true, errors empty.

Machine-captured scoped summary: `.ai/evidence/stable-runtime-verification-2026-09-30.json`. Original task logs, passwords, environment files, databases, cookies and bearer tokens are not committed. The summary is captured from the actual subprocess/HTTP results, not invented acceptance text.

## Remaining acceptance

Exact final PR CI and resulting-main CI are separate from local runtime observation and pending at preparation. Full installer UI/browser/accessibility, real HTTPS hosting, non-SQLite engines, historical exposed-key non-use/rotation and production service topology remain unverified. Issue #74 remains OPEN and Core QA promotion remains locked. PR #102 is a draft planning carrier, not completed runtime work. PR #1 is closed; references to it below older checkpoint headings are historical.
