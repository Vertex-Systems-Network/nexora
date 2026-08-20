# N0.12 — Deployment Recovery, Localization & Premium Upload

N0.12 removes the stale deployment-worker experience and establishes Nexora's localization contract from the bootstrap installer through the Laravel/React admin.

## Deployment worker ownership

Every deployment stream owns a cryptographically random `run_id`. Runtime state is persisted under `storage/app/nexora/deployment-control/` and contains the active run, owner PID, current step, child PID and heartbeat timestamp. A cancellation request targets the exact run ID rather than deleting a lock file blindly.

The active process checks for cancellation while Composer/npm/Vite is running. On Windows Nexora terminates the process tree after requesting normal process termination so child package-manager processes cannot keep the deployment lock alive. The worker releases the OS file lock before publishing the final inactive state, preventing the browser from enabling Retry before the lock is actually reusable.

If a new task encounters an existing worker, the progress response returns the active run ID. The same page can request cancellation of that previous run and poll `deployment_status` until the server confirms it is inactive. A page refresh is therefore recoverable without manually deleting files.

Runtime control files are deployment state only and are excluded from source/release artifacts. Zero-install reset scripts remove deployment state and cancellation flags.

## Localization foundation

Nexora now has one locale vocabulary across three boot phases:

1. framework-independent deployment bootstrap,
2. Laravel installation/auth surfaces,
3. installed Inertia/React admin.

Initial supported locales:

- English (`en`, LTR)
- Urdu (`ur`, RTL)
- Turkish (`tr`, LTR)
- Arabic (`ar`, RTL)
- Russian (`ru`, LTR)

The pre-Laravel bootstrap uses `bootstrap/nexora-locales.php` and persists the selected locale in the bootstrap session and a year-long locale cookie. Laravel resolves locale from authenticated user preference, then session, cookie and application default. User/profile forms persist the locale in the existing user locale field.

`SetLocale` sets both Laravel locale and request direction before Inertia shared data is produced. React receives `localization.current`, `direction`, supported locales and common translated messages. `AdminLayout` and `AuthLayout` update the document `lang`/`dir` attributes and use logical RTL-aware layout positioning.

This block starts the translation system; future feature modules must publish namespaced translation catalogs instead of embedding UI strings in feature code.

## Premium release file selection

The prebuilt-release uploader no longer exposes the native browser file button as the primary UI. A Nexora-styled drop surface supports:

- click to browse,
- drag and drop,
- selected filename and size,
- keyboard-accessible label semantics,
- a hidden native file input for browser compatibility.

The actual release verification/security pipeline is unchanged: selecting a file never deploys it until the existing verified release action succeeds.
