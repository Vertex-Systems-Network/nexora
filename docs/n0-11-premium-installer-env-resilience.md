# N0.11 — Premium Installer & Environment Resilience

N0.11 fixes the zero-install failure where the early installer reported that it could not create or write `.env`. The source distribution now always contains a non-empty `.env.example`, and the pre-Laravel bootstrap no longer requires a writable project root.

## Environment selection

1. Prefer the project-root `.env` when it can be written atomically.
2. Otherwise persist to `storage/app/nexora/environment/.env`.
3. Write `storage/app/nexora/environment/active` with `root` or `fallback`.
4. On every later HTTP/Artisan bootstrap, obey the active marker before checking other candidates.
5. Remove the temporary `bootstrap.key` after a real environment has been committed.

The protected environment directory is runtime state and must not be published in a source or production release artifact.

## Premium installation surface

The standalone Deployment Center and Laravel Installation Wizard now share a consistent Nexora visual language: real Nexora logo/mark, favicon/app icons, premium panel/spacing/button treatment, icon-based status states, improved progress stages, and a polished log console. React/admin icons are provided by `lucide-react` behind the Nexora Icon compatibility layer. Blade/pre-React stages use self-contained Lucide-compatible SVG paths because those screens must work before npm assets exist.

## Regression gates

`php scripts/source-guard.php --source-only` now fails if `.env.example` is absent/empty, the installer reintroduces an early root `.env` hard failure, protected environment fallback/active-marker logic is removed, brand/favicon assets are missing or empty, `lucide-react` disappears, the retired Untitled icon dependency returns, or protected environment state can be packaged in a production release.
