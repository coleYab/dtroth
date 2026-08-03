---
title: Hosted Verification Flow
order: 4
---

The hosted flow is a branded, Troth-hosted page where your users confirm their age without you handling national IDs. Instead of calling the API directly, you create a verification session and redirect your user to it. Troth performs the check, records their consent, and redirects them back — or delivers the result to your server with a signed callback.

## How it works

1. Your server calls `POST /v1/verify/session` with the age query you need.
2. Troth returns a short-lived `verification_url`.
3. You redirect the user to that URL (or open it in a new tab).
4. The user enters their national ID and consents on the branded page.
5. Troth verifies their age, records consent, and redirects them back to your `success_redirect_url` or `failure_redirect_url`.
6. Troth sends a signed callback to your `callback_url` with the result.

## Creating a session

```curl
curl -X POST "https://api.troth.dev/v1/verify/session" \
  -H "Authorization: Bearer sk_sandbox_..." \
  -H "Content-Type: application/json" \
  -d '{"age":18,"type":"equal_above","subject":"user-123"}'
```

```js
const response = await fetch('https://api.troth.dev/v1/verify/session', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer sk_sandbox_...',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ age: 18, type: 'equal_above', subject: 'user-123' }),
});

const { verification_url } = await response.json();
window.location.href = verification_url;
```

```python
import requests

response = requests.post(
    'https://api.troth.dev/v1/verify/session',
    headers={'Authorization': 'Bearer sk_sandbox_...'},
    json={'age': 18, 'type': 'equal_above', 'subject': 'user-123'},
)

verification_url = response.json()['verification_url']
print(f'Redirect your user to: {verification_url}')
```

### Request

The request body is identical to the [age-query endpoint](age-query-endpoint) — same `age`, `type`, and `subject` fields, same presets requirement.

### Response

On success the API returns `200` with the following JSON body:

| Field | Type | Description |
| --- | --- | --- |
| `session_id` | string | The opaque session identifier. |
| `verification_url` | string | The branded flow URL to redirect the user to. |
| `expires_at` | string | ISO 8601 timestamp after which the session is no longer valid. |

Sessions expire after **15 minutes** and can only be used **once**.

## Redirect back

When the user finishes the flow, Troth redirects to your configured redirect URLs with outcome query parameters:

```
https://yourapp.com/continue?verification=success&query_id=42&subject=user-123&result=true&verified_at=...&expires_at=...
```

| Parameter | Description |
| --- | --- |
| `verification` | `success` or `failed`. |
| `query_id` | The verification reference, matches the callback payload. |
| `subject` | The subject identifier you supplied. |
| `result` | `true` or `false`. |
| `verified_at` / `expires_at` | ISO 8601 timestamps for the result window. |
| `error` | Present on failures, e.g. `identity_failed`. |

## Signed callbacks

If you set a `callback_url` on the flow, Troth delivers the result to your server as a synchronous `POST` with a 5-second timeout:

```
POST {callback_url}
X-Troth-Event: verification.completed
X-Troth-Signature: sha256=9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08
X-Troth-Request-ID: 5e2f1a3b-8c4d-4e6f-9a7b-0c1d2e3f4a5b
Content-Type: application/json
```

```json
{
  "query_id": "42",
  "subject": "user-123",
  "age": 18,
  "type": "equal_above",
  "result": true,
  "provider_reference": "fayda-3f9e2a5b-1c4d-4e6f-8a9b-0c1d2e3f4a5b",
  "verified_at": "2026-08-05T12:00:00+00:00",
  "expires_at": "2026-11-03T12:00:00+00:00",
  "occurred_at": "2026-08-05T12:00:05+00:00"
}
```

### Verifying the signature

Every callback is signed with HMAC-SHA256 using your flow's **callback secret**. Compute the signature over the raw request body:

```
sha256 = HMAC-SHA256(callback_secret, request_body)
X-Troth-Signature = "sha256=" + sha256
```

```js
const crypto = require('crypto');

const expected = 'sha256=' + crypto
  .createHmac('sha256', process.env.TROTH_CALLBACK_SECRET)
  .update(rawBody) // the raw request body string
  .digest('hex');

if (crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(req.headers['x-troth-signature']))) {
  // signature is valid
}
```

```python
import hashlib
import hmac

expected = 'sha256=' + hmac.new(
    b'CALLBACK_SECRET',
    request_body.encode('utf-8'),
    hashlib.sha256,
).hexdigest()

if hmac.compare_digest(expected, request_headers['X-Troth-Signature']):
    pass  # signature is valid
```

- Always respond `2xx` to the callback; Troth does not retry it.
- Trust the callback — the redirect back can be forged, the signed callback cannot.
- The callback secret is generated automatically the first time a session is created and is stored encrypted.

## Sandbox

In sandbox mode the flow page shows a test banner and accepts the mock test keys (`fayda-test-a` is 20, `fayda-test-b` is 16, `fayda-test-c` is 25, `fayda-test-d` is 65).

## Errors

| Code | Status | Meaning |
| --- | --- | --- |
| `preset_not_allowed` | 403 | The requested query is not enabled for this application. |
| `session_expired` | 410 | The session link has expired. |
| `session_already_used` | 410 | The session link has already been completed. |
| `invalid_national_id` | 422 | The national ID submitted on the flow page is not valid. |
| `consent_required` | 422 | The user did not consent to verification. |
| `subject_not_found` | 404 | The national ID does not match a known identity. |

See [Error Codes](error-codes) for the full list.
