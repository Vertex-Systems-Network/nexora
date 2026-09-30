# Source reconciliation evidence — 2026-09-30

Status: LOCAL SOURCE VERIFIED; remote CI, independent review and target acceptance pending. This is not a product-complete or deployment-complete claim.

## Source inputs and implemented decisions

- Protected main: `9d1c89eed4fc67d4e1a3798992d36d5a056e3ca5`.
- Draft #1 input: `f6629d010626bb52be16fd9c3257f41b656be748`.
- Work isolated on `codex/product-source-reconciliation`; existing dirty audit checkout preserved.
- 38 conflict paths resolved individually, preserving both source histories.
- Main release workflow, runtime heartbeat fencing, HA acceptance thresholds and canonical control-plane records retained. Draft guest-route fencing bypass and relaxed HA thresholds rejected.
- Draft product additions reconciled with main token redaction, installation controls, queue contracts and source identity controls. Newsletter job uses Laravel Foundation Queueable without duplicate Dispatchable composition.
- Critical 37-file manifest and installer bindings regenerated for reconciled source.
- Environment-neutral tooling retained; no contiguous legacy vendor references found in tracked source scan. Historical Git commits remain intact.
- Supplemental development workflow has a distinct check name, pinned action SHAs and npm ci; canonical governance context remains solely in release workflow.
- Failing feature fixtures repaired: three HTTP suites now migrate their database; HSTS test uses an HTTPS request URL; fake webhook uses a public literal IP so destination-policy enforcement remains active without external DNS.

## Executed local evidence

| Check | Observed result |
|---|---|
| PHP syntax | 1,323 source files, zero failures |
| Unit + Architecture | 288 tests PASS; 3,378 assertions |
| Feature | 183 tests PASS; 976 assertions |
| Integration + Security + Compatibility | 6 tests PASS; 228 assertions |
| Frontend Vitest | 2 files / 6 tests PASS |
| TypeScript + Vite build | PASS (`npm run build:raw`) |
| Source certification | SOURCE PASS (`certify-release.php --source-only`) |
| Development QA contracts / hygiene / source guard | PASS |
| Conflict markers / whitespace | No unresolved merge paths; diff check PASS |

Toolchain: PHP 8.3.6, Composer 2.10.3, Node 22.14.0, npm 10.9.2. Composer/Node archives verified against official checksums. Dependencies installed from existing locks. Backend execution uses disposable SQLite in-memory databases, array sessions/cache and a public test APP_KEY. An inherited missing npm cache directory was created in the execution environment; the source test was not weakened. Child PHP processes require PHPRC for the extracted toolchain.

Machine-readable local results: `docs/evidence/source-reconciliation-2026-09-30/`. These results describe the source content tested before this evidence-only commit; final GitHub SHA and remote CI must be checked separately.

## Still required

1. Exact-head GitHub checks, including canonical governance and MySQL execution. Local SQLite does not certify MySQL, every database family, browser journeys or every deployment environment.
2. Independent review on the latest push; repository native rules require one approval, dismiss stale approvals and require last-push approval. No bypass or self-approval.
3. Current target provisioning, CLI/web identity, readiness, real browser/HTTPS and release acceptance. User currently has no hosting target.
4. Issue #72: exposed artifact was a local bootstrap key, not an external provider token. Installer may persist its effective APP_KEY before deleting bootstrap.key; lifecycle/invalidation/non-use must be established before closure.
5. Issues #74/#87 and formal stage promotion require their actual acceptance evidence. Old draft #1 is not blindly promoted; this branch is the reconciled proposal.

No percentage or whole-product completion is inferred from these checks.
