# N0.6 Verification

Source-level verification performed before packaging:

- all PHP sources linted with the available PHP interpreter
- JSON manifests parsed
- source guard passes in source-only mode
- zero `bootstrap_path()` calls in application/config/bootstrap source
- zero readonly controllers extending the non-readonly base `Controller`
- zero phase/milestone migration table names
- browser installation services contain no shell/process execution primitives
- installer lock and mutex files are excluded from version control

Dependency-backed gates remain authoritative on the Laragon machine because this build container does not provide Composer and cannot complete network package installation. After `scripts/setup-zero.bat` + browser installation, run `scripts/quality-check.bat`.

The full quality guard additionally requires generated `composer.lock` and `package-lock.json` so dependency resolution does not remain unpinned after the first trusted source bootstrap.
