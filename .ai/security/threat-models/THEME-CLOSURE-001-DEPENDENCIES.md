# Threat model — SEC-THEME-CI-DEPS-001

## Scope

Source-only patch candidate for the CI security audit blocker. Base 15967f1f3550a263cb1bf0ca2e557230b8e1a682. No target/provider/deployment authority. High-risk supply-chain work; no new identity contract.

## Assets and boundaries

Locked upstream executable code, Composer source/distribution origins, authentication correctness and CI audit integrity. Candidate resolution is isolated; registry content is untrusted input. No lifecycle scripts or plugins execute during candidate generation.

## Threats and controls

| Threat | Control |
|---|---|
| Advisory bypass mistaken for remediation | Preserve composer/npm audit policy and require a fresh zero-advisory locked result |
| Unsafe WebAuthn decoy/metadata/attestation parsing retained | Existing #170 targets 5.3.12; audit requires >=5.3.13. Resolve same-family patched candidate, then run passkey security regressions |
| Broad transitive/major drift | Pin same major/minor families during candidate resolution and inspect every changed lock identity |
| Malicious registry or source origin | Existing governed candidate provenance and hash/review controls; no hand-edited lock entries |
| Install script/plugin side effects | Candidate resolution uses --no-install --no-scripts --no-plugins |
| Auth or theme behavior regression | Existing negative auth/passkey/theme tests and full exact-head CI before promotion |
| Author self-certifies high-risk dependency change | Distinct exact-head review remains mandatory; no target claim from CI |

## Residual risk

Full runtime/browser/target acceptance remains unproven (#74). Upstream patched ranges are audit observations, not proof that every application path is exploitable or fixed. Discard candidate or revert accepted lock commit on regression.
