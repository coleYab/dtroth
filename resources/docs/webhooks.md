---
title: Webhooks
order: 4
---

Webhooks notify your servers about asynchronous events, such as a verification expiring or a consent being revoked.

## Events

| Event | Description |
| --- | --- |
| `verification.expired` | A verification result has passed its expiry and should be re-checked. |
| `consent.revoked` | An end user revoked their consent. |
| `webhook.test` | A test event sent from **Webhooks** settings. |

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
