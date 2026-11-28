# A/B reproduction and full-suite diagnostic — 2026-11-28

Source 28bb3e11737a77c14239f4631c68818487100c55, PR #152 / THEME-CLOSURE-001 / SEC-THEME-CI-DEPS-001. Two isolated resolutions on policy-valid PHP 8.4.28, Composer 2.10.3, Node 22.14.0 and npm 10.9.2 produce byte-identical locks matching the earlier candidate; semantic/supply-chain fingerprints match. Both audits/provenance/lock validators PASS; review-required dossier has exact source/manifest/toolchain binding.

Full attempt 1 exhausted default 128M. A bounded fresh-fixture retry at 512M and CI APP_URL completed 855 tests: 845 PASS, 10 FAIL, 7304 assertions. Nine failures report missing libargon2.so.1 when tests clear the rootless PHP child environment; one reports the workflow excluded by git archive's /.github export-ignore. Both failures are preserved; tests/assertions unchanged. Full suite is not green. Evidence: docs/theme-security-reproduction-002.md and .ai/evidence/theme-security-reproduction-002.{json,tar.gz}.

Live intake for 28bb3e1: checkout QA/Windows/traceability PASS; governance job 140792504014 FAIL, still 25 Composer advisories. Root locks unchanged; no promotion/merge/release/target acceptance. Next: fix the disposable checkout/PHP-runner fidelity, then unchanged full regression; independent authenticated review and governed root promotion/fresh exact-head CI remain required. #74 actual target stays blocked. Earlier checkpoint blockers about missing A/B proof are superseded only for that measured gate.

# Security candidate checkpoint — 2026-11-28

PR #152, stage THEME-CLOSURE-001, source 15967f1f3550a263cb1bf0ca2e557230b8e1a682; observed main 8c0a2d7bdf8e4df8e98b21e3c0d7ba4d3518e4eb. Governance run 46084084434/job 140789486157 FAIL: 25 Composer advisories across 8 packages before PHPUnit. Checkout QA was still running at the single intake snapshot; Windows/traceability passed. No workflow rerun or repeated status refresh.

Registered SEC-THEME-CI-DEPS-001 before isolated candidate preparation. Rootless PHP 8.4.28 + Composer 2.10.3 now available. Exactly the eight affected libraries updated in an isolated candidate: Composer/npm audit zero vulnerabilities, candidate provenance/lock validation PASS, focused theme/passkey/TOTP tests 33/33 with 233 assertions PASS on git-archived source plus candidate lock. No root lock promotion, independent approval or target acceptance. Candidate dossier and exact archived locks/raw test evidence: docs/theme-security-candidate-20261128.md and .ai/evidence/theme-security-candidate-20261128.{json,tar.gz}.

Milestone BLOCKED on canonical-toolchain A/B dossier, independent review (CodeRabbit login environment_unsupported), governed promotion and fresh full exact-head CI. Root dependencies remain vulnerable; #74 target remains blocked. Next: complete candidate review/promotion gates; do not advance to extensions. Earlier checkpoints are historical observations.

# Theme lifecycle review follow-up — 2026-11-28

PR #152 / THEME-CLOSURE-001 / SYS-THEME-ENGINE remains VERIFYING. Main is `8c0a2d7bdf8e4df8e98b21e3c0d7ba4d3518e4eb`; pre-follow-up head `7137a29b6487911fd5716ce451aa5d0930128174` has successful governance, Development checkout QA, Windows PowerShell bootstrap QA and PR-to-unit traceability checks. Those results do not certify this follow-up.

The authorization fixture now explicitly activates the uploaded Alpha theme and asserts its exact version before denied mutations, then checks public rendering afterwards. Progress/state mirrors are reconciled without advancing the stage. Local PHP is unavailable; no local PHPUnit PASS is claimed. Fresh exact-head CI and independent review are required. Target/browser acceptance remains blocked under #74; no target is provisioned. Dependency PRs #160/#161/#162/#165/#166/#167/#169/#170/#171 have failed governance checks (#166 also failed Development checkout QA); #143 is green but not reviewed or reconciled with current main. None was merged.

Next: validate the published #152 head in CI and independent review, resolve findings, and retain the target gate. EXTENSION-CLOSURE-001 remains PLANNED.

# Checkpoint — SECURITY-BASELINE-200 source-complete — 2026-10-23

Main 82bd8cb. Merged: #142 (deps), #145 (CSP, route authorization matrix, committed-secret scan, vitest 5.0.5), #146 (passkeys/WebAuthn; web-auth/webauthn-lib owner-approved). Each had green exact-head CI and an independent AI review (separate context; not a human review). Next: AI-GOV-AUTOMATION-100 (needs owner approval). Target acceptance (#74) unchanged, not claimed.

# Checkpoint — single execution flow — 2026-10-18

