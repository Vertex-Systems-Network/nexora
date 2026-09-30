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
