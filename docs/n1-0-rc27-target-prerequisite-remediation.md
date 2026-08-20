# N1.0 RC27 — Laragon Prerequisite Remediation

RC27 is a target-closure utility, not a new product feature. It turns the prerequisite failures isolated by RC26 into a reversible operator workflow without downloading software, changing global PATH, accepting dependency locks, or silently modifying PHP configuration.

## Commands

Review the active target and produce a remediation plan:

```bat
scripts\target-prerequisite-remediate.bat
```

On an explicitly detected Windows/Laragon target, after reviewing the plan and confirming the matching DLLs exist in the active PHP `extension_dir`, enable only the missing required PHP extensions:

```bat
scripts\target-prerequisite-remediate.bat --apply-extensions
```

The apply mode creates and SHA-256 verifies a timestamped backup of the active `php.ini`, stages the updated file, publishes it, verifies the published checksum, and then returns `restart_required`. Restart Laragon and open a new terminal before rerunning prerequisite intake.

If Composer exists inside Laragon but is not callable on PATH, RC27 may generate:

```text
storage/app/nexora/target-remediation/nexora-target-env.cmd
```

Calling that file changes PATH only for the current terminal session. RC27 never changes machine/user PATH.

## Non-goals

RC27 does not download PHP, extensions, Composer, Node, or npm packages. It does not run `composer update`, `npm install`, accept lockfiles, alter a system-wide PATH, or make a dependency-backed PASS claim. Missing DLLs require selecting/installing a trusted PHP build outside Nexora.
