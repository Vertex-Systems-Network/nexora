# N1.0 RC14 — Environment / Config Drift / Secrets Safety

RC14 is a release-candidate hardening pass, not a product-domain milestone. It makes environment selection and Laravel config caching explicit and fail closed for installed deployments.

## Contracts

- Installed deployments use one authoritative environment location selected by the `root` / `fallback` active marker.
- A marked source that disappears is a bootstrap failure; Nexora does not silently switch to another `.env` containing different credentials.
- `nexora:environment:doctor` reports only sanitized facts and a short APP_KEY fingerprint; secrets are never printed.
- Production policy requires `APP_ENV=production`, `APP_DEBUG=false`, a valid APP_KEY, HTTPS by default, encrypted/HTTP-only/secure session cookies and persistent runtime drivers.
- Environment writes invalidate Laravel's cached config file so the next process cannot keep stale environment values.
- Runtime application code may not call `env()` outside `config/*.php`; this is enforced without Laravel dependencies.
- Existing-install upgrade preflight includes the environment doctor and blocks on environment/config-cache errors.
- `.env.production.example` is safe-by-default and contains no real credential values. Real root/fallback environments remain excluded from production archives.

## Operator check

```bat
php artisan optimize:clear
php artisan nexora:environment:doctor --production
php artisan optimize
php artisan nexora:environment:doctor --production
```

The second doctor run proves that the cached configuration still represents the intended environment.
