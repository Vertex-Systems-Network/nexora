# N1.0 RC23 — Target Bootstrap / Resume Certification

RC23 is an operational closure pass, not a product-domain release. It sits on top of RC22's fail-fast target runtime runner.

## Goals

- Diagnose Laragon/Windows target prerequisites before dependency installation.
- Never auto-download Composer/PHP/Node or silently resolve an unlocked dependency graph.
- Record the active PHP binary/php.ini, required PHP extensions and certified toolchain ranges.
- Resume selected expensive PASS steps only when platform, exact source SHA, both lock hashes and installed dependency fingerprints still match.
- Re-run Laravel boot/runtime doctors on every resumed run so environment/config drift cannot hide behind prior evidence.
- Treat uploaded target-runtime ZIPs as untrusted evidence and reject traversal, missing logs, wrong version/source/locks and fake PASS state.
- Keep generated bootstrap/runtime evidence out of true-zero and production packages.

## Operator flow

Windows/Laragon:

```bat
scripts\target-environment-bootstrap.bat
scripts\target-runtime-run.bat --install-deps
scripts\target-runtime-run.bat --resume-latest
scripts\target-runtime-run.bat --full
```

Evidence verification:

```bat
php scripts\target-runtime-evidence-verify.php --input=path\to\Nexora_Target_Runtime_*.zip --require-pass --seal
```

A resume request with source, lockfile or installed-dependency fingerprint drift is rejected and must start a fresh run.
