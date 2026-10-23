# ResearchBrief — SYS-ARCH-BOUNDARIES: layer-direction boundaries

## Problem

`ARCHITECTURE.md` states a one-way dependency direction (framework → kernel → contracts → modules), but only one boundary (core modules not touching Eloquent/DB facades) was machine-checked. Domain code could import the HTTP layer, and contracts or models could depend on service implementations, without any test failing.

## Users & VOC evidence

- Users: contributors (AI and human) adding domain, contract and model code.
- VOC: owner-authored requirement only — `.ai/roadmap/stages.md` row ARCH-BOUNDARY-100 ("reconcile architecture constitution with implementation; repository/persistence boundaries; broaden architecture tests") and `ARCHITECTURE.md` "Dependency direction".

## Baseline

Measured at base `0abbdaa` by grep over imports:
- `app/Nexora` files importing `App\Http\Controllers`: 0.
- `app/Nexora` files importing `App\Http\Middleware`: 3 (all for `AssignRequestId::ATTRIBUTE`).
- `app/Nexora` files naming `\App\Http\` fully qualified: 1 (installer source-integrity class inventory).
- Contract files importing a `Services` namespace or `App\Http`: 0.
- Model files importing `App\Http`: 0; importing a domain service: 2 (both `Enterprise\Services\TenantContext`).

## Alternatives

- Do nothing: the direction stays prose-only. Rejected.
- Add a static-analysis dependency (e.g. a layer-rule tool): a new supply-chain dependency for rules expressible in a small PHPUnit test. Rejected for now.
- Fix all existing references, including the sealed installer inventory: would touch sealed installer source for no behavioural gain. Rejected; allowlisted with reason instead.

## CTQs

1. No `app/Nexora` file imports or names `App\Http` classes, except allowlisted entries with a recorded reason.
2. No contract imports a `Services` namespace or `App\Http`.
3. No model imports `App\Http`; models import only allowlisted domain services.
4. Allowlists cannot go stale (each entry must still exist and still need the exception).

## Confidence

`low` — the requirement is owner-stated; the rules are measured against the current tree but there is no outcome measurement yet.

## Sources

- `ARCHITECTURE.md` ("Dependency direction", rule 13), `tests/Architecture/ModuleBoundaryTest.php`.
- Import counts above, from `grep` at base `0abbdaa`.
