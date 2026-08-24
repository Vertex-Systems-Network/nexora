# Nexora Current AI Handoff

## Resume instruction

Always begin with `AGENTS.md`, `.ai/state.json`, this handoff, the canonical roadmap/registries, and `.ai/plans/active.md`. Then inspect current `main` HEAD, open PRs, exact active unit and exact target evidence before implementation.

## Current source context

- Canonical branch: `main`
- Baseline at start of current orchestration pass: `dffb238e655a1c474f4f7ce7e75c6eda004c0c32`
- Documented source release: `1.0.0-rc.94`
- Installer protocol: `v5.29`
- Source generation: `n1-v5.29`
- Control-plane revision: `7`
- Canonical stage count: `75`
- Existing canonical rc.93 repair tooling originated in PR #26
- Current source-work carrier: PR #30, `feat/runtime-recovery-orchestrator`

Historical SHAs are evidence references. Always re-read current HEAD before relying on this handoff.

## Governance invariants

- No future stage starts before the active stage is genuinely accepted.
- Source/CI evidence and real-target evidence remain separate.
- Existing defects use DMAIC/control evidence; high/critical work keeps FMEA/threat controls.
- AI-authored PASS prose or a self-authored receipt is not runtime proof.
- Critical changes require exact-head independent review in addition to authoring tests/CI.
- No test/security/governance weakening merely to obtain PASS.
- Dependency, migration, permission, network, secret, trust-boundary or destructive scope deltas require re-plan before implementation.
- System Graph evidence classes remain distinct; static/inferred evidence is never runtime truth.

## Active stage

`RUNTIME-CLOSURE-001 — Installation + Runtime Closure`

Active unit:

`SYS-RUNTIME-IDENTITY`

Status:

**BLOCKED pending final real-target readiness + `/login` evidence.**

Do not start `CORE-QA-001` yet.

## Real target

```text
D:\laragon\www\nexora
installed release: 1.0.0-rc.93
```

## Live target evidence received on 2026-08-25

### rc.93 repair dry-run — PASS

- exact running/installed `1.0.0-rc.93`;
- only `activation`, `environment`, `process`, `service` mismatched;
- dry-run performed no mutation.

### rc.93 bounded repair apply — PASS

- only the four approved planes repaired;
- sealed installation lock changed from SHA-256 `5db5fbf7f33a5d901146544463547800a114acad80bb62053d17c7e88e069d88` to `1576c4212323c11dbb591a45a27988afa6e5c55a62982808af738e918828022a`;
- protected backup + repair receipt paths emitted;
- mutation completed through the approved bounded repair path.

### Independent deep compatibility — PASS

```text
status=pass
mismatches=[]
runtime.compatible=true
runtime.mode=installed-data-plane
deployment_drift.status=pass
```

Environment, activation, service and process now match. Immutable/source/deployment/framework/data-plane/storage/host/resource/policy/dependency compatibility remained PASS.

The dependency runtime fingerprint is compatible. `reviewed dependency-lock attestation = missing` remains a separate release/dependency-governance item and must not be confused with the closed four-plane identity mismatch.

### Post-install handoff

First readiness assertion correctly reported:

```text
status=receipt-refresh-required
ready=false
runtime_ready=true
receipt_current=false
```

The operator then ran:

```text
php artisan nexora:runtime:post-install-reconcile --confirm=RECONCILE
```

and received `status=pass` with a new receipt bound to the repaired installation lock.

Still missing for current-stage acceptance:

1. a **fresh** `php artisan nexora:runtime:post-install-status --assert-ready` after that reconcile, proving `status=pass`, `ready=true`, `runtime_ready=true`, `receipt_current=true`, `errors=[]`;
2. `/login` target evidence without runtime/tenant/bootstrap failure.

## Runtime Recovery / Closure Orchestrator — current source work

PR #30 closes the observed operator-control gap without introducing a new roadmap stage or upgrade engine.

Intended operator command after source acceptance:

```bat
npm run runtime:recover -- --target="D:\laragon\www\nexora" --apply --confirm=RECOVER-RUNTIME
```

Current fail-closed contract:

```text
explicit target
→ apply-mode single-writer target lock
→ deep compatibility
→ if compatible, skip identity repair
→ else only exact rc.93 four-plane adapter is eligible
→ independent compatibility re-check bound to child exit 0
→ readiness assertion bound to child exit 0
→ auto-reconcile only receipt-refresh-required + runtime_ready=true + receipt_current=false
→ final readiness re-check
→ resolve only target-owned bootstrapped config('app.url')
→ verified TLS /login GET with redirects disabled
→ unique non-overwriting machine-readable recovery receipt
→ PASS / BLOCKED / FAIL
```

