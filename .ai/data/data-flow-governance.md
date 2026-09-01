# Nexora Data Architecture, Flow & Governance

## Purpose

Nexora data must remain traceable across input, transformation, persistence, indexing, APIs, themes, extensions, AI, analytics, export, retention and deletion. Database schema alone is not a sufficient data architecture.

`DATA-GOVERNANCE-200` establishes common contracts and evidence so every material data flow can answer: where did this data originate, who owns it, which tenant/site does it belong to, who/what may access it, where is it copied or derived, how long is it retained, and how is it corrected/exported/deleted?

## Canonical flow model

```text
Source / Actor
    ↓
Input / Transport
    ↓
Authentication + Authorization
    ↓
Validation / Normalization
    ↓
Domain Command / Event
    ↓
Transformation
    ↓
Authoritative Store
    ├─ Cache
    ├─ Search Index
    ├─ Analytics / Telemetry
    ├─ Derived Data
    ├─ API / Webhook
    ├─ Theme / Studio
    ├─ Extension / Integration
    └─ AI Context / Tool
    ↓
Retention / Export / Deletion / Archive
```

## Required data artifacts

For high/critical or data-heavy units record:

- `DataFlowDiagram`;
- `DataClassification`;
- `DataOwner` and authoritative source;
- tenant/site/user scope;
- `DataLineage` including derived copies;
- access/capability matrix;
- retention/deletion/export rules;
- encryption/secret requirements;
- cache/index invalidation behavior;
- backup/restore implications;
- API/webhook/AI exposure;
- migration/backfill/rollback behavior;
- audit requirements.

## Data classification

At minimum classify fields/streams as:

- public;
- internal;
- personal;
- sensitive personal;
- credential/secret;
- financial/payment metadata;
- payment account data — prohibited from generic Nexora handling under the default payment-security profile;
- security/audit evidence.

Classification determines logging, telemetry, AI, export, retention and encryption policy.

## Authority and derived stores

Each important entity identifies its authoritative source. Caches, indexes, analytics and AI/vector representations are derived data and must not silently become authoritative.

Derived stores require:

- synchronization contract;
- invalidation/rebuild path;
- tenant boundary;
- deletion propagation;
- version/schema compatibility;
- recovery behavior.

## Data minimization

Collect and retain only what the product capability needs. If a provider token/reference solves the requirement, do not collect the underlying sensitive credential/account data.

## AI boundary

Data entering AI context must pass explicit context providers and classification/policy filters. Secrets, payment account data and unrelated tenant data are excluded by default. Retrieval/vector stores are non-authoritative derived systems with deletion/rebuild semantics.

## Extension/package boundary

Packages receive data through typed contracts/capabilities, not direct unrestricted table access. The package plan declares:

- data resources requested;
- read/write purpose;
- tenant scope;
- fields/classifications required;
- retention/copy behavior;
- external transfer destinations;
- deletion/uninstall behavior.

## Flow review triggers

A new/updated DataFlow review is mandatory when work introduces:

- new personal/sensitive/financial data;
- new external provider/processor;
- new analytics/RUM/AI usage;
- new data export/import;
- cross-tenant/shared data;
- new cache/index/derived store;
- destructive migration;
- new payment provider;
- new public write API/webhook.

## Deletion and retention

Deletion must account for authoritative and derived copies. Where legal/audit/business records must be preserved, deletion policy must distinguish immutable required history from removable personal data and document the basis.

## Verification

Tests/evidence should cover, when applicable:

- tenant isolation;
- authorization and field-level exposure;
- validation/canonicalization;
- migration/backfill correctness;
- cache/index consistency;
- deletion/export propagation;
- log/telemetry redaction;
- AI-context exclusion;
- backup/restore behavior;
- provider/webhook data minimization.
