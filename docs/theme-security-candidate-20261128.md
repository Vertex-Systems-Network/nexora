# Theme CI security blocker — isolated candidate

Source `15967f1f3550a263cb1bf0ca2e557230b8e1a682`; main `8c0a2d7bdf8e4df8e98b21e3c0d7ba4d3518e4eb`. PR #152 remains unmerged. Unit `SEC-THEME-CI-DEPS-001`, stage `THEME-CLOSURE-001`.

Governance [run 46084084434](https://github.com/Vertex-Systems-Network/nexora/actions/runs/46084084434/job/140789486157) failed the Composer audit: 25 advisories across eight locked packages. The subsequent missing PHPUnit artifact is a downstream symptom; PHPUnit did not run in that job. Development checkout QA was still running at the single intake snapshot; Windows and traceability passed. No later status is inferred.

## Candidate changes

| Package | Original | Candidate |
|---|---|---|
| laravel/framework | v13.36.0 | v13.44.1 |
| symfony/http-foundation | v7.4.21 | v7.4.25 |
| symfony/http-kernel | v7.4.21 | v7.4.25 |
| symfony/mailer | v7.4.19 | v7.4.24 |
| symfony/mime | v7.4.19 | v7.4.24 |
| symfony/routing | v7.4.21 | v7.4.24 |
| symfony/serializer | v7.4.21 | v7.4.25 |
| web-auth/webauthn-lib | 5.3.9 | 5.3.17 |

Exactly eight existing libraries change; no package added/removed and no development dependency changes. All changed packages retain MIT licenses, upstream GitHub identities and fixed source/dist references; no changed package declares scripts or Composer-plugin type. Root manifest constraints are unchanged. No install scripts/plugins ran during resolution or disposable installation.

## Measured candidate evidence

- Composer 2.10.3 strict manifest/lock validation: PASS.
- Composer locked audit: no advisories or abandoned packages.
- npm lock-only audit: zero vulnerabilities at every severity; npm lock unchanged.
- Existing candidate provenance: PASS (254 Composer and 214 npm URLs; zero missing integrity records). Lock-pair validator: zero errors/warnings.
- Disposable fixture = git archive of the exact source above plus the candidate Composer lock. PHP 8.4.28, SQLite memory DB, ephemeral application key, localhost APP_URL. Focused theme lifecycle, theme engine, passkey and TOTP regressions: **33 tests / 233 assertions PASS**, warning-hard; original assertions unchanged.
- Root source lockfiles and deployed targets were not modified. This candidate result does not certify the still-vulnerable root locks or the full suite.

Machine dossier: `.ai/evidence/theme-security-candidate-20261128.json`. Exact candidate lock pair, raw baseline audit excerpt, package identities, audit responses and JUnit output: `.ai/evidence/theme-security-candidate-20261128.tar.gz`. Verify archive/lock SHA-256 against the dossier before review. Extract only into a disposable review directory.

## Reproduction and remaining gates

In a disposable copy of the exact source, use Composer 2.10.3:

```sh
composer update 'laravel/framework:^13.42' 'symfony/http-foundation:~7.4.23' 'symfony/http-kernel:~7.4.23' 'symfony/mailer:~7.4.23' 'symfony/mime:~7.4.23' 'symfony/routing:~7.4.23' 'symfony/serializer:~7.4.23' 'web-auth/webauthn-lib:~5.3.13' --with-dependencies --minimal-changes --no-install --no-scripts --no-plugins --no-progress --no-interaction
composer validate --strict --no-check-publish
composer audit --locked --format=json --no-interaction
npm audit --package-lock-only --json --audit-level=low
```

Registry state may change; the archived candidate bytes and hashes identify this measured result. An A/B canonical-toolchain refresh dossier, independent review, existing governed lock-promotion procedure and fresh full exact-head CI remain required. Do not hand-copy candidate locks into root or claim target verification. No promotion receipt or review approval is fabricated.

CodeRabbit 0.9.7 installed successfully, but auth status was `not_authenticated`; `auth login --agent` returned `environment_unsupported` (browser login unavailable). No review ran. Resolve authentication in a user-controlled terminal or approved Agentic API-key configuration; do not paste credentials into PR/chat. No manual review is represented as independent CodeRabbit evidence.

Existing dependency #170 targets 5.3.12 and therefore does not meet the observed >=5.3.13 WebAuthn fixed range. Other existing dependency carriers remain preserved. No unrelated feature stage was started. Rollback is discarding the candidate; #74 live target acceptance remains open.
