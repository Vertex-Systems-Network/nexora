# Nexora AI Engineering Supervisor Contract

## Authority and precedence

This contract is mandatory for AI-assisted engineering in `Vertex-Systems-Network/nexora`.

Repository/runtime evidence outranks chat memory. This file supplements the existing Nexora constitution and MUST NOT weaken stricter rules in `AGENTS.md`, canonical `.ai/**` governance, `ARCHITECTURE.md`, `SECURITY.md`, branch/review policy, release policy, or tool/platform safety requirements. When rules conflict, follow the stricter rule and record the conflict.

Concrete durable paths:

- Compact state path: `.ai/resume`
- Current state: `.ai/resume/CURRENT-STATE.yaml`
- Last checkpoint: `.ai/resume/LAST-CHECKPOINT.md`
- Rolling journal: `.ai/resume/EXECUTION-JOURNAL.md`
- Deterministic claims: `.ai/claims/DETERMINISTIC-CLAIMS.yaml`
- Coordination queue: `.ai/coordination/QUEUE.yaml`
- Runner Benchmark: `.ai/runner/RUNNER-BENCHMARK.yaml`

The compact resume layer is an index. It never overrides live GitHub, exact repository head, runtime/provider evidence, or canonical product state.

## Source of truth and resume sequence

On every start, continue, resume, interrupted session, tool failure, connector failure, or message-delivery timeout:

1. Read `.ai/resume/CURRENT-STATE.yaml`.
2. Read `.ai/resume/LAST-CHECKPOINT.md`.
3. Resolve the exact current default branch and exact main SHA.
4. Reconcile all accepted actionable OPEN Issues first.
5. Reconcile OPEN PRs/MRs second.
6. Re-read `.ai/claims/DETERMINISTIC-CLAIMS.yaml`, `.ai/coordination/QUEUE.yaml`, and `.ai/runner/RUNNER-BENCHMARK.yaml` after any merge or state transition.
7. Re-read canonical `.ai/state.json`, `.ai/handoff/current.md`, active plan, registries, and other required Nexora governance for the active work.
8. Read large historical checkpoints only when a specific historical fact, evidence item, or conflict requires them.
9. Never repeat a mutation merely because a prior chat response was not delivered. Verify repository evidence first.

## One user turn = one logical milestone

By default, one user “continue” or “resume” turn equals one bounded logical engineering milestone.

Valid examples include:

- reconcile and close one accepted PR;
- implement one coherent change and persist it;
- perform one exact-head verification/merge decision;
- reconcile durable shared state after a merge.

Do not chain a broad audit, multiple unrelated implementations, repeated CI polling, merge, post-merge audit, and unrelated next task into one turn. Security/incident work may contain tightly coupled actions only when splitting them would reduce safety.

## Remote-call and timeout budget

- Batch related read-only calls where supported.
- Read only files/status needed by the active milestone.
- Perform at most one consolidated CI/status refresh per milestone by default.
- Never tight-poll workflows, deployments, providers, runners, or status endpoints.
- Never repeatedly fetch unchanged workflow state while waiting.
- Never rerun a workflow merely because the UI/chat response timed out.
- Before final exact-head CI observation, persist the milestone as `VERIFYING` or `WAITING_EXTERNAL` when remote checks are expected.
- Do not create a later source-only commit merely to say CI is pending; that would invalidate the tested head.
- A second refresh in the same milestone is allowed only after a material security, merge, incident/recovery, or provider transition that requires it. Record the exception durably.

If CI is still running after the one consolidated refresh, preserve the waiting state, record available run IDs on PR/Issue status surfaces without changing the certified source head, report pending state, and end the milestone.

## Runner Benchmark

Every material remote/container/browser/runtime/full-regression/performance workload must be represented in `.ai/runner/RUNNER-BENCHMARK.yaml`.

Each task records:

- stable task ID;
- source Issue/PR/work package;
- command/workflow;
- exact source identity before execution evidence is accepted;
- environment/matrix/input/fixture identity;
- authorization state;
- security-critical classification;
- merge-blocking classification;
- expected runner time when known;
- deterministic dedup key;
- status;
- immutable terminal evidence.

A task may be registered before its exact source SHA exists only with status `REGISTERED` or `BLOCKED_BINDING`; it MUST bind an exact source SHA before `RUNNING`, `PASS`, `FAIL`, or any terminal evidence is accepted.

Safe non-blocking runner work defaults to a consolidated final batch. The following remain immediate: security-critical validation, exact-head merge-required checks, migration/auth/secrets/data-safety checks, current-change integration-safety checks, and incident/recovery checks.

Runner registration never grants execution authority. Consumed, expired, historical, destructive, provider, production, deployment, release, or formal-runtime authorization must never be inferred or silently reused.

## Durable state before reporting milestone status

Before saying a meaningful milestone is complete, blocked, verifying, or waiting, reconcile as applicable:

- `.ai/resume/CURRENT-STATE.yaml`;
- `.ai/resume/LAST-CHECKPOINT.md`;
- `.ai/resume/EXECUTION-JOURNAL.md` for meaningful transitions;
- `.ai/coordination/QUEUE.yaml` when coordination changes;
- `.ai/runner/RUNNER-BENCHMARK.yaml` when runner state changes;
- canonical Nexora state/handoff/plan only when their authoritative product truth actually changes.

`CURRENT-STATE.yaml` must contain at least:

- observed main SHA;
- active Issue;
- active PR;
- active branch;
- current milestone;
- milestone status;
- last completed milestone;
- exact next safe action;
- pending runner IDs;
- blocked runner IDs;
- current blockers;
- timeout-control settings.

If required durable state cannot be written, do not claim the milestone is fully complete.

## Compact state limits

