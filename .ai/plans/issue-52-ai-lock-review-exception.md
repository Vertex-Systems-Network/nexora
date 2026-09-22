# Issue #52 — AI Lock Review / Promotion Exception

Status: **ACTIVE — ADMIN-AUTHORIZED AI REVIEW PROCESS / FRESH CANDIDATE REQUIRED**

Date: 2026-09-10
Repository: `Vertex-Systems-Network/nexora`
Coordination: Issue #52 / PR #55

## Authority and provenance

The repository administrator operating as GitHub account `wpessential` explicitly directed that dependency-lock inspection/attestation for Issue #52 be performed by AI while the normal human reviewer is unavailable.

Reviewer identity for this exception process:

`AI:GPT-5.6-Sol@ChatGPT`

No human lockfile inspection is claimed. `HUMAN LOCK REVIEW PASS` must not be emitted by this process. Because the same autonomous development system also participates in implementation/orchestration, **independent approval is not claimed**. The repository-admin directive is the explicit scoped authority for using an AI lockfile content-review pass for Issue #52.

This is not standing permission for future dependency changes and does not weaken `AGENTS.md`, `.ai/governance/ai-development-orchestration.md`, reproducibility, provenance, audit, strict-lock, exact-head certification, rollback, or runtime-target evidence requirements.

## Historical reviewed candidate — now stale by design

The first governed candidate was generated from clean dependency source `c9531d7b3571062ec932b8999d9cf77c54cd7f1f`:

- proof head `73b45bfde0e339c223f9c1b250e79ed391ecc936`;
- governed proof run `34372657034` — SUCCESS;
- diagnostic release certification `34372656788` — SUCCESS;
- artifact `10112569527`;
- artifact digest `sha256:c70d0414f5c2ece565d1906e0bf3b6bd0993e41c29758e65f7e47f832e1cfc3e`;
- composer lock `a96e562048532b4d9773877cea6c8dc0dd2a1adff52c25b7481493402beeced8`;
- npm lock `ad3e1dd0300ef0796865fa78ed63e547090940360925efd8939f43966f3ed804`;
- source-tree attestation `bfb821dc65afc1bcd8bce2ed2209c91871618866c2e18f8aa07481ad7ccae45d`;
- toolchain fingerprint `8d713101071ed36ab1dc851e293019a03a89edd8dae5e40a2d0a002e0f6c5143`;
- supply-chain fingerprint `8bf2ddabaa59d25adbca3d36743eedb4612d9edf6904bd6faeb810df0021325b`.

AI directly inspected both exact lockfiles and found: exact expected hashes, A/B raw + semantic reproducibility, npm root dependency parity, zero npm non-registry/link sources, zero missing integrity, Laravel `v13.31.0` inside certified range, GitHub/API-GitHub Composer provenance, and successful Composer/npm audits with no candidate errors/warnings.

The truthful attestation for that historical artifact was recorded on Issue #52 as:

`AI LOCK REVIEW PASS — reviewer=AI:GPT-5.6-Sol@ChatGPT`

That review is retained as evidence but is **no longer promotion authority** after the source-attested promotion-tool fix described below.

## Diagnostic promotion failure and root cause

Disposable PR #61 / workflow run `34420488580` attempted the existing fail-closed promotion path with exact PHP 8.4.25, Composer 2.10.3, Node 22.23.2 and npm 10.9.2.

The run successfully:

- verified exact checkout;
- downloaded artifact `10112569527` with its recorded artifact digest;
- verified both reviewed lock hashes and candidate metadata;
- reproduced the candidate toolchain fingerprint exactly;
- revalidated candidate supply-chain status/fingerprint.

Promotion then transiently copied the reviewed locks but correctly rolled back. Failure artifact `10130693672` proves `rollback_verified=true` and root lock hashes returned to absent/null.

Root cause: `scripts/dependency-lock-promote.php` generated a reviewed-candidate promotion handoff containing `candidate_reproducible=true`, while `scripts/dependency-lock-review.php --require-refresh-handoff` requires the canonical handoff key `reproducible=true`. Therefore review attestation could never complete even for a valid candidate.

## Narrow corrective change

PR #55 now fixes only that handoff mismatch:

