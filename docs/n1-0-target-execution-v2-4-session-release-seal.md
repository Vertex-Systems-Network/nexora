# N1.0 Target Execution v2.4 — Session Integrity & Final Release Seal

Platform: `1.0.0-rc.39`.

This batch binds C4-C6 operator evidence to one active exact-source/reviewed-lock certification session, blocks concurrent master target executions, constrains future-clock skew/session age, creates a sanitized certification evidence bundle alongside the production ZIP, and emits an external release seal that binds the production artifact, evidence bundle, source tree, lockfiles, reviewed-lock attestation and final evidence. The production-package closure domain is PASS only when the production ZIP, evidence bundle and release seal all independently validate.
