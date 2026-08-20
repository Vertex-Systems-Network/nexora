# Nexora N1.0-C4 — Install / Upgrade / Backup Recovery

C4 certifies three operational safety domains after exact-source C2 runtime/core-database certification passes:

1. Fresh browser installation plus interrupted installation/deployment recovery.
2. Existing-install forward upgrade from a real older Nexora installation with a verified backup and sealed plan.
3. Checksum-sealed backup plus actual restore to a disposable target.

## Safety boundaries

- C4 does not install dependencies; C1 owns dependencies.
- C4 does not run the five-database matrix; C3 owns portability.
- C4 does not certify browser accessibility/performance; C5 owns those domains.
- C4 does not certify multi-node HA; C6 owns HA.
- Upgrade rehearsal must use a disposable clone and must not auto-run destructive rollback/reset/fresh operations.
- Restore planning is non-destructive. Final PASS requires an observed restore to a disposable target.

## Operator kit

Generate source-bound fail-closed evidence templates:

```bash
php scripts/n1-c4-evidence-prepare.php --operator="REAL OPERATOR"
```

Every check starts as `fail`. Change a check to `pass` only after direct observation.

## Certification

```bash
php scripts/n1-c4-operations-certify.php --evidence=/path/to/completed-c4-evidence
```

A PASS manifest is bound to current source SHA-256, C2 evidence, reviewed Composer/npm lock attestation, and the three operator evidence file hashes.
