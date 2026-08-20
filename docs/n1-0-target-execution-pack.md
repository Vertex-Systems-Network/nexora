# Nexora N1.0 Target Execution Pack v2.2

This is not a seventh product/certification chunk. It is the operator execution layer for the already implemented C1-C6 certification model.

## Dependency preparation phase

The pack can now locate a trusted Composer executable or `composer.phar` under Laragon `bin/composer` without requiring a permanent PATH change. If lockfiles are missing or intentionally need refresh, run `scripts\n1-target-execution.bat --refresh-locks --confirm-refresh=REFRESH`. Lock generation uses Composer `--no-install` and npm `--package-lock-only`, writes a review dossier, and stops with `LOCK-REVIEW-REQUIRED`; it never creates reviewed-lock attestation automatically. Review the lockfile diff and explicitly run `scripts\dependency-lock-review.bat --accept --reviewer="REAL NAME" --confirm=REVIEWED`.

## Automated phase

Run `scripts\n1-target-execution.bat --install-deps --prepare-kits --operator="REAL NAME"`. The runner proves the exact source, then runs C1, C2 and C3 in order. It stops on the first blocker. It never accepts dependency locks, runs `composer update`, or owns destructive database commands directly.

If Laragon PHP extension remediation is required, run `scripts\n1-target-execution.bat --apply-extensions`, restart Laragon, then rerun the normal command.

## Operator phase

After C1-C3 PASS, the runner can create deterministic fail-closed C4/C5/C6 evidence kits under its run directory. Complete those kits only after real fresh-install/upgrade/restore, browser/accessibility/Web Vitals, and 2+ node HA observations.

Then run `scripts\n1-target-execution.bat --base-url=https://TARGET --operator="REAL NAME" --c4-evidence=... --c5-evidence=... --c6-evidence=...`. C4, C5 and C6 execute in strict order; C6 owns final evidence aggregation and production packaging.

Exit 0 means final N1.0 production closure passed. Exit 1 means a required gate failed. Exit 2 means an operator action, lock review, or Laragon restart is required.


## One-file support capsule

Every master target run now writes `storage/app/nexora/n1-target-execution/latest-support.json` plus a SHA-256 sidecar. The capsule is intentionally plain JSON so it still works when the PHP `zip` extension is the first C1 blocker. It contains the exact source/run status, required PHP extension states, Composer/Node/npm availability, dependency lock hashes, first-blocker logs (bounded), and the latest prerequisite/remediation/lock-refresh reports. It never dumps `.env` or ambient environment variables; password/token/cookie/API-key shaped values and project/home paths are redacted. Upload this single JSON file when a real target run blocks. `php scripts/n1-target-support-capsule.php` can regenerate it from the latest run.


## v2.2 three-block execution handoff

Target Execution Pack v2.2 adds three coordinated operational controls without creating a new roadmap chunk:

1. **Restart verification ticket** — a reviewed php.ini remediation that changes extensions writes a source-bound restart ticket. The next target run verifies the same PHP binary/php.ini digest and required extensions before continuing.
2. **Reviewed-lock handoff** — `--review-locks --reviewer=<name> --confirm-review=REVIEWED` accepts only the exact lock hashes from the latest successful lock-refresh handoff and may continue into C1 in the same target run. Refresh and review can never occur in the same run.
3. **C1-C3 resumable execution** — `--resume-latest` revalidates C1/C2/C3 evidence against the exact current source, reviewed locks, installed graph and matrix evidence. Valid chunks are reused; stale or missing chunks rerun in dependency order.

Recommended target continuation after a reviewed lock refresh:

```bat
scripts\n1-target-execution.bat --review-locks --reviewer="YOUR NAME" --confirm-review=REVIEWED --install-deps --resume-latest --prepare-kits --operator="YOUR NAME"
```
