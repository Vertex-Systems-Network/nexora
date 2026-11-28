# PR #152 — reproducibility and full-suite diagnostic

Unit `SEC-THEME-CI-DEPS-001`, stage `THEME-CLOSURE-001`. Exact source `28bb3e11737a77c14239f4631c68818487100c55`; main unchanged at `8c0a2d7bdf8e4df8e98b21e3c0d7ba4d3518e4eb`.

## Proven this pass

Two isolated workspaces started from the original exact-source manifests and locks. The same minimal partial-update command independently resolved the eight affected libraries. Both raw lock pairs, semantic digests and supply-chain fingerprints match, and Composer candidate SHA-256 is identical to the previously archived candidate: `11cf1db05f0abc833e3e5ce480d796e82360b39b1dfdbbc57017b4d7c2ad2a05`.

Existing Nexora toolchain validator PASS: PHP 8.4.28, Composer 2.10.3, Node 22.14.0, npm 10.9.2. Node archive checked against the official SHA-256 list. Exact executable identities and toolchain fingerprint are recorded. Both candidate supply-chain audits exit zero; provenance and strict lock-pair validators pass.

A review-required promotion dossier binds source-tree/manifest/candidate/toolchain digests to this measured partial-update preparation. It records its producer accurately: existing validators plus the archived reproduction procedure, not an invocation of `dependency-lock-refresh.php`. No independent approval, official promotion execution or root mutation is claimed. Revalidate the full dossier through the existing promotion controls after independent review.

## Full suite is not green

| Attempt | Fixture profile | Result |
|---|---|---|
| 1 | PHP default 128M, SQLite, localhost APP_URL | Process exhausted 128M at MediaLibraryFlowTest; exit 2; no complete suite result |
| 2 | Fresh exact-source fixture, 512M bounded runner, CI APP_URL https://nexora.test | 855 tests: 845 passed, 10 failed; 7304 assertions; exit 1 |

Nine failures report rootless `PHP_BINARY` failing to locate `libargon2.so.1` when tests intentionally clear the child environment (exit 127). One integrity failure reports missing `.github/workflows/release-certification.yml`: `.gitattributes` declares `/.github export-ignore`, and the disposable fixture was created with `git archive`. These are measured fixture limitations; they do not establish a passing full suite or justify changing the tests.

The original 128M failure and retry failure remain immutable evidence. No assertions, test selection in the full run, expected exit statuses or source files were weakened. The previous 33 focused tests/233 assertions PASS remains scoped to its earlier fixture; it does not replace this failed full run.

## Current repository evidence and remaining work

Current published source 28bb3e1 has Development checkout QA, Windows and traceability PASS; governance job 140792504014 FAIL still reports 25 advisories across eight original locked packages. Root lockfiles remain unchanged. No status from this source certifies a later documentation head or the isolated candidate.

Next: prepare a faithful disposable checkout including tracked export-ignored workflow files and a PHP runner usable with intentionally empty child environments. Record its changed executable identity before any further evidence. Re-run unchanged tests after that distinct fixture correction; preserve both failures. Then obtain independent exact-source/candidate review, revalidate promotion source/toolchain binding, use existing governed lock promotion and require fresh full exact-head CI. Authentication for the independent review service remains unavailable; no review is inferred from author checks. #74 live target remains separate and blocked.

Machine evidence: `.ai/evidence/theme-security-reproduction-002.json`. Exact A/B locks, command outputs, toolchain metadata, raw JUnit/failure outputs and dossier producer: `.ai/evidence/theme-security-reproduction-002.tar.gz`. Verify its SHA-256 from the JSON before extraction into a disposable review directory. Earlier candidate archive is preserved unchanged.
