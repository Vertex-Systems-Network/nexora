# Nexora Payment Security Architecture

## Objective

Payment integration is a critical financial/security boundary, not a normal generic extension capability. Nexora must minimize payment-data scope, prevent provider extensions from becoming privilege-escalation or e-skimming paths, preserve financial state integrity under retries/concurrency/outages, and make payment behavior independently reviewable and revocable.

This architecture builds on the existing provider-neutral Commerce foundation:

- Core owns canonical order/invoice/transaction/refund/subscription state;
- providers implement public payment-provider contracts/registry adapters;
- Core does not embed a gateway vendor;
- Core does not own provider private keys;
- amounts use integer currency minor units;
- billing/provider events use idempotency/event identity foundations.

## Non-negotiable default boundary

Under the standard Nexora payment-provider security profile:

**Raw payment-card PAN, CVV/CVC, track data or PIN data must never enter Nexora Core, generic extension runtime, application database, logs, cache, queues, analytics, search indexes, backups, observability payloads or AI context.**

Preferred integration order:

1. provider-hosted redirect/checkout;
2. provider-hosted iframe/hosted fields where payment account entry remains provider-originated;
3. approved provider tokenization/SDK flow with strict payment-page controls;
4. direct/raw card collection is forbidden by default and would require a separately designed, isolated and independently assessed deployment/security model before it could ever become a supported profile.

Tokens/provider payment-method references may be stored only according to provider/PCI classification and Nexora data-minimization policy. Tokenization can reduce scope; it does not erase compliance obligations.

## Package classification

A payment provider remains an installable `integration`/approved package family using public contracts, but its manifest receives a special security profile:

`security_profile: payment-provider`

Every payment-provider unit is `critical` and requires:

- explicit ResearchBrief/CTQs;
- data flow and data classification;
- threat model;
- FMEA;
- independent security review;
- signed/verified publisher/package identity;
- Sentinel/Supply Chain scan;
- dependency/SBOM/provenance evidence;
- payment-specific integration tests;
- real sandbox/provider verification;
- version-by-version security/compatibility evidence;
- kill-switch/revocation plan.

A generic Sentinel PASS is necessary but is **not** a Payment Security certification.

## Runtime trust rule

Payment integrations should prefer declarative/remote-provider behavior and tightly bounded platform brokers.

Until Nexora has a genuinely isolated executable runtime:

- arbitrary marketplace `trusted-php` must not become the default path for payment-provider execution;
- a payment package cannot request generic database/filesystem/secrets/network access;
- high-risk executable payment adapters require explicit trusted-publisher policy and independent review or remain unsupported;
- `SENTINEL-200` may later add stronger process/WASM/container isolation, but payment safety must not depend on future isolation to enforce current least privilege.

## Payment capability model

Expose purpose-specific capabilities instead of generic power. Candidate capability families:

- `payments.intent.create`;
- `payments.method.token.use`;
- `payments.authorize`;
- `payments.capture`;
- `payments.void`;
- `payments.refund`;
- `payments.subscription.manage`;
- `payments.webhook.consume`;
- `payments.status.read`;
- `payments.reconcile`;
- `payments.provider.health`.

Capabilities are site/tenant/provider scoped and never imply human Admin permissions.

## Core-authoritative transaction rules

The provider extension is an adapter, not the financial source of truth for Nexora business rules.

Core must authoritatively determine/validate:

- order identity;
- tenant/site;
- currency;
- amount/minor units;
- allowed capture/refund amount;
- transaction lifecycle transition;
- idempotency identity;
- user/admin authorization;
- provider account binding.

Client/browser input must not be trusted to set the payable amount or final payment state.

## Payment state machine

Use explicit monotonic/validated state transitions rather than arbitrary status strings.

Conceptual states include:

`created → requires_action → authorized → captured → partially_refunded/refunded`

with explicit terminal/failure/cancel/void states according to provider semantics.

Rules:

- transitions are validated by Core;
- provider event identity is unique/deduplicated;
- events can arrive duplicate or out of order;
- ambiguous timeout does not mean failure or success;
- do not blindly retry capture/refund after an unknown provider response;
- reconcile provider state before retrying non-idempotent actions;
- historical financial events are append-oriented/auditable;
- state changes record actor/provider/request/event correlation.

## Idempotency and concurrency

Payment mutations require idempotency keys scoped to operation/provider/account/order/tenant as appropriate.

