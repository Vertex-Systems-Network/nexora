# N0.9 — Portable Toolchain Environment

N0.9 fixes a deployment failure where Composer was discoverable from Laragon but Apache/FastCGI did not provide `APPDATA` or `COMPOSER_HOME` to the child process. Nexora now treats executable discovery and executable usability as separate checks.

## Environment resolution order

1. Preserve environment variables already inherited by the web/OS process.
2. Preserve a valid user profile/AppData when it is available.
3. Extend `PATH` with the current PHP binary and detected Laragon PHP/Composer/Node directories.
4. If the web process has no normal login profile, provide project-private writable fallbacks under `storage/app/nexora/tools/`.

The private fallbacks include:

- `composer-home/`
- `composer-cache/`
- `npm-cache/`
- `home/`
- `tmp/`

The browser never accepts an arbitrary environment-variable or command field from the request.

## Tool preference

Composer is resolved in this order:

1. PATH / OS environment
2. ComposerSetup / user Composer installation
3. Laragon Composer
4. project `composer.phar`
5. Nexora private verified Composer

A candidate is not reported as READY until `composer --version --no-ansi` succeeds inside the normalized Nexora process environment.

Node.js/npm follow the same principle: existing OS/PATH tools are preferred, then Laragon/system installations, then Nexora's private checksum-verified runtime. Both are smoke-tested before the UI marks them READY.

## CLI parity

Windows CMD, PowerShell and Bash bootstrap/quality runners now establish the same Composer/npm home/cache fallbacks before running dependency commands. Existing explicit environment settings are never overwritten.
