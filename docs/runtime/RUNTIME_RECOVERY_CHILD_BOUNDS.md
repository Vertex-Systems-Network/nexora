# Runtime Recovery Child Execution Bounds

Issue #46 hardens the recovery orchestrator's child-process boundary without changing runtime-stage authority or the recovery decision model.

## Accepted boundary

Every recovery child must execute through `scripts/runtime-recovery-child-process.php` with all of the following controls:

- a finite monotonic execution deadline;
- stdout and stderr captured to transient regular files rather than mutually blocking anonymous pipes;
- a hard retained-output byte limit applied independently to stdout and stderr;
- deterministic `timeout` and `output_limit` failure classifications;
- bounded soft termination followed by bounded forced termination when required;
- explicit `cleanup_complete` evidence before a failed child can be treated as contained;
- no raw command, provider, database, or unbounded child output in the bounded-control failure evidence.

The focused contract verifier proves normal exit preservation, termination of a synthetic hanging child before it can create a delayed marker, and fail-closed truncation of simultaneous stdout/stderr floods.

## Non-goals

This boundary does not authorize source upgrades, dependency changes, migrations, target-version changes, new runtime commands, relaxed target containment, alternate HTTP targets, TLS bypasses, or project-state promotion. Parent recovery PR #30 remains draft until its independent review and real-target readiness/identity gates are separately satisfied.
