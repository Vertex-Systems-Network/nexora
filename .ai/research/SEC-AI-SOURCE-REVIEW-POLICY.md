# ResearchBrief — SEC-AI-SOURCE-REVIEW-POLICY

## Problem

No independent reviewer is available for #152; authenticated GitHub actor and PR author are the same. All source CI passed on 87051c9c18fa9ae0ef6350f771b5910fab3fed0b. The owner explicitly removes the separate-review prerequisite for AI source work.

## Users & VOC evidence

Owner instruction: "reviewer wala ruleremove kr do jo krna ha AI ne khud krna ha". Previous reviewer intake: https://github.com/Vertex-Systems-Network/nexora/pull/152#issuecomment-7232142392.

## Baseline

https://github.com/Vertex-Systems-Network/nexora/pull/152#issuecomment-7232115142: 856 tests / 7369 assertions, zero PHPUnit warnings, locked audits zero, PHP 8.3 checkout, Windows, traceability and disposable browser QA PASS. Target #74 blocked. Native approving count 0, native protections unchanged.

## Alternatives

Wait for unavailable independent reviewer; remove all verification (rejected); explicit owner-directed source-only AI self-check policy with unchanged machine, native, domain and target gates (selected).

## CTQs

No fabricated independent approval; exact-head AI self-check with findings; no tests/workflows/locks/runtime change; preserve all existing CI/audits/target/provider/release controls; re-run CI on new head; source-only exception #152 remains bounded.

## Confidence

low — owner decision is authority, not independent assurance; machine checks required on new head.

## Sources

https://github.com/Vertex-Systems-Network/nexora/pull/152#issuecomment-7232115142; owner message; prior reviewer intake; AGENTS.md; existing waiver and current native rules.
