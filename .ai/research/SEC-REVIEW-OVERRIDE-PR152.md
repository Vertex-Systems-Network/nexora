# ResearchBrief — SEC-REVIEW-OVERRIDE-PR152

## Problem

Current PR source promotion is blocked solely on unavailable independent-review identity/authentication after local candidate verification.

## Users & VOC evidence

The user explicitly directs: "koi ni ha ai hi ha rule ko overwrite kkro" in-session on 2026-11-28. This is authorization, not a fabricated review.

## Baseline

PR #152 head e1e5a9d; independent review absent; isolated candidate 855/855 tests/7363 assertions PASS; root audit still fails with 25 advisories.

## Alternatives

Continue waiting for a reviewer: rejected by owner. Remove all safeguards: rejected. A time-bound owner exception for this PR/candidate while preserving automated/source/target gates: selected.

## CTQs

Explicit owner authorization and scoped expiry; no invented independent approval; no weakened CI/audits/tests; guarded promotion with fresh validation; no target/release claims.

## Confidence

low — authorization and existing machine results establish scope, not equivalent independent review assurance.

## Sources

Current user instruction; AGENTS.md; .ai/evidence/theme-security-fixture-004.json; current GitHub PR #152 and checks.
