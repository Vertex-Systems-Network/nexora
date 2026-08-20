# N1.0 RC3 — Runtime Middleware + Frontend Semantic Stabilization

RC3 fixes two dependency-backed target-environment failures discovered after RC2.

## Runtime middleware contract

`RuntimeNodeHeartbeat` was registered as normal web middleware but declared four required `handle()` parameters. Laravel's pipeline invokes ordinary middleware with the request and next closure, so `NodeIdentity` and `NodeManager` are now constructor-injected and `handle(Request, Closure)` remains the only runtime signature. A source certification gate rejects regression.

## Inertia v3 frontend compatibility

The target `npm run build` reached real `tsc --noEmit` and reported 76 errors across 11 files. RC3 normalizes the form model to Inertia v3-compatible serializable shapes, removes `transform(...).post/put` chaining, narrows router payloads, makes Writer block JSON explicitly serializable, and uses the existing `ButtonLink` for horizontal Membership/Helpdesk sub-navigation.

`frontend-contract-verify.php` now runs in source certification before dependency-backed Laravel/npm gates. Full semantic Vite certification still requires the installed dependency tree on the target environment.
