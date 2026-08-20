# N0.9 Verification

Source verification for N0.9 covers the deployment failure category that produced:

`The APPDATA or COMPOSER_HOME environment variable must be set for composer to run correctly`

Required gates:

- framework-independent process environment helper exists;
- missing `COMPOSER_HOME` receives a writable Nexora private fallback;
- explicit `COMPOSER_HOME` is preserved;
- Composer cache and npm cache fallbacks are writable;
- deployment bootstrap loads the environment helper before child processes run;
- `proc_open()` receives the normalized environment rather than `null`;
- discovered Composer/Node/npm candidates are version-smoke-tested before being marked READY;
- browser deployment still exposes no arbitrary command input;
- CMD, PowerShell and Bash runners preserve existing environment values and provide fallbacks only when missing.
