# N0.20 Verification Gates

N0.20 release gates verify:

- Theme Engine module and runtime capabilities are registered.
- N0.20 theme migration exists and uses domain table names.
- Built-in fallback theme files exist and contain no PHP/JavaScript/TypeScript.
- Uploaded themes require a Sentinel `ALLOW` decision before installation.
- Quarantine/scan/package hashes are revalidated before extraction.
- Safe-theme archive policy rejects executable/undeclared files.
- `theme.json` and `nexora.json` identity/version must match.
- Public Theme Manager/Renderer contracts exist.
- Admin Themes screen exists and follows `@nexora/admin-ui` governance.
- Theme preview, activation, rollback and design-token routes are permission protected.
- Public document rendering keeps SEO and structured-content generation outside theme package code.
