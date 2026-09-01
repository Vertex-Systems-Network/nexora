# Nexora Public API v1

Nexora Public API v1 is the first stable external HTTP integration surface for tenant-owned data. It is intentionally small: stability, tenant isolation and credential safety take priority over exposing every internal model.

## Contract

- Base path: `/api/v1`
- Authentication: `Authorization: Bearer nxapi_...`
- Token storage: Nexora stores only a SHA-256 token hash and a non-secret display hint. Plaintext is shown only once at issue time.
- Tenant binding: every token belongs to exactly one organization and one issuing user. Tenant and actor activity/membership are revalidated on each request.
- Expiry: required, 1–365 days.
- Revocation: immediate and irreversible; the plaintext token cannot be recovered.
- Rate limit: 120 requests per minute per token.
- API version response header: `X-Nexora-Api-Version: v1`.
- Error responses are JSON and use stable error codes where the API middleware owns the failure (`invalid_token`, `insufficient_scope`, `rate_limited`).

The runtime source-of-truth descriptor is `App\Nexora\Api\Contracts\PublicApiContract`. External SDKs should derive paths/scopes from the documented HTTP contract, not depend on Eloquent models or internal service classes.

## Abilities

### `documents.read`

Allows read-only access to documents in the token organization.

## Resources

### List documents

`GET /api/v1/documents`

Optional query parameters:

- `per_page`: 1–100; values above 100 are capped.
- `status`: `draft`, `published`, or `archived`.
- `type`: document type identifier, maximum 80 characters.
- `cursor`: cursor returned by the previous response.

Responses use cursor pagination:

```json
{
  "api_version": "v1",
  "data": [],
  "pagination": {
    "per_page": 25,
    "next_cursor": null,
    "has_more": false
  }
}
```

### Read one document

`GET /api/v1/documents/{document}`

The numeric document key is re-resolved after bearer-token authentication has installed the token tenant. A document key from another organization is not disclosed and returns the normal not-found boundary.

## Credential lifecycle

API credentials are managed from **Admin → API & Integrations**. The creation response contains plaintext exactly once. The browser keeps it only in local component state so the user can copy it; Nexora does not write the plaintext token to session flash, logs, database rows, or subsequent page props.

If the issuing user is suspended, loses active membership in the token organization, the organization is suspended, the token expires, or the token is revoked, authentication fails closed.

## Webhooks

Public API tokens do not replace Automation webhook authentication. Existing inbound webhooks retain their separate HMAC-SHA256 signature, timestamp freshness, endpoint idempotency, payload-size and source-policy boundaries. Outbound webhooks retain destination policy, signing and queue tenant restoration.

## Versioning and SDK rules

1. `/api/v1` behavior is additive within v1. Breaking field/path/auth changes require a new major API prefix.
2. SDKs must treat undocumented response fields as optional and must not rely on database IDs belonging to another tenant.
3. SDKs must never accept or expose Nexora database credentials, session cookies, CSRF tokens, provider secrets, or internal model serialization as substitutes for API tokens.
4. External extensions may consume `PublicApiContract` metadata inside Nexora, but public network clients integrate through the documented HTTP surface.
5. Internal Eloquent models, controllers and service implementations are not public SDK contracts.
6. Future write abilities/endpoints require dedicated capability, validation, idempotency and audit contracts before being added.

## Target verification boundary

Source contracts and CI establish source alignment only. Public API TARGET VERIFIED status requires a real current-branch runtime request using a newly issued token, tenant-isolation checks, revocation/expiry checks and browser/Admin token lifecycle execution on the target environment.
