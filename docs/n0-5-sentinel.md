# N0.5 — Nexora Sentinel Security Foundation

N0.5 introduces the first executable-independent security gate for future plugins, themes, apps, integrations and Studio packs.

## Core invariant

```text
Upload / package source
        ↓
Quarantine (never runtime)
        ↓
Archive inspection
        ↓
Manifest validation
        ↓
AST + tokenizer + web-source scanning
        ↓
Capability-behaviour comparison
        ↓
Risk engine
        ↓
ALLOW / REVIEW / BLOCK
```

A ZIP is never extracted into `extensions/`, `themes/`, `modules/` or a web-accessible path during N0.5 scanning.

## Sentinel checks in N0.5

### Archive boundary
- ZIP path traversal (`..`)
- absolute paths and Windows drive paths
- duplicate normalized names
- null/control characters
- reserved Windows device filenames
- ADS/colon and trailing-dot/space ambiguity
- symbolic links
- encrypted entries that cannot be inspected
- nested archives
- native executables / OS scripts / PHAR payloads
- deceptive double extensions
- `.env`, private keys and credential containers
- `.htaccess` / `web.config`
- directly executable PHP in public paths
- entry count, file size, total expansion and compression-ratio limits
- source scan-size ceiling to keep Sentinel itself resistant to resource exhaustion

### Package identity
A root `nexora.json` is mandatory. N0.5 validates:
- schema
- stable package id
- name
- supported package type
- semantic version
- capability declaration shape

### PHP security
Two complementary layers are used:
1. `nikic/php-parser` AST with source locations
2. native tokenizer/text heuristics

Current detections include dynamic PHP execution, shell/process execution, unsafe deserialization, raw sockets/network calls, direct privileged filesystem operations, dangerous stream wrappers, FFI, dynamic function invocation, variable variables and encoded/obfuscated payload indicators.

### JavaScript / TypeScript security
Sentinel scans browser/server JS source for dynamic code execution, child-process primitives, direct process environment access, cookie/browser-storage access, raw network/WebSocket/XHR use, raw `innerHTML`, `document.write`, dynamic scripts and large encoded payloads.

### SVG / HTML security
Script tags, inline event handlers, `javascript:` URIs, iframes and external resources are surfaced or blocked based on severity.

### CSS / supply-chain / secret scanning
Sentinel also checks:
- remote CSS `@import` / `url()` resources and executable legacy CSS primitives
- `package.json` install/publish lifecycle hooks
- Composer lifecycle scripts, custom repositories and plugin allowlists
- embedded private keys and credential-like tokens

### Migration policy
Package migrations are treated as privileged input. Sentinel blocks attempts to mutate Nexora-owned `users` / `nx_*` tables and privileged destructive SQL such as database drops, trigger/routine manipulation, grants, core-table truncation or drops. Package-owned domain tables remain possible.

### Route policy
Package route files are checked for authentication/admin shadowing. Protected routes and protected route names are hard-blocked; raw `admin` prefixes are surfaced for review so future packages use Nexora's namespaced admin extension points.

### Capability mismatch
Observed behaviour is compared to `nexora.json` declarations. For example, direct network activity without `http.outbound` produces a hard-block mismatch.

## Integrity
The upload SHA-256 is an immutable quarantine baseline. A digest mismatch on rescan or a package that changes while being scanned is treated as tampering and hard-blocked.

## Persistence
- `nx_quarantine_packages`
- `nx_security_scans`
- `nx_security_findings`

Every finding stores rule id, severity, category, file path, start/end line, source excerpt, hard-block flag and structured metadata.

## Admin
`Security → Sentinel` provides package upload, scan history, risk summaries, quarantine state, detailed finding reports, severity filters, rescan and permanent quarantine deletion.

## CLI

```bash
php artisan nexora:sentinel:scan path/to/package.zip
php artisan nexora:sentinel:scan path/to/package.zip --json
```

A non-allow decision returns a failing exit code so CI/publishing pipelines can stop the package.

## Deliberately not claimed yet
N0.5 does **not** claim arbitrary third-party PHP is sandboxed. Package signing, SBOM/advisory scanning, publisher trust, activation transactions, network/storage brokers, runtime circuit breakers and isolated execution tiers are later security blocks. N0.5 is the pre-execution inspection and quarantine foundation they will depend on.
