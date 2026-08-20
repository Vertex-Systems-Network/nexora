# Nexora N0.27 — Automation, Workflow Engine & Webhooks

N0.27 introduces a first-party event workflow runtime. Core modules emit stable event payloads into `AutomationEventBusContract`; workflow definitions evaluate allow-listed condition operators and execute registered action adapters through queued, observable runs.

## Workflow lifecycle

A workflow is `draft`, `active`, or `paused`. Only active workflows receive new trigger events. A run owns immutable trigger context for that execution and contains ordered step-run checkpoints. If an action later in the workflow fails and Laravel retries the run job, already successful steps are skipped instead of repeated.

The initial first-party actions are Admin notification, signed outbound Webhook, and Audit Trail record. Arbitrary PHP, JavaScript, SQL, shell commands and user-defined executable expressions are not workflow actions.

## Outbound Webhook signing protocol

Every Nexora outbound delivery sends JSON and these protocol headers:

- `X-Nexora-Event`: stable event key.
- `X-Nexora-Delivery`: delivery UUID.
- `X-Nexora-Timestamp`: Unix timestamp.
- `X-Nexora-Signature`: `v1=<hex HMAC-SHA256>`.
- `Idempotency-Key`: stable delivery idempotency key.

The signed bytes are exactly:

`<timestamp>.<raw JSON body>`

Receivers should reject timestamps outside their replay window, calculate HMAC-SHA256 with the shared secret, compare signatures using a constant-time comparison, and deduplicate the idempotency key.

Nexora does not follow outbound redirects. Production destinations require HTTPS. Literal private/reserved addresses are rejected and production delivery resolves DNS records before sending so private/reserved A or AAAA destinations are blocked.

## Inbound Webhooks

Each endpoint receives `POST /hooks/{endpoint-uuid}`. Clients send JSON plus `X-Nexora-Timestamp`, `X-Nexora-Signature` and preferably `Idempotency-Key`. Nexora enforces a five-minute timestamp window, 1 MB payload limit, constant-time signature verification and endpoint-scoped idempotency.

A newly rotated endpoint keeps the previous secret valid for only 15 minutes. New and rotated secrets are displayed once in Admin; encrypted values remain available internally for verification but are not returned in normal endpoint listings.

Inbound receipts store a keyed source hash instead of a raw source IP. Optional allow-list storage exists on the endpoint model for installations that need source-address restrictions.

## Retention

Raw automation trigger events and inbound Webhook receipts may contain integration payloads. Default retention is 30 days and `nexora:automation:prune` runs daily. Workflow run and delivery records remain separate operational history.
