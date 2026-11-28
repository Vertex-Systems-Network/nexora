# ResearchBrief — SEC-PR152-SOURCE-INTEGRATION

## Problem

The existing source merge rule blocks PR #152 while #74 live target is not provisioned, despite substantial source verification. Original #152 also contains one actual PHPUnit warning despite successful job status.

## Users & VOC evidence

Owner approved the narrow proposal in-session on 2026-11-28: "I approve WVR-NEX-PR152-SOURCE-INTEGRATION-20261128 for the narrowly scoped source-only integration of PR #152 and its warning correction. Proceed with implementation and all remaining review and CI gates. Keep target acceptance blocked. No deployment or release." Proposal: https://github.com/Vertex-Systems-Network/nexora/pull/152#issuecomment-7231608400.

## Baseline

Protected main 8c0a2d7bdf8e4df8e98b21e3c0d7ba4d3518e4eb; original #152 3e7685c47aac888f6e56fad67c13b4e7f067d41e; warning-only successor eae4cc6560afaf362b7b157f0d5164816d56fc5f (856 tests / 7368 assertions, warning-hard zero PHPUnit warnings, locked audits zero, release 46089727462 / traceability 46089727450 SUCCESS). Prior #152 head warning preserved. Actual target unavailable; no independent review of combined scope.

## Alternatives

Wait for provisioned target: preserves all gates but delays source security fixes. Broad waiver or weakening tests/native rules: rejected. Use one owner-approved, independently reviewed, expiring exception solely to merge-time live-target prerequisite for exact #152 plus minimal warning cut: selected. Separate later #172 regression changes remain out of scope.

## CTQs

Exact base/head/tree, same promoted lock hashes, no additional runtime changes, independent combined-head review, fresh required CI and actual zero PHPUnit warnings, both audits zero, no source-to-target evidence promotion, no deployment/release, no stage advancement.

## Confidence

low — owner scope and prior CI establish the candidate, not new-head review or target assurance.

## Sources

https://github.com/Vertex-Systems-Network/nexora/pull/152#issuecomment-7231608400; PR #152 and #172 exact-head checks; AGENTS.md; existing scoped review waiver; #74; .ai/evidence/pr152-owner-promotion-20261128.json.
