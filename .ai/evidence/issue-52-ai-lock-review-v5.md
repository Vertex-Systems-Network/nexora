# Issue #52 — Fresh AI Lock Review v5

Status: **AI REVIEW PASS / PROMOTION NOT YET COMPLETE**

Date: 2026-09-10
Reviewer: `AI:GPT-5.6-Sol@ChatGPT`
Independent approval claimed: `false`
Human lockfile inspection claimed: `false`
Admin authorization: `.ai/plans/issue-52-ai-lock-review-exception.md`

## Exact governed artifact

- Corrected clean #55 source before diagnostic-only workflow: `76660332d2a9154dfd0ad94562948edbb595c149`
- Diagnostic proof PR: `#62` — workflow-only, do not merge
- Diagnostic proof head: `f1829d676fd0bf9ef807c3207c1654de530c2e37`
- Governed proof v5 run: `34420984941` — SUCCESS
- Fresh artifact ID: `10130890580`
- Artifact SHA-256: `ba92c2ce57b5e7590acaa26f3b24ccda0cbf646a5ab6743dac7f887c72942a97`
- Candidate metadata SHA-256: `242862cb8d4a879946f17fec16ff766b0c876e11f3da6e6871176eed8d62ef04`
- Candidate source-tree attestation: `ea18ce24741d7bd79ee618fe673e49f618672dabce5a38810dadfc5af42b2194`
- Candidate toolchain fingerprint: `8d713101071ed36ab1dc851e293019a03a89edd8dae5e40a2d0a002e0f6c5143`
- Candidate supply-chain fingerprint: `e48c62702769ee324a685b58654ed7fd6be0799c2a61a314be7c374835c71414`
- Candidate provenance fingerprint: `5b6f49200982734abbad61be4c7da5cc6d793b5fd2576af3389a957319df9441`
- Fresh `composer.lock` SHA-256: `1e00ab9e4b63991260e20ae28f7c2f3e092da75425e27a474731c3ad8b86a198`
- Fresh `package-lock.json` SHA-256: `09c913a87f16b13c47020b2bf36aaf9068dbe50948402c9fdcd1bfd644090c75`

The downloaded artifact ZIP independently hashes to the same GitHub artifact digest above.

## Machine proof checked

- candidate status `review-required`;
- `reproducible=true`;
- A/B `raw_exact_match=true`;
- A/B `semantic_exact_match=true`;
- A/B `exact_match=true`;
- A/B supply-chain fingerprints equal;
- candidate supply-chain status `pass`;
- candidate validation errors `[]`;
- candidate validation warnings `[]`;
- npm unsafe sources `0`;
- npm missing integrity `0`;
- Composer audit exit `0`;
- npm audit exit `0`;
- Composer provenance URLs checked `220`;
- npm provenance URLs checked `213`;
- Composer provenance hosts only `github.com` / `api.github.com`;
- npm provenance host only `registry.npmjs.org`;
- exact generated npm lock replay, typecheck, Vitest, production build and build verifier all PASS in run `34420984941`.

## Direct `package-lock.json` inspection

Root dependencies and devDependencies match the exact current `package.json` declarations.

Reviewed direct resolution highlights:

- `vitest`: declared exact `5.0.0`, locked `5.0.0`;
- `vite`: declared `^8.2.0`, locked `8.2.2`;
- `typescript`: declared `^7.0.2`, locked `7.0.2`;
- React: declared `^19.2.0`, locked `19.3.0`;
- React DOM: declared `^19.2.0`, locked `19.3.0`;
- `@vitejs/plugin-react`: declared `^6.0.5`, locked `6.1.1`;
- `laravel-vite-plugin`: declared `^3.1.0`, locked `3.2.0`.

All 213 resolved npm artifact URLs inspected are on `registry.npmjs.org`. No git/file/workspace/link package source was found. No resolved registry package lacked integrity metadata. The expected Rolldown native optional package family is retained, including Linux x64 GNU `@rolldown/binding-linux-x64-gnu@1.2.8`.

Compared with the earlier now-stale candidate, package names are unchanged: npm added package paths `0`, removed package paths `0`. Seven in-range versions moved during fresh registry resolution: React `19.2.8→19.3.0`, React DOM `19.2.8→19.3.0`, `@types/node 24.13.3→24.13.4`, `@types/react 19.2.18→19.3.0`, `@types/react-dom 19.2.7→19.3.0`, scheduler `0.27.0→0.28.0`, and `use-sync-external-store 1.6.0→1.7.0`. These are accepted only because they remain inside declared dependency intent and the fresh exact candidate passed the complete governed replay; the old lock hashes are not reused.

No root `overrides` key exists. No `--force` or `--legacy-peer-deps` bypass is part of the reviewed manifest/candidate generation path.

## Direct `composer.lock` inspection

- locked Composer package count: `110`;
- `laravel/framework`: `v13.31.0`, within declared `^13.24` / certified Laravel 13 boundary;
- every Composer package source type inspected is `git`;
- every Composer distribution type inspected is `zip`;
- source/dist hosts are only GitHub/API GitHub;
- local/path/file Composer package sources found: `0`.

Compared with the earlier now-stale candidate, Composer package names are unchanged and only `laravel/pint` moved from `v1.31.1` to `v1.32.0`, within declared `^1.27` intent.

License metadata observed is the same policy shape as the prior candidate: MIT/BSD/Apache plus dual-license Nette metadata. The governed candidate supply-chain proof reported no license/provenance failure.

## Review verdict

`AI LOCK REVIEW PASS — reviewer=AI:GPT-5.6-Sol@ChatGPT — composer.lock=1e00ab9e4b63991260e20ae28f7c2f3e092da75425e27a474731c3ad8b86a198 — package-lock.json=09c913a87f16b13c47020b2bf36aaf9068dbe50948402c9fdcd1bfd644090c75 — reviewed exact governed artifact 10130890580`

This is an AI content-review attestation under the one-time repository-admin authorization. It is not a human attestation and not independent approval.

## Next gate

Promotion must still execute fail-closed against this exact candidate/toolchain. The corrected promotion path must independently verify source-tree/manifests/toolchain/candidate hashes/provenance/supply-chain, re-run supply-chain checks, validate strict root locks, create and verify the reviewed-lock attestation, and preserve rollback on failure. Only after a successful promotion proof may these exact lock bytes be attached to #55 and freshly release-certified on the lock-bearing head.
