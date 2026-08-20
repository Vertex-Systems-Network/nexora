# N1.0 Target Execution v2.5 — Signed Release Trust & Offline Verification

Platform: `1.0.0-rc.40`.

## Scope

v2.5 is a release-trust hardening batch. It does not start N1.1 and it does not replace real target/browser/operator/HA certification.

## Certified toolchain freeze

C1 captures the exact PHP, trusted Composer, Node and npm versions/binary fingerprints plus source and reviewed lock hashes. C2-C6, the certification session, target evidence intake and final evidence reject toolchain drift.

## Signed final release

When N1.0 target evidence is genuinely green, C6 requires signing-key readiness before production packaging. The final delivery contains:

- `nexora-<version>-production.zip`
- `nexora-<version>-certification-evidence.zip`
- `nexora-<version>-release-seal.json`
- `nexora-<version>-release-seal.sig`
- `nexora-<version>-release-public.pem`

The seal binds exact source, certification session, certified toolchain, reviewed lockfiles, final evidence and both release archives. The private signing key remains runtime-only and is excluded from source/production archives.

## Signing-key setup

Key generation is explicit and fail-closed:

```bat
scripts\release-signing-key.bat --generate --confirm=GENERATE
```

The default minimum RSA size is 3072 bits. Existing keys are never silently overwritten.

## Offline verification

A release recipient can verify the signed artifact set without the original certification host:

```bat
php scripts\release-offline-verify.php ^
  --production=<production.zip> ^
  --evidence=<certification-evidence.zip> ^
  --seal=<release-seal.json> ^
  --signature=<release-seal.sig> ^
  --public-key=<release-public.pem>
```

The verifier checks detached signature validity, public-key identity, production/evidence archive hashes, release manifest bindings, evidence-index bindings, final PASS evidence and ZIP hygiene.

## Archive hygiene

Production and evidence archives reject path traversal, absolute/drive paths, symbolic links, duplicate/case-colliding entries and configured archive expansion limits.

## Session finalization

After signed artifacts pass independent verification, C6 records a separate session-finalization receipt binding the signed artifacts to the certification session. A finalized session cannot collect new C4-C6 operator evidence; a new certification cycle is required.

## Current status

Source contracts are certifiable without dependency-backed target claims. Real Laragon dependencies/build, Laravel runtime/database tests, five-database matrix, operator/browser/Web-Vitals evidence and real 2+ node HA evidence remain required before N1.0 and the production package can be marked PASS.