Main a197149 (#140 TOTP MFA merged; CI green). Current stage SECURITY-BASELINE-200 / unit SEC-BASELINE. The plan is aligned to one ordered flow (`.ai/roadmap/stages.md` → "Execution flow"). Next: owner picks the next security increment, or approves AI-GOV-AUTOMATION-100 traceability. MOBILE-APPS-100 is on hold. No target acceptance is claimed.

# PR #71 clean replacement source preparation — 2026-10-01

Base main 55c249c06621fe7dc14868b3a5740b657bcb6b7a. Owner requested starting the clean replacement. Eight new backend regressions / 59 assertions PASS. Original unchanged suite diagnostic FAILED (61 schema errors, 5 failures, 6 incomplete). First full replacement run had a toolchain fixture failure: inherited NPM_CONFIG_CACHE pointed at a nonexistent directory; actual writable disposable cache configured, assertions unchanged. Corrected combined backend run PASS: 488 tests / 4663 assertions. Source certification and remote exact-head CI remain separate gates. New suite covers implemented auth/admin/module-read boundaries only. Original 76 requirements remain explicitly inventoried for contract mapping; no global Core QA/target or historical key invalidation completion. Original #71 stays quarantined. Evidence: docs/pr71-clean-replacement.md. Earlier entries are historical.

# Nine-point evidence reconciliation — 2026-09-30

Protected main `3439ac7e324e867046674044ba4ad1586bd963af` after #105: exact-main certification `36742999910` SUCCESS; #105 release/development QA SUCCESS; 480 backend tests / 4607 assertions + 6 frontend tests PASS. Exact published #105 head disposable installation, readiness, CLI↔web proof, recovery, login/admin and drift-negative checks PASS. #87 closed NOT_PLANNED per owner approval-rule removal; no independent-review implementation claimed. Current #102 plan is reconciled against completed source work. #72 historical key and #74 actual target acceptance remain OPEN; global runtime/release stage BLOCKED and Core QA locked. Nine-point acceptance table: `docs/nine-point-closure-batch.md`. Earlier checkpoints are historical. Final #102 CI pending at preparation; later results belong on PR/Issue to avoid status-only head churn.

# Stable runtime / auth throttle checkpoint — 2026-09-30

Main `186138b0f7b7ca0da1ce3b2bb37b051edc75c855`: #104 merged and exact-main certification 36735105784 SUCCESS. Prior service mismatch reproduced by proxy-port drift only; stable disposable activation/recovery PASS. Fixed real public→auth throttle interference while preserving shared five/minute/IP auth limit. Regression red before patch; 10 tests / 40 assertions PASS afterwards. Fresh corrected candidate `5cfa5a6a54907e85d0cee8e90f04c38e486dd0a3`: source certification, real install, activation/web acknowledgement/CLI proof, governed recovery, public smoke, login 302/admin 200 and final readiness/current receipt PASS. Final published-source CI pending at preparation. Product target/release remains BLOCKED; no production/TLS/other-engine/key-rotation acceptance. Open #102 remains draft; #1 closed. Evidence: `docs/stable-runtime-verification-2026-09-30.md` and `.ai/evidence/stable-runtime-verification-2026-09-30.json`. Earlier checkpoints below remain historical.

# Source Batch Checkpoint — 2026-09-30

- Observed protected main after #100: `131addac09e888961989448e6415124dd3321215`.
- Last verified protected source baseline: `d8e83e87305bc8c11d8b073b42f13cb69414c550`; push certification #1125 / 36689146406 completed SUCCESS.
- Integrated source work: environment-neutral toolchain discovery (#97), generic drive-rooted build-path detection (#98), upload-artifact action update (#94), Vite/Vitest lock update (#95), icon library update (#96), and Pint patch update (#99).
- PR #100 merged after exact head `2a95badaacc254981e478ea682ff0f12feee5cfa` passed certification #1126 / 36689540542. Resulting main is `131addac09e888961989448e6415124dd3321215`; its push CI is a separate pending check at preparation.
- User scope: source development on GitHub; no current installation path or live URL has been provisioned. Historical rc.93 repair evidence is retained as historical, not current-target truth.
- Runtime target acceptance remains BLOCKED. No current target/provider/browser evidence or credential rotation is claimed.
- Issue #87 remains open for repository-enforced independent security review. Issues #72/#74 retain provider/target acceptance requirements. PR #1 remains draft and must be reconciled by registered unit before promotion.
- Next action: verify resulting main and source/governance checkpoint certification. Continue authorized source work without assuming an existing target. Provisioning and target certification remain separate.
- Module and overall numeric progress are unavailable; no percentage is inferred.

## Historical checkpoint

# Last Checkpoint — TARGET-EVIDENCE-WAIT-008

The last verified source/governance baseline is `fde0667b150de551184ec5c5261a34f18538ceed`.

Verified source evidence:

- PR #82 exact head `5d727efe1ba2810c590e8a86ebc3f122a0c998cf` passed #980 / `35671637039` and merged under scoped admin waiver `WVR-NEX-PR82-MAIN-REVIEW-002`.
- Protected-main push #981 / `35672290666` completed SUCCESS.
- PR #83 final exact head `75d5436b0394f246bdca626571666abe77cd4012` passed #985 / `35673582569`, merged under scoped admin waiver `WVR-NEX-PR83-MAIN-REVIEW-001`, and resulting protected-main #986 / `35767459733` completed SUCCESS.
- No active source/governance carrier remains for this runtime-closure stage.
- PR #1 remains FUTURE_CARRIER_FROZEN.
- Issue #72 is source-complete and remains open only for real target/provider credential rotation.
- Issue #74 owns the remaining target-environment readiness/identity/login evidence.

The source audit confirms `npm run runtime:recover -- --target="<operator-provided-target-path>" --apply --confirm=RECOVER-RUNTIME` already performs final readiness/current receipt, exact target↔web one-time identity proof and authoritative same-origin `/login`, and writes fail-closed machine-readable evidence.

No fresh target-environment output after the accepted historical packet was found in prior conversation/file context. Therefore W03/W04/W07 remain genuinely WAITING_EXTERNAL_TARGET. CORE-QA stays locked.