- promotion handoff copies the candidate's verified reproducibility fact to canonical `reproducible`;
- retains `candidate_reproducible` for explicit provenance;
- confirmation help text says `authorized review` rather than falsely hard-coding a human-only statement;
- the existing reproducible-dependency contract now requires both promotion handoff markers and the authorized-review wording.

No review, reproducibility, toolchain, provenance, supply-chain, strict-lock, rollback, exact-hash, release-certification or target-runtime check is removed or relaxed.

Because `scripts/**` participates in `nexoraComputeSourceAttestation`, this corrective change intentionally invalidates the previous candidate source-tree digest. The old artifact must not be forced through promotion.

## Fresh-candidate AI review law

A new governed candidate must be generated from the corrected exact #55 source. It receives a new artifact ID, artifact digest, source-tree attestation, candidate lock hashes, toolchain fingerprint and supply-chain fingerprint.

The AI reviewer must then directly inspect the fresh artifact before any promotion attempt. At minimum verify:

1. artifact digest and candidate metadata identity;
2. exact SHA-256 of both candidate lockfiles;
3. A/B raw and semantic reproducibility;
4. manifest/root dependency parity;
5. direct dependency intent and resolved versions relevant to Issue #52;
6. npm resolved-source policy, link/git/file/workspace absence and integrity coverage;
7. Composer locked framework range, source/dist provenance and local/path source absence;
8. candidate validation, provenance and supply-chain errors/warnings;
9. npm and Composer audit results;
10. no force/legacy-peer-deps/override or verifier-bypass behavior.

Only after those checks may a new truthful line be emitted:

`AI LOCK REVIEW PASS — reviewer=AI:GPT-5.6-Sol@ChatGPT — composer.lock=<FRESH_SHA256> — package-lock.json=<FRESH_SHA256> — reviewed exact governed artifact <FRESH_ARTIFACT_ID>`

The line must never use a `HUMAN` prefix.

## Promotion gate after fresh review

The fresh reviewed candidate must be promoted through the existing corrected `scripts/dependency-lock-promote.php` using:

- reviewer `AI:GPT-5.6-Sol@ChatGPT`;
- explicit `--confirm=PROMOTE-REVIEWED`;
- the same toolchain fingerprint as candidate generation.

Promotion must independently PASS all existing source/manifests/candidate hashes/toolchain/provenance/supply-chain validation, promotion-time supply-chain revalidation, strict root-lock contract, reviewed-lock attestation, attestation re-verification and rollback protections.

Promoted root lock hashes must exactly equal the fresh reviewed candidate hashes.

## Exception boundary

This admin-authorized exception permits AI content review/attestation for Issue #52; it does not permit:

- fabricating human review;
- using the stale artifact after source-attested code changes;
- hand-editing generated lockfiles;
- changing dependency manifests without a fresh candidate;
- disabling, warning-only converting, skipping or weakening any dependency/release check to obtain PASS;
- using `--force` or `--legacy-peer-deps`;
- bypassing exact-head release certification;
- treating source/CI evidence as real-target runtime evidence;
- auto-advancing `RUNTIME-CLOSURE-001` or `CORE-QA-001`.

The exact-artifact authorization expires whenever reviewed lock hashes, manifests, source-tree attestation, toolchain or supply-chain fingerprint changes. A new candidate requires a new direct AI inspection and exact-artifact attestation. The Issue #52 exception process itself expires when the reviewed dependency closure is integrated into PR #30.

## Authorized next sequence

1. Generate a fresh governed A/B candidate from corrected #55 source in an isolated diagnostic workflow.
2. Require candidate reproducibility, supply-chain/audit, npm-ci replay, typecheck, tests, production build and build-verifier PASS.
3. Download the fresh artifact and perform the fresh direct AI lockfile review above.
4. Record the new exact-artifact AI attestation on Issue #52 with no human claim.
5. Run the corrected fail-closed promotion path in the exact candidate toolchain.
6. Verify exact promoted root lock hashes and reviewed-lock attestation.
7. Attach only the exact reviewed/promoted root lock bytes to #55; diagnostic workflows remain unmerged.
8. Require fresh exact-head Nexora release certification on the lock-bearing #55 head.
9. Perform a fresh exact-head AI review pass and require clean review threads; independent-human approval is not claimed.
10. Integrate #55 into PR #30 only after those exact-head gates pass.
11. Refresh/rebase/re-run #51 only as required by the accepted dependency base. Runtime closure remains separate scope.
