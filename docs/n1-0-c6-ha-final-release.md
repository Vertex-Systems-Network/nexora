# N1.0-C6 — Multi-node HA + Final Release Closure

C6 is the final N1.0 certification chunk. It cannot compensate for missing C1-C5 target PASS evidence. It first revalidates exact-source C1 through C5, then requires real HA readiness/rehearsal, two-or-more-node operator evidence, unified operator evidence intake, final automated certification, all 11 closure domains, and an independently revalidated production ZIP.

## HA evidence

Generate a fail-closed kit with:

```bat
php scripts\n1-c6-evidence-prepare.php --operator="REAL NAME"
```

Every node row and HA check starts as FAIL. Use real node keys and direct observations from at least two independent active nodes. `php artisan nexora:ha:status` and `php artisan nexora:ha:rehearse` must pass on the real target topology.

## Final command

After C1-C5 target evidence is PASS and C4/C5 operator evidence is sealed:

```bat
scripts\n1-c6-final-certify.bat --base-url=https://TARGET --evidence=<C6-HA-KIT-DIR>
```

C6 seals the five operator evidence domains, runs the existing final target certification in final mode, validates all 11 closure domains, independently reopens/verifies the production ZIP, and only then writes `storage/app/nexora/n1-c6/c6-evidence.json` with `n1_0_done=true`.

C6 never runs `composer update`, unlocked `npm install`, automatic lock acceptance, or direct destructive migration commands. The strict five-database operations remain owned by C3/final certification safeguards.


## v3.9 HA identity additions

Current `1.0.0-rc.54` C6 evidence defines **21 HA checks**. Every node row must include the exact deployment generation plus runtime environment, engine, database, storage and service-data-plane fingerprints. Before sealing evidence run `php artisan nexora:runtime:service-status --deep --assert-installed` on every node and prove cache/session/queue/mail/TLS/proxy service-data-plane consistency in addition to existing storage/database/engine checks. Source-level contracts do not satisfy these observations.


## v4.2 effective policy-plane HA additions

Current `1.0.0-rc.57` C6 evidence defines **27 HA checks**. Every node row must include `runtime_policy_fingerprint` and `runtime_policy_status=pass`. Run `php artisan nexora:runtime:policy-status --deep --assert-installed` on every node and prove exact policy-plane convergence; source-level policy files or matching source SHA alone are insufficient because environment overrides can change effective behavior.
