# N0.28 — Sentinel Supply-Chain Security & Stability

N0.28 extends Sentinel from static archive inspection into a software supply-chain trust layer while also closing installation/runtime issues found during real zero-install testing.

## Artifact identity and signatures

Nexora computes two SHA-256 values. `artifact_sha256` identifies the exact uploaded ZIP. `content_sha256` is a deterministic digest over sorted package entries and excludes only `nexora.signature.json`, avoiding a circular detached-signature dependency. Changing any other package content changes the content digest.

A package may provide `nexora.signature.json` containing `algorithm=ed25519`, `key_id`, `content_sha256`, and a Base64 detached signature. Verification uses the publisher's stored public Ed25519 key and signs the binary SHA-256 digest bytes. Nexora never stores a publisher private signing key.

## SBOM

A package may supply `sbom.cdx.json` or `bom.json` in CycloneDX form. When it does not, Nexora generates a dependency inventory from Composer and npm manifests/lockfiles. Component records are persisted separately for querying and later vulnerability/advisory integrations.

## Provenance

`nexora.provenance.json` may describe the source repository, source commit, builder, build type, times and build materials. The provenance subject must match the current package content digest. Provenance becomes verified only when its subject matches and the artifact signature is verified.

## Trust and sandbox policy

N0.28 introduces policy profiles for future execution isolation. These profiles decide whether an artifact can even be considered for execution and what capability boundary applies. This release does **not** claim operating-system/container/process isolation; `PolicySandboxAdapter` is the stable contract/foundation for later concrete sandbox backends.

## Stability hardening

- Laravel callback schedules are explicitly named before `withoutOverlapping()`.
- Media uploads use Windows-safe PHP temporary-file paths and verify the storage write.
- HTTP failures receive safe Inertia/JSON presentations with request IDs instead of raw framework HTML in Admin requests.
- Shared Admin UI primitives give immediate press feedback, while processing actions keep explicit busy states.
