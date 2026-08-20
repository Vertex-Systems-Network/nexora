# N1.0 RC18 verification

Source certification requires `scripts/runtime-safety-contract-verify.php` in addition to all RC1–RC17 gates. Dependency-backed target certification additionally runs `php artisan nexora:runtime:doctor` after Laravel dependencies are installed.

Expected source invariants:

- exactly four first-party queue jobs;
- maximum first-party job timeout 1800 seconds;
- every retrying job has explicit backoff;
- every first-party queue job fails closed on timeout;
- queue retry-after defaults are at least 1860 seconds;
- explicit trusted proxies only, no wildcard default;
- request body ceiling is enforced from `CONTENT_LENGTH`;
- queue worker lifecycle clears tenant context and supports graceful memory restart;
- SEO crawl has queued/running cancellation flow;
- N1.1 remains blocked until real dependency/browser/restore/HA evidence is green.
