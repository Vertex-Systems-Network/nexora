# Nexora Continuous Security Program

Security is a continuous product/development track. `SENTINEL-200` is an advanced package/runtime-security stage, not the first time security is considered.

## Security objective

Nexora does not claim to be unhackable. The objective is:

```text
prevent → reduce privilege → contain → detect → respond → recover → learn
```

Assume breach for high-value boundaries and minimize blast radius.

## Security evidence frameworks

Map controls/evidence to current applicable versions of:

- OWASP ASVS;
- OWASP Top 10;
- OWASP API Security Top 10;
- OWASP LLM/agentic guidance when AI applies;
- NIST Secure Software Development Framework;
- SLSA/build-provenance principles;
- PCI DSS / PCI Secure Software guidance when payment account data/payment software boundaries apply;
- Nexora architecture/data/package/payment/AI threat models.

Standards guide evidence; they do not replace threat modeling or automatically certify a deployment.

## `SECURITY-BASELINE-200`

Executes early before large platform expansion.

### Identity / Admin protection

- passkeys/WebAuthn;
- TOTP MFA/recovery where appropriate;
- high-risk-role MFA policy;
- session/device inventory/revocation;
- sensitive-action re-authentication;
- rate/lockout/recovery controls without account enumeration;
- privilege/session rotation tests.

### Browser/application hardening

- strict CSP nonce/hash strategy;
- minimize/remove unsafe inline/eval where feasible;
- Trusted Types strategy for high-risk DOM sinks;
- restrictive `frame-ancestors`, `object-src`, `base-uri`, `form-action`, `connect-src`;
- HSTS/secure cookies;
- output encoding/content isolation;
- CSRF protection;
- upload/download MIME/filename/content-disposition controls.

### Authorization / tenancy

- default deny;
- tenant-scoped route/model assertions;
- IDOR/cross-tenant tests;
- permission/capability matrix tests;
- code capabilities never elevate human permissions;
- audited impersonation/support sessions.

### Secrets / data

- no committed/logged/AI-exposed secrets by default;
- encrypted secret storage and rotation;
- secret references separate from normal config;
- sensitive data classification/inventory;
- retention/export/delete coordinated with Data Governance/Privacy;
- backup encryption/integrity/recovery evidence.

### Network / SSRF

- approved network brokers/adapters for restricted code;
- private/reserved/metadata denial;
- DNS-rebinding/redirect revalidation;
- timeouts/size limits/egress allowlists;
- signed/idempotent webhook policies.

### Package isolation

Preferred execution tiers:

1. declarative;
2. genuinely isolated executable runtime when available;
3. signed/reviewed restricted executable package — exceptional;
4. first-party trusted runtime — still public-contract/review bounded.

`trusted-php` is not the default marketplace model and must never be described as process/container isolated unless that is actually implemented/verified.

### Supply chain

- Composer/npm advisory scanning;
- secret/SAST/dependency review;
- SBOM/provenance/signing;
- controlled CI dependencies;
- malicious-package corpus testing;
- emergency revocation/kill-switch design;
- rollback/update-channel integrity.

### Security testing

- unit/integration security regression;
- architecture boundary;
- auth/tenancy matrices;
- SAST/dependency/secret scanning;
- DAST on disposable target;
- API/parser fuzzing where relevant;
- malicious package corpus;
- payment-specific adversarial/provider sandbox testing when applicable;
- restore/DR security;
- independent review for critical work.

## Payment / financial boundary

Payment-provider security has a dedicated mandatory architecture at `.ai/security/payment-security.md` and stage `PAYMENT-SECURITY-200` before payment-enabled Commerce 2.0.

### Standard-profile invariants

- raw PAN/CVV/CVC/track/PIN data does not enter Nexora Core, generic package runtime, DB/log/cache/queue/analytics/search/backups/observability/AI;
- prefer provider-hosted redirect/iframe/hosted fields or approved tokenized provider SDK;
- direct/raw account-data collection is forbidden by default and cannot be enabled by a normal manifest/capability;
- payment integrations are `critical` and use `security_profile: payment-provider`;
- generic DB/filesystem/secrets/network power is not a payment-provider capability model;
- Core authoritatively validates order/tenant/amount/currency/financial state;
- browser return/success URL is not payment proof;
- signed/fresh/replay-safe tenant-bound provider webhook/API reconciliation is authoritative;
- ambiguous non-idempotent capture/refund outcomes reconcile before retry;
- payment pages restrict arbitrary Theme/Extension/custom scripts and use payment-specific CSP/origin/tamper/session-replay policy;
- payment activation requires threat model, FMEA, independent payment-security review and real provider sandbox evidence;
- generic Sentinel/package/Marketplace PASS is not payment/PCI certification.

### Payment secret/network/webhook boundaries

Use scoped Secret Broker and Network Broker contracts, strict origin/SSRF/timeout/redaction rules, provider-specific webhook signature/freshness/event-deduplication/schema/state validation, idempotency/concurrency controls, and explicit test/live credentials.

### Payment incident containment

Compromised/suspect provider/package response prioritizes stopping new payment intents, preserving financial history, quarantining affected package version, credential rotation, in-flight reconciliation and independently verified recovery. Do not delete transaction history to hide/contain an incident.

## Threat-model requirement

Written threat model mandatory for `high`/`critical` units and changes introducing auth, public write API, executable runtime, secrets/network/filesystem, parsers/uploads, payment/commerce mutation, cross-tenant access, destructive update/restore, AI tools/code execution or external agents.

Payment-provider work also requires FMEA because financial correctness/reliability failures may exist even when no direct security exploit is involved.

## AI / agent security

AI is never privileged bypass. It uses typed tools and normal identity/authorization/capabilities.

Default restrictions:

- no unrestricted shell/raw DB/.env/filesystem/network;
- no silent package install/permission/tenant change;
- no destructive migration/restore/publish without policy/approval;
- no external/tool content treated as trusted instructions;
- no raw payment account data in AI context;
- no autonomous payment mutation unless a future explicit narrowly bounded payment policy permits it with required human approval and reconciliation controls.

Required AI controls include injection-resistant context/tool boundaries, structured schemas, least privilege, tenant/user identity, dry-run, approvals, output validation, rate/budget/concurrency, immutable audit, rollback metadata and misuse/leakage/agency evals.

## Repository/release governance

Target controls:

- protected main/no direct push;
- required PR/checks;
- security/architecture/payment ownership review for governed paths;
- stale review dismissal;
- no force push/delete on protected release branches;
- signed/attested release artifacts/tags where supported;
- production approval gates.

## Incident response destination

Support classification, account/session revocation, package/provider emergency disable, credential rotation, forensic export, affected tenant/site scoping, safe maintenance/read-only mode, reconciliation/recovery/restore and post-incident DMAIC/control evidence.

`OBSERVABILITY-200`, `SENTINEL-200`, `RELIABILITY-ENGINEERING-200` and `DR-PLATFORM-100` complete later operational layers.
