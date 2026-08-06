---
title: Webhooks
order: 6
---

Webhooks notify your servers about lifecycle events automatically — you never need to poll. Every verification, revocation, and expiry triggers a signed POST to your configured endpoint.

## When events fire

| Event                    | Trigger                                                                                                                                                |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `verification.completed` | Every completed age check — the [Age Query API](age-query-endpoint) and the [hosted flow](hosted-flow), with both `true` and `false` results.          |
| `verification.expired`   | A stored verification result passes its `expires_at`, discovered hourly.                                                                               |
| `consent.revoked`        | A consent is revoked via the [Consents API](consents). Only fires on an actual state change — revoking an already-revoked consent does not fire again. |

Deliveries are scoped per application: only webhooks of the app that owns the event receive it.

## Event payloads

Every payload includes an `event` field naming the event and an `occurred_at` timestamp (ISO 8601, UTC).

### verification.completed

```json
{
    "event": "verification.completed",
    "query_id": "6f0e2a3b-9c1d-4e5f-a678-b90123c4d5e6",
    "subject": "user-123",
    "age": 18,
    "type": "equal_above",
    "result": true,
    "verified_at": "2026-08-06T12:00:00+00:00",
    "expires_at": "2026-11-04T12:00:00+00:00",
    "environment": "sandbox",
    "occurred_at": "2026-08-06T12:00:00+00:00"
}
```

| Field         | Type    | Description                                                               |
| ------------- | ------- | ------------------------------------------------------------------------- |
| `query_id`    | string  | The verification's unique ID — use it for deduplication.                  |
| `subject`     | string  | Your opaque identifier for the subject.                                   |
| `age`         | integer | The age that was queried.                                                 |
| `type`        | enum    | The operator used: `equal_above` or `equal_below`.                        |
| `result`      | boolean | Whether the query matched. `null` when the provider declined the subject. |
| `verified_at` | string  | When the verification was performed.                                      |
| `expires_at`  | string  | When the result expires; `null` for declined subjects.                    |
| `environment` | enum    | `sandbox` or `production` — filter test traffic here.                     |

### verification.expired

```json
{
    "event": "verification.expired",
    "query_id": "6f0e2a3b-9c1d-4e5f-a678-b90123c4d5e6",
    "subject": "user-123",
    "expired_at": "2026-11-04T12:00:00+00:00",
    "occurred_at": "2026-11-04T12:00:00+00:00"
}
```

Fired when a consent passes its expiry, referencing the most recent completed verification for that subject.

### consent.revoked

```json
{
    "event": "consent.revoked",
    "consent_id": 42,
    "subject": "user-123",
    "revoked_at": "2026-08-06T09:30:00+00:00",
    "occurred_at": "2026-08-06T09:30:00+00:00"
}
```

Fired by [POST /v1/consents/{id}/revoke](consents).

## Idempotency

Every delivery is keyed by the triggering operation — `query_id` for verifications, `consent:{id}` for revocations. If the same operation fires twice (a replayed flow, a retried job), no duplicate delivery is created. When handling events, prefer your own deduplication on `query_id` / `consent_id` as a second line of defence.

## Receiving events

Configure an HTTPS endpoint in **App settings → Webhooks**. Troth signs every payload so you can verify it came from us.

### Signature verification

Each request includes an `X-Troth-Signature` header containing the HMAC-SHA256 of the raw request body, keyed with your webhook secret:

```js
const crypto = require('crypto');

const signature = crypto
    .createHmac('sha256', process.env.TROTH_WEBHOOK_SECRET)
    .update(rawBody)
    .digest('hex');

if (signature !== req.headers['x-troth-signature']) {
    throw new Error('Invalid signature');
}
```

Always compare signatures using a constant-time comparison.

## Delivery and retries

Webhook delivery is asynchronous and queued. If your endpoint fails, Troth retries with exponential backoff (30, 120, then 480 seconds) for up to four attempts. Delivery attempts and their outcomes are visible in **App settings → Webhooks → Logs**, where you can also manually retry a failed delivery.

## Acknowledging

Respond with a `2xx` status as quickly as possible — Troth treats anything else as a failure and retries. You should respond immediately and process the payload asynchronously.