Mutation still requires:

```text
--apply --confirm=RECOVER-RUNTIME
```

### Hardenings completed before merge

Adversarial review found and closed these source risks:

1. JSON PASS could not override a non-zero child exit code.
2. Explicit non-200 `/login` is FAIL; only no-HTTP transport/TLS inability is BLOCKED.
3. Arbitrary `--base-url` override was removed; an unrelated host cannot satisfy target certification.
4. Unsupported readiness states fail closed instead of being described as successful planning.
5. PR CI was checking a GitHub merge ref despite an “exact source” label; workflow now checks out the PR head SHA and explicitly asserts `git rev-parse HEAD == expected SHA`.
6. The new runtime-recovery PHPUnit contract was not guaranteed to run in release CI; a dedicated required `runtime-recovery-orchestrator-contract-verify.php` gate plus PHP lint is now in the workflow.
7. Concurrent apply-mode writers could race one sealed target; apply mode now requires `flock(LOCK_EX | LOCK_NB)` on a target-owned `.apply.lock` and a second writer fails closed.
8. Timestamp-only final receipt names could overwrite same-second evidence; final recovery receipts now include a random unique identifier.

Canonical PR #30 files include:

- `scripts/runtime-recovery-orchestrator.php`
- `scripts/runtime-recovery-orchestrator-contract-verify.php`
- `tests/Unit/Certification/RuntimeRecoveryOrchestratorTest.php`
- `docs/runtime/RUNTIME_RECOVERY_ORCHESTRATOR.md`
- `package.json` → `runtime:recover`
- `.github/workflows/release-certification.yml`
- `.ai/state.json`
- `.ai/plans/active.md`
- this handoff

No migrations, dependency versions, product modules, business features or roadmap stages are added.

## Exact-head CI contract

For PR events the release-certification workflow must:

1. checkout `${{ github.event.pull_request.head.sha }}` rather than the generated merge ref;
2. assert the actual checkout SHA equals the expected PR head SHA;
3. PASS certification preflight;
4. PASS Source Guard;
5. lint the orchestrator + contract verifier;
6. PASS the dedicated Runtime Recovery control contract gate, including single-writer serialization and unique receipts;
7. PASS unified source certification;
8. PASS frontend typecheck, Vitest and production build.

Any head change makes prior CI evidence stale.

## Independent review status

Independent exact-head review remains mandatory because this is a critical recovery-control change.

Attempts made so far:

- CodeRabbit review comments were posted on earlier heads but no CodeRabbit review was returned.
- Local CodeRabbit/Fallow execution was unavailable in the current environment.
- GitHub Copilot reviewer `copilot-pull-request-reviewer[bot]` was requested, but GitHub did not persist a requested reviewer or create a review object for this repository/account.

Absence of review comments is **not** a review PASS. The authoring agent must not self-approve and call that independent evidence.

## Current source-work acceptance requirements

Before merging PR #30:

1. final hardened head exact-checkout identity PASS;
2. certification preflight PASS;
3. Source Guard PASS;
4. Runtime Recovery control contract + lint PASS;
5. unified source certification PASS;
6. frontend dependency/type/test/build compatibility PASS;
7. exact-head independent review for the critical recovery-control change;
8. no scope expansion outside `SYS-RUNTIME-IDENTITY`.

Source merge does **not** advance the runtime stage by itself.

## Exact target continuation after source closure

Because this target is already repaired and reconciled, the orchestrator should:

- acquire the target single-writer apply lock;
- observe compatibility PASS;
- skip identity repair;
- assert final readiness;
- skip reconcile if the receipt is already current;
- run target-owned `/login` smoke;
- write a unique recovery outcome receipt.

If HTTP smoke is blocked only by local TLS trust/client reachability while runtime readiness passes, preserve `BLOCKED`; do not disable TLS verification or point to another host to force green.

## Next stage after genuine closure

Only after `RUNTIME-CLOSURE-001` becomes `TARGET_VERIFIED`:

`CORE-QA-001 — Super Admin + Core Application Functional QA`

Then canonical sequence remains:

`AI-GOV-AUTOMATION-100 → RESEARCH-DISCOVERY-100 → QUALITY-GOVERNANCE-100 → ADMIN-UX-CLOSURE-001 → SECURITY-BASELINE-200 → ARCH-BOUNDARY-100`.

## Completion warning

Do not infer final target PASS from successful repair, compatibility output, reconcile output, source CI, an orchestrator receipt, or self-review. Final readiness/current-receipt + target `/login` evidence remain mandatory for the active stage, and PR #30 still requires independent exact-head review before merge.
