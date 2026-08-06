---
title: Consents
order: 5
---

Consents are the legal record of an end user agreeing to an age query. A consent is created when a hosted-flow session succeeds, and its lifecycle (granted, renewed, revoked, expired) is exposed to your application so you can act on it.

## Lifecycle

| State     | Meaning                                              |
| --------- | ---------------------------------------------------- |
| `active`  | The user's consent is valid.                         |
| `revoked` | The user or your application revoked the consent.    |
| `expired` | The consent passed its expiry without being renewed. |

Consents renew silently: when the same subject completes another successful verification, the existing consent is extended and a `renewed` event is recorded instead of a new consent.

## Revoking a consent

Revoke a consent at any time, even after it has expired. Revocation is idempotent — revoking an already-revoked consent succeeds without creating a duplicate event.

```
POST /v1/consents/{consent_id}/revoke
```

```curl
curl -X POST "https://api.troth.dev/v1/consents/42/revoke" \
  -H "Authorization: Bearer sk_sandbox_..."
```

```json
{
    "consent_id": 42,
    "status": "revoked",
    "revoked_at": "2026-08-06T09:30:00+00:00"
}
```

| Field        | Type    | Description                                            |
| ------------ | ------- | ------------------------------------------------------ |
| `consent_id` | integer | The ID of the revoked consent.                         |
| `status`     | string  | Always `revoked`.                                      |
| `revoked_at` | string  | ISO 8601 timestamp of when the revocation took effect. |

The consent is scoped to your application: an unknown or another app's consent ID returns `404 not_found`.

## Events

Every state change writes a consent event. The dashboard's consent timeline renders them newest-first, and they are also available in [Webhooks](webhooks) as they happen:

| Event             | Trigger                                                       |
| ----------------- | ------------------------------------------------------------- |
| `consent.granted` | A subject completes their first successful verification.      |
| `consent.renewed` | An existing active consent is extended by a new verification. |
| `consent.revoked` | The consent is revoked via the API above.                     |
| `consent.expired` | The consent passes its expiry, discovered hourly.             |
