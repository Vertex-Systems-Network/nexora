# N1.0 RC25 — Unified Target Evidence Intake & Closure Dashboard

RC25 does not add a product domain. It makes N1.0 operator evidence portable and fail-closed across machines without treating a copied JSON file as proof by itself.

- `scripts/target-evidence-intake.*` accepts a directory or bounded ZIP, rejects unsafe archive paths/symlinks, recognizes ten certification evidence types and validates each against the current platform/source contracts.
- `--seal` requires the exact reviewed dependency-lock attestation before validated evidence is copied into protected certification storage.
- `--require-complete` requires all five operator-owned domains: zero-install/recovery, existing-install upgrade rehearsal, browser/A11y/RTL, backup/restore, and real multi-node HA.
- The intake manifest records platform version, source-tree SHA-256, Composer lock SHA-256, npm lock SHA-256 and reviewed-lock attestation SHA-256.
- `scripts/closure-dashboard.php` combines reviewed dependency state, target-runtime evidence and the existing eleven-domain final closure ledger without weakening any gate.
- Automated build/database/runtime evidence remains independently generated and validated; RC25 is an intake/aggregation layer, not a mechanism to fabricate PASS evidence.
