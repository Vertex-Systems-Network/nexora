# Threat Model — THEME-CLOSURE-001 / SYS-THEME-ENGINE (lifecycle closure)

## Unit

- `SYS-THEME-ENGINE`, stage `THEME-CLOSURE-001`, risk high (third-party package path).

## Scope

Test-only increment: `tests/Feature/Themes/ThemeLifecycleClosureTest.php` proves lifecycle properties of existing code. No production code changes.

## Assets

The public site's rendered output, the active-theme state, installed theme files, and the Sentinel decision trail.

## Threats and controls (as now proven by tests)

| Threat | Control | Evidence |
|---|---|---|
| Unsafe theme (executable PHP) reaches the site | Sentinel scan runs before install; non-ALLOW decisions redirect to the scan report and install nothing | `test_sentinel_blocks_a_theme_carrying_executable_php` |
| Preview leaks a candidate theme to visitors | Preview uses a token URL; the active theme and public render are unchanged | `test_preview_renders_the_candidate_while_the_public_site_keeps_the_active_theme` |
| Switch/rollback leaves the site on the wrong theme | Activation deactivates the previous theme; rollback restores the previous snapshot and the public render follows | `test_switching_between_uploaded_themes_renders_each_and_rollback_restores_the_previous_one` (mutation-checked) |
| Non-privileged user changes the theme | Install/activate/rollback require theme permissions | `test_users_without_theme_permissions_cannot_install_or_activate` |

## Residual risk

- Not target-verified: no browser, real web server or CDN evidence (RUNTIME-TARGET-ACCEPTANCE, #74).
- Sentinel coverage is proven here for one unsafe pattern (PHP file); other scanner rules rely on Sentinel's own tests.