Guard against:

- duplicate authorization;
- duplicate capture;
- duplicate webhook processing;
- duplicate refund;
- concurrent over-refund;
- subscription duplicate mutation;
- retry after connection loss.

Use database locking/unique constraints/state preconditions as appropriate; application-memory dedupe alone is insufficient.

## Secret Broker

Provider credentials must not be ordinary extension configuration values exposed to package code/UI/logs.

Target Secret Broker properties:

- encrypted at rest;
- tenant/site/provider scoped;
- access by opaque secret reference/operation, not bulk export;
- least-privilege credential purpose;
- masked Admin UI;
- no AI-context exposure;
- no logs/traces/dumps;
- rotation/versioning;
- revoke/disable;
- audit of use/config changes without logging secret value;
- separate test/live credentials;
- production secret changes require sensitive-action re-auth/approval policy.

Where provider architecture permits, prefer delegated/short-lived credentials over static master secrets.

## Network Broker

Payment package network access must be mediated by a payment/provider network policy:

- declared provider API origins;
- TLS verification;
- DNS/private/reserved/metadata address denial;
- redirect revalidation;
- bounded timeout/response size;
- no arbitrary URL supplied by untrusted request data;
- tenant/provider binding;
- request/response redaction;
- retry rules aware of operation idempotency;
- optional certificate/provider-specific controls where justified.

## Payment Webhook Gateway

All provider webhooks enter a dedicated hardened gateway before Commerce mutation.

Required controls:

1. preserve exact/raw body only as needed for provider signature validation;
2. verify provider-specific signature/MAC/certificate contract;
3. verify timestamp/freshness where provider supports it;
4. bind endpoint to expected tenant/site/provider account;
5. rate/size limit before expensive processing;
6. deduplicate immutable provider event ID;
7. safely handle replay and out-of-order delivery;
8. parse using provider-specific bounded schema;
9. map only known event types/transitions;
10. unknown/invalid events fail closed and remain auditable;
11. process mutations idempotently;
12. reconcile with provider API for high-risk/ambiguous events where required;
13. never trust a browser return/success URL as proof of payment.

Webhook secrets/signing keys follow Secret Broker policy.

## Checkout / Payment Surface Guard

The browser page capable of affecting payment security receives a stricter runtime than a normal theme page.

### Script/asset policy

- no arbitrary Theme/Extension script injection on protected payment surface;
- only approved payment-safe slots/components;
- every provider/payment script has declared origin, purpose and owner;
- strict CSP assembled from Core + approved provider manifest;
- constrain `script-src`, `frame-src`, `connect-src`, `form-action` and related directives;
- integrity controls where technically applicable;
- monitor for unauthorized script/DOM/security-header changes;
- session replay/analytics must not capture payment fields/account data;
- no generic custom code widget on the payment-entry surface;
- browser extension/third-party content cannot be treated as trusted evidence.

### Hosted iframe/fields

When card entry is provider-hosted:

- provider origin owns the card-input DOM;
- Nexora must not read/scrape the iframe/hosted-field account data;
- parent page is still hardened because malicious parent scripts can affect payment security/social-engineering or redirect flows;
- provider postMessage/events require strict origin/schema validation.

## Frontend return/callback handling

A customer returning to `success`, `cancel` or `return_url` changes UX only. It does not authoritatively settle financial state.

Flow:

```text
browser return
→ show pending/processing if necessary
→ Core queries/reconciles verified provider state and/or authenticated webhook
→ validated state transition
→ final order/invoice result
```

## 3DS / SCA / asynchronous payment methods

Provider adapters must model required-action and asynchronous states without pretending every payment is immediate.

The contract must support:

- redirect/challenge/action-required state;
- pending/processing;
- delayed confirmation;
- cancellation/failure;
- later webhook reconciliation;
- session expiry/restart;
- region/provider-specific capability declaration.

Nexora does not implement regulatory SCA logic by guessing; the provider adapter declares and executes supported provider flows while Core preserves state integrity.

## Refund and privileged operations

Refund/void/manual capture/provider credential changes are sensitive actions.

Controls may include:

- explicit permission;
- re-authentication/MFA for policy-selected amounts/actions;
- amount/currency/order validation;
- cumulative-refund lock/check;
- idempotency;
- immutable audit;
- approval/dual-control threshold for high-value enterprise policy;
- no AI autonomous execution unless an explicit payment policy allows a narrowly bounded operation with approval.

