# Phase 1 — Runtime closure operator handoff (source vs. target)

**Stage:** `RUNTIME-CLOSURE-001` / `SYS-RUNTIME-IDENTITY`  
**Source PR:** [#122](https://github.com/Vertex-Systems-Network/nexora/pull/122)  
**Source CI checkpoint:** `ef699a70c4334bec3284a545e456e36e027c60eb`  
**2026-10-15 source CI:** [Development QA #38664117744](https://github.com/Vertex-Systems-Network/nexora/actions/runs/38664117744) SUCCESS, [Release Certification #38664117747](https://github.com/Vertex-Systems-Network/nexora/actions/runs/38664117747) SUCCESS.

This is a **non-secret operator checklist**, not a readiness receipt, deployment action, or evidence that the external target was inspected. The source CI ran real Chromium `/install` and disposable authenticated Admin → Health → Back/Forward → reload tests; those results **do not attest a live hosted target**.

## 1. Scope and authorization

Before making any target change, the operator must establish:

- [ ] The actual target installation(s), their owners, whether any historical installation ever consumed the bootstrap key exposed in quarantined PR #71, and whether any source/archive/backup copied that key. Record scope without publishing secret bytes, decoded keys or key digests.
- [ ] The exact target's own checkout and protected storage, and an independently reachable **HTTPS** origin under operator control. Do not infer ownership from an app URL string or plain HTTP 200.
- [ ] An explicit maintenance/rollback decision and tested encrypted-data/session backup and restore plan **before any key change**.
- [ ] A fresh target/source artifact corresponding to the intended PR release, with dependencies built from reviewed locks. GitHub's disposable runner cannot provide the host/operator evidence.

The historical exposed key belongs to a **local installation bootstrap/application-key lifecycle**, not an established external SaaS token. A public PR artifact deletion, random fresh QA key, or automated `key:generate --force` does **not** invalidate copies that might have become effective APP_KEY values.

## Confidential historical-key inventory helper (#72)

The repo includes a **read-only** audit, `scripts/phase1-exposed-key-audit.php`, for comparing a privately supplied historical PR #71 key to the target's readable root/fallback environment and protected bootstrap key. The reference must be a **non-symlink, owner-only private file outside the repository**, containing the original key, and must never be committed, pasted into terminal flags or uploaded to GitHub.

On the authorized target machine:

```sh
chmod 600 /secure/operator-only/exposed-bootstrap.key
php scripts/phase1-exposed-key-audit.php \
  --reference-file=/secure/operator-only/exposed-bootstrap.key \
  --target="<operator-provided-target-path>"
```

Interpret the status conservatively: `EXPOSED_KEY_OBSERVED` (exit 3) identifies a match in at least one scanned file, `INCONCLUSIVE` (exit 2) means an unreadable/missing key source or cached configuration blocks a reliable file assessment, and `NO_MATCH_IN_SCANNED_FILES` (exit 0) means **only** that scanned readable files did not contain the reference key. All statuses still report `historical_non_use_proved=false`, `target_verified=false`, and `retired=false`. No secret, replacement key or fingerprint is printed.

This tool cannot observe the exact active key of external running processes, backups/snapshots or installations not supplied as targets. Review application runtime configuration, `APP_PREVIOUS_KEYS`, historical deployments, offline backups and owner inventory before accepting key retirement/non-use. Do not automatically revoke a key needed for decrypting stored data.

## 2. Issue #72 — affected-key retirement

- [ ] Inventory current active keys **and accepted previous keys** for every actually affected installation, using confidential comparison by the target's authorized operator. Report only the scope and a conclusion (affected / not affected / unknown); never attach key material or raw environment files to GitHub.
- [ ] If a defensible, independently accepted non-use case exists, record the basis, inventory coverage and reviewer approval in #72. A statement that "there is no current hosting" does not by itself prove the historical key was never reused.
- [ ] If an affected installation exists, establish backup/recovery and a controlled rekey/re-encryption plan. Confirm sessions, encrypted records, jobs, signed cookies, and multi-node key convergence before any cutover.
- [ ] Ensure the exposed key is absent from **both active APP_KEY and any accepted APP_PREVIOUS_KEYS** after recovery. Test encrypted data and session recovery after retirement, document non-secret result and approver, and keep the receipt private until sanitized.
- [ ] Record a non-secret closure decision and acceptance in [#72](https://github.com/Vertex-Systems-Network/nexora/issues/72).

**Important:** `php artisan nexora:runtime:key-rotation` manages an authorized rotation *continuity receipt* and node convergence; its default continuity path requires the previous key in `APP_PREVIOUS_KEYS`. That is not by itself historical-key **revocation**. In particular, do not claim exposure remediation complete while a compromised key remains accepted for decryption. Do not use that command to improvise a rotation without the owner-approved recovery/retirement sequence.

## 3. Issue #74 — exact-target runtime acceptance

Run these commands **on the operator-approved target checkout**, not in GitHub CI. No machine vendor or absolute installation path is committed to this repository.

Read-only/troubleshooting entry points:

```sh
php scripts/target-diagnostics.php
php scripts/target-runtime-run.php
php scripts/runtime-recovery-orchestrator.php --target="<operator-provided-target-path>"
```

Review diagnostics for missing PHP extensions, Composer/npm build lock alignment, writable directories, current source identity, and source/runtime mismatch. These commands collect diagnostics or dry-run compatibility but do **not** imply acceptance of the live target.

Only when a real operator has explicitly authorized runtime recovery after reviewing a dry-run, maintenance state, compatibility and backups may the existing governed path be used:

```sh
php scripts/runtime-recovery-orchestrator.php --target="<operator-provided-target-path>" --apply --confirm=RECOVER-RUNTIME
```

The governed orchestrator does **not** deploy source, install packages, run migrations/seeders, or change the release version. It may perform narrowly authorized recovery/reconciliation operations, so **do not** execute apply mode merely to produce a green report.

Required independent **same-target** observations:

1. [ ] A fresh, one-time target-local CLI ↔ web source activation challenge, consumed on the target application's configured origin, followed by independently validated CLI web-ack and matching runtime/source fingerprints. No token may be copied into reports.
2. [ ] Exact target `nexora:runtime:post-install-status --assert-ready` with exit code 0 and `status=pass`, `ready=true`, `runtime_ready=true`, `receipt_current=true`, and `errors=[]`.
3. [ ] Authorized, real **HTTPS** `GET /login` on the same independently proven origin, TLS verification on and redirects disabled; a different source's HTTP 200 is insufficient.
4. [ ] Valid source, environment, service, deployment, activation and current-receipt identities; sanitized evidence tied to the accepted source head and exact target.
5. [ ] #72 closure accepted and closure state reconciled without turning SOURCE/DEVELOPMENT test results into TARGET evidence.

Consult [runtime recovery safety contract](runtime/RUNTIME_RECOVERY_ORCHESTRATOR.md), [target runtime runner](n1-0-rc22-target-runtime-closure.md) and [target evidence intake](n1-0-rc25-unified-target-evidence.md) for authoritative detailed rules.

## 4. Evidence handling and acceptance record

Only attach sanitized metadata: target identifier approved for publication, checker version/source commit, status, check name, timestamp, outcome and responsible reviewer. **Never attach** `.env`, `bootstrap.key`, `APP_KEY`, `APP_PREVIOUS_KEYS`, raw session cookies, source-activation tokens, cloud credentials, private backups or unredacted command output. Inspect the evidence bundle before sharing it.

| Gate | Evidence required | Owner/acceptance |
| --- | --- | --- |
| Source and disposable browser QA | PR exact-head workflows green and Chromium browser test logs | Source QA: **PASS** (2026-10-15 checkpoint) |
| Issue #114 | Installer responsiveness, dark theme, meter + real authenticated Health/history browser test | Source fixed and disposable browser verified; integration review remains |
| Issue #72 | Complete affected-use inventory; independent non-use acceptance OR controlled key retirement + recovery checks | **OPEN — target/operator owned** |
| Issue #74 | Fresh exact CLI↔web proof, current post-install receipt, HTTPS login on same target origin | **OPEN — target/operator owned** |
| Final stage reconciliation | Target acceptance + approved security decision + canonical state/evidence update | **BLOCKED** |

Do **not** mark `RUNTIME-CLOSURE-001` `TARGET_VERIFIED`, start `CORE-QA-001` or merge #122 solely because GitHub CI passed. The original issue authority and release rules still apply.
