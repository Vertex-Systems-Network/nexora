# Nexora Continuous Security Program

Security is a continuous product/development track. `SENTINEL-200` is an advanced package/runtime-security stage, not the first time security is considered.

## Security objective

Nexora does not claim to be unhackable. The security objective is:

```text
prevent -> reduce privilege -> contain -> detect -> respond -> recover -> learn
```

Assume breach for high-value boundaries and minimize blast radius.

## Security baselines

The security program should map controls/evidence to current versions of:

- OWASP ASVS;
- OWASP Top 10;
- OWASP API Security Top 10 where APIs apply;
- OWASP guidance for LLM/agentic systems where AI applies;
- NIST Secure Software Development Framework (SSDF);
- SLSA/build-provenance principles for supply-chain/release integrity;
- Nexora-specific architecture, tenancy, extension, installer and AI threat models.

Standards are evidence frameworks, not substitutes for threat modeling.

## `SECURITY-BASELINE-200` — early mandatory stage

This stage executes after Core QA and before large platform expansion.

### Identity and administrator protection

- passkeys/WebAuthn support plan and implementation;
- TOTP MFA fallback where appropriate;
- recovery codes;
- MFA enforcement policy for Super Admin/high-risk roles;
- session/device inventory and remote revocation;
- sensitive-action re-authentication;
- rate limiting, lockout/risk controls without account enumeration;
- secure recovery flows;
- privilege/session rotation regression tests.

### Browser/application hardening

- strict Content Security Policy with nonce/hash strategy;
- minimize/remove `unsafe-inline` and `unsafe-eval` where feasible;
- Trusted Types strategy for high-risk DOM sinks where supported;
- `frame-ancestors`, `object-src`, `base-uri`, `form-action`, `connect-src` policies;
- HSTS and secure-cookie policy;
- output encoding and active-content isolation;
- CSRF protection and explicit exceptions only for independently authenticated endpoints;
- upload/download MIME, filename and content-disposition controls.

### Authorization and tenancy

- default-deny authorization;
- tenant-scoped route/model assertions;
- IDOR/cross-tenant relationship injection tests;
- permission/capability matrix tests;
- no extension/theme/AI ability to elevate human permissions;
- impersonation and support-access policies with visible/audited sessions.

### Secrets and data

- secrets never committed/logged/exposed to AI context by default;
- encrypted secret storage with key rotation strategy;
- separate secret references from non-secret provider configuration;
- sensitive field inventory;
- retention/deletion/export hooks coordinated with `PRIVACY-CONSENT-100`;
- backup encryption/integrity/recovery evidence.

### Network and SSRF

- outbound requests through approved brokers/adapters for restricted code;
- deny private/reserved/metadata endpoints by default;
- DNS rebinding protections where remote destinations are allowed;
- redirect policy, timeouts, response-size limits and egress allowlists by capability;
- signed/idempotent inbound/outbound webhook rules.

### Extension/package isolation

Execution tiers:

1. `declarative` — preferred, structured and non-arbitrary.
2. isolated executable runtime — future process/WASM/container backend for untrusted code.
3. signed/reviewed restricted executable package — exceptional.
4. first-party trusted runtime — still subject to public contracts and review.

`trusted-php` must not become the default marketplace execution model.

### Supply chain

- dependency advisory scanning for Composer/npm and future ecosystems;
- secret scanning;
- SAST/CodeQL-equivalent analysis;
- dependency review on PRs;
- SBOM generation/verification;
- package/release signing and provenance;
- pinned/controlled CI dependencies where feasible;
- malicious-package corpus tests for Sentinel;
- emergency publisher/package revocation and kill-switch design;
- rollback protection and update-channel integrity.

### Security testing pipeline

Target pipeline includes:

- unit/integration security regression tests;
- architecture boundary tests;
- authorization/tenancy matrix tests;
- SAST;
- dependency/advisory scan;
- secret scan;
- DAST on disposable target;
- API schema/property fuzzing where applicable;
- parser/archive/input fuzzing for high-risk surfaces;
- extension/theme malicious corpus tests;
- restore/DR security verification;
- independent review for critical changes.

### Repository/release governance

Target GitHub/release controls:

- protected `main` with no direct push;
- required PRs and required checks;
- required security/architecture review for owned paths;
- CODEOWNERS/ownership model;
- stale review dismissal after material changes;
- no force push/delete on protected release branches;
- signed/attested release artifacts/tags where supported;
- production environment approval gates.

## Threat-model requirement

A written threat model is mandatory for `high` and `critical` development units and for any unit that introduces:

- authentication/authorization change;
- public write API;
- executable package runtime;
- secret/network/filesystem capability;
- upload/archive parser;
- payment/commerce mutation;
- cross-tenant access;
- destructive migration/update/restore;
- AI execution tool;
- code generation/execution;
- external agent interoperability.

Use `.ai/security/threat-model-template.md`.

## AI/agent security

AI is never a privileged bypass. AI must use typed tools and normal platform authorization/capabilities.

Default AI restrictions:

- no unrestricted shell;
- no direct raw database mutation;
- no raw `.env`/secret access;
- no arbitrary filesystem traversal;
- no unrestricted outbound HTTP;
- no silent package installation;
- no silent permission/tenant changes;
- no destructive migration/restore/publish action without explicit policy/approval;
- no treating external content/tool output as trusted instructions.

Required AI controls:

- prompt-injection resistant tool/context boundaries;
- structured tool schemas;
- least-privilege tool scopes;
- tenant/user identity propagation;
- dry-run where possible;
- approval policy based on risk;
- output validation before side effects;
- budget/rate/concurrency limits;
- immutable audit records of requested/approved/executed actions;
- rollback metadata;
- eval suites for tool misuse, prompt injection, data leakage and excessive agency.

## Incident response destination

Nexora must eventually support:

- security event classification;
- compromised account/session revocation;
- publisher/package emergency disable/quarantine;
- key/secret rotation procedures;
- forensic audit export;
- affected tenant/site scoping;
- safe maintenance/read-only mode;
- recovery/restore workflow;
- post-incident regression/evidence update.

`OBSERVABILITY-200`, `SENTINEL-200` and `DR-PLATFORM-100` complete the later operational layers.