Keep resume state intentionally small:

- `CURRENT-STATE.yaml` <= 12 KiB
- `LAST-CHECKPOINT.md` <= 16 KiB
- `EXECUTION-JOURNAL.md` <= 32 KiB

The journal is rolling. Archive older detail when needed. Large historical/checkpoint files are evidence, not mandatory every-turn input.

## Issues / PRs first — hard gate

New development is forbidden while an accepted actionable OPEN Issue or PR/MR is being bypassed.

An Issue already represented by an accepted PR is one work path. Finish/review/fix that PR instead of creating duplicate implementation.

Before new development:

`Compact State → Exact Main → OPEN Issues → OPEN PRs/MRs → Claims/Queue → Runner Benchmark → New Work`

Merge only dependency-safe, exact-head, review-clean work. Draft, red, stale, conflicted, target-evidence-gated, authorization-gated, or review-incomplete work is not “ready” merely because it is open.

## State drift

At every resume:

- compare compact state with live repository evidence;
- reconcile stale main SHA observations;
- reconcile merged/closed Issues and PRs;
- reconcile queue and Runner Benchmark statuses;
- inspect relevant commits since the recorded anchor;
- never leave a merged item marked `PENDING_MERGE`;
- never use chat memory to override repository evidence.

## Security — fail closed

Never:

- weaken authentication or authorization;
- weaken nonce/CSRF protections;
- weaken input validation or output escaping;
- disable security checks merely to obtain green CI;
- remove or weaken correct tests to make a build pass;
- weaken branch protection or required checks;
- force-push shared history;
- hard-code secrets;
- expose tokens or sensitive information;
- execute destructive operations without explicit current authority;
- execute provider/production/deployment/release work without explicit current authority;
- invent test results;
- mark deferred, skipped, pending, or running work PASS;
- reuse consumed or expired grants;
- interpret “continue” as permission for otherwise gated actions.

A timeout, connector failure, or missing chat context grants zero additional authority. When evidence conflicts, stop the affected action, reconcile repository truth, persist the conflict, and continue only from a safe verified state.

## Migrations and data safety

Migrations require explicit review for:

- idempotency;
- transaction boundaries where supported;
- apply-success / marker-write-failure recovery;
- retry behavior;
- rollback or restore path;
- destructive-operation recovery;
- concurrency;
- partial execution;
- backup/snapshot requirements.

Do not assume `apply()` followed by `markApplied()` is crash-safe. Destructive migration authority remains separate and explicit.

## README progress synchronization — mandatory milestone gate

Every material engineering milestone MUST update the fixed progress ledger in `README.md` before the milestone may be reported complete, blocked, verifying, or waiting.

The README progress sync is not optional housekeeping. It is part of milestone Definition of Done and must record the latest repository-backed state for the active work, including as applicable:

- observation date;
- active stage/unit and lifecycle status;
- active Issue/PR and exact head SHA;
- latest exact-head CI/run result;
- completed or newly proven change;
- current blocker;
- exact next safe action;
- current module and overall progress bars.

Rules:

- update only the compact `AI-Native Progress Ledger` / current-status area; do not churn the historical long-form README;
- never fabricate a percentage. If no canonical numeric metric exists, write `[??????????] N/A — canonical numeric metric unavailable`;
- record regressions and FAIL/WAITING states explicitly; README progress must not be success-only;
- pending/running/skipped work is never written as PASS;
- source, target, and release states remain separate;
- a status-only README update must be committed with the material milestone when practical, not as a later source-head-changing commit that invalidates already-certified code;
- when the material source head is intentionally frozen while CI is running, persist the live waiting evidence on the PR/Issue and update README in the next material source/governance commit; do not create a new code head solely to say CI is pending;
- governance/security/coordination milestones also update the ledger when they materially change engineering workflow, gates, or active status.

If the README ledger cannot be updated when required, report the milestone as incomplete and state why.

## CI and supply-chain security

Where applicable:

- pin third-party CI actions to immutable revisions;
- disable unnecessary credential persistence;
- use least-privilege workflow permissions;
- avoid dangerous `pull_request_target` execution without separately reviewed exception;
- enforce dependency-security gates;
- do not run untrusted lifecycle scripts during security lockfile generation unless explicitly reviewed;
- keep production/distributable dependency audits separate from development-tooling audits when appropriate.

## Progress reporting and mandatory user-facing footer

Every engineering status response for this repository must include, at the end:

- repository name;
- current module/stage;
- current module progress bar;
- overall progress bar.

Progress values must come from a current authoritative repository metric. Chat memory, stale PR prose, historical dashboards, intuition, or “rough estimates” must not be converted into a percentage.

If no authoritative numeric metric exists, use an explicit non-fabricated bar:

`[??????????] N/A — canonical numeric metric unavailable`

If a canonical percentage exists, render a ten-cell bar rounded down by decile, for example:

`[███████░░░] 76%`

The footer format is:

```text
Repository: Vertex-Systems-Network/nexora
Current module: <stage/unit>
Current module progress: <bar> | <status/basis>
Overall progress: <bar> | <status/basis>
```

The same response should also compactly report active/completed milestone, Issue/PR/commit evidence where available, CI state, blockers, and the exact next safe action. Never hide unfinished CI, review, state reconciliation, target evidence, or authorization behind a success statement.

## Recovery after message-delivery timeout

After a delivery timeout, do not assume the prior operation failed.

Immediately:

1. read compact durable state;
2. resolve exact current main;
3. inspect the previously active Issue/PR;
4. determine what actually persisted;
5. reconcile queue and Runner Benchmark;
6. continue only the next unfinished logical milestone.

Never redo a merge, deployment, destructive action, migration, provider call, or formal runtime execution solely because the previous response was not delivered.
