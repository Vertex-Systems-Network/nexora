# Nine-point closure batch — current measured acceptance

Observed 2026-09-30. Verified protected source: `3439ac7e324e867046674044ba4ad1586bd963af`; resulting-main release certification `36742999910` SUCCESS. Source integration and scoped disposable execution are accepted below. The nine-point product/release batch is PARTIALLY VERIFIED, not complete.

| Point | Work | Verified evidence | Remaining acceptance |
|---|---|---|---|
| 1 | Approval-enforcement proposal (#87) | Owner explicitly removed mandatory approval; native review count 0 observed, strict governance/resolved-thread/deletion/non-fast-forward/code-quality rules active | Closed NOT_PLANNED under owner decision; no independent-approval implementation claimed |
| 2 | Historical exposed local application key (#72) | Quarantined PR #71 never merged; source controls and key lifecycle audited | Determine affected historical use; safe invalidation/rotation or accepted non-use evidence. OPEN; bootstrap-file deletion alone is insufficient |
| 3 | Disposable current-source installation | Fresh real SQLite migrations/seeding/admin/install lock committed on exact published #105 head | Full installer UI and other database engines not observed |
| 4 | Runtime readiness (#74) | Fresh final status pass, ready/runtime_ready/receipt_current true, errors empty | Disposable loopback target only; real deployment acceptance separate |
| 5 | CLI/web identity (#74) | Fresh one-time token web acknowledgement + independent exact-target CLI proof + governed recovery PASS | Live deployment identity separate |
| 6 | Login/authentication (#74) | Real cookie/CSRF POST 302 to /admin; authenticated GET 200; public smoke PASS; shared auth/public throttle bug fixed; proxy drift correctly rejected | Loopback HTTP is not live HTTPS/browser acceptance |
| 7 | Core functional QA | Exact source CI: 480 backend tests / 4607 assertions and 6 frontend tests PASS | Formal stage remains locked; this is source regression coverage, not complete Core target QA |
| 8 | Product draft reconciliation | All 38 measured conflicts resolved through merged #103; intended product/source/security controls preserved; #1 closed; #104/#105 verified fixes merged | DONE SOURCE-SIDE; not a whole-product runtime claim |
| 9 | Final release | Source/type/build/certification controls PASS on #105 and exact resulting main | Five-engine target matrix, applicable HA/provider/backup/restore/upgrade rehearsal, browser/AT/performance/accessibility and signed release evidence not accepted |

## Source and runtime evidence

- #103 source reconciliation merged to 84feada170f0529078984821f4a6706a830a1b1d; exact-main run 36720382700 SUCCESS.
- #104 real SQLite runtime-data provenance fix merged to 186138b0f7b7ca0da1ce3b2bb37b051edc75c855; exact-main run 36735105784 SUCCESS.
- #105 exact published head 670ecac569fe6572b7546236e728ffab38175a14: release run 36742228583 and development QA run 36742228213 SUCCESS. Runtime re-execution of this exact head passed install, activation, web acknowledgement, CLI proof, governed recovery, public smoke, actual login/admin and final readiness.
- #105 merged to `3439ac7e324e867046674044ba4ad1586bd963af`; resulting-main run `36742999910` SUCCESS. Canonical PR logs record 480 backend tests / 4607 assertions and 6 frontend tests; no skipped live target gate is included as PASS.
- Prior local service mismatch was reproduced by substituting only the original ephemeral proxy endpoint. Fresh stable local environment converges. Negative proxy drift fails closed and restoring the original environment restores readiness without changing the sealed lock.
- Actual public health traffic caused auth POST 429 in the old shared numeric throttle bucket. `auth:` now isolates public counters while preserving the combined five/minute/IP auth budget across login/register/password request/reset; negative limit regression remains PASS.
- Details: [source reconciliation](source-reconciliation-2026-09-30.md), [SQLite install proof](disposable-runtime-verification-2026-09-30.md), [stable runtime proof](stable-runtime-verification-2026-09-30.md), and PR #105 final-head evidence. Original failures remain historical observations, never edited into success.

## Execution and trust boundary

This reconciliation refreshes the previously stale draft #102 plan from current protected main. It does not add runtime code, change policy assertions, start Core QA, provision hosting, inspect/rotate historical keys or publish a release. No live installation URL/path is provided. Future target path is operator input `<operator-provided-target-path>`; disposable fixture paths are not canonical deployment identity.

Current source status SOURCE_DONE; live Target and Release acceptance remain BLOCKED. #72 and #74 stay OPEN. #87 is NOT_PLANNED, not remediated. Native approving count 0 reflects the owner decision; extra approval for unattributed changes remains true, and independent human review is not claimed.

Next safe work is accepted evidence for the actual target/key/release prerequisites, using existing governed tools and preserved security controls. Do not re-solve completed conflicts, request an imaginary external credential provider, repeatedly rerun green source CI, or promote later stages from local HTTP/source tests. A documentation-only plan does not complete the missing target work.

Rollback: ordinary revert of this evidence reconciliation; historical machine/test evidence remains retained.
