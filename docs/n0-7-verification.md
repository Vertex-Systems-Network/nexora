# N0.7 Verification

Source-level release gates include:

- PHP syntax lint for all PHP sources
- explicit `config/view.php` compiled path
- pre-Laravel runtime bootstrap in HTTP and CLI entry points
- required runtime directory markers
- no `bootstrap_path()` usage
- no readonly controller extending the non-readonly base Controller
- standalone deployment bootstrap exists and exposes only fixed build tasks
- request data is never passed directly to `proc_open`
- main Laravel installer services remain process/shell-free
- domain migration naming guard
- Nexora Admin UI abstraction guard

Dependency-backed gates remain authoritative on the target developer/CI environment: Composer package discovery, MySQL migrations/seeding, Laravel tests, TypeScript, frontend tests and production build.
