# PR #152 — corrected fixture and complete source regression

Measured source `99274cedd8117f149da5f0e56ce3ab1312eb1833`; stage `THEME-CLOSURE-001`, unit `SEC-THEME-CI-DEPS-001`. Candidate Composer SHA-256 `11cf1db05f0abc833e3e5ce480d796e82360b39b1dfdbbc57017b4d7c2ad2a05`. Root lockfiles remain unchanged.

## Corrections to the disposable test environment

The original rootless PHP executable required LD_LIBRARY_PATH, but subprocess tests intentionally pass an empty environment. Debian patchelf added a RUNPATH to the existing extracted libargon2 directory in this disposable runner only. Empty-environment PHP now exits zero and prints 8.4.28. No repository PHP/runtime/security code or test assertions changed. The original executable was preserved outside the repository. The corrected runner has SHA-256 `1d8012df5a22ff889df33cbc38d04c81e6c580d68e9873562f0e5f77a4be768b`; its new toolchain fingerprint is `97c3e3105e04a248d9b04f52bca1ae4bcec72962f11fc5eef599143f0105477b`.

The new disposable checkout materializes all 2,008 tracked blobs directly from Git rather than honoring git-archive export exclusions. Every source blob hash was checked. Executable modes were preserved, `.github/workflows/release-certification.yml` is present, and the only tracked overlay is the candidate Composer lock. Vendor setup disabled scripts/plugins. PHP 8.4.28 / Composer 2.10.3 / Node 22.14.0 / npm 10.9.2 passes the existing toolchain validator. SQLite in-memory, ephemeral application key, CI APP_URL https://nexora.test, and bounded PHP memory_limit 512M.

## Measured results

| Verification | Result |
|---|---|
| Previously failing test classes | 10 tests / 77 assertions PASS |
| Unchanged full PHPUnit, warning-hard | **855 tests / 7,363 assertions PASS**, exit 0 |
| Raw full JUnit | 855 test cases; zero failure/error/skipped elements |
| Rebound independent A/B resolutions | Raw locks, semantic digests, supply-chain fingerprints identical; match original archived candidate |
| Both A/B supply-chain audits and strict lock validators | PASS |
| Root lock mutation, promotion, target acceptance | None |

The A/B resolutions and review-required dossier were regenerated against source 99274ce and the corrected executable fingerprint. Earlier original-runner proof is not relabelled. Both old failed full runs remain in immutable reproduction-002 evidence; the current run supersedes their fixture blockers only. It does not establish deployed runtime, MySQL/full-engine, browser, frontend or final-release acceptance.

The measured source's remote intake reported Windows/traceability PASS, checkout QA in progress and governance FAIL. No later conclusion is inferred. Root locks remain vulnerable until an accepted reviewed candidate is actually promoted; passing isolated candidate tests do not make the current root governance audit green.

## Review and promotion boundary

Machine dossier `.ai/evidence/theme-security-fixture-004.json` binds source, candidate lock, runner/toolchain, checkout and tests. Exact candidate pair, A/B raw checks/commands, corrected-runner metadata, review-required promotion dossier, and full raw JUnit are in `.ai/evidence/theme-security-fixture-004.tar.gz`. Verify the archive hash from JSON before extracting into a disposable review directory. No PHP binary, credential or target state is embedded.

The promotion dossier accurately identifies the measured partial-update producer plus existing validators; it does not claim a stock refresh invocation, independent approval, or promotion receipt. Source/tree/manifest/toolchain binding must be revalidated through existing governed promotion after independent exact-source/candidate review. The author cannot approve its own high-risk change. Review-service authentication is unavailable; a GitHub reviewer identity or authenticated independent review service is needed. No reviewer request has been sent without a supplied identity.

Next: independent review, governed root promotion, fresh full exact-head CI of that root-lock change. Target #74 remains separate. The test/fixture correction milestone is complete locally; source integration/review, target and release are not complete.