## Logging, analytics, observability and AI

Payment telemetry must use allowlisted fields. Redaction is defense-in-depth, not permission to log sensitive data first.

Allowed examples may include:

- internal transaction ID;
- provider transaction/event reference;
- provider name/account reference;
- amount/currency;
- state/outcome;
- latency/error category;
- card brand/last4/expiry only when provider/token policy and business purpose allow it.

Never log/store/emit to AI:

- CVV/CVC;
- full PAN;
- track/PIN data;
- provider secret/API private key;
- raw sensitive request bodies.

Logs, traces, error reports, browser replay, support export and backups require card-data leak scanning/redaction tests.

## Payment-provider manifest profile

A future manifest extension should declare at least:

```yaml
security_profile: payment-provider
payment:
  provider_id: example
  flow_types: [redirect, hosted-fields]
  account_data_access: none # allowed standard values: none | token-only
  api_origins: []
  frontend_origins: []
  webhook_signing_scheme: provider-defined
  supports_3ds: true
  supports_sca: true
  idempotency_contract: provider-defined
  supported_currencies: []
  supported_regions: []
  secret_slots: []
  data_retention: provider-reference-only
```

Standard marketplace payment providers may not declare raw account-data access.

## Install/enable gate

A payment provider cannot become active merely because package installation succeeded.

Activation requires:

```text
package signature/publisher trust
→ Sentinel/Supply Chain
→ payment security profile validation
→ requested capability review
→ provider origins/secret slots review
→ sandbox credentials
→ provider health check
→ webhook signature test
→ idempotency/replay tests
→ payment surface CSP/script test
→ test authorization/capture/refund/reconciliation
→ Admin approval
→ enable
```

Live mode requires an additional explicit environment/account switch and should never be inferred from a copied key name.

## Marketplace policy

Payment providers receive separate trust information from normal package ratings:

- publisher verification;
- package signature/provenance;
- security review version/date;
- supported Nexora/provider versions;
- payment flow class;
- sandbox verification;
- known compliance/assessment metadata supplied by publisher;
- revocation/advisory status.

Nexora must not display a generic "PCI compliant" badge solely from self-attestation or Sentinel scan. Compliance validation depends on the merchant/service-provider environment and applicable PCI program.

## Incident / kill switch

If a provider/package is compromised or suspected:

1. stop creation of new payment intents for that provider;
2. preserve existing order/transaction history;
3. quarantine/disable affected package version;
4. invalidate/rotate relevant credentials;
5. retain evidence without sensitive data leakage;
6. reconcile in-flight transactions directly with provider through trusted path;
7. notify authorized operators;
8. publish/update advisory and safe version;
9. require explicit recovery verification before re-enable.

Do not delete transaction history as a containment technique.

## Payment-specific threat/test matrix

Mandatory adversarial/integration tests include:

- forged webhook signature;
- stale/replayed webhook;
- duplicate event;
- out-of-order events;
- wrong tenant/provider-account event;
- amount/currency/order tampering;
- browser success-return forgery;
- duplicate authorization/capture/refund;
- concurrent refund race;
- timeout after provider accepted mutation;
- provider 5xx/rate limiting/outage;
- secret rotation/revocation;
- cross-tenant transaction IDOR;
- malicious payment package requesting excessive capabilities;
- unauthorized payment-page script injection;
- CSP bypass/regression;
- payment DOM/header tamper detection;
- session replay/analytics leakage;
- log/trace/exception scan for card-like sensitive data;
- 3DS/SCA/action-required state transitions;
- uninstall/update with in-flight transactions;
- rollback/provider-version compatibility;
- restore/recovery/reconciliation.

## Compliance boundary

Nexora's architecture is designed to reduce attack surface, PCI scope and blast radius, not to claim that any installation is automatically compliant or unhackable. The merchant/operator/provider/acquirer/payment brands and qualified assessors determine applicable PCI scope and validation obligations for the deployed environment.

## Stage mapping

- existing provider-neutral foundation: `COMMERCE-CLOSURE-001`;
- payment security contracts/brokers/surface/webhook profile: `PAYMENT-SECURITY-200`;
- broader cart/checkout/provider/fulfillment product: `COMMERCE-200`;
- advanced executable package isolation/revocation: `SENTINEL-200`;
- final payment-enabled release evidence is part of production security/release certification.
