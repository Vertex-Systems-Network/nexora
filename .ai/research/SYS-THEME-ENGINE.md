# ResearchBrief — SYS-THEME-ENGINE: theme lifecycle closure (THEME-CLOSURE-001)

## Problem

The stage requires the current theme lifecycle — upload → Sentinel → install → preview → activate → switch → rollback → render — to be proven end to end. `tests/Feature/Themes/ThemeEngineFlowTest.php` proves upload, scan, install and a single activation plus a database-level rollback, but not: switching between two uploaded themes, the preview page itself, what the public site renders after rollback, Sentinel blocking an unsafe theme, or authorization on lifecycle actions.

## Users & VOC evidence

- Users: site administrators installing third-party themes; visitors who must never see an unscanned or blocked theme.
- VOC: owner-authored requirement only — `.ai/roadmap/stages.md` row THEME-CLOSURE-001 and the registry acceptance criterion for SYS-THEME-ENGINE.

## Baseline

At base `8c0a2d7`: 4 theme feature tests; 0 tests open a preview URL, 0 switch between two uploaded themes, 0 assert rendered output after rollback, 0 upload an unsafe theme. No live-target evidence exists.

## Alternatives

- Browser/E2E tests on a live target: the correct final evidence, but no target is provisioned (RUNTIME-TARGET-ACCEPTANCE, #74). Deferred, not replaced.
- Extend the existing test file: would grow one test into a long scenario. Rejected for a focused closure file.

## CTQs

1. Activating theme B after A renders B publicly and deactivates A; rollback renders A again.
2. A preview URL renders the candidate theme while the public site keeps the active theme.
3. A theme carrying executable PHP is blocked by Sentinel, never installed, and the operator is redirected to that scan's report.
4. A user without theme permissions cannot install, activate or roll back (403) and the active theme is unchanged.

## Confidence

`low` — source-level tests only; target/browser verification is still pending.

## Sources

- `app/Nexora/Themes/Services/ThemeManager.php`, `app/Http/Controllers/Admin/Appearance/ThemeController.php`, `tests/Feature/Themes/ThemeEngineFlowTest.php`.
- Counts above from reading the theme test files at `8c0a2d7`.
