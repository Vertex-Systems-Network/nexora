# Issue #52 — AI Lock Review / Promotion Exception

Status: **ACTIVE — ONE-TIME ADMIN-AUTHORIZED EXCEPTION**

Date: 2026-09-10
Repository: `Vertex-Systems-Network/nexora`
Coordination: Issue #52 / PR #55

## Authority and provenance

The repository administrator operating as GitHub account `wpessential` explicitly directed that the remaining dependency-lock inspection/attestation for Issue #52 be performed by AI so development can continue while the normal human reviewer is unavailable.

This document records that governance decision. It does **not** claim that a human inspected either lockfile, and no `HUMAN LOCK REVIEW PASS` attestation may be emitted for this exception.

The review actor for this one-time exception is recorded as:

`AI:GPT-5.6-Sol@ChatGPT`

The AI review is a distinct dependency-lock review pass over the governed artifact and objective machine evidence. Because the same autonomous development system has participated elsewhere in this workstream, **independent approval is not claimed**. The repository-admin directive is the explicit scoped waiver/approval authority for using this AI review in place of the previously requested human lock review for the exact bytes below.

This is not a global weakening of Nexora review policy. `AGENTS.md` and `.ai/governance/ai-development-orchestration.md` remain authoritative for all other work.

## Exact review target

- Clean dependency source used to generate proof: `c9531d7b3571062ec932b8999d9cf77c54cd7f1f`
- Governed proof head: `73b45bfde0e339c223f9c1b250e79ed391ecc936`
- Governed proof workflow: `34372657034` — SUCCESS
- Diagnostic release certification: `34372656788` — SUCCESS
- Governed artifact ID: `10112569527`
- Artifact digest: `sha256:c70d0414f5c2ece565d1906e0bf3b6bd0993e41c29758e65f7e47f832e1cfc3e`
- Candidate `composer.lock` SHA-256: `a96e562048532b4d9773877cea6c8dc0dd2a1adff52c25b7481493402beeced8`
- Candidate `package-lock.json` SHA-256: `ad3e1dd0300ef0796865fa78ed63e547090940360925efd8939f43966f3ed804`
- Candidate source-tree attestation: `bfb821dc65afc1bcd8bce2ed2209c91871618866c2e18f8aa07481ad7ccae45d`
- Candidate toolchain fingerprint: `8d713101071ed36ab1dc851e293019a03a89edd8dae5e40a2d0a002e0f6c5143`
- Candidate supply-chain fingerprint: `8bf2ddabaa59d25adbca3d36743eedb4612d9edf6904bd6faeb810df0021325b`

Adding this `.ai/**` governance record does not alter the source-tree attestation roots defined by `scripts/lib/source-attestation.php`; source/runtime/manifests used by the candidate remain unchanged. Any material source/manifests/toolchain mutation still invalidates the candidate through existing fail-closed checks.

## AI lockfile inspection performed

The governed artifact was downloaded and both candidate lockfiles were inspected directly.

### Exact-byte checks

- `composer.lock` SHA-256 matches the governed expected value exactly.
- `package-lock.json` SHA-256 matches the governed expected value exactly.
- Candidate metadata reports A/B raw lock hashes equal and A/B semantic hashes equal.
- Candidate status is `review-required`, `reproducible=true`, and reproduction `exact_match=true`.

### npm review

- Root `dependencies` and `devDependencies` in `package-lock.json` exactly match `package.json`.
- Reviewed direct Vitest intent is preserved as exact `vitest: 5.0.0`.
- Locked Vitest resolves to `5.0.0`.
- Vite remains inside declared `^8.2.0` intent and resolves to `8.2.2`.
- All resolved npm artifact URLs inspected are from `registry.npmjs.org`.
- Non-registry/git/file/workspace/link package sources found: `0`.
- Resolved packages missing integrity metadata found: `0` (bundled/optional coverage remains separately machine-accounted).
- No `overrides`, `--force`, or `--legacy-peer-deps` policy bypass is introduced by the reviewed manifest/lock pair.
- The expected Rolldown native optional binding family is present, including Linux x64 GNU `@rolldown/binding-linux-x64-gnu@1.2.8`.

### Composer review

- `composer.lock` contains the declared Laravel 13 dependency closure and locks `laravel/framework` to `v13.31.0`, within the repository-certified `>=13.24.0 <14.0.0` range.
- Composer package source metadata is Git-backed with ZIP dist artifacts from GitHub/API GitHub; no local/path/file package source was found.
- Candidate provenance checked 220 Composer URLs with no provenance errors.
- Candidate Composer audit exit code is `0`.
- Licenses observed are declared package licenses including MIT/BSD/Apache and dual-license metadata on Nette packages; no license-policy failure was reported by the governed supply-chain proof.

### Governed machine evidence retained

- Candidate supply-chain status: `pass`.
- Candidate supply-chain exact match: `true`.
- Candidate supply-chain errors: none.
- Candidate supply-chain warnings: none.
- npm audit exit code: `0`.
- Composer audit exit code: `0`.
- Candidate validation errors: none.
- Candidate validation warnings: none.
- `npm_unsafe_sources=0`.
- `npm_integrity_missing=0`.

## One-time waiver boundary

This exception authorizes AI lockfile content review and AI reviewer identity only for the exact governed artifact and lock hashes above.

It does not authorize:

- claiming human review occurred;
- changing the candidate lock bytes after review;
- changing dependency manifests without refreshing proof;
- bypassing candidate reproducibility, provenance, audit, integrity, toolchain, strict-lock, review-attestation or exact-head release-certification checks;
- using `--force` or `--legacy-peer-deps`;
- weakening dependency or release verification scripts merely to obtain PASS;
- treating this exception as standing permission for future dependency changes;
- bypassing separate runtime-target evidence required by `RUNTIME-CLOSURE-001`.

The exception expires immediately if either reviewed lock hash, the dependency manifests, candidate source-tree attestation, candidate supply-chain fingerprint, or required promotion validation changes. It also expires after the exact reviewed lock pair is promoted and integrated into the Issue #52 dependency closure.

## Authorized next sequence

1. Record the superseding Issue #52 note that human lock review is replaced only for this exact artifact by the admin-authorized AI review above.
2. Promote the exact reviewed candidate through the existing `scripts/dependency-lock-promote.php` path using reviewer identity `AI:GPT-5.6-Sol@ChatGPT` and explicit `--confirm=PROMOTE-REVIEWED`.
3. Require promotion-time toolchain/source/manifest/candidate-hash/provenance/supply-chain/strict-lock/review-attestation verification to PASS without weakening.
4. Verify promoted root hashes equal the two reviewed SHA-256 values exactly.
5. Commit only the reviewed root lockfiles plus any already-authorized Issue #52 dependency closure changes/evidence required by the repository process; no runtime-recovery scope mixing.
6. Run fresh exact-head Nexora release certification on the lock-bearing PR #55 head.
7. Perform a fresh AI review pass on that exact lock-bearing head and record provenance honestly; no independent-human claim.
8. Require clean review/thread state and integrate #55 into PR #30 only after exact-head certification succeeds.
9. Re-run/rebase PR #51 only as required by the newly accepted dependency base; runtime closure remains a separate scope.

## Attestation language

Allowed exact-artifact AI attestation prefix:

`AI LOCK REVIEW PASS — reviewer=AI:GPT-5.6-Sol@ChatGPT`

Forbidden for this exception:

`HUMAN LOCK REVIEW PASS`

The AI attestation must include the exact two reviewed SHA-256 values and artifact ID `10112569527`.
