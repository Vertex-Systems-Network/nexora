# Phase 1 — Runtime hardening batch (2026-10-16)

PR: #122. Scope: Phase 1 source/runtime hardening; **not** hosted-target acceptance.

## Code changes

- Reuse of PASS install/build steps now binds to explicit Composer, Node and npm versions. Missing/changed versions fail closed to step reruns.
- The target runtime runner now starts commands with an argv array (no shell interpolation), drains stdout/stderr concurrently to avoid deadlocks, enforces a 16 MiB limit per stream, and terminates timed-out subprocesses.
- Target evidence directory-boundary PHPUnit fixtures now model the actual complete and ordered producer receipt, including execution modes and exact log SHA-256 fields. The previous one-step PASS fixtures were inconsistent with the security contract and caused failures at commit `2b0ce7b`.
- Five toolchain and five process-capture regression tests were added. Existing runtime receipt, log integrity, build and deployment fingerprints continue to be enforced.

## Verification bounds

Scoped local PHP syntax checks and 46 PHPUnit-shaped method bodies passed via a compatibility harness; native full-repository PHPUnit could not run in the limited local checkout. Historical GitHub CI at parent commit `2b0ce7b` reported failures for six release-suite assertions and one development-QA assertion in `TargetRuntimeEvidenceDirectoryBoundaryTest`; this batch updates those fixtures. Only CI executed on **this** new commit may establish whether those failures were resolved.

## Acceptance gates not yet closed

1. Independent authorized security reviewer signoff (not the developer/test author).
2. Approved confidential inventory and non-use/retirement evidence for the historically exposed APP_KEY, including backups/previous accepted keys where relevant.
3. Owner-approved actual hosted HTTPS target, same-host CLI-to-web identity handshake, readiness, and safe `/login` TLS verification.
4. Final PR merge/release acceptance, after required gates.

A clean source test, synthetic receipt or GitHub CI run does **not** substitute for these external target/operator decisions. Do not label Phase 1 `TARGET_VERIFIED`, `RUNTIME-CLOSURE-001` complete, or merge #122 until the above evidence exists.
