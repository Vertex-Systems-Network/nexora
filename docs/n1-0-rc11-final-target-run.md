# N1.0 RC11 — Final Target Evidence Run

RC11 is not a product-feature milestone. It is the final target-environment closure harness layered on top of RC1–RC10.

## Modes

Windows:

```bat
scripts\final-target-run.bat --install-deps
scripts\final-target-run.bat --status-only
scripts\final-target-run.bat --final
```

PowerShell/Linux wrappers call the same PHP runner.

- `--install-deps`: install Composer + Node dependencies, then run the automated target certification without pretending manual evidence exists.
- `--status-only`: inspect existing exact-version evidence and write `storage/app/nexora/certification/closure-status.json` and `.md`.
- `--final`: require `NEXORA_CERT_FINAL_EVIDENCE=1`, rerun the complete certification and permit production packaging only when every final evidence domain passes.

## Closure domains

1. Automated Laravel/database/frontend certification.
2. Production Vite build asset budgets.
3. Target HTTP/header/performance evidence.
4. Browser/accessibility/RTL evidence.
5. Disposable-target backup/restore rehearsal.
6. Independent-node HA rehearsal.
7. Final SHA-256 evidence aggregation.
8. Certified production ZIP sealing.

The first seven domains must be PASS before the production package may be created. N1.0 is DONE only when the production package is also sealed.

## Fail-closed behavior

Missing, stale, wrong-version, placeholder or failing evidence remains `pending`/`fail`. RC11 never promotes SKIP to PASS. A source-only run is not N1.0 closure evidence.
