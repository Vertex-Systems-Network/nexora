# N1.0 RC20 verification

Source-level verification is intentionally separate from dependency-backed and operator evidence.

Expected source gates:
- final closure integrity contract
- source-tree attestation contract
- module/runtime/database/security/frontend/source guards
- strict zero-state source package hygiene

Dependency-backed gates remain target requirements because the source package intentionally does not fabricate Composer/npm lockfiles or dependencies.
