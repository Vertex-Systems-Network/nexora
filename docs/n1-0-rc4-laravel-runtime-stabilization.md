# N1.0 RC4 — Laravel Boot / Middleware / Scheduler Contract Stabilization

RC4 turns the runtime-middleware defect class discovered during target-machine testing into a whole-tree certification rule. It adds no new product domain.

## Dependency-free Laravel runtime contract gate

`scripts/laravel-runtime-contract-verify.php` runs before Laravel package discovery and analyzes source without requiring `vendor`.

It verifies:

- every local HTTP middleware either inherits a framework middleware `handle()` implementation or exposes `handle(Request, Closure, ...)` in Laravel pipeline order;
- service/container dependencies are constructor-injected rather than appended after `$next`; only route-supplied scalar middleware parameters may follow `$next`;
- middleware classes appended/aliased in `bootstrap/app.php` are imported and backed by source files;
- route middleware aliases used by `routes/web.php` are built-in Laravel aliases or explicitly registered Nexora aliases;
- every `Schedule::command()` target is actually registered by an Artisan closure or Console Command signature;
- all cluster schedules except node heartbeat are scheduler-leader gated;
- every scheduler callback has a unique explicit name and therefore remains compatible with `withoutOverlapping()`;
- queued jobs expose a `handle()` entry point and do not depend on HTTP `Request`/`Response` or request/session helper context;
- Service Provider `register()` methods stay zero-argument and `boot()` injection parameters remain container-resolvable object contracts.

## Dependency-backed boot evidence

`tests/Feature/Certification/LaravelRuntimeBootCertificationTest.php` verifies the runtime heartbeat middleware signature through reflection and explicitly boots both `route:list` and `schedule:list` once Composer/Laravel dependencies are installed.

This does not replace the full target-machine certification runner. It makes the previously reported middleware argument-count and scheduler callback failures fail earlier and with a more specific error.
