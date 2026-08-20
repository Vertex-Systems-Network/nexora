# N0.20 — Theme Engine & Safe Public Rendering

N0.20 introduces a theme runtime without turning themes into unrestricted PHP plugins. Nexora themes are presentation packages; content semantics, SEO metadata, Schema Graph, identities and business rules remain owned by Core contracts.

## Package boundary

Every uploaded theme must contain both `nexora.json` and `theme.json`. Sentinel scans the original quarantine archive first. Theme installation is allowed only when the completed scan decision is `ALLOW`, and the archive SHA-256 must still match both the quarantine baseline and scan baseline at install time.

The initial runtime engine is `nexora-safe-html`. It accepts declared `.html` templates and static CSS/image assets only. PHP, JavaScript, OS scripts and undeclared files are rejected by the Theme Engine even if a future Sentinel policy would otherwise review them.

## Runtime model

- `nx_themes` stores stable theme identity and active state.
- `nx_theme_versions` stores immutable semantic versions, checksums and manifests.
- `nx_theme_settings` stores design-token overrides separately from package files.
- `nx_theme_activations` stores activation/rollback history.
- `nx_theme_preview_tokens` stores short-lived hashed preview tokens.

The built-in `Nexora Base` theme is the guaranteed fallback. If the configured active theme loses required runtime files, public rendering falls back to the built-in version rather than executing an incomplete third-party theme.

## Rendering contract

Themes receive a deliberately small set of platform-owned slots such as `nx_head`, `nx_schema` and `nx_content`. SEO Core generates head/schema payloads, and Document Engine renders structured document blocks. Themes cannot persist competing SEO state or execute arbitrary application code.

## Design tokens

Theme manifests declare token definitions. User overrides are persisted outside the package and validated by type. Color, numeric and select values are strongly validated, and text token values reject CSS control/injection characters.

## Preview and activation

Preview tokens are random, hashed at rest, user-bound and expire automatically. A preview never changes the public active theme. Activation is transactional and logs the previous theme/version so the administrator can roll back to the previous activation snapshot.
