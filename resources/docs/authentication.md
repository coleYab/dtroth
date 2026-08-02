---
title: Authentication
order: 2
---

Every request to the Age Verification API must be authenticated with a secret key.

## API keys

API keys are created per application in **App settings → API keys**. Each key belongs to an environment:

- **Sandbox keys** — for development. They verify against mock test identities and never touch real data.
- **Production keys** — for live traffic. They are only usable once your application has been reviewed and enabled for production verification.

Key secrets are shown **once** at creation. Store them somewhere safe — Troth only keeps a hash of the secret, so it can never be recovered or re-shown.

## Sending the secret

Send the key secret in the `Authorization` header as a bearer token:

```curl
curl -X POST "https://api.troth.dev/v1/verify/age" \
  -H "Authorization: Bearer sk_sandbox_..." \
  -H "Content-Type: application/json" \
  -d '{"age":18,"type":"equal_above","subject":"fayda-test-a"}'
```

```js
const response = await fetch('https://api.troth.dev/v1/verify/age', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer sk_sandbox_...',
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ age: 18, type: 'equal_above', subject: 'fayda-test-a' }),
});
```

```python
import requests

response = requests.post(
    'https://api.troth.dev/v1/verify/age',
    headers={'Authorization': 'Bearer sk_sandbox_...'},
    json={'age': 18, 'type': 'equal_above', 'subject': 'fayda-test-a'},
)
```

## Key rotation

If a key is compromised, revoke it from **API keys** and create a new one. Revoked keys immediately return `401 invalid_key`.

## Error handling

Requests with a missing, invalid, expired, or revoked key are rejected with:

| Code | Status | Meaning |
| --- | --- | --- |
| `invalid_key` | 401 | The bearer token is missing, invalid, revoked, or expired. |

See [Error Codes](error-codes) for the full list.
