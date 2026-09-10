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
- Fresh protected-main baseline reconciled into carrier: `e19d6fa818a7eebaf293d34bd87cff79ebc90ade`
- Integrated #30 source head before this status sync: `68d6aa5d8610e05acf7c51bed503ae09bd4311cd`
- Issue #52 dependency closure: integrated; Issue #52 closed after #51 exact-head rerun passed
- Issue #46 bounded-child source fix: integrated into #30 via PR #51

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

**BLOCKED pending final real-target readiness + exact target-to-web `/login` evidence.**

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

The historical rc.93 dependency runtime fingerprint was compatible. The active #30 source branch now carries the governed reviewed lock pair (`composer.lock=1e00ab9e4b63991260e20ae28f7c2f3e092da75425e27a474731c3ad8b86a198`, `package-lock.json=09c913a87f16b13c47020b2bf36aaf9068dbe50948402c9fdcd1bfd644090c75`) and Issue #52 is closed. That source-branch review evidence does not by itself prove the installed rc.93 target has consumed or resealed the new source dependency state, so target evidence remains separate.

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
2. fresh proof that the configured `app.url` web process belongs to this exact target;
3. `/login` evidence on that same proven origin without runtime/tenant/bootstrap failure.

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
→ every child bounded by finite deadline + per-stream output cap with deterministic termination/cleanup
→ deep compatibility
→ if compatible, skip identity repair
→ else only exact rc.93 four-plane adapter is eligible
→ independent compatibility re-check bound to child exit 0
→ readiness assertion bound to child exit 0
→ auto-reconcile only receipt-refresh-required + runtime_ready=true + receipt_current=false
→ final readiness re-check (source + deep deployment + compatibility + activation + sealed receipt)
→ resolve only target-owned bootstrapped config('app.url')
→ preflight existing /install/source-status with TLS verify + no redirects
→ issue fresh target-local one-time SourceActivationHandshake token
→ configured web origin must consume token and return acknowledged nonce
→ exact target CLI must reverify same nonce + source/runtime fingerprints with nexora:source:status --require-web-ack
→ only then perform /login GET on the same origin
→ unique non-overwriting machine-readable recovery receipt
→ PASS / BLOCKED / FAIL
```

Mutation still requires:

```text
--apply --confirm=RECOVER-RUNTIME
```

The one-time web acknowledgement token is held only in process memory/request headers. It is single-use and must never be written to the orchestrator receipt, public result, PR evidence, or logs.

### Hardenings completed before merge

Adversarial review found and closed these source risks:

1. JSON PASS could not override a non-zero child exit code.
2. Explicit non-200 `/login` is FAIL; only no-HTTP transport/TLS inability is BLOCKED.
3. Arbitrary `--base-url` override was removed.
4. Unsupported readiness states fail closed instead of being described as successful planning.
5. PR CI was checking a GitHub merge ref despite an “exact source” label; workflow now checks out the PR head SHA and explicitly asserts `git rev-parse HEAD == expected SHA`.
6. The runtime-recovery PHPUnit contract was not guaranteed to run in release CI; a dedicated required `runtime-recovery-orchestrator-contract-verify.php` gate plus PHP lint is now in the workflow.
7. Concurrent apply-mode writers could race one sealed target; apply mode now requires `flock(LOCK_EX | LOCK_NB)` on a target-owned `.apply.lock` and a second writer fails closed.
8. Timestamp-only final receipt names could overwrite same-second evidence; final recovery receipts now include a random unique identifier.
9. **`app.url` ownership did not prove HTTP server identity.** A stale/misconfigured `app.url` could point at another reachable Nexora deployment and a bare `/login` 200 could falsely certify the wrong web target. The orchestrator now requires a fresh target-local one-time CLI→web challenge, matching web acknowledgement nonce, and independent local `--require-web-ack` source/runtime verification before `/login` is authoritative.

The ninth hardening reuses existing Nexora trust primitives (`SourceActivationHandshake`, `/install/source-status`, `nexora:source:status --require-web-ack`). It adds no public endpoint, dependency, external destination, permission, migration, or product capability.

10. **Unbounded child execution could hold the target lock indefinitely or exhaust capture resources.** Issue #46 / PR #51 adds a finite 120-second default deadline, 256 KiB per-stream caps, lower-only test overrides, file-backed capture, deterministic termination/cleanup, bounded redaction, and behavioral timeout/output/lock-reuse verification. Exact #51 head `2e0736e4da1d5a89b6979e9171c672a1c0c32745` passed release certification #885 / `34424303763` and is integrated into #30.

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

No migrations, product modules, business features or roadmap stages are added. The separately governed Issue #52 dependency closure is now intentionally integrated into this source carrier with exact reviewed lock bytes; it does not widen runtime feature scope.

## Exact-head CI contract

For PR events the release-certification workflow must:

1. checkout `${{ github.event.pull_request.head.sha }}` rather than the generated merge ref;
2. assert the actual checkout SHA equals the expected PR head SHA;
3. PASS certification preflight;
4. PASS Source Guard;
5. lint the orchestrator + contract verifier;
6. PASS the dedicated Runtime Recovery control contract gate, including exact target-to-web challenge ordering/secret handling, single-writer serialization and unique receipts;
7. PASS unified source certification;
8. PASS frontend typecheck, Vitest and production build.

Any head change makes prior CI evidence stale.

## Independent review status

- Exact-head AI review exists for the accepted Issue #52 dependency closure and for PR #51 bounded-child source fix, with reviewer provenance recorded honestly.
- No human review or independent-human approval is claimed for those AI reviews.
- Because #51 integration and this status synchronization move PR #30's head, every older #30 exact-head review is stale.
- Before any PR #30 merge, perform a fresh review bound to the final exact head. If repository policy requires an independent actor/runtime beyond this AI context, that requirement remains a merge gate; absence of findings is not implicit approval.
- Source review never substitutes for fresh real-target readiness, exact CLI↔web identity, or `/login` evidence.

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
- preflight the configured source-status endpoint;
- issue and consume a fresh one-time target-local web identity challenge;
- independently verify the same acknowledgement through the exact target CLI;
- run `/login` only after that proof passes;
- write a unique recovery outcome receipt with no bearer token material.

If source-status or `/login` is blocked only by local TLS trust/client reachability while runtime readiness passes, preserve `BLOCKED`; do not disable TLS verification or point to another host to force green. If a reachable origin rejects or mismatches the exact challenge, that is `FAIL`, not `BLOCKED`.

## Next stage after genuine closure

Only after `RUNTIME-CLOSURE-001` becomes `TARGET_VERIFIED`:

`CORE-QA-001 — Super Admin + Core Application Functional QA`

Then canonical sequence remains:

`AI-GOV-AUTOMATION-100 → RESEARCH-DISCOVERY-100 → QUALITY-GOVERNANCE-100 → ADMIN-UX-CLOSURE-001 → SECURITY-BASELINE-200 → ARCH-BOUNDARY-100`.

## Completion warning

Do not infer final target PASS from successful repair, compatibility output, reconcile output, source CI, `app.url`, a bare `/login` 200, an orchestrator receipt, or self-review. Final readiness/current-receipt + fresh exact target-to-web proof + `/login` evidence remain mandatory for the active stage, and PR #30 still requires independent exact-head review before merge.
